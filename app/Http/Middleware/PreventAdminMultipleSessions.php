<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PreventAdminMultipleSessions
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            $userId = Auth::id();
            $currentSessionId = session()->getId();
            $cacheKey = "admin_session_{$userId}";

            $existingSessionId = Cache::get($cacheKey);

            // Se existe uma sessão diferente da atual
            if ($existingSessionId && $existingSessionId !== $currentSessionId) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')->withErrors([
                    'email' => 'Outra sessão de administrador foi detectada. Por segurança, você foi desconectado.',
                ]);
            }

            // Armazenar a sessão atual
            Cache::put($cacheKey, $currentSessionId, now()->addHours(2));
        }

        return $next($request);
    }
}
