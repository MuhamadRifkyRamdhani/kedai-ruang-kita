<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Alias route: admin.auth. Staf harus login lewat tabel users. */
class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            // guest() menyimpan URL tujuan agar setelah login kembali ke halaman itu.
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
