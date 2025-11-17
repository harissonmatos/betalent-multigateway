<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        // Força que a requisição aceite JSON
        $request->headers->set('Accept', 'application/json');

        // Próximo middleware / controller
        return $next($request);
    }
}
