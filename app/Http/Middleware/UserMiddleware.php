<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->isAdmin()) {
            abort(403, '一般ユーザー権限が必要です。');
        }

        return $next($request);
    }
}
