<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\Profile;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\UserDailyActivityService;
use App\Support\CacheKeys;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function __construct(
        private readonly UserDailyActivityService $userDailyActivityService
    ) {}

    public function show(Request $request, string $username): Response
    {
        $normalizedUsername = strtolower($username);
        $ttl = 86400;
        $userId = Cache::remember(
            CacheKeys::publicProfileUsername($normalizedUsername),
            $ttl,
            fn () => User::query()
                ->where('username', $normalizedUsername)
                ->value('id')
        );

        abort_unless($userId, 404);

        $dateKey = CacheKeys::todayJakarta();
        $today = Carbon::createFromFormat('Y-m-d', $dateKey, 'Asia/Jakarta')->startOfDay();
        $summaryPayload = Cache::remember(
            CacheKeys::publicProfileSummary((int) $userId, $dateKey),
            $ttl,
            fn () => $this->buildPublicProfileSummaryPayload((int) $userId, $today)
        );
        $currentStreak = (int) ($summaryPayload['streakSummary']['current_streak'] ?? 0);
        $bestStreak = (int) ($summaryPayload['streakSummary']['best_streak'] ?? 0);

        $payload = [
            ...$summaryPayload,
            'stats' => Cache::remember(
                CacheKeys::publicProfileStats((int) $userId, $dateKey),
                $ttl,
                fn () => $this->userDailyActivityService->buildStats((int) $userId, $today, $currentStreak, $bestStreak)
            ),
            'heatmap' => Cache::remember(
                CacheKeys::publicProfileHeatmap((int) $userId, $dateKey),
                $ttl,
                fn () => $this->userDailyActivityService->buildHeatmap((int) $userId, $today)
            ),
            'badgeVault' => Cache::remember(
                CacheKeys::publicProfileBadgeVault((int) $userId, $dateKey),
                $ttl,
                fn () => $this->buildBadgeVaultPayload((int) $userId)
            ),
        ];

        $payload['identity']['is_owner'] = (int) optional($request->user())->id === (int) $userId;
        $payload['rankSummary']['current_rank'] = $this->resolveLeaderboardRank((int) $userId, $dateKey);

        return Inertia::render('Profile/Show', $payload);
    }

    private function buildPublicProfileSummaryPayload(int $userId, Carbon $today): array
    {
        $user = User::query()
            ->select(['id', 'name', 'username', 'created_at'])
            ->findOrFail($userId);

        $profile = Profile::query()
            ->select([
                'user_id',
                'bio',
                'streak_current',
                'streak_best',
                'last_active_date',
                'freezes_used_total',
                'streak_resets_total',
            ])
            ->where('user_id', $userId)
            ->first();
        $currentStreak = (int) ($profile->streak_current ?? 0);
        $bestStreak = (int) ($profile->streak_best ?? 0);

        return [
            'identity' => [
                'name' => $user->name,
                'username' => $user->username,
                'bio' => $profile->bio,
                'joined_at' => optional($user->created_at)->toDateString(),
                'is_owner' => false,
            ],
            'streakSummary' => [
                'current_streak' => $currentStreak,
                'best_streak' => $bestStreak,
                'status' => $this->resolveStreakStatus($profile?->last_active_date, $today),
                'last_active_date' => $profile?->last_active_date,
            ],
            'rankSummary' => [
                'current_rank' => '-',
            ],
        ];
    }

    private function buildBadgeVaultPayload(int $userId): array
    {
        $profile = Profile::query()
            ->select(['user_id', 'streak_best', 'freezes_used_total', 'streak_resets_total'])
            ->where('user_id', $userId)
            ->first();

        $bestStreak = (int) ($profile->streak_best ?? 0);
        $freezesTotal = (int) ($profile->freezes_used_total ?? 0);
        $resetsTotal = (int) ($profile->streak_resets_total ?? 0);

        $visibleKeys = array_merge(
            array_column(BadgeService::streakMilestones(), 'key'),
            ['second_wind', 'comeback_kid']
        );

        $badges = Badge::query()
            ->whereIn('key', $visibleKeys)
            ->orderBy('id')
            ->get(['id', 'key', 'name', 'description', 'category']);

        $owned = DB::table('user_badges')
            ->where('user_id', $userId)
            ->whereIn('badge_id', $badges->pluck('id'))
            ->pluck('earned_at', 'badge_id');

        $nextBadgeKey = $this->resolveNextBadgeKey($bestStreak, $freezesTotal, $resetsTotal);

        $items = $badges->map(function ($badge) use ($owned, $nextBadgeKey) {
            $earnedAt = $owned[$badge->id] ?? null;

            return [
                'key' => $badge->key,
                'name' => $badge->name,
                'description' => $badge->description,
                'category' => $badge->category,
                'earned_at' => $earnedAt,
                'is_unlocked' => $earnedAt !== null,
                'is_next' => $badge->key === $nextBadgeKey,
            ];
        })->all();

        return [
            'items' => $items,
            'unlocked_count' => collect($items)->where('is_unlocked', true)->count(),
            'total_count' => count($items),
        ];
    }

    private function resolveNextBadgeKey(int $bestStreak, int $freezesTotal, int $resetsTotal): ?string
    {
        foreach (BadgeService::streakMilestones() as $milestone) {
            if ($bestStreak < $milestone['threshold']) {
                return $milestone['key'];
            }
        }

        if ($freezesTotal <= 0) {
            return 'second_wind';
        }

        if ($resetsTotal > 0 && $bestStreak < 7) {
            return 'comeback_kid';
        }

        return null;
    }

    private function resolveLeaderboardRank(int $userId, string $todayYmd): string|int
    {
        $rosterKey = CacheKeys::leaderboardRoster($todayYmd);
        $roster = Cache::get($rosterKey);

        if (! $roster) {
            return '-';
        }

        $roster = collect($roster)->values();

        $myIndex = $roster->search(fn ($row) => (int) $row->user_id === (int) $userId);

        if ($myIndex === false) {
            return '50+';
        }

        return $myIndex + 1;
    }

    private function resolveStreakStatus(?string $lastActiveDate, Carbon $today): string
    {
        if (! $lastActiveDate) {
            return 'Cold';
        }

        if ($lastActiveDate === $today->toDateString()) {
            return 'On Fire';
        }

        if ($lastActiveDate === $today->copy()->subDay()->toDateString()) {
            return 'Pending';
        }

        return 'Cold';
    }
}
