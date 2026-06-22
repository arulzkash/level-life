<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBadgeDebugEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment('local') || config('app.features.badge_debug'), 404);

        return $next($request);
    }
}