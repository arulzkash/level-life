<!-- resources/js/Components/Leaderboard/LeaderboardRow.vue -->
<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { getBadgeIcon } from '@/Utils/badgeMeta';

const props = defineProps({
    row: { type: Object, required: true },
    rank: { type: [Number, String], required: true },
    currentView: { type: String, required: true },
    isMe: { type: Boolean, default: false },
    metricCfg: { type: Object, default: () => ({ val: '-', label: '-', color: 'text-slate-400', unit: '' }) },
    tierClass: { type: String, default: '' },
    meterInfo: { type: Object, default: () => ({ pct: 0, hint: '' }) },
});

const emit = defineEmits(['open-lore']);

// --- Helper functions ---
const statusCfg = (status) => {
    if (status === 'On Fire')
        return {
            icon: '🔥',
            label: 'BLAZING',
            cls: 'bg-orange-500/15 text-orange-300 border-orange-500/30 shadow-[0_0_18px_rgba(249,115,22,0.18)]',
        };
    if (status === 'Pending')
        return {
            icon: '🌙',
            label: 'RECOVERING',
            cls: 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30 shadow-[0_0_18px_rgba(99,102,241,0.14)]',
        };
    if (status === 'Unknown')
        return {
            icon: '🕵️',
            label: 'HIDDEN',
            cls: 'bg-slate-700/35 text-slate-200 border-slate-600/60 shadow-none',
        };
    return {
        icon: '❄️',
        label: 'AFK',
        cls: 'bg-slate-900/60 text-slate-400 border-slate-700 shadow-none',
    };
};

const badgeLabel = (row) => {
    const b = row?.badge_top;
    if (!b) return 'No Badge';
    return `${getBadgeIcon(b.key)} ${b.name}`;
};

const profileHref = (row) => {
    const username = row?.user?.username;
    return username ? `/u/${username}` : null;
};

const metricIcon = computed(() => {
    if (props.currentView === 'current') return '🔥';
    if (props.currentView === 'best') return '🏆';
    if (props.currentView === 'active7') return '⚡';
    if (props.currentView === 'recent') return '🕒';
    return '✦';
});

const metricChipText = computed(() => {
    const row = props.row;
    if (!row) return '—';

    if (props.currentView === 'current') return String(row.streak_current ?? 0);
    if (props.currentView === 'best') return String(row.streak_best ?? 0);
    if (props.currentView === 'active7') return `${row.active_days_last_7d ?? 0}/7`;
    if (props.currentView === 'recent') return props.metricCfg?.val ?? '—';

    return '—';
});

const formatDetailTime = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const meterFillClass = computed(() => {
    const tier = props.tierClass ? extractTier(props.tierClass) : 'common';
    const map = {
        common: 'bg-rarity-common/20',
        uncommon: 'bg-rarity-uncommon/30',
        rare: 'bg-rarity-rare/30',
        epic: 'bg-rarity-epic/30',
        legendary: 'bg-rarity-legendary/35',
    };
    return map[tier] || map.common;
});

// Extract tier name from the tierClass string for meter fill
function extractTier(tierClassStr) {
    if (tierClassStr.includes('rarity-legendary')) return 'legendary';
    if (tierClassStr.includes('rarity-epic')) return 'epic';
    if (tierClassStr.includes('rarity-rare')) return 'rare';
    if (tierClassStr.includes('rarity-uncommon')) return 'uncommon';
    return 'common';
}

// --- Mobile styles ---
const getRankStyle = computed(() => {
    const rank = Number(props.rank);
    if (props.isMe)
        return 'border-indigo-500/50 bg-gradient-to-r from-indigo-900/30 to-slate-900/60 shadow-[0_0_20px_rgba(99,102,241,0.22)] translate-x-1';

    if (rank === 1)
        return 'border-yellow-500/45 bg-gradient-to-r from-yellow-900/20 to-slate-900/60 shadow-[0_0_18px_rgba(234,179,8,0.18)]';
    if (rank === 2)
        return 'border-slate-300/35 bg-gradient-to-r from-slate-700/20 to-slate-900/60 shadow-[0_0_14px_rgba(203,213,225,0.12)]';
    if (rank === 3)
        return 'border-orange-700/35 bg-gradient-to-r from-orange-900/20 to-slate-900/60 shadow-[0_0_14px_rgba(194,65,12,0.12)]';

    return 'border-slate-800/60 bg-slate-900/30 hover:border-slate-700 hover:bg-slate-800/50';
});

