<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    // Exibir o formulário de login para administradores
    public function showLoginForm()
    {
        return Inertia::render('Auth/AdminLogin');
    }

    // Processar o login do administrador
    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $key = $request->getRateLimitKey();

        // Tentar autenticar o usuário
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Verificar se o usuário tem o role de administrador
            if (Auth::user()->hasRole('admin')) {
                RateLimiter::clear($key); // Limpar tentativas em caso de sucesso
                
                Log::info('Admin login successful', [
                    'admin_id' => Auth::id(),
                    'admin_email' => Auth::user()->email,
                    'ip' => $request->ip()
                ]);
                
                return redirect()->intended(route('admin.dashboard'));
            }

            // Se não tiver o role, fazer logout e retornar erro
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            RateLimiter::hit($key, 300); // 5 minutos de bloqueio
            
            Log::warning('Admin login attempt by non-admin user', [
                'email' => $request->input('email'),
                'ip' => $request->ip()
            ]);
            
            return back()->withErrors([
                'email' => 'Você não tem permissão para acessar esta área.',
            ]);
        }

        RateLimiter::hit($key, 300); // 5 minutos de bloqueio
        
        Log::warning('Failed admin login attempt', [
            'email' => $request->input('email'),
            'ip' => $request->ip()
        ]);

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ]);
    }

    // Processar o logout do administrador
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
