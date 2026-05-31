<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import XpProgressBar from '@/Components/Game/XpProgressBar.vue';
import CoinIcon from '@/Components/Game/icons/CoinIcon.vue';
import DashboardStatCard from '@/Components/Dashboard/DashboardStatCard.vue';
import { useStreak } from '@/Composables/useStreak';
import { getBadgeIcon } from '@/Utils/badgeMeta';

const props = defineProps({
    profile: { type: Object, required: true },
    streakStatus: { type: Object, default: null },
    habitSummary: { type: Object, default: null },
    leaderboardData: { type: Object, default: null },
    topBadge: { type: Object, default: null },
});

// Use streakStatus prop if provided, otherwise derive from profile
const streakNumberClass = computed(() => {
    if (props.streakStatus?.streakNumberClass) return props.streakStatus.streakNumberClass;
    return 'text-orange-400';
});

const streakLabel = computed(() => {
    if (props.streakStatus?.streakStatus === 'Cold') return '❄️';
    return '🔥';
});

const isRecoverable = computed(() => {
    if (props.streakStatus?.isRecoverable !== undefined) return props.streakStatus.isRecoverable;
    return true;
});

const getRankClass = (rank) => {
    if (rank === 1) {
        return 'text-transparent bg-clip-text bg-gradient-to-b from-yellow-200 via-yellow-400 to-yellow-600 drop-shadow-[0_0_10px_rgba(234,179,8,0.6)]';
    }
    if (rank === 2) {
        return 'text-transparent bg-clip-text bg-gradient-to-b from-slate-100 via-slate-300 to-slate-500 drop-shadow-[0_0_10px_rgba(203,213,225,0.4)]';
    }
    if (rank === 3) {
        return 'text-transparent bg-clip-text bg-gradient-to-b from-orange-200 via-orange-400 to-orange-700 drop-shadow-[0_0_10px_rgba(249,115,22,0.4)]';
    }
    return 'text-white drop-shadow-sm group-hover:text-indigo-200 transition-colors';
};
</script>

