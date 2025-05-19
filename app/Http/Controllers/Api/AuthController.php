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
     * Login and create access token
     * 
     * This endpoint validates user credentials and returns an access token.
     *
     * @unauthenticated
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
            'email.required' => 'Email is required.',
            'email.email' => 'Invalid email format.',
            'password.required' => 'Password is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Verificar credenciales del usuario
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verificar si el usuario está activo
        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'User is disabled. Please contact the administrator.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Cargar relaciones necesarias
        $user->load(['employee.department', 'roles', 'permissions']);

        // Obtener todos los permisos
        $allPermissions = $user->getAllPermissions()->pluck('name');

        // Generar token
        $deviceName = $request->input('device_name', 'API Token');
        $token = $user->createToken($deviceName, $allPermissions->toArray())->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'permissions' => $allPermissions
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Logout (revoke token)
     * 
     * This endpoint revokes the current user's access token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ], Response::HTTP_OK);
    }

    /**
     * Get authenticated user details
     * 
     * This endpoint returns the authenticated user's details including roles and permissions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function me(Request $request)
    {
        $user = $request->user()->load(['employee.department', 'roles']);

        return response()->json([
            'status' => 'success',
            'message' => 'User details retrieved successfully',
            'data' => new UserResource($user)
        ], Response::HTTP_OK);
    }

    /**
     * Change authenticated user's password
     * 
     * This endpoint allows the authenticated user to change their password.
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
            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password.min' => 'New password must be at least 8 characters.',
            'new_password.regex' => 'New password must contain at least 8 characters, 1 letter, 1 number, and 1 special character.',
            'new_password_confirmation.required' => 'Password confirmation is required.',
            'new_password_confirmation.same' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully'
        ], Response::HTTP_OK);
    }
}
