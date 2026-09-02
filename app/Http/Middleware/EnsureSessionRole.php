<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->session()->has('user.id')) {
            return redirect()->guest(route('login'));
        }

        abort_unless(in_array($request->session()->get('user.role'), $roles, true), 403);

        return $next($request);
    }
}
