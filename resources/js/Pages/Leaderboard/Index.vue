<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLeaderboardMetrics } from '@/Composables/useLeaderboardMetrics';
import LeaderboardViewTabs from '@/Components/Leaderboard/LeaderboardViewTabs.vue';
import LeaderboardChampion from '@/Components/Leaderboard/LeaderboardChampion.vue';
import LeaderboardRow from '@/Components/Leaderboard/LeaderboardRow.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ items: Array, me: Object, inheritAttrs: false });

const {
    currentView, viewOptions, rankedItems, champion, meRow,
    metricCfg, metricTier, rarityChipClass, meterInfo, formatAgo,
    isMe, startNowTicker, stopNowTicker,
} = useLeaderboardMetrics(computed(() => props.items), computed(() => props.me));

const contendersCount = computed(() => rankedItems.value.length);
const metricIcon = computed(() => {
    const icons = { current: '🔥', best: '🏆', active7: '⚡', recent: '🕒' };
    return icons[currentView.value] || '✦';
});
const metricChipText = (row) => {
    if (!row) return '—';
    if (currentView.value === 'current') return String(row.streak_current ?? 0);
    if (currentView.value === 'best') return String(row.streak_best ?? 0);
    if (currentView.value === 'active7') return `${row.active_days_last_7d ?? 0}/7`;
    if (currentView.value === 'recent') return formatAgo(row.last_active_at);
    return '—';
};
const profileHref = (row) => row?.user?.username ? `/u/${row.user.username}` : null;
const streakToBeat = (row) => {
    const r = Number(row?.dynamicRank);
    if (!Number.isFinite(r) || r <= 1) return null;
    const prev = rankedItems.value[r - 2];
    if (!prev) return null;
    return Math.max(1, (prev.streak_current ?? 0) - (row.streak_current ?? 0) + 1);
};
const statusCfg = (status) => {
    if (status === 'On Fire') return { icon: '🔥', label: 'BLAZING', cls: 'bg-orange-500/15 text-orange-300 border-orange-500/30 shadow-[0_0_18px_rgba(249,115,22,0.18)]' };
    if (status === 'Pending') return { icon: '🌙', label: 'RECOVERING', cls: 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30 shadow-[0_0_18px_rgba(99,102,241,0.14)]' };
    if (status === 'Unknown') return { icon: '🕵️', label: 'HIDDEN', cls: 'bg-slate-700/35 text-slate-200 border-slate-600/60 shadow-none' };
    return { icon: '❄️', label: 'AFK', cls: 'bg-slate-900/60 text-slate-400 border-slate-700 shadow-none' };
};
const meterFillClass = (tier) => ({ common: 'bg-slate-300/20', uncommon: 'bg-emerald-300/30', rare: 'bg-sky-300/30', epic: 'bg-purple-300/30', legendary: 'bg-amber-300/35' }[tier] || 'bg-slate-300/20');
const weekRangeLabel = ref('');
const computeWeekRangeLabel = () => {
    const d = new Date(); d.setDate(d.getDate() - ((d.getDay() + 6) % 7)); d.setHours(0, 0, 0, 0);
    const start = new Date(d); const end = new Date(d); end.setDate(end.getDate() + 6);
    const fmt = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' });
    weekRangeLabel.value = `This week · ${fmt.format(start)} – ${fmt.format(end)}`;
};
const showJumpTop = ref(false);
const scrollToTop = () => window.scrollTo({ top: 0, behavior: 'smooth' });
const computeShowJumpTop = () => { showJumpTop.value = (window.scrollY || 0) > 320; };
const lore = ref({ open: false, x: 0, y: 0, desc: '' });
const openLore = async (e, row) => {
    lore.value.desc = row?.badge_top?.description || row?.badge_top?.name || 'No lore available.';
    lore.value.open = true; await nextTick();
    if (e?.currentTarget) {
        const r = e.currentTarget.getBoundingClientRect(), vw = window.innerWidth || 360, vh = window.innerHeight || 640, maxW = Math.min(320, vw - 16);
        lore.value.x = Math.max(8 + maxW / 2, Math.min(vw - 8 - maxW / 2, r.left + r.width / 2));
        lore.value.y = (r.bottom + 70 < vh) ? r.bottom + 10 : Math.max(8, r.top - 70);
    }
};
const closeLore = () => { lore.value.open = false; };
const onOutside = (ev) => { if (!lore.value.open) return; const tip = document.getElementById('lore-tip'); if (tip && tip.contains(ev.target)) return; if (ev.target?.closest?.('[data-lore-trigger="1"]')) return; closeLore(); };
const onEsc = (ev) => { if (ev.key === 'Escape') closeLore(); };
const onScrollClose = () => { if (lore.value.open) closeLore(); };
onMounted(() => {
    computeWeekRangeLabel(); computeShowJumpTop();
    document.addEventListener('pointerdown', onOutside, { capture: true });
    window.addEventListener('keydown', onEsc);
    ['scroll', 'resize'].forEach(e => { window.addEventListener(e, onScrollClose, { passive: true, capture: e === 'scroll' }); window.addEventListener(e, computeShowJumpTop, { passive: true }); });
    if (currentView.value === 'recent') startNowTicker();
});
onBeforeUnmount(() => {
    stopNowTicker();
    document.removeEventListener('pointerdown', onOutside, { capture: true });
    window.removeEventListener('keydown', onEsc);
    ['scroll', 'resize'].forEach(e => { window.removeEventListener(e, onScrollClose, { capture: e === 'scroll' }); window.removeEventListener(e, computeShowJumpTop); });
});
watch(currentView, (v) => { v === 'recent' ? startNowTicker() : stopNowTicker(); });
watch(currentView, (v) => { if (v === 'active7') computeWeekRangeLabel(); });
</script>

<template>
    <Head title="Hall of Legends" />
    <div class="flex min-h-screen flex-col overflow-x-hidden pb-[90px] font-sans text-gray-200 antialiased md:pb-10">
        <!-- STICKY HEADER -->
        <div class="sticky top-0 z-40 border-b border-slate-800/50 bg-slate-950/85 transition-all duration-300">
            <div class="mx-auto flex max-w-4xl flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center justify-between">
                    <h1 class="flex items-center gap-2 text-xl font-black tracking-tight text-white drop-shadow-sm">
                        <span class="text-2xl drop-shadow-[0_0_5px_rgba(255,255,255,0.3)] filter">🏰</span> Hall of Legends
                    </h1>
                    <div class="flex items-center gap-2">
                        <span class="hidden items-center gap-2 rounded-full border border-slate-700/60 bg-slate-900/50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-slate-300 md:inline-flex">👥 {{ contendersCount }} contenders</span>
                        <Link href="/dashboard" class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-indigo-300 hover:text-indigo-200 md:hidden">Cmd Center</Link>
                    </div>
                </div>
                <LeaderboardViewTabs :view-options="viewOptions" :current-view="currentView" @update:current-view="currentView = $event" />
            </div>
        </div>

        <!-- MOBILE MAIN -->
        <main class="mx-auto w-full max-w-3xl flex-1 space-y-6 px-4 py-6 md:hidden">
            <div v-if="currentView === 'active7'" class="rounded-2xl border border-purple-500/15 bg-slate-900/30 px-4 py-2 text-center text-[11px] font-bold text-slate-300">⚡ {{ weekRangeLabel }}</div>
            <LeaderboardChampion v-if="champion" :champion="champion" :current-view="currentView" :metric-cfg="metricCfg(champion)" :tier-class="rarityChipClass(metricTier(champion))" :meter-info="meterInfo(champion)" :metric-chip-text="metricChipText(champion)" :format-ago="formatAgo" @open-lore="openLore" @close-lore="closeLore" />
            <section class="relative z-10 space-y-2.5">
                <div v-if="rankedItems.length <= 1" class="rounded-2xl border border-dashed border-slate-800/50 bg-slate-900/30 py-8 text-center text-slate-500">
                    <div class="mb-2 text-3xl opacity-50">👥</div>
                    <p class="text-sm font-medium">Waiting for more contenders.</p>
                </div>
                <LeaderboardRow v-for="row in rankedItems.slice(1, 51)" :key="row.user?.id + '-' + row.dynamicRank" :row="row" :rank="row.dynamicRank" :current-view="currentView" :is-me="isMe(row)" :metric-cfg="metricCfg(row)" :tier-class="rarityChipClass(metricTier(row))" :meter-info="meterInfo(row)" @open-lore="openLore" @close-lore="closeLore">
                    <template #climb-hint>
                        <div v-if="currentView === 'current' && row.dynamicRank > 1" class="mt-1 text-[10px] font-bold text-slate-600">▲ +{{ streakToBeat(row) }} streak to beat #{{ row.dynamicRank - 1 }}</div>
                    </template>
                    <template #climb-hint-desktop>
                        <div v-if="currentView === 'current' && row.dynamicRank > 1" class="mt-1 text-xs font-semibold text-slate-500">▲ +{{ streakToBeat(row) }} streak to beat #{{ row.dynamicRank - 1 }}</div>
                    </template>
                </LeaderboardRow>
            </section>
        </main>

        <!-- DESKTOP MAIN -->
        <div class="mx-auto hidden w-full max-w-7xl px-4 py-8 md:block md:px-8">
            <div v-if="rankedItems.length === 0" class="rounded-3xl border border-dashed border-slate-700 bg-slate-800/30 p-12 text-center">
                <div class="mb-3 text-6xl">🕸️</div>
                <div class="text-lg font-bold text-slate-300">No contenders yet.</div>
                <div class="mt-1 text-sm text-slate-500">Complete quests to enter the Hall.</div>
            </div>
            <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <section class="space-y-4 lg:col-span-8">
                    <div v-if="currentView === 'active7'" class="rounded-2xl border border-purple-500/15 bg-slate-900/35 px-4 py-2 text-center text-xs font-bold text-slate-300">⚡ {{ weekRangeLabel }}</div>
                    <LeaderboardChampion v-if="champion" :champion="champion" :current-view="currentView" :metric-cfg="metricCfg(champion)" :tier-class="rarityChipClass(metricTier(champion))" :meter-info="meterInfo(champion)" :metric-chip-text="metricChipText(champion)" :format-ago="formatAgo" @open-lore="openLore" @close-lore="closeLore" />
                    <div class="rounded-3xl border border-slate-700 bg-slate-800/50 p-3 shadow-xl">
                        <div class="flex items-center justify-between px-2 pb-2">
                            <div class="text-xs font-black uppercase tracking-widest text-slate-400">Full roster</div>
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-500">Sorted by: {{ viewOptions.find((v) => v.key === currentView)?.label }}</div>
                        </div>
                        <div class="space-y-2">
                            <LeaderboardRow v-for="row in rankedItems.slice(1, 51)" :key="'d-' + row.user?.id + '-' + row.dynamicRank" :row="row" :rank="row.dynamicRank" :current-view="currentView" :is-me="isMe(row)" :metric-cfg="metricCfg(row)" :tier-class="rarityChipClass(metricTier(row))" :meter-info="meterInfo(row)" @open-lore="openLore" @close-lore="closeLore">
                                <template #climb-hint>
                                    <div v-if="currentView === 'current' && row.dynamicRank > 1" class="mt-1 text-[10px] font-bold text-slate-600">▲ +{{ streakToBeat(row) }} streak to beat #{{ row.dynamicRank - 1 }}</div>
                                </template>
                                <template #climb-hint-desktop>
                                    <div v-if="currentView === 'current' && row.dynamicRank > 1" class="mt-1 text-xs font-semibold text-slate-500">▲ +{{ streakToBeat(row) }} streak to beat #{{ row.dynamicRank - 1 }}</div>
                                </template>
                            </LeaderboardRow>
                        </div>
                    </div>
                </section>

                <!-- Right rail -->
                <aside class="hidden lg:col-span-4 lg:block">
                    <div v-if="meRow" class="rounded-3xl border border-indigo-500/20 bg-slate-900/50 p-5 shadow-xl transition-transform duration-300 hover:-translate-y-0.5 hover:border-indigo-400/30 hover:bg-slate-900/60">
                        <div class="text-xs font-black uppercase tracking-widest text-slate-400">Your position</div>
                        <div class="mt-3 flex items-center justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-12 w-12 flex-col items-center justify-center rounded-xl border border-indigo-500/30 bg-slate-950/60 text-center">
                                    <div class="text-[9px] font-black uppercase text-indigo-300/70">Rank</div>
                                    <div class="text-lg font-black text-indigo-200">{{ meRow.dynamicRank }}</div>
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-black text-white">
                                        <Link v-if="profileHref(meRow)" :href="profileHref(meRow)" class="hover:text-indigo-200 hover:underline underline-offset-2">{{ meRow.user?.name || 'You' }}</Link>
                                        <span v-else>{{ meRow.user?.name || 'You' }}</span>
                                    </div>
                                    <div class="mt-1 inline-flex items-center gap-1 rounded border px-1.5 py-[1px] text-[9px] font-black uppercase tracking-wider" :class="statusCfg(meRow.status).cls">
                                        <span class="text-[10px]">{{ statusCfg(meRow.status).icon }}</span> {{ statusCfg(meRow.status).label }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500">{{ metricCfg(meRow).label }}</div>
                                <div class="mt-2 flex flex-col items-end gap-1">
                                    <div :class="rarityChipClass(metricTier(meRow))" class="text-lg"><span class="opacity-90">{{ metricIcon }}</span> <span>{{ metricChipText(meRow) }}</span></div>
                                    <div class="h-1 w-20 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full" :class="meterFillClass(metricTier(meRow))" :style="{ width: `${meterInfo(meRow).pct}%` }"></div></div>
                                    <div class="text-[9px] font-bold text-slate-500">{{ meterInfo(meRow).hint }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="meRow.dynamicRank === '-'" class="mt-3 text-xs text-slate-500">Outside top 50. Keep the chain alive to climb back in.</div>
                    </div>
                    <div class="mt-5 rounded-3xl border border-slate-700 bg-slate-800/50 p-5 shadow-xl transition-transform duration-300 hover:-translate-y-0.5 hover:border-slate-500/70 hover:bg-slate-800/70">
                        <div class="text-xs font-black uppercase tracking-widest text-slate-400">Quick links</div>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <Link href="/quests" class="rounded-2xl border border-slate-700 bg-slate-900/40 px-4 py-3 text-center text-xs font-black uppercase tracking-widest text-slate-200 transition-colors hover:bg-slate-900/70">📜 Quest Board</Link>
                            <Link href="/dashboard" class="rounded-2xl border border-indigo-500/30 bg-indigo-600/15 px-4 py-3 text-center text-xs font-black uppercase tracking-widest text-indigo-200 transition-colors hover:bg-indigo-600/25">🧭 Command Center</Link>
                        </div>
                        <div class="mt-4 text-xs text-slate-500">Tip: the fastest climb is one clean win today. Keep it simple.</div>
                    </div>
                </aside>
            </div>
        </div>

        <!-- MOBILE BOTTOM BAR (me) -->
        <div v-if="meRow" class="fixed bottom-0 left-0 z-40 w-full border-t border-indigo-500/20 bg-slate-900/85 p-3 shadow-[0_-5px_25px_rgba(0,0,0,0.3)] backdrop-blur-md md:hidden">
            <div class="absolute inset-x-0 top-0 h-[1px] bg-gradient-to-r from-transparent via-indigo-500/50 to-transparent"></div>
            <div class="mx-auto flex max-w-3xl items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="group relative flex h-11 w-11 shrink-0 flex-col items-center justify-center overflow-hidden rounded-xl border border-indigo-500/30 bg-gradient-to-br from-indigo-500/10 to-slate-950 shadow-sm">
                        <div class="absolute inset-0 bg-indigo-500/20 opacity-0 blur-md transition-opacity group-hover:opacity-100"></div>
                        <span class="relative z-10 text-[8px] font-black uppercase text-indigo-300/70">Rank</span>
                        <span class="relative z-10 text-lg font-black leading-none text-indigo-200 drop-shadow-sm filter">{{ meRow.dynamicRank }}</span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-sm font-black text-white">
                            <Link v-if="profileHref(meRow)" :href="profileHref(meRow)" class="truncate hover:text-indigo-200 hover:underline underline-offset-2">{{ meRow.user?.name || 'You' }}</Link>
                            <span v-else class="truncate">{{ meRow.user?.name || 'You' }}</span>
                            <span :class="statusCfg(meRow.status).cls" class="rounded border px-1.5 py-[1px] text-[8px] font-black uppercase tracking-wider">{{ statusCfg(meRow.status).label }}</span>
                        </div>
                        <div class="mt-0.5 flex items-center gap-1 text-[10px] font-medium text-slate-400">
                            <span v-if="meRow.dynamicRank === 1">👑 Defend your throne.</span>
                            <span v-else-if="currentView === 'current'">🔥 Keep the chain alive.</span>
                            <span v-else-if="currentView === 'best'">🏆 Beat your record.</span>
                            <span v-else-if="currentView === 'active7'">⚡ Add one active day.</span>
                            <span v-else>🕒 Stay visible.</span>
                        </div>
                        <div v-if="currentView === 'recent'" class="mt-0.5 text-[10px] font-bold text-slate-500">{{ formatAgo(meRow.last_active_at) }}</div>
                        <div v-if="currentView === 'current' && meRow.dynamicRank > 1" class="mt-0.5 text-[10px] font-bold text-slate-500">▲ +{{ streakToBeat(meRow) }} streak to beat #{{ meRow.dynamicRank - 1 }}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="mb-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-500">{{ metricCfg(meRow).label }}</div>
                    <div class="flex flex-col items-end gap-1">
                        <div :class="rarityChipClass(metricTier(meRow))" class="text-lg"><span class="opacity-90">{{ metricIcon }}</span> <span>{{ metricChipText(meRow) }}</span></div>
                        <div class="h-1 w-20 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full" :class="meterFillClass(metricTier(meRow))" :style="{ width: `${meterInfo(meRow).pct}%` }"></div></div>
                        <div class="text-[9px] font-bold text-slate-500">{{ meterInfo(meRow).hint }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jump to top -->
        <button v-if="showJumpTop" type="button" @click="scrollToTop" class="fixed bottom-[110px] right-4 z-50 flex items-center gap-2 rounded-full border border-indigo-400/25 bg-slate-950/80 px-3 py-2 text-xs font-black uppercase tracking-widest text-indigo-200 shadow-[0_0_20px_rgba(99,102,241,0.14)] transition-all hover:-translate-y-0.5 hover:border-indigo-300/35 hover:bg-slate-950/90 hover:shadow-[0_0_28px_rgba(99,102,241,0.18)] active:scale-[0.98] md:bottom-6">
            <span class="text-sm text-indigo-300">↑</span>
        </button>

        <!-- LORE TOOLTIP -->
        <Teleport to="body">
            <transition name="fade">
                <div v-if="lore.open" id="lore-tip" class="fixed z-[9999] -translate-x-1/2 rounded-xl border border-slate-700 bg-slate-950/95 p-3 text-xs text-slate-200 shadow-2xl backdrop-blur" :style="{ left: lore.x + 'px', top: lore.y + 'px', maxWidth: Math.min(360, (typeof window !== 'undefined' ? window.innerWidth : 360) - 16) + 'px' }">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Lore</div>
                    <div class="mt-1 leading-relaxed text-slate-200">{{ lore.desc }}</div>
                    <div class="mt-2 text-[10px] font-bold uppercase tracking-widest text-slate-600 md:hidden">tap outside to close</div>
                </div>
            </transition>
        </Teleport>
    </div>
</template>

<style scoped>
.shine { background: linear-gradient(45deg, transparent 35%, rgba(255,255,255,0.08) 45%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.08) 55%, transparent 65%); background-size: 250% 250%; animation: none !important; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translate(-50%, 6px); }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.mask-linear-x { -webkit-mask-image: linear-gradient(to right, transparent 0%, black 2%, black 98%, transparent 100%); mask-image: linear-gradient(to right, transparent 0%, black 2%, black 98%, transparent 100%); }
</style>
