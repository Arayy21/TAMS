<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Mengeluarkan sesi lama saat kata sandi akun berubah (setelah reset atau penggantian)
class EnsureSessionCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $current = (int) ($user->session_version ?? 0);
            $stored  = $request->session()->get('session_version');

            if ($stored === null) {
                $request->session()->put('session_version', $current);
            } elseif ((int) $stored !== $current) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Kata sandi akun ini telah diubah. Silakan masuk kembali dengan kata sandi yang baru.');
            }
        }

        return $next($request);
    }
}