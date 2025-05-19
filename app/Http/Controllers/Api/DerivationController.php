<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DerivationCollection;
use App\Http\Resources\DerivationResource;
use App\Models\Derivation;
use App\Models\DerivationDetail;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DerivationController extends Controller
{
    /**
     * Get paginated list of derivations.
     *
     * This endpoint returns a paginated list of document derivations related to the authenticated user's department
     * (either as origin or destination) with optional filtering by document, departments, and date range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage                 = $request->query('per_page', 15);
        $page                    = $request->query('page', 1);
        $documentId              = $request->query('document_id');
        $originDepartmentId      = $request->query('origin_department_id');
        $destinationDepartmentId = $request->query('destination_department_id');
        $dateFrom                = $request->query('date_from');
        $dateTo                  = $request->query('date_to');

        $user             = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        $query = Derivation::with([
            'document',
            'originDepartment',
            'destinationDepartment',
            'derivatedBy',
            'details'
        ])
            ->where(function ($q) use ($userDepartmentId) {
                $q->where('origin_department_id', $userDepartmentId)
                    ->orWhere('destination_department_id', $userDepartmentId);
            });

        if ($documentId) {
            $query->where('document_id', $documentId);
        }

        if ($originDepartmentId) {
            $query->where('origin_department_id', $originDepartmentId);
        }

        if ($destinationDepartmentId) {
            $query->where('destination_department_id', $destinationDepartmentId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $sortBy  = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $derivations = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status'  => 'success',
            'message' => 'Derivations list retrieved successfully',
            'data'    => new DerivationCollection($derivations),
            'meta'    => [
                'current_page' => $derivations->currentPage(),
                'from'         => $derivations->firstItem(),
                'last_page'    => $derivations->lastPage(),
                'per_page'     => $derivations->perPage(),
                'to'           => $derivations->lastItem(),
                'total'        => $derivations->total(),
            ],
            'links'   => [
                'first' => $derivations->url(1),
                'last'  => $derivations->url($derivations->lastPage()),
                'prev'  => $derivations->previousPageUrl(),
                'next'  => $derivations->nextPageUrl(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get all derivations without pagination.
     *
     * This endpoint returns a complete list of all document derivations related to the authenticated user's department
     * (either as origin or destination), including details of documents, departments, and derivation history.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll()
    {
        $user             = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        $derivations = Derivation::with([
            'document',
            'originDepartment',
            'destinationDepartment',
            'derivatedBy',
            'details'
        ])
            ->where(function ($q) use ($userDepartmentId) {
                $q->where('origin_department_id', $userDepartmentId)
                    ->orWhere('destination_department_id', $userDepartmentId);
            })
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Complete list of derivations retrieved successfully',
            'data'    => DerivationResource::collection($derivations),
            'total'   => $derivations->count(),
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created derivation.
     *
     * This endpoint creates a new document derivation from the authenticated user's department to another department.
     * It validates that the document belongs to the user's department and updates the document's derived status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_id'                => 'required|exists:documents,id',
            'destination_department_id'  => 'required|exists:departments,id',
            'comments'                   => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user             = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        if (! $userDepartmentId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The authenticated user does not have an associated department',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $document = Document::find($request->document_id);

        if (! $document) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Document not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($document->created_by_department_id !== $userDepartmentId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You can only derive documents that belong to your department',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($userDepartmentId === $request->destination_department_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The destination department cannot be the same as the origin department',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $derivation = new Derivation([
            'document_id'               => $request->document_id,
            'origin_department_id'      => $userDepartmentId,
            'destination_department_id' => $request->destination_department_id,
            'derivated_by_user_id'      => $user->id,
        ]);
        $derivation->save();

        if ($request->filled('comments')) {
            $detail = new DerivationDetail([
                'derivation_id' => $derivation->id,
                'comments'      => $request->comments,
                'user_id'       => $user->id,
                'status'        => 'derived',
            ]);
            $detail->save();
        }

        $derivation->load([
            'document',
            'originDepartment',
            'destinationDepartment',
            'derivatedBy',
            'details'
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Derivation created successfully',
            'data'    => new DerivationResource($derivation),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified derivation.
     *
     * This endpoint retrieves detailed information about a specific derivation by ID, including its document,
     * origin and destination departments, and history. Access is restricted to users from departments involved
     * in the derivation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $derivation = Derivation::with([
            'document',
            'originDepartment',
            'destinationDepartment',
            'derivatedBy',
            'details.user'
        ])
            ->find($id);

        if (! $derivation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Derivation not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $user             = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        if (
            $derivation->origin_department_id !== $userDepartmentId &&
            $derivation->destination_department_id !== $userDepartmentId
        ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You do not have permission to view this derivation',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Derivation retrieved successfully',
            'data'    => new DerivationResource($derivation),
        ], Response::HTTP_OK);
    }

    /**
     * Add a comment to an existing derivation.
     *
     * This endpoint adds a new comment and optional status update to an existing derivation.
     * Access is restricted to users from departments involved in the derivation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                        $id
     * @return \Illuminate\Http\Response
     */
    public function addComment(Request $request, $id)
    {
        $derivation = Derivation::find($id);

        if (! $derivation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Derivation not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $user             = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        if (
            $derivation->origin_department_id !== $userDepartmentId &&
            $derivation->destination_department_id !== $userDepartmentId
        ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You do not have permission to comment on this derivation',
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'comments' => 'required|string|max:1000',
            'status'   => 'required|in:derived,in_progress,completed,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $detail = new DerivationDetail([
            'derivation_id' => $derivation->id,
            'comments'      => $request->comments,
            'user_id'       => $user->id,
            'status'        => $request->status,
        ]);
        $detail->save();

        $derivation->load([
            'document',
            'originDepartment',
            'destinationDepartment',
            'derivatedBy',
            'details.user'
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Comment added successfully',
            'data'    => new DerivationResource($derivation),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified derivation.
     *
     * This endpoint deletes a derivation and its associated details.
     * Access is restricted to the origin department and only within 2 hours of creation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $derivation = Derivation::find($id);

        if (! $derivation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Derivation not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $user             = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        if ($derivation->origin_department_id !== $userDepartmentId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You do not have permission to delete this derivation',
            ], Response::HTTP_FORBIDDEN);
        }

        $hoursDifference = Carbon::parse($derivation->created_at)
            ->diffInHours(Carbon::now());

        if ($hoursDifference > 2) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot delete derivation after 2 hours of creation',
            ], Response::HTTP_CONFLICT);
        }

        DerivationDetail::where('derivation_id', $derivation->id)->delete();
        $derivation->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Derivation deleted successfully',
        ], Response::HTTP_OK);
    }
}
