<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackLastActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $timeout = (int) config('app.auto_logout_minutes', 15);
            $lastActivity = $user->last_activity_at;

            if ($lastActivity && $lastActivity->diffInMinutes(now()) >= $timeout) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('warning', 'You have been logged out due to inactivity.');
            }

            $user->update(['last_activity_at' => now()]);
        }

        return $next($request);
    }
}
