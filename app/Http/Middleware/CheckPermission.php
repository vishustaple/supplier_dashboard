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
            // return response()->json(['error' => 'Unauthorized'], 403);
            // return redirect()->back();
            // return redirect()->back()->with('error', 'Unauthorized');
            return redirect('/')->with('error', 'Unauthorized');
        }

        return $next($request);
    }
}
