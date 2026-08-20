<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordReset
{
    /**
     * Redirect users who must reset their password before accessing the app.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_reset_password) {
            if (! $request->routeIs('password.change', 'password.update', 'logout')) {
                return redirect()->route('password.change')
                    ->with('warning', 'You must change your password before continuing.');
            }
        }

        return $next($request);
    }
}
