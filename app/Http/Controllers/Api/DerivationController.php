<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DerivationCollection;
use App\Http\Resources\DerivationResource;
use App\Models\Derivation;
use App\Models\DerivationDetail;
use App\Models\Document;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DerivationController extends Controller
{
    /**
     * Get paginated list of derivations
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get pagination parameters or use defaults
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        
        // Get search/filter parameters
        $documentId = $request->query('document_id');
        $originDepartmentId = $request->query('origin_department_id');
        $destinationDepartmentId = $request->query('destination_department_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        // Obtener el usuario autenticado y su departamento
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        // Start the query
        $query = Derivation::with(['document', 'originDepartment', 'destinationDepartment', 'derivatedBy', 'details']);
        
        // Filtrar por derivaciones relacionadas con el departamento del usuario
        // (ya sea como origen o destino)
        $query->where(function($q) use ($userDepartmentId) {
            $q->where('origin_department_id', $userDepartmentId)
              ->orWhere('destination_department_id', $userDepartmentId);
        });
        
        // Apply filters if present
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
        
        // Sort (optional)
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        // Execute the paginated query
        $derivations = $query->paginate($perPage, ['*'], 'page', $page);
          return response()->json([
            'status' => 'success',
            'message' => 'Derivations list retrieved successfully',
            'data' => new DerivationCollection($derivations),
            'meta' => [
                'current_page' => $derivations->currentPage(),
                'from' => $derivations->firstItem(),
                'last_page' => $derivations->lastPage(),
                'per_page' => $derivations->perPage(),
                'to' => $derivations->lastItem(),
                'total' => $derivations->total(),
            ],
            'links' => [
                'first' => $derivations->url(1),
                'last' => $derivations->url($derivations->lastPage()),
                'prev' => $derivations->previousPageUrl(),
                'next' => $derivations->nextPageUrl(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get all derivations without pagination
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll()
    {
        // Obtener el usuario autenticado y su departamento
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        // Obtener derivaciones relacionadas con el departamento del usuario
        $derivations = Derivation::with(['document', 'originDepartment', 'destinationDepartment', 'derivatedBy', 'details'])
            ->where(function($query) use ($userDepartmentId) {
                $query->where('origin_department_id', $userDepartmentId)
                      ->orWhere('destination_department_id', $userDepartmentId);
            })
            ->get();
          return response()->json([
            'status' => 'success',
            'message' => 'Complete list of derivations retrieved successfully',
            'data' => DerivationResource::collection($derivations),
            'total' => $derivations->count()
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created derivation
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_id' => 'required|exists:documents,id',
            'destination_department_id' => 'required|exists:departments,id',
            'comments' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Obtener el usuario autenticado
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        if (!$userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'The authenticated user does not have an associated department'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        // Verificar que el documento existe y pertenece al departamento del usuario
        $document = Document::find($request->document_id);
        
        if (!$document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        if ($document->created_by_department_id != $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only derive documents that belong to your department'
            ], Response::HTTP_FORBIDDEN);
        }
        
        // Verificar que el departamento de destino no sea el mismo que el de origen
        if ($userDepartmentId == $request->destination_department_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'The destination department cannot be the same as the origin department'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Crear la derivación
        $derivation = new Derivation([
            'document_id' => $request->document_id,
            'origin_department_id' => $userDepartmentId,
            'destination_department_id' => $request->destination_department_id,
            'derivated_by_user_id' => $user->id,
        ]);

        $derivation->save();
        
        // Crear el detalle de la derivación si hay comentarios
        if ($request->has('comments')) {
            $derivationDetail = new DerivationDetail([
                'derivation_id' => $derivation->id,
                'comments' => $request->comments,
                'user_id' => $user->id,
                'status' => 'derived' // Establecer el estado inicial
            ]);
            
            $derivationDetail->save();
        }

        // Cargar las relaciones para la respuesta
        $derivation->load(['document', 'originDepartment', 'destinationDepartment', 'derivatedBy', 'details']);        return response()->json([
            'status' => 'success',
            'message' => 'Derivation created successfully',
            'data' => new DerivationResource($derivation)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified derivation
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $derivation = Derivation::with(['document', 'originDepartment', 'destinationDepartment', 'derivatedBy', 'details.user'])->find($id);

        if (!$derivation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Derivation not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario pertenece a uno de los departamentos involucrados
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        // Si el usuario no pertenece ni al departamento de origen ni al de destino
        if ($derivation->origin_department_id != $userDepartmentId && 
            $derivation->destination_department_id != $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to view this derivation'
            ], Response::HTTP_FORBIDDEN);
        }        return response()->json([
            'status' => 'success',
            'message' => 'Derivation retrieved successfully',
            'data' => new DerivationResource($derivation)
        ], Response::HTTP_OK);
    }

    /**
     * Add a comment to an existing derivation
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addComment(Request $request, $id)
    {
        $derivation = Derivation::find($id);

        if (!$derivation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Derivation not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario pertenece a uno de los departamentos involucrados
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        // Si el usuario no pertenece ni al departamento de origen ni al de destino
        if ($derivation->origin_department_id != $userDepartmentId && 
            $derivation->destination_department_id != $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to comment on this derivation'
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'comments' => 'required|string|max:1000',
            'status' => 'required|in:derived,in_progress,completed,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Crear el nuevo detalle de derivación
        $derivationDetail = new DerivationDetail([
            'derivation_id' => $derivation->id,
            'comments' => $request->comments,
            'user_id' => $user->id,
            'status' => $request->status
        ]);
        
        $derivationDetail->save();
        
        // Cargar las relaciones para la respuesta
        $derivation->load(['document', 'originDepartment', 'destinationDepartment', 'derivatedBy', 'details.user']);        return response()->json([
            'status' => 'success',
            'message' => 'Comment added successfully',
            'data' => new DerivationResource($derivation)
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified derivation
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $derivation = Derivation::find($id);

        if (!$derivation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Derivation not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Solo el departamento de origen puede eliminar una derivación
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        if ($derivation->origin_department_id != $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to delete this derivation'
            ], Response::HTTP_FORBIDDEN);
        }

        // No permitimos eliminar derivaciones después de un cierto tiempo
        $createdDate = Carbon::parse($derivation->created_at);
        $now = Carbon::now();
        $hoursDifference = $createdDate->diffInHours($now);

        if ($hoursDifference > 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete derivation after 2 hours of creation'
            ], Response::HTTP_CONFLICT);
        }

        // Eliminar primero los detalles asociados
        DerivationDetail::where('derivation_id', $derivation->id)->delete();
        
        // Luego eliminar la derivación
        $derivation->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Derivation deleted successfully'
        ], Response::HTTP_OK);
    }
}
