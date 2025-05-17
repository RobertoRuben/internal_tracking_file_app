<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentCollection;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Get paginated list of documents
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */    public function index(Request $request)
    {
        // Get pagination parameters or use defaults
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);

        // Get search/filter parameters
        $search = $request->query('search');
        $registeredBy = $request->query('registered_by');
        $isDerived = $request->has('is_derived') ? $request->query('is_derived') : null;
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        // Obtener el usuario autenticado y su departamento
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        // Start the query
        $query = Document::with(['registeredBy', 'creatorDepartment']);

        // Filtrar por departamento del usuario autenticado
        $query->where('created_by_department_id', $userDepartmentId);

        // Apply filters if present
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('subject', 'LIKE', "%{$search}%")
                    ->orWhere('registration_number', 'LIKE', "%{$search}%");
            });
        }

        if ($registeredBy) {
            $query->where('registered_by_user_id', $registeredBy);
        }
        if ($isDerived !== null) {
            $query->where('is_derived', $isDerived === 'true' || $isDerived === '1');
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
        $documents = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Documents list retrieved successfully',
            'data' => new DocumentCollection($documents),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'from' => $documents->firstItem(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'to' => $documents->lastItem(),
                'total' => $documents->total(),
            ],
            'links' => [
                'first' => $documents->url(1),
                'last' => $documents->url($documents->lastPage()),
                'prev' => $documents->previousPageUrl(),
                'next' => $documents->nextPageUrl(),
            ],
        ], Response::HTTP_OK);
    }
    /**
     * Get all documents without pagination
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll()
    {
        // Obtener el usuario autenticado y su departamento
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        // Obtener documentos solo del departamento del usuario
        $documents = Document::with(['registeredBy', 'creatorDepartment'])
            ->where('created_by_department_id', $userDepartmentId)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Complete list of documents retrieved successfully',
            'data' => DocumentResource::collection($documents),
            'total' => $documents->count()
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created document
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'subject' => 'required|string|min:5|max:1000',
            'pages' => 'required|integer|min:1|max:1000',
            'document_file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'registered_by_user_id' => 'sometimes|exists:users,id', // Opcional, se usará el usuario autenticado si no se proporciona
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
        $registeredByUserId = $request->registered_by_user_id ?? $user->id;

        // Obtener el departamento del usuario a través de su empleado
        $departmentId = $user->employee->department_id;

        if (!$departmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'The authenticated user does not have an associated department'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Handle file upload
        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $filename = time() . '_' . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('documentos', $filename, 'public');
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Document file is required'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Create document
        $document = new Document([
            'name' => $request->name,
            'subject' => $request->subject,
            'pages' => $request->pages,
            'path' => $path,
            'registered_by_user_id' => $registeredByUserId,
            'created_by_department_id' => $departmentId,
            'is_derived' => false
        ]);

        $document->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Document created successfully',
            'data' => new DocumentResource($document)
        ], Response::HTTP_CREATED);
    }
    /**
     * Display the specified document
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $document = Document::with(['registeredBy', 'creatorDepartment', 'derivations'])->find($id);

        if (!$document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario pertenece al mismo departamento que creó el documento
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        // Si el usuario no es del mismo departamento que creó el documento
        // y no tiene permisos especiales, no permitir ver el documento
        if ($document->created_by_department_id != $userDepartmentId) {
            // Aquí se podría implementar una verificación de permisos especiales
            // Por ahora, simplemente rechazamos la solicitud
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to view this document'
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Document retrieved successfully',
            'data' => new DocumentResource($document)
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified document
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */    public function update(Request $request, $id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario pertenece al mismo departamento que creó el documento
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        if ($document->created_by_department_id != $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to update this document'
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|min:3|max:255',
            'subject' => 'sometimes|required|string|min:5|max:1000',
            'pages' => 'sometimes|required|integer|min:1|max:1000',
            'document_file' => 'sometimes|file|mimes:pdf|max:10240', // 10MB max
            'is_derived' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($request->hasFile('document_file')) {
            // Delete old file if exists
            if ($document->path && Storage::disk('public')->exists($document->path)) {
                Storage::disk('public')->delete($document->path);
            }

            $file = $request->file('document_file');
            $filename = time() . '_' . Str::slug($request->name ?? $document->name) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('documentos', $filename, 'public');
            $document->path = $path;
        }

        if ($request->has('name')) $document->name = $request->name;
        if ($request->has('subject')) $document->subject = $request->subject;
        if ($request->has('pages')) $document->pages = $request->pages;
        if ($request->has('is_derived')) $document->is_derived = $request->is_derived;

        $document->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Document updated successfully',
            'data' => new DocumentResource($document)
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified document
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */    public function destroy($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        if ($document->created_by_department_id != $userDepartmentId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to delete this document'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($document->derivations()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete document because it has derivations'
            ], Response::HTTP_CONFLICT);
        }

        if ($document->chargeBooks()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete document because it has charge books'
            ], Response::HTTP_CONFLICT);
        }

        if ($document->path && Storage::disk('public')->exists($document->path)) {
            Storage::disk('public')->delete($document->path);
        }

        $document->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Document deleted successfully'
        ], Response::HTTP_OK);
    }
    /**
     * Get the document file for download
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function downloadFile($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario pertenece al mismo departamento que creó el documento
        $user = Auth::user();
        $userDepartmentId = $user->employee->department_id;

        // Si el usuario no es del mismo departamento que creó el documento
        // y no tiene permisos especiales, no permitir descargar el documento
        if ($document->created_by_department_id != $userDepartmentId) {
            // Aquí se podría implementar una verificación de permisos especiales
            // Por ahora, simplemente rechazamos la solicitud
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to download this document'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$document->path || !Storage::disk('public')->exists($document->path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Document file not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $file = Storage::disk('public')->get($document->path);
        $filename = Str::slug($document->name) . '.pdf';

        return response($file)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
