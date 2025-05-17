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
     * Get paginated list of users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        
        $search = $request->query('search');
        $role = $request->query('role');
        $isActive = $request->has('is_active') ? $request->query('is_active') : null;
        
        $query = User::with(['employee.department', 'roles']);
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhereHas('employee', function($eq) use ($search) {
                      $eq->where('names', 'LIKE', "%{$search}%")
                        ->orWhere('paternal_surname', 'LIKE', "%{$search}%")
                        ->orWhere('maternal_surname', 'LIKE', "%{$search}%")
                        ->orWhere('dni', 'LIKE', "%{$search}%");
                  });
            });
        }
        
        if ($role) {
            $query->whereHas('roles', function($q) use ($role) {
                $q->where('name', $role);
            });
        }
        
        if ($isActive !== null) {
            $query->where('is_active', $isActive === 'true' || $isActive === '1');
        }
        
        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);
        
        $users = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Usuarios recuperados exitosamente',
            'data' => new UserCollection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'from' => $users->firstItem(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'to' => $users->lastItem(),
                'total' => $users->total(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get all users without pagination
     *
     * @return \Illuminate\Http\Response
     */
    public function getAll()
    {
        $users = User::with(['employee.department', 'roles'])->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Lista completa de usuarios recuperada exitosamente',
            'data' => UserResource::collection($users),
            'total' => $users->count()
        ], Response::HTTP_OK);
    }

    /**
     * Get all available roles
     * 
     * @return \Illuminate\Http\Response
     */
    public function getRoles()
    {
        $roles = Role::all(['id', 'name', 'guard_name']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Roles recuperados exitosamente',
            'data' => $roles,
            'total' => $roles->count()
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users,name|regex:/^.*[0-9].*$/',
            'email' => 'required|string|email|max:255|unique:users,email|regex:/^[a-zA-Z0-9._%+-]+@mda\.gob\.pe$/',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
            'employee_id' => 'required|exists:employees,id|unique:users,employee_id',
            'is_active' => 'boolean',
            'roles' => 'sometimes|required|array',
            'roles.*' => 'exists:roles,name',
        ], [
            'name.regex' => 'El nombre de usuario debe contener al menos un número.',
            'email.regex' => 'El correo debe pertenecer al dominio @mda.gob.pe',
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres, una letra, un número y un carácter especial.',
            'employee_id.unique' => 'Este empleado ya tiene un usuario asignado.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'employee_id' => $request->employee_id,
            'is_active' => $request->input('is_active', true),
        ];

        $user = User::create($userData);
        
        // Asignar roles si se proporcionaron
        if ($request->has('roles') && is_array($request->roles)) {
            $user->syncRoles($request->roles);
        }

        // Cargar las relaciones necesarias para la respuesta
        $user->load(['employee.department', 'roles']);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario creado exitosamente',
            'data' => new UserResource($user)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified user
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with(['employee.department', 'roles'])->find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario recuperado exitosamente',
            'data' => new UserResource($user)
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:users,name,' . $id . '|regex:/^.*[0-9].*$/',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id . '|regex:/^[a-zA-Z0-9._%+-]+@mda\.gob\.pe$/',
            'password' => 'sometimes|nullable|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
            'employee_id' => 'sometimes|required|exists:employees,id|unique:users,employee_id,' . $id,
            'is_active' => 'sometimes|boolean',
            'roles' => 'sometimes|required|array',
            'roles.*' => 'exists:roles,name',
        ], [
            'name.regex' => 'El nombre de usuario debe contener al menos un número.',
            'email.regex' => 'El correo debe pertenecer al dominio @mda.gob.pe',
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres, una letra, un número y un carácter especial.',
            'employee_id.unique' => 'Este empleado ya tiene un usuario asignado.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $userData = $request->except('password', 'roles');
        
        // Actualizar contraseña solo si se proporciona una nueva
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);
        
        // Actualizar roles si se proporcionaron
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        // Cargar las relaciones necesarias para la respuesta
        $user->load(['employee.department', 'roles']);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario actualizado exitosamente',
            'data' => new UserResource($user)
        ], Response::HTTP_OK);
    }

    /**
     * Toggle user active status
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusMessage = $user->is_active ? 'habilitado' : 'deshabilitado';

        return response()->json([
            'status' => 'success',
            'message' => "Usuario {$statusMessage} exitosamente",
            'data' => new UserResource($user)
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified user
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        // Eliminar todas las asignaciones de roles antes de eliminar el usuario
        $user->syncRoles([]);
        
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario eliminado exitosamente'
        ], Response::HTTP_OK);
    }
}
