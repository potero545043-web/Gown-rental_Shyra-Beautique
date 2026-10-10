<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        $isLoopback = in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true);

        if (app()->environment('production') && !$request->isSecure() && !$isLoopback) {
            return redirect()->secure($request->getRequestUri(), 308);
        }

        return $next($request);
    }
}
