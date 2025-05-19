<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Get paginated list of users.
     *
     * This endpoint returns a paginated list of users with optional filtering.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        $perPage  = $request->query('per_page', 15);
        $page     = $request->query('page', 1);
        $search   = $request->query('search');
        $roleName = $request->query('role');
        $isActive = $request->has('is_active') ? $request->query('is_active') : null;

        $query = User::with(['employee.department', 'roles']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->where('names', 'LIKE', "%{$search}%")
                            ->orWhere('paternal_surname', 'LIKE', "%{$search}%")
                            ->orWhere('maternal_surname', 'LIKE', "%{$search}%")
                            ->orWhere('dni', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($roleName) {
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        if ($isActive !== null) {
            $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy  = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status'  => 'success',
            'message' => 'Users retrieved successfully',
            'data'    => new UserCollection($users),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'from'         => $users->firstItem(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'to'           => $users->lastItem(),
                'total'        => $users->total(),
            ],
            'links'   => [
                'first' => $users->url(1),
                'last'  => $users->url($users->lastPage()),
                'prev'  => $users->previousPageUrl(),
                'next'  => $users->nextPageUrl(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get all users without pagination.
     *
     * This endpoint returns a complete list of all users.
     *
     * @return Response
     */
    public function getAll()
    {
        $users = User::with(['employee.department', 'roles'])->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'All users retrieved successfully',
            'data'    => UserResource::collection($users),
            'total'   => $users->count(),
        ], Response::HTTP_OK);
    }

    /**
     * Get all available roles.
     *
     * This endpoint returns all roles available in the system.
     *
     * @return Response
     */
    public function getRoles()
    {
        $roles = Role::all(['id', 'name', 'guard_name']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Roles retrieved successfully',
            'data'    => $roles,
            'total'   => $roles->count(),
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created user.
     *
     * This endpoint creates a new user with specified details and roles.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255|unique:users,name|regex:/^.*[0-9].*$/',
            'email'        => 'required|string|email|max:255|unique:users,email|regex:/^[a-zA-Z0-9._%+-]+@mda\.gob\.pe$/',
            'password'     => 'required|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&]).+$/',
            'employee_id'  => 'required|exists:employees,id|unique:users,employee_id',
            'is_active'    => 'boolean',
            'roles'        => 'sometimes|required|array',
            'roles.*'      => 'exists:roles,name',
        ], [
            'name.regex'         => 'Username must contain at least one number.',
            'email.regex'        => 'Email must belong to the @mda.gob.pe domain.',
            'password.regex'     => 'Password must be at least 8 characters long, include at least one letter, one number, and one special character.',
            'employee_id.unique' => 'This employee already has an assigned user.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'employee_id' => $request->employee_id,
            'is_active'   => $request->input('is_active', true),
        ]);

        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        $user->load(['employee.department', 'roles']);

        return response()->json([
            'status'  => 'success',
            'message' => 'User created successfully',
            'data'    => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified user.
     *
     * This endpoint returns detailed information about a specific user.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $user = User::with(['employee.department', 'roles'])->find($id);

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'User retrieved successfully',
            'data'    => new UserResource($user),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified user.
     *
     * This endpoint updates the details of an existing user including roles.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'name'         => "sometimes|required|string|max:255|unique:users,name,{$id}|regex:/^.*[0-9].*$/",
            'email'        => "sometimes|required|string|email|max:255|unique:users,email,{$id}|regex:/^[a-zA-Z0-9._%+-]+@mda\.gob\.pe$/",
            'password'     => 'sometimes|nullable|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&]).+$/',
            'employee_id'  => "sometimes|required|exists:employees,id|unique:users,employee_id,{$id}",
            'is_active'    => 'sometimes|boolean',
            'roles'        => 'sometimes|required|array',
            'roles.*'      => 'exists:roles,name',
        ], [
            'name.regex'         => 'Username must contain at least one number.',
            'email.regex'        => 'Email must belong to the @mda.gob.pe domain.',
            'password.regex'     => 'Password must be at least 8 characters long, include at least one letter, one number, and one special character.',
            'employee_id.unique' => 'This employee already has an assigned user.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->except('password', 'roles');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        $user->load(['employee.department', 'roles']);

        return response()->json([
            'status'  => 'success',
            'message' => 'User updated successfully',
            'data'    => new UserResource($user),
        ], Response::HTTP_OK);
    }

    /**
     * Toggle user active status.
     *
     * This endpoint toggles the active status of a user (enables/disables the user).
     *
     * @param  int  $id
     * @return Response
     */
    public function toggleStatus($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusMessage = $user->is_active ? 'enabled' : 'disabled';

        return response()->json([
            'status'  => 'success',
            'message' => "User {$statusMessage} successfully",
            'data'    => new UserResource($user),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified user.
     *
     * This endpoint deletes a user from the system.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $user->syncRoles([]);
        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'User deleted successfully',
        ], Response::HTTP_OK);
    }
}
