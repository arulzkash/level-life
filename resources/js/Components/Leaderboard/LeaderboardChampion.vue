<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { getBadgeIcon } from '@/Utils/badgeMeta';

const props = defineProps({
    champion: {
        type: Object,
        default: null,
    },
    currentView: {
        type: String,
        required: true,
    },
    metricCfg: {
        type: Object,
        default: () => ({ val: '-', label: '-', color: 'text-slate-400', unit: '' }),
    },
    tierClass: {
        type: String,
        default: '',
    },
    meterInfo: {
        type: Object,
        default: () => ({ pct: 0, hint: '' }),
    },
    metricChipText: {
        type: String,
        default: '—',
    },
    formatAgo: {
        type: Function,
        default: () => '—',
    },
});

const emit = defineEmits(['open-lore']);

// --- Internal helpers ---

const metricIcon = computed(() => {
    if (props.currentView === 'current') return '🔥';
    if (props.currentView === 'best') return '🏆';
    if (props.currentView === 'active7') return '⚡';
    if (props.currentView === 'recent') return '🕒';
    return '✦';
});

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

const meterFillClass = (tier) => {
    const map = {
        common: 'bg-rarity-common/20',
        uncommon: 'bg-rarity-uncommon/30',
        rare: 'bg-rarity-rare/30',
        epic: 'bg-rarity-epic/30',
        legendary: 'bg-rarity-legendary/35',
    };
    return map[tier] || map.common;
};

const metricTierValue = computed(() => {
    if (!props.champion) return 'common';
    // Extract tier from tierClass by checking which tier keyword is present
    // tierClass is the full rarityChipClass string, so we derive tier from champion data
    if (props.currentView === 'current') return getTierFromStreak(props.champion.streak_current ?? 0);
    if (props.currentView === 'best') return getTierFromStreak(props.champion.streak_best ?? 0);
    if (props.currentView === 'active7') return getTierFromActive(props.champion.active_days_last_7d ?? 0);
    if (props.currentView === 'recent') return getTierFromRecent(props.champion.last_active_at);
    return 'common';
});

function getTierFromStreak(n) {
    if (n >= 30) return 'legendary';
    if (n >= 14) return 'epic';
    if (n >= 7) return 'rare';
    if (n >= 3) return 'uncommon';
    return 'common';
}

function getTierFromActive(n) {
    if (n >= 7) return 'legendary';
    if (n >= 6) return 'epic';
    if (n >= 4) return 'rare';
    if (n >= 2) return 'uncommon';
    return 'common';
}

function getTierFromRecent(iso) {
    if (!iso) return 'common';
    const diff = Date.now() - new Date(iso).getTime();
    if (diff <= 60 * 60 * 1000) return 'legendary';
    if (diff <= 6 * 60 * 60 * 1000) return 'epic';
    if (diff <= 24 * 60 * 60 * 1000) return 'rare';
    return 'common';
}
</script>

