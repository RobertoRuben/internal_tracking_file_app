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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get pagination parameters or use defaults
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        
        // Get search/filter parameters
        $search = $request->query('search');
        $documentId = $request->query('document_id');
        $senderUserId = $request->query('sender_user_id');
        $receiverUserId = $request->query('receiver_user_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        // Obtener el usuario autenticado y su departamento
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        // Start the query
        $query = ChargeBook::with(['document', 'senderDepartment', 'senderUser', 'receiverUser', 'department']);
        
        // Filtrar por departamento del usuario autenticado
        $query->where('department_id', $userDepartmentId);
        
        // Apply filters if present
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('notes', 'LIKE', "%{$search}%")
                  ->orWhere('registration_number', 'LIKE', "%{$search}%");
            });
        }
        
        if ($documentId) {
            $query->where('document_id', $documentId);
        }
        
        if ($senderUserId) {
            $query->where('sender_user_id', $senderUserId);
        }
        
        if ($receiverUserId) {
            $query->where('receiver_user_id', $receiverUserId);
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
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get all charge books without pagination
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll()
    {
        // Obtener el usuario autenticado y su departamento
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        // Obtener charge books solo del departamento del usuario
        $chargeBooks = ChargeBook::with(['document', 'senderDepartment', 'senderUser', 'receiverUser', 'department'])
            ->where('department_id', $userDepartmentId)
            ->get();
          return response()->json([
            'status' => 'success',
            'message' => 'Complete list of charge books retrieved successfully',
            'data' => ChargeBookResource::collection($chargeBooks),
            'total' => $chargeBooks->count()
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created charge book
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
            'notes' => 'nullable|string|max:1000',
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
        $receiverUserId = $user->id;
        $departmentId = $user->employee->department_id;
        
        if (!$departmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'The authenticated user does not have an associated department'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Create charge book
        $chargeBook = new ChargeBook([
            'document_id' => $request->document_id,
            'sender_department_id' => $request->sender_department_id,
            'sender_user_id' => $request->sender_user_id,
            'receiver_user_id' => $receiverUserId,
            'department_id' => $departmentId,
            'notes' => $request->notes
        ]);

        $chargeBook->save();        return response()->json([
            'status' => 'success',
            'message' => 'Charge book created successfully',
            'data' => new ChargeBookResource($chargeBook)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified charge book
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $chargeBook = ChargeBook::with(['document', 'senderDepartment', 'senderUser', 'receiverUser', 'department'])->find($id);

        if (!$chargeBook) {
            return response()->json([
                'status' => 'error',
                'message' => 'Charge book not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario pertenece al mismo departamento que el cargo book
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        // Si el usuario no es del mismo departamento que el charge book
        if ($chargeBook->department_id != $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to view this charge book'
            ], Response::HTTP_FORBIDDEN);
        }        return response()->json([
            'status' => 'success',
            'message' => 'Charge book retrieved successfully',
            'data' => new ChargeBookResource($chargeBook)
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified charge book
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $chargeBook = ChargeBook::find($id);

        if (!$chargeBook) {
            return response()->json([
                'status' => 'error',
                'message' => 'Charge book not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario pertenece al mismo departamento que el charge book
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        // Si el usuario no es del mismo departamento que el charge book
        if ($chargeBook->department_id != $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to update this charge book'
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Solo permitimos actualizar las notas del charge book
        if ($request->has('notes')) {
            $chargeBook->notes = $request->notes;
        }

        $chargeBook->save();        return response()->json([
            'status' => 'success',
            'message' => 'Charge book updated successfully',
            'data' => new ChargeBookResource($chargeBook)
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified charge book
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $chargeBook = ChargeBook::find($id);

        if (!$chargeBook) {
            return response()->json([
                'status' => 'error',
                'message' => 'Charge book not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Verificar que el usuario pertenece al mismo departamento que el charge book
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;
        
        // Si el usuario no es del mismo departamento que el charge book
        if ($chargeBook->department_id != $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to delete this charge book'
            ], Response::HTTP_FORBIDDEN);
        }        // No permitimos eliminar charge books después de un cierto tiempo
        $createdDate = Carbon::parse($chargeBook->created_at);
        $now = Carbon::now();
        $daysDifference = $createdDate->diffInDays($now);

        if ($daysDifference > 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete charge book after 24 hours of creation'
            ], Response::HTTP_CONFLICT);
        }

        $chargeBook->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Charge book deleted successfully'
        ], Response::HTTP_OK);
    }
}
