<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentCollection;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    /**
     * Get paginated list of departments
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        
        $page = $request->query('page', 1);
        
        $search = $request->query('search');
        
        $query = Department::withCount('employees');
        
        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }
        
        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        $departments = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Departments list retrieved successfully',
            'data' => new DepartmentCollection($departments),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'from' => $departments->firstItem(),
                'last_page' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'to' => $departments->lastItem(),
                'total' => $departments->total(),
            ],
            'links' => [
                'first' => $departments->url(1),
                'last' => $departments->url($departments->lastPage()),
                'prev' => $departments->previousPageUrl(),
                'next' => $departments->nextPageUrl(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get all departments without pagination
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll()
    {
        $departments = Department::withCount('employees')->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Complete list of departments retrieved successfully',
            'data' => DepartmentResource::collection($departments),
            'total' => $departments->count()
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created department
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:departments,name|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/',
        ], [
            'name.required' => 'The department name is required.',
            'name.unique' => 'This department name is already in use.',
            'name.max' => 'The department name must not exceed 255 characters.',
            'name.regex' => 'The department name can only contain letters.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $department = Department::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Department created successfully',
            'data' => new DepartmentResource($department)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified department
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $department = Department::withCount('employees')->find($id);

        if (!$department) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Department retrieved successfully',
            'data' => new DepartmentResource($department)
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified department
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/|unique:departments,name,' . $id,
        ], [
            'name.required' => 'The department name is required.',
            'name.unique' => 'This department name is already in use.',
            'name.max' => 'The department name must not exceed 255 characters.',
            'name.regex' => 'The department name can only contain letters.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $department->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Department updated successfully',
            'data' => new DepartmentResource($department)
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified department
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($department->employees()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete department because it has associated employees'
            ], Response::HTTP_CONFLICT);
        }

        if ($department->documents()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete department because it has associated documents'
            ], Response::HTTP_CONFLICT);
        }

        if ($department->originDerivations()->exists() || $department->destinationDerivations()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete department because it has associated derivations'
            ], Response::HTTP_CONFLICT);
        }

        $department->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Department deleted successfully'
        ], Response::HTTP_OK);
    }
}
