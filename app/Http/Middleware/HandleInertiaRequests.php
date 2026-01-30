<?php

namespace App\Http\Middleware;

use App\Support\CacheKeys;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Profile;

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

        // OPTIMASI: Cek auth dari session, TIDAK query database
        if (! Auth::check()) {
            return $shared;
        }

        $userId = Auth::id(); // Dari session, NO DB QUERY

        // OPTIMASI: Cache user data (name & email jarang berubah)
        // Cache per hari karena jarang update, tapi bisa di-invalidate manual
        $dateKey = CacheKeys::todayJakarta();
        $userCacheKey = CacheKeys::navUser($userId, $dateKey);

        $user = Cache::remember($userCacheKey, 86400, function () use ($userId) {
            return User::select(['id', 'name', 'email'])
                ->find($userId);
        });

        if (! $user) {
            return $shared;
        }

        // OPTIMASI: Cache profile menggunakan CacheKeys yang sudah ada
        // Ini sudah di-invalidate otomatis di CacheBuster::onQuestComplete()
        $profileCacheKey = CacheKeys::navProfile($userId, $dateKey);

        $profile = Cache::remember($profileCacheKey, 86400, function () use ($userId) {
            // PENTING: Query langsung ke Profile, BUKAN pakai relationship
            // Ini menghindari Laravel load relationship yang bisa trigger query tambahan
            return Profile::select(['id', 'user_id', 'coin_balance', 'xp_total'])
                ->where('user_id', $userId)
                ->first();
        });

        $shared['auth'] = [
            'user' => $user->only(['id', 'name', 'email']),
            'profile' => $profile,
        ];

        return $shared;
    }
}