<template>
    <template v-if="champion">
        <!-- MOBILE CHAMPION -->
        <section
            class="shine group relative overflow-hidden rounded-3xl border border-yellow-500/25 bg-gradient-to-b from-slate-800/80 to-slate-900/70 p-5 shadow-[0_0_40px_rgba(234,179,8,0.10)] transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.01] hover:border-amber-300/35 hover:shadow-[0_0_60px_rgba(245,158,11,0.16)] md:hidden"
        >
            <div class="pointer-events-none absolute inset-0 z-0">
                <div
                    class="absolute -right-12 -top-12 h-48 w-48 rounded-full bg-yellow-500/10 mix-blend-screen blur-[70px]"
                ></div>
                <div
                    class="absolute -bottom-12 -left-12 h-48 w-48 rounded-full bg-orange-500/10 opacity-50 mix-blend-screen blur-[70px]"
                ></div>
                <div class="shine absolute inset-0 opacity-25 mix-blend-overlay"></div>
            </div>

            <div
                class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
            >
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(245,158,11,0.14),transparent_45%)]"
                ></div>
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_70%_60%,rgba(99,102,241,0.12),transparent_50%)]"
                ></div>

                <!-- sweep -->
                <div
                    class="via-white/12 absolute -left-1/3 top-0 h-full w-1/2 translate-x-[-120%] -skew-x-12 bg-gradient-to-r from-transparent to-transparent transition-transform duration-700 group-hover:translate-x-[220%]"
                ></div>
            </div>

            <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex flex-1 items-center gap-4">
                    <div
                        class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-yellow-400/45 bg-gradient-to-tl from-yellow-600/30 to-slate-900 text-3xl shadow-lg ring-2 ring-yellow-500/10 ring-offset-2 ring-offset-slate-950"
                    >
                        👑
                        <div
                            class="absolute -bottom-2 -right-1 rounded-full border border-yellow-300/50 bg-gradient-to-r from-yellow-500 to-orange-500 px-2 py-0.5 text-[10px] font-black text-slate-950 shadow-sm"
                        >
                            #1
                        </div>
                    </div>

                    <div class="min-w-0 flex-1 space-y-1">
                        <h2
                            class="truncate bg-gradient-to-r from-yellow-100 via-yellow-200 to-orange-200 bg-clip-text text-xl font-black text-transparent drop-shadow-sm filter"
                        >
                            <Link
                                v-if="profileHref(champion)"
                                :href="profileHref(champion)"
                                class="hover:opacity-85"
                            >
                                {{ champion.user?.name || 'Unknown' }}
                            </Link>
                            <span v-else>{{ champion.user?.name || 'Unknown' }}</span>
                        </h2>

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                :class="statusCfg(champion.status).cls"
                                class="inline-flex items-center gap-1 rounded border px-1.5 py-[2px] text-[9px] font-black uppercase tracking-wider shadow-sm"
                            >
                                <span class="text-[10px]">{{ statusCfg(champion.status).icon }}</span>
                                {{ statusCfg(champion.status).label }}
                            </span>

                            <button
                                v-if="champion.badge_top"
                                type="button"
                                data-lore-trigger="1"
                                class="relative z-20 inline-flex touch-manipulation items-center gap-1 rounded border border-slate-700/50 bg-slate-950/40 px-1.5 py-[2px] text-[9px] font-bold uppercase text-slate-300 transition-transform active:scale-[0.98]"
                                @click.stop="(e) => emit('open-lore', e, champion)"
                            >
                                {{ badgeLabel(champion) }}
                            </button>
                        </div>

                        <!-- Detail extra for LAST SEEN view -->
                        <div v-if="currentView === 'recent'" class="text-[10px] font-bold text-slate-500">
                            {{ formatDetailTime(champion.last_active_at) }}
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-end gap-4 rounded-xl border border-slate-800/50 bg-slate-900/30 p-2"
                >
                    <div class="flex-1 text-right">
                        <div
                            class="mb-0.5 text-[9px] font-bold uppercase tracking-widest text-yellow-500/60"
                        >
                            {{ metricCfg.label }}
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <div :class="tierClass" class="text-3xl">
                                <span class="opacity-90">{{ metricIcon }}</span>
                                <span>{{ metricChipText }}</span>
                            </div>

                            <div class="h-1 w-24 overflow-hidden rounded-full bg-white/10">
                                <div
                                    class="h-full rounded-full"
                                    :class="meterFillClass(metricTierValue)"
                                    :style="{ width: `${meterInfo.pct}%` }"
                                ></div>
                            </div>

                            <div class="text-[9px] font-bold text-slate-500">
                                {{ meterInfo.hint }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="relative z-10 mt-4 rounded-xl border border-yellow-500/10 bg-gradient-to-r from-yellow-900/10 to-slate-900/20 p-2.5 text-center text-[11px] font-medium text-yellow-200/70 backdrop-blur-sm"
            >
                🎯
                <span class="font-bold text-yellow-100">Keep the crown.</span>
                Don't break the chain.
            </div>
        </section>

        <!-- DESKTOP CHAMPION -->
        <div
            class="shine group relative hidden overflow-hidden rounded-3xl border border-yellow-500/25 bg-gradient-to-b from-slate-800/80 to-slate-900/70 p-5 shadow-[0_0_40px_rgba(234,179,8,0.10)] transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.01] hover:border-amber-300/35 hover:shadow-[0_0_60px_rgba(245,158,11,0.16)] md:block"
        >
            <div
                class="absolute -right-28 -top-28 h-64 w-64 rounded-full bg-yellow-500/10 blur-[90px]"
            ></div>
            <div
                class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
            >
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(245,158,11,0.14),transparent_45%)]"
                ></div>
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_70%_60%,rgba(99,102,241,0.12),transparent_50%)]"
                ></div>

                <!-- sweep -->
                <div
                    class="via-white/12 absolute -left-1/3 top-0 h-full w-1/2 translate-x-[-120%] -skew-x-12 bg-gradient-to-r from-transparent to-transparent transition-transform duration-700 group-hover:translate-x-[220%]"
                ></div>
            </div>

            <div class="relative z-10 flex items-center justify-between gap-6">
                <div class="flex min-w-0 items-center gap-4">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl border border-yellow-500/30 bg-slate-950/70 text-2xl shadow transition-transform duration-300 group-hover:scale-110"
                        title="Champion"
                    >
                        👑
                        <span
                            class="absolute inset-0 rounded-2xl opacity-0 shadow-[0_0_26px_rgba(245,158,11,0.18)] transition-opacity duration-300 group-hover:opacity-100"
                        ></span>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <div
                                class="text-sm font-black uppercase tracking-widest text-yellow-300"
                            >
                                #1 Champion
                            </div>

                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border px-2 py-1 text-[10px] font-black uppercase tracking-widest"
                                :class="statusCfg(champion.status).cls"
                            >
                                <span class="text-xs">{{ statusCfg(champion.status).icon }}</span>
                                {{ statusCfg(champion.status).label }}
                            </span>

                            <!-- DESKTOP BADGE -->
                            <button
                                v-if="champion.badge_top"
                                type="button"
                                data-lore-trigger="1"
                                class="relative z-20 inline-flex cursor-default touch-manipulation items-center gap-1.5 rounded-full border border-white/10 bg-slate-900/40 px-2 py-1 text-[10px] font-black uppercase tracking-widest text-slate-200 active:scale-[0.98]"
                                @pointerenter="(e) => e.pointerType === 'mouse' && emit('open-lore', e, champion)"
                                @pointerleave="(e) => e.pointerType === 'mouse' && emit('close-lore')"
                                @click.stop="(e) => emit('open-lore', e, champion)"
                            >
                                {{ badgeLabel(champion) }}
                            </button>
                        </div>

                        <div class="mt-1 truncate text-2xl font-black tracking-tight text-white">
                            <Link
                                v-if="profileHref(champion)"
                                :href="profileHref(champion)"
                                class="hover:text-indigo-200 hover:underline underline-offset-2"
                            >
                                {{ champion.user?.name || 'Unknown' }}
                            </Link>
                            <span v-else>{{ champion.user?.name || 'Unknown' }}</span>
                        </div>

                        <div v-if="currentView === 'recent'" class="mt-1 text-xs text-slate-500">
                            Last seen {{ formatAgo(champion.last_active_at) }} •
                            {{ formatDetailTime(champion.last_active_at) }}
                        </div>

                        <div v-if="currentView === 'active7'" class="mt-1 text-xs text-slate-500">
                            Active: {{ champion.active_days_last_7d ?? 0 }}/7
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                        {{ metricCfg.label }}
                    </div>
                    <div class="mt-2 flex flex-col items-end gap-1">
                        <div :class="tierClass" class="text-3xl">
                            <span class="opacity-90">{{ metricIcon }}</span>
                            <span>{{ metricChipText }}</span>
                        </div>

                        <div class="h-1 w-28 overflow-hidden rounded-full bg-white/10">
                            <div
                                class="h-full rounded-full"
                                :class="meterFillClass(metricTierValue)"
                                :style="{ width: `${meterInfo.pct}%` }"
                            ></div>
                        </div>

                        <div class="text-[10px] font-bold text-slate-500">
                            {{ meterInfo.hint }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="currentView === 'active7'" class="relative z-10 mt-3">
                <div
                    class="h-2.5 w-full overflow-hidden rounded-full border border-purple-500/20 bg-slate-950/40"
                >
                    <div
                        class="h-full rounded-full bg-purple-500/60"
                        :style="{
                            width: `${Math.min(100, ((champion.active_days_last_7d ?? 0) / 7) * 100)}%`,
                        }"
                    ></div>
                </div>
            </div>

            <!-- PC: Keep the crown callout -->
            <div
                class="relative z-10 mt-4 rounded-2xl border border-yellow-500/10 bg-gradient-to-r from-yellow-900/10 to-slate-900/20 p-3 text-center text-sm font-medium text-yellow-200/70 backdrop-blur-sm"
            >
                🎯
                <span class="font-bold text-yellow-100">Keep the crown.</span>
                Don't break the chain.
            </div>
        </div>
    </template>
</template>
