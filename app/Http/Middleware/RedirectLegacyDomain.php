<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLegacyDomain
{
    private const TARGET_HOST = 'levellife.net';

    /**
     * @var array<int, string>
     */
    private const LEGACY_HOSTS = [
        'levellife.my.id',
        'www.levellife.my.id',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());

        if (in_array($host, self::LEGACY_HOSTS, true)) {
            return redirect()->away('https://'.self::TARGET_HOST.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
