<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeCollection;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{    
    /**
     * Get paginated list of employees
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        
        $search = $request->query('search');
        $department = $request->query('department_id');
        $isActive = $request->has('is_active') ? $request->query('is_active') : null;
        
        $query = Employee::with('department');
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('dni', 'LIKE', "%{$search}%")
                  ->orWhere('names', 'LIKE', "%{$search}%")
                  ->orWhere('paternal_surname', 'LIKE', "%{$search}%")
                  ->orWhere('maternal_surname', 'LIKE', "%{$search}%")
                  ->orWhere('phone_number', 'LIKE', "%{$search}%");
            });
        }
        
        if ($department) {
            $query->where('department_id', $department);
        }
        
        if ($isActive !== null) {
            $query->where('is_active', $isActive === 'true' || $isActive === '1');
        }
        
        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        $employees = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Employees list retrieved successfully',
            'data' => new EmployeeCollection($employees),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'from' => $employees->firstItem(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'to' => $employees->lastItem(),
                'total' => $employees->total(),
            ],
            'links' => [
                'first' => $employees->url(1),
                'last' => $employees->url($employees->lastPage()),
                'prev' => $employees->previousPageUrl(),
                'next' => $employees->nextPageUrl(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get all employees without pagination
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll()
    {
        $employees = Employee::with('department')->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Complete list of employees retrieved successfully',
            'data' => EmployeeResource::collection($employees),
            'total' => $employees->count()
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created employee
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dni' => 'required|unique:employees,dni|numeric|digits:8',
            'names' => 'required|string|min:3|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/',
            'paternal_surname' => 'required|string|min:3|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/',
            'maternal_surname' => 'required|string|min:3|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/',
            'gender' => 'required|in:M,F',
            'phone_number' => 'nullable|numeric|digits:9|regex:/^9\d{8}$/',
            'is_active' => 'boolean',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $employee = Employee::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Employee created successfully',
            'data' => new EmployeeResource($employee)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified employee
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $employee = Employee::with('department')->find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Employee retrieved successfully',
            'data' => new EmployeeResource($employee)
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified employee
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'dni' => 'sometimes|required|numeric|digits:8|unique:employees,dni,' . $id,
            'names' => 'sometimes|required|string|min:3|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/',
            'paternal_surname' => 'sometimes|required|string|min:3|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/',
            'maternal_surname' => 'sometimes|required|string|min:3|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/',
            'gender' => 'sometimes|required|in:M,F',
            'phone_number' => 'nullable|numeric|digits:9|regex:/^9\d{8}$/',
            'is_active' => 'sometimes|boolean',
            'department_id' => 'sometimes|required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $employee->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Employee updated successfully',
            'data' => new EmployeeResource($employee)
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified employee
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($employee->user()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete employee because it has an associated user'
            ], Response::HTTP_CONFLICT);
        }

        $employee->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Employee deleted successfully'
        ], Response::HTTP_OK);
    }
}
