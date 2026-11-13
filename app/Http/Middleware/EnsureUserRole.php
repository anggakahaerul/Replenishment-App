<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('login'));
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (!in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk tindakan ini.',
                ], 403);
            }

            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman atau fitur ini.');
        }

        return $next($request);
    }
}
