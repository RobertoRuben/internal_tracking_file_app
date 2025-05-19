<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChargeBookCollection;
use App\Http\Resources\ChargeBookResource;
use App\Models\ChargeBook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ChargeBookController extends Controller
{
    /**
     * Get paginated list of charge books
     * 
     * This endpoint returns a paginated list of charge books for the authenticated user's department with optional filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        $query = ChargeBook::with(['document', 'senderDepartment', 'senderUser', 'receiverUser', 'department'])
            ->where('department_id', $userDepartmentId);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('notes', 'LIKE', "%{$request->search}%")
                  ->orWhere('registration_number', 'LIKE', "%{$request->search}%");
            });
        }

        if ($request->filled('document_id')) {
            $query->where('document_id', $request->document_id);
        }

        if ($request->filled('sender_user_id')) {
            $query->where('sender_user_id', $request->sender_user_id);
        }

        if ($request->filled('receiver_user_id')) {
            $query->where('receiver_user_id', $request->receiver_user_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay()
            ]);
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $chargeBooks = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Charge books list retrieved successfully',
            'data' => new ChargeBookCollection($chargeBooks),
            'meta' => [
                'current_page' => $chargeBooks->currentPage(),
                'from' => $chargeBooks->firstItem(),
                'last_page' => $chargeBooks->lastPage(),
                'per_page' => $chargeBooks->perPage(),
                'to' => $chargeBooks->lastItem(),
                'total' => $chargeBooks->total(),
            ],
            'links' => [
                'first' => $chargeBooks->url(1),
                'last' => $chargeBooks->url($chargeBooks->lastPage()),
                'prev' => $chargeBooks->previousPageUrl(),
                'next' => $chargeBooks->nextPageUrl(),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Get all charge books without pagination
     * 
     * @return \Illuminate\Http\Response
     */
    public function getAll()
    {
        $user = Auth::user();
        $chargeBooks = ChargeBook::with(['document', 'senderDepartment', 'senderUser', 'receiverUser', 'department'])
            ->where('department_id', $user->employee->department_id)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Complete list of charge books retrieved successfully',
            'data' => ChargeBookResource::collection($chargeBooks),
            'total' => $chargeBooks->count()
        ], Response::HTTP_OK);
    }

    /**
     * Store a new charge book
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_id' => 'required|exists:documents,id',
            'sender_department_id' => 'required|exists:departments,id',
            'sender_user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = Auth::user();
        $chargeBook = ChargeBook::create([
            'document_id' => $request->document_id,
            'sender_department_id' => $request->sender_department_id,
            'sender_user_id' => $request->sender_user_id,
            'receiver_user_id' => $user->id,
            'department_id' => $user->employee->department_id,
            'notes' => $request->notes
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Charge book created successfully',
            'data' => new ChargeBookResource($chargeBook)
        ], Response::HTTP_CREATED);
    }

    /**
     * Show specific charge book
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $chargeBook = ChargeBook::with(['document', 'senderDepartment', 'senderUser', 'receiverUser', 'department'])
            ->findOrFail($id);

        $userDepartmentId = Auth::user()->employee->department_id;
        
        if ($chargeBook->department_id !== $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to charge book'
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Charge book retrieved successfully',
            'data' => new ChargeBookResource($chargeBook)
        ], Response::HTTP_OK);
    }

    /**
     * Update charge book notes
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $chargeBook = ChargeBook::findOrFail($id);
        $userDepartmentId = Auth::user()->employee->department_id;

        if ($chargeBook->department_id !== $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized update attempt'
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $chargeBook->update($request->only('notes'));

        return response()->json([
            'status' => 'success',
            'message' => 'Charge book updated successfully',
            'data' => new ChargeBookResource($chargeBook)
        ], Response::HTTP_OK);
    }

    /**
     * Delete charge book
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $chargeBook = ChargeBook::findOrFail($id);
        $userDepartmentId = Auth::user()->employee->department_id;

        if ($chargeBook->department_id !== $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized deletion attempt'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($chargeBook->created_at->diffInHours() > 24) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete charge book older than 24 hours'
            ], Response::HTTP_CONFLICT);
        }

        $chargeBook->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Charge book deleted successfully'
        ], Response::HTTP_OK);
    }
}