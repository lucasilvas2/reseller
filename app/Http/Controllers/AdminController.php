<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminController extends Controller
{
    // Exibir o formulário de login para administradores
    public function showLoginForm()
    {
        return Inertia::render('Auth/AdminLogin');
    }

    // Processar o login do administrador
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Tentar autenticar o usuário

        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();

            // Verificar se o usuário tem a permissão de administrador
            if ($request->user()->hasRole('admin')) {
                return Inertia::render('Admin/Dashboard');
            }

            // Se não tiver a permissão, fazer logout e retornar erro
            Auth::logout();
            return back()->withErrors([
                'email' => 'Você não tem permissão para acessar esta área.',
            ]);
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ]);
    }
}
