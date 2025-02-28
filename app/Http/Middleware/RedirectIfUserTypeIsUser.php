<?php
    namespace App\Http\Middleware;

    use Closure;
    use App\Models\User;
    use Illuminate\Support\Facades\Auth;

    class RedirectIfUserTypeIsUser
    {
        public function handle($request, Closure $next)
        {
            if (Auth::check() && Auth::user()->user_type == User::USER_TYPE_USER) {
                return redirect()->back();
            }

            return $next($request);
        }
    }