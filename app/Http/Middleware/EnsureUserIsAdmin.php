<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Verificar se o usuário está autenticado e tem a permissão de admin
        if (Auth::check() && Auth::user()->hasPermissionTo('admin')) {
            return $next($request);
        }

        // Redirecionar para uma página de erro ou login
        return redirect('/')->withErrors(['message' => 'Acesso negado.']);
    }
}
