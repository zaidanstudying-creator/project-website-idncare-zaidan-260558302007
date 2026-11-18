<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateAccountSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->is_active || $request->session()->get('auth_version') !== $user->auth_version)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Sesi akun tidak lagi berlaku.'], 401)
                : redirect()->route('login')->with('status', 'Sesi berakhir. Silakan masuk kembali.');
        }

        return $next($request);
    }
}
