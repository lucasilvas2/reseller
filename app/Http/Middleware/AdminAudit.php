<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminAudit
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Apenas log ações dos admins
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            Log::channel('admin')->info('Admin Action', [
                'admin_id' => Auth::id(),
                'admin_email' => Auth::user()->email,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data' => $this->getFilteredData($request),
                'timestamp' => now()->toISOString()
            ]);
        }

        return $response;
    }

    private function getFilteredData(Request $request): array
    {
        $data = $request->all();
        
        // Remover campos sensíveis do log
        $sensitiveFields = ['password', 'password_confirmation', 'current_password', '_token'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}
