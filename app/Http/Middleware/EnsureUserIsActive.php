<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->is_active === false) {
            Auth::logout();
            
            return redirect()->route('filament.app.auth.login')->with('error', 'Tu cuenta ha sido desactivada. Por favor, contacta al administrador.');
        }

        return $next($request);
    }
}
