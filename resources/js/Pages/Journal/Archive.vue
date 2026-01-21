<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    month: String, // YYYY-MM
    todayKey: String, // YYYY-MM-DD (Jakarta)
    filledDays: Array, // ["YYYY-MM-DD", ...]
    recent: Array, // optional
});

const filledSet = computed(() => new Set(props.filledDays || []));

const pad2 = (n) => String(n).padStart(2, '0');

const parseMonth = (ym) => {
    const [y, m] = (ym || '').split('-').map(Number);
    return { y, m };
};

// pakai UTC biar ga geser timezone client
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
    return d.toLocaleDateString([], { month: 'long', year: 'numeric' });
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

const goMonth = (ym) => {
    router.get('/journal/archive', { month: ym }, { preserveScroll: true, preserveState: true });
};

const goToday = () => {
    const [y, m] = props.todayKey.split('-');
    goMonth(`${y}-${m}`);
};

const openDay = (dateKey) => {
    router.get('/journal', { date: dateKey }, { preserveScroll: true, preserveState: false });
};
</script>

<template>
    <Head title="Journal Archive" />

    <div class="mx-auto max-w-5xl space-y-6 p-4 text-gray-200 md:p-8">
        <!-- Header -->
        <div class="flex items-center gap-3 border-b border-slate-700 pb-4">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-700 text-2xl shadow-lg shadow-slate-500/10"
            >
                🗓️
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-black tracking-tight text-white">Journal Archive</h1>
                <p class="text-sm text-slate-400">See which days you wrote. Tap a day to open editor.</p>
            </div>
            <Link
                href="/journal"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-lg hover:bg-indigo-500"
            >
                Open Journal
            </Link>
        </div>

        <!-- Month controls -->
        <div class="flex items-center justify-between rounded-xl border border-slate-700 bg-slate-800 p-4">
            <div class="flex items-center gap-2">
                <button
                    @click="goMonth(prevMonth)"
                    class="rounded bg-slate-700 px-3 py-2 text-sm font-bold hover:bg-slate-600"
                >
                    ←
                </button>
                <div class="text-lg font-black text-white">{{ monthLabel }}</div>
                <button
                    @click="goMonth(nextMonth)"
                    class="rounded bg-slate-700 px-3 py-2 text-sm font-bold hover:bg-slate-600"
                >
                    →
                </button>
            </div>

            <div class="flex items-center gap-2">
                <div class="text-xs text-slate-400">
                    Filled:
                    <span class="font-bold text-slate-200">{{ filledDays?.length || 0 }}</span>
                </div>
                <button
                    @click="goToday"
                    class="rounded bg-slate-700 px-3 py-2 text-sm font-bold hover:bg-slate-600"
                >
                    Today
                </button>
            </div>
        </div>

        <!-- Calendar -->
        <div class="rounded-xl border border-slate-700 bg-slate-800 p-4">
            <div class="grid grid-cols-7 gap-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                <div class="text-center">Mon</div>
                <div class="text-center">Tue</div>
                <div class="text-center">Wed</div>
                <div class="text-center">Thu</div>
                <div class="text-center">Fri</div>
                <div class="text-center">Sat</div>
                <div class="text-center">Sun</div>
            </div>

            <div class="mt-3 grid grid-cols-7 gap-2">
                <div v-for="c in cells" :key="c.key" class="min-h-[44px]">
                    <div v-if="c.kind === 'empty'" class="h-full rounded-lg border border-transparent"></div>

                    <button
                        v-else
                        @click="openDay(c.dateKey)"
                        class="relative h-full w-full rounded-lg border px-2 py-2 text-left transition-all"
                        :class="[
                            c.dateKey === todayKey
                                ? 'border-indigo-400/60 bg-indigo-500/10'
                                : 'border-slate-700 bg-slate-900/30 hover:bg-slate-900/50',
                        ]"
                    >
                        <div class="flex items-start justify-between">
                            <div class="text-sm font-black text-slate-200">{{ c.day }}</div>

                            <!-- dot filled -->
                            <div
                                v-if="filledSet.has(c.dateKey)"
                                class="mt-1 h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.25)]"
                                title="Entry exists"
                            ></div>
                        </div>

                        <div v-if="c.dateKey === todayKey" class="mt-1 text-[10px] font-bold text-indigo-200">
                            Today
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Optional recent -->
        <div v-if="(recent?.length || 0) > 0" class="rounded-xl border border-slate-700 bg-slate-800 p-4">
            <div class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Recent entries</div>
            <div class="space-y-2">
                <button
                    v-for="r in recent"
                    :key="r.id"
                    @click="openDay(r.date)"
                    class="flex w-full items-center justify-between rounded-lg border border-slate-700 bg-slate-900/30 px-3 py-2 text-left hover:bg-slate-900/50"
                >
                    <div class="font-mono text-sm text-slate-200">{{ r.date }}</div>
                    <div class="text-xs text-slate-500">
                        <span v-if="r.rewarded_at" class="text-emerald-300">rewarded</span>
                        <span v-else class="text-slate-500">—</span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
