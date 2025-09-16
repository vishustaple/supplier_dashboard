<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // If user needs to change password and is trying to access any page other than change password or logout
            if (
                $user->needs_password_change &&
                !$request->is('admin/adminupdate') && // allow password change page
                !$request->is('logout') &&                // allow logout
                !$request->is('login')                    // allow login
            ) {
                return redirect()->route('admin.changePasswordView');
            }
        }
        return $next($request);
    }
}
