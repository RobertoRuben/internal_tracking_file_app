<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\Exceptions\UnauthorizedException;

class CheckApiPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (Auth::guest()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }        $permissions = is_array($permission)
            ? $permission
            : explode('|', $permission);

        $userHasPermission = false;
        $user = Auth::user();
        
        foreach ($permissions as $perm) {
            if ($user->hasPermissionTo($perm) || $user->hasRole('super_admin')) {
                $userHasPermission = true;
                break;
            }
        }
        
        if (!$userHasPermission) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tiene permisos para acceder a este recurso'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