<template>
    <section
        class="relative overflow-hidden rounded-3xl border border-slate-700 bg-slate-800 p-6 shadow-2xl shadow-blue-500/20"
    >
        <div
            class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-blue-500 opacity-25 mix-blend-screen blur-[100px] filter transition-opacity duration-700"
        ></div>

        <div class="relative z-10 grid grid-cols-1 items-center gap-8 md:grid-cols-12">
            <!-- Level Display -->
            <div class="flex flex-col items-center justify-center md:col-span-3">
                <div class="group relative">
                    <div
                        class="absolute inset-0 rounded-full bg-gradient-to-tr from-blue-500 to-sky-400 opacity-75 blur transition duration-500 group-hover:opacity-100"
                    ></div>
                    <div
                        class="relative z-10 flex h-28 w-28 items-center justify-center rounded-full border-4 border-slate-700 bg-slate-900"
                    >
                        <span
                            class="text-5xl font-black text-white drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]"
                        >
                            {{ profile.level_data.current_level }}
                        </span>
                    </div>
                    <div
                        class="absolute -bottom-3 left-1/2 z-20 -translate-x-1/2 transform rounded-full border border-slate-600 bg-slate-900 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-cyan-300"
                    >
                        Level
                    </div>
                </div>
            </div>

            <!-- XP Progress + Stat Cards -->
            <div class="space-y-6 md:col-span-9">
                <XpProgressBar
                    :current="profile.level_data.xp_current"
                    :max="profile.level_data.xp_needed"
                    :percent="profile.level_data.progress_percent"
                />

                <div class="grid grid-cols-6 gap-3 md:grid-cols-5 md:gap-4">
                    <!-- Treasury Card -->
                    <DashboardStatCard
                        label="Treasury"
                        :value="profile.coin_balance"
                        class="col-span-2 hover:shadow-yellow-500/10 md:col-span-1"
                    >
                        <div class="mt-1 flex flex-col items-center justify-center gap-1 md:mt-2">
                            <span class="text-xl font-black text-yellow-400 md:text-3xl">
                                {{ profile.coin_balance }}
                            </span>
                            <span
                                class="inline-flex items-center gap-1 text-xs text-slate-500 md:text-sm"
                            >
                                <CoinIcon cls="w-3.5 h-3.5 drop-shadow-[0_0_6px_rgba(234,179,8,0.45)]" />
                                <span>Gold</span>
                            </span>
                        </div>
                    </DashboardStatCard>

                    <!-- Streak Card -->
                    <DashboardStatCard
                        label="Streak"
                        :value="profile.streak_current"
                        class="col-span-2 hover:shadow-orange-500/10 md:col-span-1"
                    >
                        <div class="mt-1 flex flex-col items-center justify-center gap-1 md:mt-2">
                            <span class="inline-block text-xl font-black md:text-3xl" :class="streakNumberClass">
                                {{ profile.streak_current }}
                            </span>
                            <span class="text-[10px] text-slate-500 md:text-sm flex items-center justify-center gap-1 md:gap-1.5">
                                <span class="whitespace-nowrap">
                                    {{ streakLabel }} Days
                                </span>
                                <span class="h-3 w-[1px] bg-slate-700/50"></span>
                                <div class="flex gap-0.5" :title="isRecoverable ? 'Weekly Streak Freeze (🛡️ available)' : 'Streak Unrecoverable (Gaps too large)'">
                                    <span
                                        v-for="i in 2" :key="i"
                                        class="text-[9px] md:text-[10px] transition-all duration-500"
                                        :class="!isRecoverable
                                            ? 'text-red-900/40 grayscale'
                                            : (i <= (2 - (profile.freezes_used_count || 0))
                                                ? 'text-indigo-400 drop-shadow-[0_0_5px_rgba(129,140,248,0.5)]'
                                                : 'text-slate-600 grayscale opacity-30')"
                                    >
                                        {{ isRecoverable ? '🛡️' : '💔' }}
                                    </span>
                                </div>
                            </span>
                        </div>
                    </DashboardStatCard>

                    <!-- Habits Card -->
                    <DashboardStatCard
                        label="Habits"
                        :value="habitSummary?.done_today ?? 0"
                        class="col-span-2 hover:shadow-emerald-500/10 md:col-span-1"
                    >
                        <div class="mt-1 flex flex-col items-center justify-center gap-1 md:mt-2">
                            <span class="text-xl font-black text-emerald-400 md:text-3xl">
                                {{ habitSummary?.done_today }}
                                <span class="text-lg text-slate-600">/{{ habitSummary?.total }}</span>
                            </span>
                            <span class="text-xs text-slate-500 md:text-sm">✅ Done</span>
                        </div>
                    </DashboardStatCard>

                    <!-- Global Rank Card -->
                    <DashboardStatCard
                        label="Global Rank"
                        :value="leaderboardData?.rank ?? '—'"
                        href="/leaderboard"
                        class="group col-span-3 hover:shadow-indigo-500/20 md:col-span-1"
                    >
                        <div class="mt-1 flex flex-col items-center justify-center md:mt-2">
                            <span
                                v-if="typeof leaderboardData?.rank === 'number'"
                                class="text-3xl font-black md:text-4xl"
                                :class="getRankClass(leaderboardData.rank)"
                            >
                                #{{ leaderboardData.rank }}
                            </span>

                            <span
                                v-else-if="leaderboardData?.rank === '50+'"
                                class="text-2xl font-black text-slate-500 md:text-3xl"
                            >
                                50+
                            </span>

                            <span v-else class="text-3xl font-black text-slate-600 md:text-4xl">—</span>

                            <div class="mt-1 flex items-center justify-center gap-1">
                                <span class="text-xs">🏆</span>

                                <span
                                    v-if="leaderboardData?.rival?.is_king"
                                    class="text-[10px] font-bold text-yellow-500 md:text-xs"
                                >
                                    King
                                </span>

                                <span
                                    v-else-if="leaderboardData?.rival"
                                    class="truncate text-[10px] text-slate-500 md:text-xs"
                                >
                                    Vs:
                                    <span class="text-slate-300 group-hover:text-white">
                                        {{ leaderboardData.rival.name }}
                                    </span>
                                    <span class="ml-1 text-slate-600">
                                        (-{{ leaderboardData.rival.gap }})
                                    </span>
                                </span>

                                <span
                                    v-else
                                    class="text-[10px] font-medium text-indigo-400 underline-offset-2 group-hover:underline md:text-xs"
                                >
                                    {{ leaderboardData?.message || 'Check Leaderboard' }}
                                </span>
                            </div>
                        </div>
                    </DashboardStatCard>

                    <!-- Honor/Badge Card -->
                    <DashboardStatCard
                        v-if="topBadge"
                        label="Honor"
                        :value="topBadge.name"
                        class="group col-span-3 hover:shadow-indigo-500/10 md:col-span-1"
                        title="Latest Achievement"
                    >
                        <div class="mt-1 flex w-full flex-col items-center justify-center gap-1 md:mt-2">
                            <div class="flex items-center gap-2">
                                <span
                                    class="max-w-[120px] truncate text-sm font-black text-white group-hover:text-indigo-200 md:text-lg"
                                >
                                    {{ topBadge.name }}
                                </span>
                                <span
                                    class="text-sm grayscale filter transition-all group-hover:grayscale-0 md:text-lg"
                                >
                                    {{ getBadgeIcon(topBadge.key) }}
                                </span>
                            </div>

                            <div
                                class="line-clamp-2 text-[9px] leading-tight text-slate-500 group-hover:text-slate-400 md:text-[10px]"
                            >
                                {{ topBadge.description }}
                            </div>
                        </div>
                    </DashboardStatCard>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div
            class="mt-8 flex flex-wrap justify-center gap-3 border-t border-slate-700/50 pt-4 md:justify-start"
        >
            <Link href="/goals" class="btn-secondary">🎯 Goals</Link>
            <Link href="/quests" class="btn-secondary">📜 Quest Board</Link>
            <Link href="/logs/completions" class="btn-secondary">📒 Completion Log</Link>
            <Link href="/treasury" class="btn-secondary">💰 Merchant</Link>
        </div>
    </section>
</template>
