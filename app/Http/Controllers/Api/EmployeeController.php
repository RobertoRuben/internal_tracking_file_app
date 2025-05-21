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
     * This endpoint returns a paginated list of employees with optional filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);

        $query = Employee::with('department')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('dni', 'LIKE', "%{$search}%")
                        ->orWhere('names', 'LIKE', "%{$search}%")
                        ->orWhere('paternal_surname', 'LIKE', "%{$search}%")
                        ->orWhere('maternal_surname', 'LIKE', "%{$search}%")
                        ->orWhere('phone_number', 'LIKE', "%{$search}%");
                });
            })
            ->when($request->department_id, function ($q, $departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->when($request->has('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy($request->query('sort_by', 'id'), $request->query('sort_dir', 'desc'));

        $employees = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Employees list retrieved successfully',
            'data' => new EmployeeCollection($employees),
            'meta' => $this->buildPaginationMeta($employees),
            'links' => $this->buildPaginationLinks($employees)
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
            'message' => 'Complete employee list retrieved',
            'data' => EmployeeResource::collection($employees),
            'count' => $employees->count()
        ], Response::HTTP_OK);
    }

    /**
     * Create new employee
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dni' => 'required|unique:employees,dni|digits:8|numeric',
            'names' => 'required|string|min:3|max:255|regex:/^[\pL\s]+$/u',
            'paternal_surname' => 'required|string|min:3|max:255|regex:/^[\pL\s]+$/u',
            'maternal_surname' => 'required|string|min:3|max:255|regex:/^[\pL\s]+$/u',
            'gender' => 'required|in:M,F',
            'phone_number' => 'nullable|digits:9|numeric|regex:/^9\d{8}$/',
            'is_active' => 'sometimes|boolean',
            'department_id' => 'required|exists:departments,id'
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
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
     * Get specific employee details
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
            'message' => 'Employee details retrieved',
            'data' => new EmployeeResource($employee)
        ], Response::HTTP_OK);
    }

    /**
     * Update employee
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
            'dni' => 'sometimes|required|digits:8|numeric|unique:employees,dni,' . $id,
            'names' => 'sometimes|required|string|min:3|max:255|regex:/^[\pL\s]+$/u',
            'paternal_surname' => 'sometimes|required|string|min:3|max:255|regex:/^[\pL\s]+$/u',
            'maternal_surname' => 'sometimes|required|string|min:3|max:255|regex:/^[\pL\s]+$/u',
            'gender' => 'sometimes|required|in:M,F',
            'phone_number' => 'nullable|digits:9|numeric|regex:/^9\d{8}$/',
            'is_active' => 'sometimes|boolean',
            'department_id' => 'sometimes|required|exists:departments,id'
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
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
     * Delete employee
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $employee = Employee::with('user')->find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($employee->user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete employee with associated user account'
            ], Response::HTTP_CONFLICT);
        }

        $employee->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Employee deleted successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Build pagination metadata
     */
    private function buildPaginationMeta($paginator)
    {
        return [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Build pagination links
     */
    private function buildPaginationLinks($paginator)
    {
        return [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }

    /**
     * Validation messages
     */
    private function validationMessages()
    {
        return [
            'dni.required' => 'DNI is required',
            'dni.unique' => 'DNI already registered',
            'dni.digits' => 'DNI must have 8 digits',
            'names.regex' => 'Names can only contain letters and spaces',
            'paternal_surname.regex' => 'Paternal surname can only contain letters and spaces',
            'maternal_surname.regex' => 'Maternal surname can only contain letters and spaces',
            'phone_number.regex' => 'Phone number must start with 9 and have 9 digits',
            'department_id.exists' => 'Selected department does not exist'
        ];
    }
}
