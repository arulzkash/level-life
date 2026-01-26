<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Cache;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */

    public function share(Request $request): array
    {
        $shared = [
            ...parent::share($request),
            'auth' => [
                'user' => null,
                'profile' => null,
            ],
        ];

        $user = $request->user();

        if (! $user) {
            return $shared;
        }

        $profile = Cache::remember("nav_profile:{$user->id}", now()->addMinutes(10), function () use ($user) {
            return $user->profile()
                ->select(['id', 'user_id', 'coin_balance', 'xp_total'])
                ->first();
        });


        $shared['auth'] = [
            'user' => $user->only(['id', 'name', 'email']),
            'profile' => $profile,
        ];

        return $shared;
    }
}