const getRankIcon = computed(() => {
    const rank = Number(props.rank);
    if (rank === 1) return '👑';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return rank;
});

// --- Desktop styles ---
const desktopBorderClass = computed(() => {
    const rank = Number(props.rank);
    if (props.isMe)
        return 'border-indigo-500/35 shadow-[0_0_22px_rgba(99,102,241,0.16)]';
    if (rank === 1)
        return 'border-yellow-500/30 shadow-[0_0_24px_rgba(234,179,8,0.10)]';
    if (rank === 2)
        return 'border-slate-300/25 shadow-[0_0_18px_rgba(203,213,225,0.06)]';
    if (rank === 3)
        return 'border-orange-500/25 shadow-[0_0_18px_rgba(249,115,22,0.06)]';
    return 'border-slate-700/70 hover:border-slate-600';
});

const desktopRankClass = computed(() => {
    const rank = Number(props.rank);
    if (rank === 1) return 'border-yellow-500/30 text-yellow-200';
    if (rank === 2) return 'border-slate-300/25 text-slate-100';
    if (rank === 3) return 'border-orange-500/25 text-orange-100';
    return 'border-slate-700 text-slate-200';
});

// Streak needed to beat previous rank (only for "current" view, passed via slot or computed externally)
// This is kept simple - the parent can pass streakToBeat info if needed
const streakToBeat = computed(() => {
    // This will be handled by the parent page since it needs access to the full ranked list
    return null;
});

