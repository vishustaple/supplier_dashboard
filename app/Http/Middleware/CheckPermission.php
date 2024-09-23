<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (!auth()->user()->hasPermission($permission) && Auth::user()->user_type == User::USER_TYPE_USER) {
            if ($request->ajax()) {
                return response()->json(['error' => 'You are not authorized to do this action'], 403);
            } else {
                return redirect('/')->with('error', 'Unauthorized');
            }
        }

        return $next($request);
    }
}
