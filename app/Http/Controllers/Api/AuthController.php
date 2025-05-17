<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Iniciar sesión y crear token de acceso
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Verificar si el usuario existe y las credenciales son correctas
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciales incorrectas'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verificar si el usuario está activo
        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario deshabilitado. Contacte al administrador.'
            ], Response::HTTP_FORBIDDEN);
        }        // Cargar relaciones necesarias para la respuesta
        $user->load(['employee.department', 'roles', 'permissions']);

        // Obtener todos los permisos del usuario (incluyendo los de sus roles)
        $allPermissions = $user->getAllPermissions()->pluck('name');

        // Generar token con el nombre del dispositivo
        $deviceName = $request->input('device_name', 'API Token');
        $token = $user->createToken($deviceName, $allPermissions->toArray())->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'permissions' => $allPermissions
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Cerrar sesión (revocar el token)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        // Revocar el token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Obtener datos del usuario autenticado
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function me(Request $request)
    {
        // Cargar las relaciones necesarias
        $user = $request->user()->load(['employee.department', 'roles']);

        return response()->json([
            'status' => 'success',
            'message' => 'Datos del usuario recuperados exitosamente',
            'data' => new UserResource($user)
        ], Response::HTTP_OK);
    }

    /**
     * Cambiar la contraseña del usuario autenticado
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/',
            'new_password_confirmation' => 'required|same:new_password',
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'new_password.required' => 'La nueva contraseña es obligatoria.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'new_password.regex' => 'La nueva contraseña debe tener al menos 8 caracteres, una letra, un número y un carácter especial.',
            'new_password_confirmation.required' => 'La confirmación de la nueva contraseña es obligatoria.',
            'new_password_confirmation.same' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        // Verificar si la contraseña actual es correcta
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'La contraseña actual es incorrecta'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Actualizar la contraseña
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Contraseña actualizada exitosamente'
        ], Response::HTTP_OK);
    }
}
