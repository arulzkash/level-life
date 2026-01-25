<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch } from 'vue';
import debounce from 'lodash/debounce';

defineOptions({ layout: AppLayout });

const props = defineProps({
    month: String, // YYYY-MM
    todayKey: String, // YYYY-MM-DD (Jakarta)
    filledDays: Array, // ["YYYY-MM-DD", ...]
    entries: Array, // [{ id,date,title,mood_emoji,is_favorite,headline,rewarded_at }, ...]
    query: String, // current q
});

const pad2 = (n) => String(n).padStart(2, '0');

const parseMonth = (ym) => {
    const [y, m] = (ym || '').split('-').map(Number);
    return { y, m };
};

// UTC calendar math (biar ga geser timezone client)
const daysInMonth = (y, m) => new Date(Date.UTC(y, m, 0)).getUTCDate(); // m = 1..12
const firstDowMondayIndex = (y, m) => {
    // 0=Mon .. 6=Sun
    const d = new Date(Date.UTC(y, m - 1, 1));
    const dow = d.getUTCDay(); // 0=Sun..6=Sat
    return (dow + 6) % 7;
};

const monthLabel = computed(() => {
    const { y, m } = parseMonth(props.month);
    const d = new Date(Date.UTC(y, m - 1, 1));
    return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const cells = computed(() => {
    const { y, m } = parseMonth(props.month);
    const total = daysInMonth(y, m);
    const lead = firstDowMondayIndex(y, m);

    const out = [];
    for (let i = 0; i < lead; i++) out.push({ kind: 'empty', key: `e-${i}` });

    for (let day = 1; day <= total; day++) {
        const dateKey = `${y}-${pad2(m)}-${pad2(day)}`;
        out.push({ kind: 'day', key: dateKey, dateKey, day });
    }
    return out;
});

const prevMonth = computed(() => {
    const { y, m } = parseMonth(props.month);
    const d = new Date(Date.UTC(y, m - 1, 1));
    d.setUTCMonth(d.getUTCMonth() - 1);
    return `${d.getUTCFullYear()}-${pad2(d.getUTCMonth() + 1)}`;
});

const nextMonth = computed(() => {
    const { y, m } = parseMonth(props.month);
    const d = new Date(Date.UTC(y, m - 1, 1));
    d.setUTCMonth(d.getUTCMonth() + 1);
    return `${d.getUTCFullYear()}-${pad2(d.getUTCMonth() + 1)}`;
});

const openDay = (dateKey) => {
    router.get('/journal', { date: dateKey }, { preserveScroll: false, preserveState: false });
};

const goToday = () => {
    const [y, m] = props.todayKey.split('-');
    goMonth(`${y}-${m}`);
};

// --- search (server-side) ---
const searchQuery = ref(props.query ?? '');
watch(
    () => props.query,
    (v) => {
        const next = v ?? '';
        if (next !== searchQuery.value) searchQuery.value = next;
    }
);

const goMonth = (ym) => {
    router.get(
        '/journal/archive',
        { month: ym, q: searchQuery.value || undefined },
        { preserveScroll: true, preserveState: true }
    );
};

const runSearch = debounce(() => {
    router.get(
        '/journal/archive',
        { month: props.month, q: searchQuery.value || undefined },
        { preserveScroll: true, preserveState: true }
    );
}, 250);

watch(searchQuery, () => runSearch());

// --- quick filters (client-side) ---
const filter = ref('all'); // all | fav | rewarded

const filteredEntries = computed(() => {
    const list = props.entries || [];
    if (filter.value === 'fav') return list.filter((e) => !!e.is_favorite);
    if (filter.value === 'rewarded') return list.filter((e) => !!e.rewarded_at);
    return list;
});

// --- derived maps / sets (respect filter for calendar) ---
const calendarEntries = computed(() => filteredEntries.value);

const calendarFilledSet = computed(() => {
    if (filter.value === 'all' && !searchQuery.value) {
        return new Set(props.filledDays || []);
    }
    return new Set(calendarEntries.value.map((e) => e.date));
});

const entriesByDate = computed(() => {
    const map = new Map();
    for (const e of calendarEntries.value) map.set(e.date, e);
    return map;
});

const stats = computed(() => {
    const list = props.entries || [];
    const fav = list.reduce((n, e) => n + (e.is_favorite ? 1 : 0), 0);
    const rewarded = list.reduce((n, e) => n + (e.rewarded_at ? 1 : 0), 0);
    return {
        filled: (props.filledDays || []).length,
        entries: list.length,
        fav,
        rewarded,
    };
});

const clearSearch = () => {
    if (!searchQuery.value) return;
    searchQuery.value = '';
};
</script>

<template>
    <Head title="Chronicles" />

    <div class="min-h-screen bg-slate-900 pb-20 text-slate-200">
        
        <div class="sticky top-0 z-40 border-b border-slate-800 bg-slate-900/80 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 md:py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-900 text-xl shadow-lg shadow-slate-950/40 md:h-11 md:w-11 md:text-2xl">
                        🗓️
                    </div>
                    <div>
                        <div class="text-lg font-black tracking-tight text-white md:text-2xl">Chronicles</div>
                        <div class="hidden text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500 md:block">
                            Browse past entries from your journey.
                        </div>
                    </div>
                </div>

                <Link
                    href="/journal"
                    class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-sky-500/15 hover:bg-sky-500"
                >
                    Open Log
                </Link>
            </div>
        </div>

        <div class="mx-auto mt-6 grid max-w-6xl grid-cols-1 gap-6 px-4 md:grid-cols-12">
            
            <div class="md:col-span-5">
                <div class="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-800/80 to-slate-900/70 p-5 shadow-xl shadow-slate-950/40 ring-1 ring-sky-500/10">
                    
                    <div class="mb-5 flex items-center justify-between">
                        <button
                            @click="goMonth(prevMonth)"
                            class="rounded-xl border border-slate-700 bg-slate-900/40 px-3 py-2 text-sm font-bold text-slate-300 hover:bg-slate-800 hover:text-white"
                        >
                            ◀
                        </button>

                        <div class="text-center">
                            <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">
                                Month
                            </div>
                            <div class="text-lg font-black text-sky-400 uppercase tracking-widest md:text-xl">
                                {{ monthLabel }}
                            </div>
                        </div>

                        <button
                            @click="goMonth(nextMonth)"
                            class="rounded-xl border border-slate-700 bg-slate-900/40 px-3 py-2 text-sm font-bold text-slate-300 hover:bg-slate-800 hover:text-white"
                        >
                            ▶
                        </button>
                    </div>

                    <div class="mb-5 grid grid-cols-4 gap-2">
                        <div class="rounded-xl border border-slate-800 bg-slate-950/20 p-2 text-center md:p-3 md:text-left">
                            <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 md:text-[10px]">Filled</div>
                            <div class="mt-1 text-base font-black text-white md:text-lg">{{ stats.filled }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-800 bg-slate-950/20 p-2 text-center md:p-3 md:text-left">
                            <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 md:text-[10px]">Entries</div>
                            <div class="mt-1 text-base font-black text-white md:text-lg">{{ stats.entries }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-800 bg-slate-950/20 p-2 text-center md:p-3 md:text-left">
                            <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 md:text-[10px]">Fav</div>
                            <div class="mt-1 text-base font-black text-white md:text-lg">{{ stats.fav }}</div>
                        </div>
                        <button
                            @click="goToday"
                            class="rounded-xl border border-sky-500/30 bg-sky-500/10 p-2 text-center hover:bg-sky-500/15 md:p-3 md:text-left"
                            title="Jump to current month"
                        >
                            <div class="text-[9px] font-bold uppercase tracking-wider text-sky-300 md:text-[10px]">Today</div>
                            <div class="mt-1 font-mono text-[10px] text-sky-200 md:text-[11px]">{{ todayKey }}</div>
                        </button>
                    </div>

                    <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500 md:gap-2 md:text-[11px]">
                        <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
                    </div>

                    <div class="mt-3 grid grid-cols-7 gap-1 md:gap-2">
                        <div v-for="c in cells" :key="c.key" class="aspect-square">
                            <div v-if="c.kind === 'empty'" class="h-full w-full rounded-xl border border-transparent"></div>

                            <button
                                v-else
                                @click="openDay(c.dateKey)"
                                class="relative h-full w-full overflow-hidden rounded-lg border transition-all md:rounded-xl"
                                :class="[
                                    c.dateKey === todayKey
                                        ? (calendarFilledSet.has(c.dateKey)
                                            ? 'border-sky-400/70 bg-sky-500/20 ring-2 ring-sky-400/40 shadow-[0_0_16px_rgba(56,189,248,0.25)]'
                                            : 'border-sky-400/40 bg-slate-900/40 ring-1 ring-sky-400/20')
                                        : calendarFilledSet.has(c.dateKey)
                                            ? 'border-sky-500/30 bg-sky-500/10 hover:bg-sky-500/15'
                                            : 'border-slate-800 bg-slate-950/20 hover:bg-slate-900/40',
                                ]"
                            >
                                <div class="absolute left-1 top-1 text-[10px] font-black text-slate-200/90 md:left-2 md:top-2 md:text-[11px]">
                                    {{ c.day }}
                                </div>

                                <div v-if="entriesByDate.get(c.dateKey)?.is_favorite" class="absolute right-1 top-1 text-[9px] text-amber-300 md:right-2 md:top-2 md:text-[11px]">
                                    ⭐
                                </div>

                                <div class="flex h-full w-full items-center justify-center">
                                    <div v-if="entriesByDate.get(c.dateKey)?.mood_emoji" class="text-lg md:text-2xl">
                                        <span v-twemoji="entriesByDate.get(c.dateKey).mood_emoji"></span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <button @click="filter = 'all'" class="chip" :class="filter === 'all' ? 'chip-on' : 'chip-off'">All</button>
                        <button @click="filter = 'fav'" class="chip" :class="filter === 'fav' ? 'chip-on' : 'chip-off'">⭐ Fav</button>
                        <button @click="filter = 'rewarded'" class="chip" :class="filter === 'rewarded' ? 'chip-on' : 'chip-off'">✅ Rewarded</button>
                    </div>
                </div>
            </div>

            <div class="md:col-span-7">
                <div class="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-800/70 to-slate-900/60 p-4 shadow-xl shadow-slate-950/40 ring-1 ring-sky-500/10 md:p-5">
                    
                    <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <div class="text-sm font-black uppercase tracking-widest text-slate-400">Entries List</div>
                            <div class="text-[11px] text-slate-500">
                                Month: <span class="font-mono text-slate-300">{{ month }}</span>
                            </div>
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Showing: <span class="font-bold text-white">{{ filteredEntries.length }}</span>
                        </div>
                    </div>

                    <div class="mb-5 relative">
                        <input
                            v-model="searchQuery"
                            class="w-full rounded-xl border border-slate-700 bg-slate-950/40 px-4 py-3 text-sm text-slate-200 placeholder-slate-500 outline-none transition-all focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                            placeholder="Search title, body, or sections..."
                        />
                        <button
                            v-if="searchQuery"
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg border border-slate-700 bg-slate-900 px-2 py-1 text-[10px] font-bold text-slate-300 hover:bg-slate-800"
                        >
                            CLEAR
                        </button>
                    </div>

                    <div v-if="filteredEntries.length === 0" class="rounded-xl border border-slate-800 bg-slate-950/20 p-6 text-center">
                        <div class="text-lg font-black text-white">No entries found</div>
                        <div class="mt-1 text-sm text-slate-500">Try adjusting your search or filters.</div>
                    </div>

                    <div v-else class="space-y-3">
                        <button
                            v-for="e in filteredEntries"
                            :key="e.id"
                            @click="openDay(e.date)"
                            class="group w-full overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/15 text-left transition-all hover:-translate-y-0.5 hover:border-slate-700 hover:bg-slate-900/30 hover:shadow-lg hover:shadow-sky-500/10"
                        >
                            <div class="flex gap-3 p-4">
                                <div class="mt-1 h-auto w-1 shrink-0 rounded-full bg-gradient-to-b from-sky-400 via-blue-600 to-sky-500 opacity-80 md:w-1.5"></div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div v-if="e.mood_emoji" class="text-lg md:text-xl"><span v-twemoji="e.mood_emoji"></span></div>
                                        <div class="min-w-0 truncate text-sm font-black text-white md:text-base">
                                            {{ e.title }}
                                        </div>
                                        
                                        <span v-if="e.is_favorite" class="rounded-lg bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-bold text-amber-200 md:text-[10px]">fav</span>
                                        <span v-if="e.date === todayKey" class="rounded-lg bg-sky-500/15 px-1.5 py-0.5 text-[9px] font-bold text-sky-200 md:text-[10px]">Today</span>
                                        <span v-if="e.rewarded_at" class="rounded-lg bg-emerald-500/12 px-1.5 py-0.5 text-[9px] font-bold text-emerald-200 md:text-[10px]">rewarded</span>
                                    </div>

                                    <div class="mt-1 font-mono text-[10px] text-slate-500 md:text-[11px]">
                                        {{ e.date }}
                                    </div>

                                    <div class="mt-2 line-clamp-2 text-xs text-slate-300/90 md:text-sm">
                                        {{ e.headline || '…' }}
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.chip {
    @apply rounded-xl border px-3 py-1.5 text-xs font-bold transition-all;
}
.chip-off {
    @apply border-slate-700 bg-slate-900/40 text-slate-300 hover:bg-slate-800 hover:text-white;
}
.chip-on {
    @apply border-sky-500/40 bg-sky-500/10 text-sky-200 shadow-[0_0_16px_rgba(14,165,233,0.12)];
}

/* clamp helper */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
