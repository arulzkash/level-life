<!-- resources/js/Pages/Leaderboard/Index.vue -->
<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    items: Array,
    me: Object,
});

const currentView = ref('current');

const viewOptions = [
    { key: 'current', label: 'Streak', icon: '🔥', mobileLabel: 'Streak' },
    { key: 'best', label: 'Best Streak', icon: '🏆', mobileLabel: 'Best' },
    { key: 'active7', label: 'This Week', icon: '⚡', mobileLabel: 'Week' },
    { key: 'recent', label: 'Last Seen', icon: '🕒', mobileLabel: 'Seen' },
];

// Keep styling “glass” + vivid
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
const badgeLore = (row) => row?.badge_top?.description || row?.badge_top?.name || 'No lore available.';

const nowMs = ref(Date.now());
let nowTicker = null;

const startNowTicker = () => {
    if (nowTicker) return;
    nowMs.value = Date.now();
    nowTicker = window.setInterval(() => {
        nowMs.value = Date.now();
    }, 30_000);
};

const stopNowTicker = () => {
    if (!nowTicker) return;
    window.clearInterval(nowTicker);
    nowTicker = null;
};

const formatAgo = (iso) => {
    if (!iso) return '—';
    const diff = nowMs.value - new Date(iso).getTime();
    if (diff < 60_000) return 'NOW';
    const minutes = Math.floor(diff / 60_000);
    if (minutes < 60) return `${minutes}m`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h`;
    const days = Math.floor(hours / 24);
    return `${days}d`;
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

const metricCfg = (row) => {
    if (!row) return { val: '-', label: '-', color: 'text-slate-400', unit: '' };

    if (currentView.value === 'current')
        return { val: row.streak_current ?? 0, label: 'STREAK', color: 'text-orange-400', unit: 'streak' };

    if (currentView.value === 'best')
        return { val: row.streak_best ?? 0, label: 'BEST', color: 'text-yellow-400', unit: 'streak' };

    if (currentView.value === 'active7')
        return {
            val: `${row.active_days_last_7d ?? 0}/7`,
            label: 'ACTIVE',
            color: 'text-purple-400',
            unit: 'days',
        };

    if (currentView.value === 'recent')
        return {
            val: formatAgo(row.last_active_at),
            label: 'SEEN',
            color: 'text-cyan-400',
            unit: 'time',
        };

    return { val: '-', label: '-', color: 'text-slate-300', unit: '' };
};

// --- RPG Premium (Genshin-ish): Rarity Chip for metrics ---
const metricIcon = computed(() => {
    if (currentView.value === 'current') return '🔥';
    if (currentView.value === 'best') return '🏆';
    if (currentView.value === 'active7') return '⚡';
    if (currentView.value === 'recent') return '🕒';
    return '✦';
});

const tierFromStreak = (n) => {
    // 0–2 common, 3–6 uncommon, 7–13 rare, 14–29 epic, 30+ legendary
    if (n >= 30) return 'legendary';
    if (n >= 14) return 'epic';
    if (n >= 7) return 'rare';
    if (n >= 3) return 'uncommon';
    return 'common';
};

const tierFromActive = (n) => {
    // 0–1 common, 2–3 uncommon, 4–5 rare, 6 epic, 7 legendary
    if (n >= 7) return 'legendary';
    if (n >= 6) return 'epic';
    if (n >= 4) return 'rare';
    if (n >= 2) return 'uncommon';
    return 'common';
};

const tierFromRecent = (iso) => {
    if (!iso) return 'common';
    const diff = nowMs.value - new Date(iso).getTime();
    if (diff <= 60 * 60 * 1000) return 'legendary'; // <= 1h
    if (diff <= 6 * 60 * 60 * 1000) return 'epic'; // <= 6h
    if (diff <= 24 * 60 * 60 * 1000) return 'rare'; // <= 24h
    return 'common';
};

const rarityChipClass = (tier) => {
    // Glossy + subtle glow (Genshin-ish)
    const base =
        'inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1 font-mono font-black tracking-tight ' +
        'shadow-[0_10px_25px_rgba(0,0,0,0.25)] backdrop-blur-sm ' +
        'ring-1 ring-white/5';

    const tiers = {
        common: 'border-slate-700/60 bg-slate-950/40 text-slate-200',
        uncommon:
            'border-emerald-400/20 bg-gradient-to-r from-emerald-500/18 to-slate-950/35 text-emerald-100 shadow-[0_0_24px_rgba(16,185,129,0.10)]',
        rare: 'border-sky-400/20 bg-gradient-to-r from-sky-500/18 to-slate-950/35 text-sky-100 shadow-[0_0_24px_rgba(56,189,248,0.10)]',
        epic: 'border-purple-400/20 bg-gradient-to-r from-purple-500/18 to-slate-950/35 text-purple-100 shadow-[0_0_24px_rgba(168,85,247,0.12)]',
        legendary:
            'border-amber-400/25 bg-gradient-to-r from-amber-500/18 to-slate-950/35 text-amber-100 shadow-[0_0_28px_rgba(245,158,11,0.14)]',
    };

    return `${base} ${tiers[tier] || tiers.common}`;
};

const metricTier = (row) => {
    if (!row) return 'common';

    if (currentView.value === 'current') return tierFromStreak(row.streak_current ?? 0);
    if (currentView.value === 'best') return tierFromStreak(row.streak_best ?? 0);
    if (currentView.value === 'active7') return tierFromActive(row.active_days_last_7d ?? 0);
    if (currentView.value === 'recent') return tierFromRecent(row.last_active_at);

    return 'common';
};

const metricChipText = (row) => {
    if (!row) return '—';

    if (currentView.value === 'current') return String(row.streak_current ?? 0);
    if (currentView.value === 'best') return String(row.streak_best ?? 0);
    if (currentView.value === 'active7') return `${row.active_days_last_7d ?? 0}/7`;
    if (currentView.value === 'recent') return formatAgo(row.last_active_at);

    return '—';
};

// --- Mini-meter (progress to next milestone) ---
const STREAK_MILESTONES = [3, 7, 14, 30, 60, 100];

const meterFillClass = (tier) => {
    const map = {
        common: 'bg-slate-300/20',
        uncommon: 'bg-emerald-300/30',
        rare: 'bg-sky-300/30',
        epic: 'bg-purple-300/30',
        legendary: 'bg-amber-300/35',
    };
    return map[tier] || map.common;
};

const meterInfo = (row) => {
    // returns { pct: number (0-100), hint: string }
    if (!row) return { pct: 0, hint: '' };

    // Current / Best -> progress to next streak milestone
    if (currentView.value === 'current' || currentView.value === 'best') {
        const val = currentView.value === 'current' ? (row.streak_current ?? 0) : (row.streak_best ?? 0);

        // already at/above max
        const maxM = STREAK_MILESTONES[STREAK_MILESTONES.length - 1];
        if (val >= maxM) return { pct: 100, hint: 'MAX' };

        let prev = 0;
        let next = STREAK_MILESTONES[0];
        for (let i = 0; i < STREAK_MILESTONES.length; i++) {
            const m = STREAK_MILESTONES[i];
            if (val < m) {
                next = m;
                prev = i === 0 ? 0 : STREAK_MILESTONES[i - 1];
                break;
            }
        }

        const span = Math.max(1, next - prev);
        const pct = Math.max(0, Math.min(100, ((val - prev) / span) * 100));
        return { pct, hint: `Next ${next}` };
    }

    // Active7 -> progress 0..7
    if (currentView.value === 'active7') {
        const d = Math.max(0, Math.min(7, row.active_days_last_7d ?? 0));
        return { pct: (d / 7) * 100, hint: `${d}/7` };
    }

    // Recent -> momentum decay (NOW full -> 24h empty)
    if (currentView.value === 'recent') {
        if (!row.last_active_at) {
            return { pct: 0, hint: 'Stopped' };
        }

        const diff = nowMs.value - new Date(row.last_active_at).getTime();
        const dayMs = 24 * 60 * 60 * 1000;

        const pct = Math.max(0, Math.min(100, (1 - diff / dayMs) * 100));

        let hint = 'Stopped';

        if (diff <= 30 * 60 * 1000) {
            hint = 'On fire';
        } else if (diff <= 3 * 60 * 60 * 1000) {
            hint = 'Strong momentum';
        } else if (diff <= 8 * 60 * 60 * 1000) {
            hint = 'Steady';
        } else if (diff <= 16 * 60 * 60 * 1000) {
            hint = 'Fading';
        } else if (diff <= dayMs) {
            hint = 'Lost momentum';
        }

        return { pct, hint };
    }

    return { pct: 0, hint: '' };
};

// --- Sorting (dynamic rank per filter) ---
const sortedItems = computed(() => {
    const list = [...(props.items || [])];

    const sortFn = (a, b) => {
        if (currentView.value === 'current') {
            const d1 = (b.streak_current ?? 0) - (a.streak_current ?? 0);
            if (d1 !== 0) return d1;

            const d2 = (b.streak_best ?? 0) - (a.streak_best ?? 0);
            if (d2 !== 0) return d2;

            const d3 = (b.active_days_last_7d ?? 0) - (a.active_days_last_7d ?? 0);
            if (d3 !== 0) return d3;

            return new Date(b.last_active_at || 0) - new Date(a.last_active_at || 0);
        }
        if (currentView.value === 'best') {
            const d = (b.streak_best ?? 0) - (a.streak_best ?? 0);
            return d !== 0 ? d : (b.streak_current ?? 0) - (a.streak_current ?? 0);
        }
        if (currentView.value === 'active7') {
            const d = (b.active_days_last_7d ?? 0) - (a.active_days_last_7d ?? 0);
            return d !== 0 ? d : (b.streak_current ?? 0) - (a.streak_current ?? 0);
        }
        if (currentView.value === 'recent') {
            const d = new Date(b.last_active_at || 0) - new Date(a.last_active_at || 0);
            return d !== 0 ? d : (b.streak_current ?? 0) - (a.streak_current ?? 0);
        }
        return 0;
    };

    return list.sort(sortFn);
});

const rankedItems = computed(() => sortedItems.value.map((r, idx) => ({ ...r, dynamicRank: idx + 1 })));
const contendersCount = computed(() => rankedItems.value.length);
const champion = computed(() => rankedItems.value[0] || null);

const meRow = computed(() => {
    const id = props.me?.user?.id;
    if (!id) return null;
    return rankedItems.value.find((r) => r.user?.id === id) || { ...props.me, dynamicRank: '-' };
});

const isMe = (row) => row?.user?.id && row.user.id === props.me?.user?.id;

// streak needed to beat previous rank (only makes sense in "current" view)
const prevRankRow = (row) => {
    const r = Number(row?.dynamicRank);
    if (!Number.isFinite(r) || r <= 1) return null;
    return rankedItems.value[r - 2] || null;
};
const streakToBeat = (row) => {
    const prev = prevRankRow(row);
    if (!prev) return null;
    const need = (prev.streak_current ?? 0) - (row.streak_current ?? 0) + 1;
    return Math.max(1, need);
};

// --- Mobile styles: top 3 + me ---
const getRankStyle = (rank, isMeRow) => {
    if (isMeRow)
        return 'border-indigo-500/50 bg-gradient-to-r from-indigo-900/30 to-slate-900/60 shadow-[0_0_20px_rgba(99,102,241,0.22)] translate-x-1';

    if (rank === 1)
        return 'border-yellow-500/45 bg-gradient-to-r from-yellow-900/20 to-slate-900/60 shadow-[0_0_18px_rgba(234,179,8,0.18)]';
    if (rank === 2)
        return 'border-slate-300/35 bg-gradient-to-r from-slate-700/20 to-slate-900/60 shadow-[0_0_14px_rgba(203,213,225,0.12)]';
    if (rank === 3)
        return 'border-orange-700/35 bg-gradient-to-r from-orange-900/20 to-slate-900/60 shadow-[0_0_14px_rgba(194,65,12,0.12)]';

    return 'border-slate-800/60 bg-slate-900/30 hover:border-slate-700 hover:bg-slate-800/50';
};

const getRankIcon = (rank) => {
    if (rank === 1) return '👑';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return rank;
};

// --------------------
// LORE TOOLTIP (Teleport ke body, jadi gak ke-clip)
// --------------------
const lore = ref({
    open: false,
    x: 0,
    y: 0,
    desc: '',
});

const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

const placeLore = (el) => {
    const r = el.getBoundingClientRect();
    const vw = window.innerWidth || 360;
    const vh = window.innerHeight || 640;

    const maxW = Math.min(320, vw - 16);
    const approxH = 150;

    const x = clamp(r.left + r.width / 2, 8 + maxW / 2, vw - 8 - maxW / 2);

    // prefer below; if overflow, place above
    const below = r.bottom + 10;
    const above = r.top - approxH - 10;
    const y = below + approxH < vh ? below : Math.max(8, above);

    lore.value.x = x;
    lore.value.y = y;
};

const openLore = async (e, row) => {
    lore.value.desc = badgeLore(row);
    lore.value.open = true;
    await nextTick();
    if (e?.currentTarget) placeLore(e.currentTarget);
};

const closeLore = () => {
    lore.value.open = false;
};

const toggleLore = async (e, row) => {
    if (lore.value.open) return closeLore();
    return openLore(e, row);
};

const onOutside = (ev) => {
    if (!lore.value.open) return;

    const tip = document.getElementById('lore-tip');
    if (tip && tip.contains(ev.target)) return;
    if (ev.target?.closest?.('[data-lore-trigger="1"]')) return;

    closeLore();
};

const onEsc = (ev) => {
    if (ev.key === 'Escape') closeLore();
};

const onScrollClose = () => {
    if (lore.value.open) closeLore();
};

onMounted(() => {
    computeWeekRangeLabel();
    document.addEventListener('pointerdown', onOutside, { capture: true });
    window.addEventListener('keydown', onEsc);
    window.addEventListener('scroll', onScrollClose, { passive: true, capture: true });
    window.addEventListener('resize', onScrollClose, { passive: true });

    // PERF: ticker only for "recent"
    if (currentView.value === 'recent') startNowTicker();
});
onBeforeUnmount(() => {
    stopNowTicker();
    document.removeEventListener('pointerdown', onOutside, { capture: true });
    window.removeEventListener('keydown', onEsc);
    window.removeEventListener('scroll', onScrollClose, { capture: true });
    window.removeEventListener('resize', onScrollClose);
});

watch(currentView, (v) => {
    if (v === 'recent') startNowTicker();
    else stopNowTicker();
});

watch(currentView, (v) => {
    if (v === 'active7') computeWeekRangeLabel();
});

const getBadgeIcon = (key) => {
    const icons = {
        // Streak Badges
        streak_3: '👞',
        streak_7: '🔥',
        streak_14: '⚔️',
        streak_30: '🛡️',
        streak_60: '💎',
        streak_100: '👑',

        // Recovery Badges
        second_wind: '🍃',
        comeback_kid: '❤️‍🔥',
    };

    return icons[key] || '🎖️';
};

const weekRangeLabel = ref('');

const computeWeekRangeLabel = () => {
    const d = new Date();
    const day = d.getDay(); // 0 Sun .. 6 Sat
    const diff = (day + 6) % 7; // Monday=0
    d.setDate(d.getDate() - diff);
    d.setHours(0, 0, 0, 0);

    const start = new Date(d);
    const end = new Date(d);
    end.setDate(end.getDate() + 6);
    end.setHours(23, 59, 59, 999);

    const fmt = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' });
    weekRangeLabel.value = `This week · ${fmt.format(start)} – ${fmt.format(end)}`;
};
</script>

<template>
    <Head title="Hall of Legends" />

    <div
        class="flex min-h-screen flex-col overflow-x-hidden pb-[90px] font-sans text-gray-200 antialiased md:pb-10"
    >
        <!-- ===================== -->
        <!-- STICKY HEADER (mobile-first) -->
        <!-- ===================== -->
        <div
            class="sticky top-0 z-40 border-b border-slate-800/50 bg-slate-950/85 transition-all duration-300"
        >
            <div
                class="mx-auto flex max-w-4xl flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between"
            >
                <div class="flex items-center justify-between">
                    <h1
                        class="flex items-center gap-2 text-xl font-black tracking-tight text-white drop-shadow-sm"
                    >
                        <span class="text-2xl drop-shadow-[0_0_5px_rgba(255,255,255,0.3)] filter">🏰</span>
                        Hall of Legends
                    </h1>

                    <div class="flex items-center gap-2">
                        <span
                            class="hidden items-center gap-2 rounded-full border border-slate-700/60 bg-slate-900/50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-slate-300 md:inline-flex"
                        >
                            👥 {{ contendersCount }} contenders
                        </span>

                        <Link
                            href="/dashboard"
                            class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-indigo-300 hover:text-indigo-200 md:hidden"
                        >
                            Cmd Center
                        </Link>
                    </div>
                </div>

                <div class="scrollbar-hide mask-linear-x flex gap-2 overflow-x-auto px-1 pb-1 md:pb-0">
                    <button
                        v-for="v in viewOptions"
                        :key="v.key"
                        @click="currentView = v.key"
                        class="group relative flex shrink-0 items-center gap-1.5 overflow-hidden rounded-full border px-3 py-1.5 text-[11px] font-black uppercase tracking-wider transition-all duration-300"
                        :class="
                            currentView === v.key
                                ? 'border-indigo-400/50 bg-indigo-500/20 text-indigo-100 shadow-[0_0_15px_rgba(99,102,241,0.28)]'
                                : 'border-slate-700/50 bg-slate-900/60 text-slate-400 hover:border-slate-500 hover:bg-slate-800 hover:text-slate-200'
                        "
                    >
                        <div
                            class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/10 to-transparent transition-transform duration-700 group-hover:translate-x-full"
                            :class="{ 'translate-x-full': currentView === v.key }"
                        ></div>
                        <span class="relative z-10 drop-shadow-sm filter">{{ v.icon }}</span>
                        <span class="relative z-10 hidden sm:inline">{{ v.label }}</span>
                        <span class="relative z-10 sm:hidden">{{ v.mobileLabel }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ===================== -->
        <!-- MOBILE MAIN (keep “old” feel) -->
        <!-- ===================== -->
        <main class="mx-auto w-full max-w-3xl flex-1 space-y-6 px-4 py-6 md:hidden">
            <!-- WEEK BANNER (Active7 only) -->
            <div
                v-if="currentView === 'active7'"
                class="rounded-2xl border border-purple-500/15 bg-slate-900/30 px-4 py-2 text-center text-[11px] font-bold text-slate-300"
            >
                ⚡ {{ weekRangeLabel }}
            </div>

            <!-- CHAMPION (mobile visible, compact, clear #1) -->
            <section
                v-if="champion"
                class="shine group relative overflow-hidden rounded-3xl border border-yellow-500/25 bg-gradient-to-b from-slate-800/80 to-slate-900/70 p-5 shadow-[0_0_40px_rgba(234,179,8,0.10)] transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.01] hover:border-amber-300/35 hover:shadow-[0_0_60px_rgba(245,158,11,0.16)]"
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
                                {{ champion.user?.name || 'Unknown' }}
                            </h2>

                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    :class="statusCfg(champion.status).cls"
                                    class="inline-flex items-center gap-1 rounded border px-1.5 py-[2px] text-[9px] font-black uppercase tracking-wider shadow-sm"
                                >
                                    <span class="text-[10px]">{{ statusCfg(champion.status).icon }}</span>
                                    {{ statusCfg(champion.status).label }}
                                </span>

                                <!-- MOBILE BADGE: pointerdown fix -->
                                <button
                                    v-if="champion.badge_top"
                                    type="button"
                                    data-lore-trigger="1"
                                    class="relative z-20 inline-flex touch-manipulation items-center gap-1 rounded border border-slate-700/50 bg-slate-950/40 px-1.5 py-[2px] text-[9px] font-bold uppercase text-slate-300 transition-transform active:scale-[0.98]"
                                    @pointerdown.stop.prevent="(e) => toggleLore(e, champion)"
                                    @click.stop.prevent="(e) => toggleLore(e, champion)"
                                >
                                    {{ badgeLabel(champion) }}
                                </button>
                            </div>

                            <!-- Detail extra hanya di LAST SEEN -->
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
                                {{ metricCfg(champion).label }}
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <div :class="rarityChipClass(metricTier(champion))" class="text-3xl">
                                    <span class="opacity-90">{{ metricIcon }}</span>
                                    <span>{{ metricChipText(champion) }}</span>
                                </div>

                                <div class="h-1 w-24 overflow-hidden rounded-full bg-white/10">
                                    <div
                                        class="h-full rounded-full"
                                        :class="meterFillClass(metricTier(champion))"
                                        :style="{ width: `${meterInfo(champion).pct}%` }"
                                    ></div>
                                </div>

                                <div class="text-[9px] font-bold text-slate-500">
                                    {{ meterInfo(champion).hint }}
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
                    Don’t break the chain.
                </div>
            </section>

            <!-- ROSTER (compact list, fits better in 1 screen) -->
            <section class="relative z-10 space-y-2.5">
                <div
                    v-if="rankedItems.length <= 1"
                    class="rounded-2xl border border-dashed border-slate-800/50 bg-slate-900/30 py-8 text-center text-slate-500"
                >
                    <div class="mb-2 text-3xl opacity-50">👥</div>
                    <p class="text-sm font-medium">Waiting for more contenders.</p>
                </div>

                <div
                    v-for="row in rankedItems.slice(1, 51)"
                    :key="row.user?.id + '-' + row.dynamicRank"
                    class="group relative flex transform items-center gap-3 rounded-xl border p-3 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.012]"
                    :class="getRankStyle(row.dynamicRank, isMe(row))"
                >
                    <div
                        class="pointer-events-none absolute inset-0 z-0 overflow-hidden rounded-xl opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                    >
                        <div
                            class="absolute -right-10 -top-10 h-32 w-32 bg-white/5 mix-blend-overlay blur-[50px]"
                        ></div>
                    </div>

                    <div
                        class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border bg-slate-950/80 font-mono text-sm font-black shadow-sm transition-transform group-hover:scale-110"
                        :class="
                            row.dynamicRank <= 3
                                ? 'border-transparent bg-transparent text-xl'
                                : 'border-slate-700/50 text-slate-500'
                        "
                    >
                        {{ getRankIcon(row.dynamicRank) }}
                    </div>

                    <div class="relative z-10 flex min-w-0 flex-1 flex-col justify-center">
                        <div class="mb-0.5 flex items-center gap-2">
                            <div
                                class="truncate text-sm font-bold text-slate-200 transition-colors group-hover:text-white"
                                :class="{ '!text-indigo-200': isMe(row) }"
                            >
                                {{ row.user?.name || 'Unknown' }}
                                <span
                                    v-if="isMe(row)"
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
                                @pointerdown.stop.prevent="(e) => toggleLore(e, row)"
                                @click.stop.prevent="(e) => toggleLore(e, row)"
                            >
                                {{ badgeLabel(row) }}
                            </button>
                        </div>

                        <!-- Detail hanya di tab tertentu -->
                        <div
                            v-if="currentView === 'recent'"
                            class="mt-1 text-[10px] font-bold text-slate-600"
                        >
                            {{ formatDetailTime(row.last_active_at) }}
                        </div>

                        <div
                            v-if="currentView === 'active7'"
                            class="mt-1 text-[10px] font-bold text-slate-600"
                        >
                            Active: {{ row.active_days_last_7d ?? 0 }}/7
                        </div>

                        <!-- CLIMB HINT (Streak view only) -->
                        <div
                            v-if="currentView === 'current' && row.dynamicRank > 1"
                            class="mt-1 text-[10px] font-bold text-slate-600"
                        >
                            ▲ +{{ streakToBeat(row) }} streak to beat #{{ row.dynamicRank - 1 }}
                        </div>
                    </div>

                    <div class="relative z-10 text-right">
                        <div class="flex flex-col items-end gap-1">
                            <div
                                :class="rarityChipClass(metricTier(row))"
                                class="origin-right text-base transition-transform group-hover:scale-110"
                            >
                                <span class="opacity-90">{{ metricIcon }}</span>
                                <span>{{ metricChipText(row) }}</span>
                            </div>

                            <div class="h-1 w-20 overflow-hidden rounded-full bg-white/10">
                                <div
                                    class="h-full rounded-full"
                                    :class="meterFillClass(metricTier(row))"
                                    :style="{ width: `${meterInfo(row).pct}%` }"
                                ></div>
                            </div>

                            <div class="text-[8px] font-bold text-slate-600">
                                {{ meterInfo(row).hint }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- ===================== -->
        <!-- DESKTOP MAIN (keep PC like “now”) -->
        <!-- ===================== -->
        <div class="mx-auto hidden w-full max-w-7xl px-4 py-8 md:block md:px-8">
            <div
                v-if="rankedItems.length === 0"
                class="rounded-3xl border border-dashed border-slate-700 bg-slate-800/30 p-12 text-center"
            >
                <div class="mb-3 text-6xl">🕸️</div>
                <div class="text-lg font-bold text-slate-300">No contenders yet.</div>
                <div class="mt-1 text-sm text-slate-500">Complete quests to enter the Hall.</div>
            </div>

            <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <section class="space-y-4 lg:col-span-8">
                    <!-- WEEK BANNER (Active7 only) -->
                    <div
                        v-if="currentView === 'active7'"
                        class="rounded-2xl border border-purple-500/15 bg-slate-900/35 px-4 py-2 text-center text-xs font-bold text-slate-300"
                    >
                        ⚡ {{ weekRangeLabel }}
                    </div>

                    <!-- Champion desktop -->
                    <div
                        v-if="champion"
                        class="shine group relative overflow-hidden rounded-3xl border border-yellow-500/25 bg-gradient-to-b from-slate-800/80 to-slate-900/70 p-5 shadow-[0_0_40px_rgba(234,179,8,0.10)] transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.01] hover:border-amber-300/35 hover:shadow-[0_0_60px_rgba(245,158,11,0.16)]"
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

                                        <!-- DESKTOP BADGE: hover + pointerdown safe -->
                                        <button
                                            v-if="champion.badge_top"
                                            type="button"
                                            data-lore-trigger="1"
                                            class="relative z-20 inline-flex cursor-default touch-manipulation items-center gap-1.5 rounded-full border border-white/10 bg-slate-900/40 px-2 py-1 text-[10px] font-black uppercase tracking-widest text-slate-200 active:scale-[0.98]"
                                            @pointerenter="
                                                (e) => e.pointerType === 'mouse' && openLore(e, champion)
                                            "
                                            @pointerleave="(e) => e.pointerType === 'mouse' && closeLore()"
                                            @pointerdown.stop.prevent="(e) => toggleLore(e, champion)"
                                            @click.stop.prevent="(e) => toggleLore(e, champion)"
                                        >
                                            {{ badgeLabel(champion) }}
                                        </button>
                                    </div>

                                    <div class="mt-1 truncate text-2xl font-black tracking-tight text-white">
                                        {{ champion.user?.name || 'Unknown' }}
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
                                    {{ metricCfg(champion).label }}
                                </div>
                                <div class="mt-2 flex flex-col items-end gap-1">
                                    <div :class="rarityChipClass(metricTier(champion))" class="text-3xl">
                                        <span class="opacity-90">{{ metricIcon }}</span>
                                        <span>{{ metricChipText(champion) }}</span>
                                    </div>

                                    <div class="h-1 w-28 overflow-hidden rounded-full bg-white/10">
                                        <div
                                            class="h-full rounded-full"
                                            :class="meterFillClass(metricTier(champion))"
                                            :style="{ width: `${meterInfo(champion).pct}%` }"
                                        ></div>
                                    </div>

                                    <div class="text-[10px] font-bold text-slate-500">
                                        {{ meterInfo(champion).hint }}
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
                            Don’t break the chain.
                        </div>
                    </div>

                    <!-- Roster desktop -->
                    <div class="rounded-3xl border border-slate-700 bg-slate-800/50 p-3 shadow-xl">
                        <div class="flex items-center justify-between px-2 pb-2">
                            <div class="text-xs font-black uppercase tracking-widest text-slate-400">
                                Full roster
                            </div>
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                Sorted by: {{ viewOptions.find((v) => v.key === currentView)?.label }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="row in rankedItems.slice(1, 51)"
                                :key="row.user?.id + '-' + row.dynamicRank"
                                class="group relative overflow-hidden rounded-2xl border bg-slate-900/35 p-4 transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.01] hover:border-indigo-400/30 hover:bg-slate-900/55 hover:shadow-[0_0_26px_rgba(99,102,241,0.14)]"
                                :class="
                                    isMe(row)
                                        ? 'border-indigo-500/35 shadow-[0_0_22px_rgba(99,102,241,0.16)]'
                                        : row.dynamicRank === 1
                                          ? 'border-yellow-500/30 shadow-[0_0_24px_rgba(234,179,8,0.10)]'
                                          : row.dynamicRank === 2
                                            ? 'border-slate-300/25 shadow-[0_0_18px_rgba(203,213,225,0.06)]'
                                            : row.dynamicRank === 3
                                              ? 'border-orange-500/25 shadow-[0_0_18px_rgba(249,115,22,0.06)]'
                                              : 'border-slate-700/70 hover:border-slate-600'
                                "
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
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-xl border bg-slate-950/50 font-mono text-sm font-black"
                                            :class="
                                                row.dynamicRank === 1
                                                    ? 'border-yellow-500/30 text-yellow-200'
                                                    : row.dynamicRank === 2
                                                      ? 'border-slate-300/25 text-slate-100'
                                                      : row.dynamicRank === 3
                                                        ? 'border-orange-500/25 text-orange-100'
                                                        : 'border-slate-700 text-slate-200'
                                            "
                                        >
                                            #{{ row.dynamicRank }}
                                        </div>

                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-slate-900 text-sm font-black text-white"
                                        >
                                            {{ (row.user?.name || 'U').slice(0, 1).toUpperCase() }}
                                        </div>

                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <div class="truncate text-base font-black text-white">
                                                    {{ row.user?.name || 'Unknown' }}
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
                                                    @pointerenter="
                                                        (e) => e.pointerType === 'mouse' && openLore(e, row)
                                                    "
                                                    @pointerleave="
                                                        (e) => e.pointerType === 'mouse' && closeLore()
                                                    "
                                                    @pointerdown.stop.prevent="(e) => toggleLore(e, row)"
                                                    @click.stop.prevent="(e) => toggleLore(e, row)"
                                                >
                                                    {{ badgeLabel(row) }}
                                                </button>

                                                <span
                                                    v-if="isMe(row)"
                                                    class="inline-flex items-center gap-1.5 rounded-full border border-indigo-400/30 bg-indigo-600/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-indigo-200"
                                                >
                                                    🎯 YOU
                                                </span>
                                            </div>

                                            <div
                                                v-if="currentView === 'recent'"
                                                class="mt-1 text-xs text-slate-500"
                                            >
                                                Last seen {{ formatAgo(row.last_active_at) }} •
                                                {{ formatDetailTime(row.last_active_at) }}
                                            </div>

                                            <div
                                                v-if="currentView === 'active7'"
                                                class="mt-1 text-xs text-slate-500"
                                            >
                                                Active: {{ row.active_days_last_7d ?? 0 }}/7
                                            </div>

                                            <!-- CLIMB HINT (Streak view only) -->
                                            <div
                                                v-if="currentView === 'current' && row.dynamicRank > 1"
                                                class="mt-1 text-xs font-semibold text-slate-500"
                                            >
                                                ▲ +{{ streakToBeat(row) }} streak to beat #{{
                                                    row.dynamicRank - 1
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl px-4 py-3 text-right">
                                        <div
                                            class="text-[9px] font-black uppercase tracking-widest text-slate-500"
                                        >
                                            {{ metricCfg(row).label }}
                                        </div>
                                        <div class="mt-2 flex flex-col items-end gap-1">
                                            <div
                                                :class="rarityChipClass(metricTier(row))"
                                                class="text-xl transition-transform duration-300 group-hover:scale-105 group-hover:brightness-110"
                                            >
                                                <span class="opacity-90">{{ metricIcon }}</span>
                                                <span>{{ metricChipText(row) }}</span>
                                            </div>

                                            <div
                                                class="group-hover:bg-white/18 h-1 w-24 overflow-hidden rounded-full bg-white/10 transition-colors duration-300"
                                            >
                                                <div
                                                    class="h-full rounded-full"
                                                    :class="meterFillClass(metricTier(row))"
                                                    :style="{ width: `${meterInfo(row).pct}%` }"
                                                ></div>
                                            </div>

                                            <div class="text-[10px] font-bold text-slate-500">
                                                {{ meterInfo(row).hint }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- progress only in ACTIVE 7D -->
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
                        </div>
                    </div>
                </section>

                <!-- Right rail (light) -->
                <aside class="hidden lg:col-span-4 lg:block">
                    <div
                        v-if="meRow"
                        class="rounded-3xl border border-indigo-500/20 bg-slate-900/50 p-5 shadow-xl transition-transform duration-300 hover:-translate-y-0.5 hover:border-indigo-400/30 hover:bg-slate-900/60"
                    >
                        <div class="text-xs font-black uppercase tracking-widest text-slate-400">
                            Your position
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex h-12 w-12 flex-col items-center justify-center rounded-xl border border-indigo-500/30 bg-slate-950/60 text-center"
                                >
                                    <div class="text-[9px] font-black uppercase text-indigo-300/70">Rank</div>
                                    <div class="text-lg font-black text-indigo-200">
                                        {{ meRow.dynamicRank }}
                                    </div>
                                </div>

                                <div class="min-w-0">
                                    <div class="truncate text-sm font-black text-white">
                                        {{ meRow.user?.name || 'You' }}
                                    </div>
                                    <div
                                        class="mt-1 inline-flex items-center gap-1 rounded border px-1.5 py-[1px] text-[9px] font-black uppercase tracking-wider"
                                        :class="statusCfg(meRow.status).cls"
                                    >
                                        <span class="text-[10px]">{{ statusCfg(meRow.status).icon }}</span>
                                        {{ statusCfg(meRow.status).label }}
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500">
                                    {{ metricCfg(meRow).label }}
                                </div>
                                <div class="mt-2 flex flex-col items-end gap-1">
                                    <div :class="rarityChipClass(metricTier(meRow))" class="text-lg">
                                        <span class="opacity-90">{{ metricIcon }}</span>
                                        <span>{{ metricChipText(meRow) }}</span>
                                    </div>

                                    <div class="h-1 w-20 overflow-hidden rounded-full bg-white/10">
                                        <div
                                            class="h-full rounded-full"
                                            :class="meterFillClass(metricTier(meRow))"
                                            :style="{ width: `${meterInfo(meRow).pct}%` }"
                                        ></div>
                                    </div>

                                    <div class="text-[9px] font-bold text-slate-500">
                                        {{ meterInfo(meRow).hint }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="meRow.dynamicRank === '-'" class="mt-3 text-xs text-slate-500">
                            Outside top 50. Keep the chain alive to climb back in.
                        </div>
                    </div>

                    <div
                        class="mt-5 rounded-3xl border border-slate-700 bg-slate-800/50 p-5 shadow-xl transition-transform duration-300 hover:-translate-y-0.5 hover:border-slate-500/70 hover:bg-slate-800/70"
                    >
                        <div class="text-xs font-black uppercase tracking-widest text-slate-400">
                            Quick links
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <Link
                                href="/quests"
                                class="rounded-2xl border border-slate-700 bg-slate-900/40 px-4 py-3 text-center text-xs font-black uppercase tracking-widest text-slate-200 transition-colors hover:bg-slate-900/70"
                            >
                                📜 Quest Board
                            </Link>
                            <Link
                                href="/dashboard"
                                class="rounded-2xl border border-indigo-500/30 bg-indigo-600/15 px-4 py-3 text-center text-xs font-black uppercase tracking-widest text-indigo-200 transition-colors hover:bg-indigo-600/25"
                            >
                                🧭 Command Center
                            </Link>
                        </div>

                        <div class="mt-4 text-xs text-slate-500">
                            Tip: the fastest climb is one clean win today. Keep it simple.
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <!-- ===================== -->
        <!-- MOBILE BOTTOM BAR (me) -->
        <!-- ===================== -->
        <div
            v-if="meRow"
            class="fixed bottom-0 left-0 z-50 w-full border-t border-indigo-500/20 bg-slate-900/85 p-3 shadow-[0_-5px_25px_rgba(0,0,0,0.3)] backdrop-blur-md md:hidden"
        >
            <div
                class="absolute inset-x-0 top-0 h-[1px] bg-gradient-to-r from-transparent via-indigo-500/50 to-transparent"
            ></div>

            <div class="mx-auto flex max-w-3xl items-center justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="group relative flex h-11 w-11 shrink-0 flex-col items-center justify-center overflow-hidden rounded-xl border border-indigo-500/30 bg-gradient-to-br from-indigo-500/10 to-slate-950 shadow-sm"
                    >
                        <div
                            class="absolute inset-0 bg-indigo-500/20 opacity-0 blur-md transition-opacity group-hover:opacity-100"
                        ></div>
                        <span class="relative z-10 text-[8px] font-black uppercase text-indigo-300/70">
                            Rank
                        </span>
                        <span
                            class="relative z-10 text-lg font-black leading-none text-indigo-200 drop-shadow-sm filter"
                        >
                            {{ meRow.dynamicRank }}
                        </span>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-sm font-black text-white">
                            <span class="truncate">{{ meRow.user?.name || 'You' }}</span>
                            <span
                                :class="statusCfg(meRow.status).cls"
                                class="rounded border px-1.5 py-[1px] text-[8px] font-black uppercase tracking-wider"
                            >
                                {{ statusCfg(meRow.status).label }}
                            </span>
                        </div>

                        <!-- Subtext super ringkas -->
                        <div class="mt-0.5 flex items-center gap-1 text-[10px] font-medium text-slate-400">
                            <span v-if="meRow.dynamicRank === 1">👑 Defend your throne.</span>
                            <span v-else-if="currentView === 'current'">🔥 Keep the chain alive.</span>
                            <span v-else-if="currentView === 'best'">🏆 Beat your record.</span>
                            <span v-else-if="currentView === 'active7'">⚡ Add one active day.</span>
                            <span v-else>🕒 Stay visible.</span>
                        </div>

                        <!-- Detail hanya pada tab tertentu -->
                        <div
                            v-if="currentView === 'recent'"
                            class="mt-0.5 text-[10px] font-bold text-slate-500"
                        >
                            {{ formatDetailTime(meRow.last_active_at) }}
                        </div>

                        <!-- CLIMB HINT (Streak view only) -->
                        <div
                            v-if="currentView === 'current' && meRow.dynamicRank > 1"
                            class="mt-0.5 text-[10px] font-bold text-slate-500"
                        >
                            ▲ +{{ streakToBeat(meRow) }} streak to beat #{{ meRow.dynamicRank - 1 }}
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <div class="mb-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-500">
                        {{ metricCfg(meRow).label }}
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <div :class="rarityChipClass(metricTier(meRow))" class="text-lg">
                            <span class="opacity-90">{{ metricIcon }}</span>
                            <span>{{ metricChipText(meRow) }}</span>
                        </div>

                        <div class="h-1 w-20 overflow-hidden rounded-full bg-white/10">
                            <div
                                class="h-full rounded-full"
                                :class="meterFillClass(metricTier(meRow))"
                                :style="{ width: `${meterInfo(meRow).pct}%` }"
                            ></div>
                        </div>

                        <div class="text-[9px] font-bold text-slate-500">
                            {{ meterInfo(meRow).hint }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== -->
        <!-- TELEPORTED LORE TOOLTIP -->
        <!-- ===================== -->
        <Teleport to="body">
            <transition name="fade">
                <div
                    v-if="lore.open"
                    id="lore-tip"
                    class="fixed z-[9999] -translate-x-1/2 rounded-xl border border-slate-700 bg-slate-950/95 p-3 text-xs text-slate-200 shadow-2xl backdrop-blur"
                    :style="{
                        left: lore.x + 'px',
                        top: lore.y + 'px',
                        maxWidth:
                            Math.min(360, (typeof window !== 'undefined' ? window.innerWidth : 360) - 16) +
                            'px',
                    }"
                >
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Lore</div>
                    <div class="mt-1 leading-relaxed text-slate-200">
                        {{ lore.desc }}
                    </div>
                    <div
                        class="mt-2 text-[10px] font-bold uppercase tracking-widest text-slate-600 md:hidden"
                    >
                        tap outside to close
                    </div>
                </div>
            </transition>
        </Teleport>
    </div>
</template>

<style scoped>
/* Shine overlay */
.shine {
    background: linear-gradient(
        45deg,
        transparent 35%,
        rgba(255, 255, 255, 0.08) 45%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.08) 55%,
        transparent 65%
    );
    background-size: 250% 250%;
    /* PERF: disable infinite animation (major repaint cost) */
    animation: none !important;
}
@keyframes shine-anim {
    0% {
        background-position: 250% 0;
    }
    100% {
        background-position: -250% 0;
    }
}

/* Tooltip transition */
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translate(-50%, 6px);
}

/* Utilities */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.mask-linear-x {
    -webkit-mask-image: linear-gradient(to right, transparent 0%, black 2%, black 98%, transparent 100%);
    mask-image: linear-gradient(to right, transparent 0%, black 2%, black 98%, transparent 100%);
}
</style>
