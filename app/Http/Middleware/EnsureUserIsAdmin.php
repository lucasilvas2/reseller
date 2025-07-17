<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Verificar se o usuário está autenticado
        if (!Auth::check()) {
            Log::warning('Admin access attempt without authentication', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);

            return $this->redirectToAdminLogin($request);
        }

        // Verificar se o usuário tem o role de admin
        if (!Auth::user()->hasRole('admin')) {
            Log::warning('Admin access attempt by non-admin user', [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);

            // Fazer logout por segurança
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->redirectToAdminLogin($request, 'Acesso negado. Você não tem permissão para acessar esta área.');
        }

        return $next($request);
    }

    private function redirectToAdminLogin(Request $request, string $message = 'Acesso negado.')
    {
        // Se for uma requisição AJAX/Inertia, retornar resposta apropriada
        if ($request->expectsJson() || $request->inertia()) {
            return response()->json(['message' => $message], 403);
        }

        // Redirecionar para login de admin
        return redirect()->route('admin.login')->withErrors(['message' => $message]);
    }
}
