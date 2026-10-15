<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk mengakses menu ini.');
        }

        $user = $request->user();
        $userRole = $user->role;

        // Role 'super_admin', 'superadmin', dan 'admin' selalu memiliki hak akses penuh ke seluruh modul
        if (in_array($userRole, ['super_admin', 'superadmin', 'admin']) || (method_exists($user, 'hasRole') && ($user->hasRole('super_admin') || $user->hasRole('admin')))) {
            return $next($request);
        }

        // Cek via kolom role string atau Spatie hasAnyRole
        if (in_array($userRole, $roles) || (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles))) {
            return $next($request);
        }

        abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