const formatAgo = (iso) => {
    if (!iso) return '—';
    const diff = Date.now() - new Date(iso).getTime();
    if (diff < 60_000) return 'NOW';
    const minutes = Math.floor(diff / 60_000);
    if (minutes < 60) return `${minutes}m`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h`;
    const days = Math.floor(hours / 24);
    return `${days}d`;
};
</script>

<template>
    <!-- ===================== -->
    <!-- MOBILE ROW -->
    <!-- ===================== -->
    <div
        class="group relative flex transform items-center gap-3 rounded-xl border p-3 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.012] md:hidden"
        :class="getRankStyle"
    >
        <div
            class="pointer-events-none absolute inset-0 z-0 overflow-hidden rounded-xl opacity-0 transition-opacity duration-500 group-hover:opacity-100"
        >
            <div
                class="absolute -right-10 -top-10 h-32 w-32 bg-white/5 mix-blend-overlay blur-[50px]"
            ></div>
        </div>

        <!-- Rank badge -->
        <div
            class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border bg-slate-950/80 font-mono text-sm font-black shadow-sm transition-transform group-hover:scale-110"
            :class="
                rank <= 3
                    ? 'border-transparent bg-transparent text-xl'
                    : 'border-slate-700/50 text-slate-500'
            "
        >
            {{ getRankIcon }}
        </div>

        <!-- Player info -->
        <div class="relative z-10 flex min-w-0 flex-1 flex-col justify-center">
            <div class="mb-0.5 flex items-center gap-2">
                <div
                    class="truncate text-sm font-bold text-slate-200 transition-colors group-hover:text-white"
                    :class="{ '!text-indigo-200': isMe }"
                >
                    <Link
                        v-if="profileHref(row)"
                        :href="profileHref(row)"
                        class="hover:text-indigo-200 hover:underline underline-offset-2"
                    >
                        {{ row.user?.name || 'Unknown' }}
                    </Link>
                    <span v-else>{{ row.user?.name || 'Unknown' }}</span>
                    <span
                        v-if="isMe"
                        class="ml-1 rounded border border-indigo-500/20 bg-indigo-500/10 px-1 text-[9px] font-black uppercase tracking-wider text-indigo-300"
                    >
                        (YOU)
                    </span>
                </div>

                <span v-if="row.status && row.status !== 'Unknown'" class="text-xs">
                    {{ statusCfg(row.status).icon }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <span
                    :class="statusCfg(row.status).cls"
                    class="inline-flex items-center gap-1 rounded border px-1.5 py-[1px] text-[8px] font-black uppercase tracking-wider"
                >
                    {{ statusCfg(row.status).label }}
                </span>

                <!-- MOBILE BADGE: pointerdown fix -->
                <button
                    type="button"
                    data-lore-trigger="1"
                    class="relative z-20 inline-flex touch-manipulation items-center gap-1 rounded border border-slate-700/50 bg-slate-950/40 px-1.5 py-[1px] text-[9px] font-bold uppercase text-slate-400 transition-colors hover:border-slate-600 hover:text-slate-300 active:scale-[0.98]"
                    @click.stop="(e) => emit('open-lore', e, row)"
                >
                    {{ badgeLabel(row) }}
                </button>
            </div>

            <!-- Detail for "recent" tab -->
            <div
                v-if="currentView === 'recent'"
                class="mt-1 text-[10px] font-bold text-slate-600"
            >
                {{ formatDetailTime(row.last_active_at) }}
            </div>

            <!-- Detail for "active7" tab -->
            <div
                v-if="currentView === 'active7'"
                class="mt-1 text-[10px] font-bold text-slate-600"
            >
                Active: {{ row.active_days_last_7d ?? 0 }}/7
            </div>

            <!-- CLIMB HINT (Streak view only) -->
            <slot name="climb-hint"></slot>
        </div>

        <!-- Metric display -->
        <div class="relative z-10 text-right">
            <div class="flex flex-col items-end gap-1">
                <div
                    :class="tierClass"
                    class="origin-right text-base transition-transform group-hover:scale-110"
                >
                    <span class="opacity-90">{{ metricIcon }}</span>
                    <span>{{ metricChipText }}</span>
                </div>

                <div class="h-1 w-20 overflow-hidden rounded-full bg-white/10">
                    <div
                        class="h-full rounded-full"
                        :class="meterFillClass"
                        :style="{ width: `${meterInfo.pct}%` }"
                    ></div>
                </div>

                <div class="text-[8px] font-bold text-slate-600">
                    {{ meterInfo.hint }}
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== -->
    <!-- DESKTOP ROW -->
    <!-- ===================== -->
    <div
        class="group relative hidden overflow-hidden rounded-2xl border bg-slate-900/35 p-4 transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.01] hover:border-indigo-400/30 hover:bg-slate-900/55 hover:shadow-[0_0_26px_rgba(99,102,241,0.14)] md:block"
        :class="desktopBorderClass"
    >
        <div
            class="absolute -right-24 -top-24 h-56 w-56 rounded-full bg-white/5 opacity-0 blur-[90px] transition-opacity duration-300 group-hover:opacity-100"
        ></div>
        <div
            class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
        >
            <div
                class="absolute -left-1/3 top-0 h-full w-1/2 translate-x-[-120%] -skew-x-12 bg-gradient-to-r from-transparent via-white/10 to-transparent transition-transform duration-700 group-hover:translate-x-[220%]"
            ></div>
        </div>

        <div class="relative z-10 flex items-center justify-between gap-6">
            <div class="flex min-w-0 items-center gap-3">
                <!-- Rank number -->
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl border bg-slate-950/50 font-mono text-sm font-black"
                    :class="desktopRankClass"
                >
                    #{{ rank }}
                </div>

                <!-- Avatar initial -->
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-slate-900 text-sm font-black text-white"
                >
                    {{ (row.user?.name || 'U').slice(0, 1).toUpperCase() }}
                </div>

                <!-- Player details -->
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="truncate text-base font-black text-white">
                            <Link
                                v-if="profileHref(row)"
                                :href="profileHref(row)"
                                class="hover:text-indigo-200 hover:underline underline-offset-2"
                            >
                                {{ row.user?.name || 'Unknown' }}
                            </Link>
                            <span v-else>{{ row.user?.name || 'Unknown' }}</span>
                        </div>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-widest"
                            :class="statusCfg(row.status).cls"
                        >
                            <span class="text-[11px]">
                                {{ statusCfg(row.status).icon }}
                            </span>
                            {{ statusCfg(row.status).label }}
                        </span>

                        <!-- DESKTOP BADGE: hover + pointerdown safe -->
                        <button
                            type="button"
                            data-lore-trigger="1"
                            class="relative z-20 inline-flex cursor-default touch-manipulation items-center gap-1.5 rounded-full border border-white/10 bg-slate-900/40 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-slate-200 transition-all duration-300 active:scale-[0.98] group-hover:border-white/20 group-hover:bg-slate-900/55 group-hover:shadow-[0_0_18px_rgba(255,255,255,0.08)]"
                            @pointerenter="(e) => e.pointerType === 'mouse' && emit('open-lore', e, row)"
                            @pointerleave="(e) => e.pointerType === 'mouse' && emit('close-lore')"
                            @click.stop="(e) => emit('open-lore', e, row)"
                        >
                            {{ badgeLabel(row) }}
                        </button>

                        <span
                            v-if="isMe"
                            class="inline-flex items-center gap-1.5 rounded-full border border-indigo-400/30 bg-indigo-600/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-indigo-200"
                        >
                            🎯 YOU
                        </span>
                    </div>

                    <!-- Detail for "recent" tab -->
                    <div
                        v-if="currentView === 'recent'"
                        class="mt-1 text-xs text-slate-500"
                    >
                        Last seen {{ formatAgo(row.last_active_at) }} •
                        {{ formatDetailTime(row.last_active_at) }}
                    </div>

                    <!-- Detail for "active7" tab -->
                    <div
                        v-if="currentView === 'active7'"
                        class="mt-1 text-xs text-slate-500"
                    >
                        Active: {{ row.active_days_last_7d ?? 0 }}/7
                    </div>

                    <!-- CLIMB HINT (Streak view only) -->
                    <slot name="climb-hint-desktop"></slot>
                </div>
            </div>

            <!-- Metric display -->
            <div class="rounded-2xl px-4 py-3 text-right">
                <div
                    class="text-[9px] font-black uppercase tracking-widest text-slate-500"
                >
                    {{ metricCfg.label }}
                </div>
                <div class="mt-2 flex flex-col items-end gap-1">
                    <div
                        :class="tierClass"
                        class="text-xl transition-transform duration-300 group-hover:scale-105 group-hover:brightness-110"
                    >
                        <span class="opacity-90">{{ metricIcon }}</span>
                        <span>{{ metricChipText }}</span>
                    </div>

                    <div
                        class="group-hover:bg-white/18 h-1 w-24 overflow-hidden rounded-full bg-white/10 transition-colors duration-300"
                    >
                        <div
                            class="h-full rounded-full"
                            :class="meterFillClass"
                            :style="{ width: `${meterInfo.pct}%` }"
                        ></div>
                    </div>

                    <div class="text-[10px] font-bold text-slate-500">
                        {{ meterInfo.hint }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress bar only in ACTIVE 7D -->
        <div v-if="currentView === 'active7'" class="relative z-10 mt-3">
            <div
                class="h-2 w-full overflow-hidden rounded-full border border-slate-700 bg-slate-950/40"
            >
                <div
                    class="h-full rounded-full bg-purple-500/60 transition-all"
                    :style="{
                        width: `${Math.min(100, ((row.active_days_last_7d ?? 0) / 7) * 100)}%`,
                    }"
                ></div>
            </div>
        </div>
    </div>
</template>
