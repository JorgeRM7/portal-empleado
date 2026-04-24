<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepte
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->terms_condition) {
            return redirect()->route('term-conditions');
        }

        return $next($request);
    }
}
