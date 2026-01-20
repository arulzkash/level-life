<script setup>
import { Link, useForm, Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    logs: Object,
    filters: Object,
});

// --- HELPER: QUEST STYLES ---
const getQuestVisuals = (type) => {
    const map = {
        'Boss Fight': {
            color: 'text-red-400',
            border: 'border-red-500/50',
            bg: 'bg-red-500/10',
            icon: '👹',
            shadow: 'shadow-[0_0_15px_rgba(239,68,68,0.15)]',
            dot: 'bg-red-500',
        },
        'Main Quest': {
            color: 'text-yellow-400',
            border: 'border-yellow-500/50',
            bg: 'bg-yellow-500/10',
            icon: '👑',
            shadow: 'shadow-[0_0_15px_rgba(234,179,8,0.15)]',
            dot: 'bg-yellow-500',
        },
        'Side Quest': {
            color: 'text-blue-400',
            border: 'border-blue-500/50',
            bg: 'bg-blue-500/10',
            icon: '🔍',
            shadow: 'shadow-none',
            dot: 'bg-blue-500',
        },
        'Daily Grind': {
            color: 'text-emerald-400',
            border: 'border-emerald-500/50',
            bg: 'bg-emerald-500/10',
            icon: '♻️',
            shadow: 'shadow-none',
            dot: 'bg-emerald-500',
        },
        Learning: {
            color: 'text-purple-400',
            border: 'border-purple-500/50',
            bg: 'bg-purple-500/10',
            icon: '🧠',
            shadow: 'shadow-[0_0_10px_rgba(168,85,247,0.1)]',
            dot: 'bg-purple-500',
        },
    };

    return (
        map[type] || {
            color: 'text-slate-400',
            border: 'border-slate-600',
            bg: 'bg-slate-800',
            icon: '📜',
            shadow: 'shadow-none',
            dot: 'bg-slate-500',
        }
    );
};

// --- EDIT NOTE LOGIC ---
const editingId = ref(null);
const editForm = useForm({ note: '' });

const startEdit = (log) => {
    editingId.value = log.id;
    editForm.note = log.note ?? '';
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
};

const saveEdit = () => {
    editForm.patch(`/logs/completions/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

// --- SMART FILTER LOGIC ---
const filterMode = ref('preset');
if (props.filters.date) filterMode.value = 'date';
else if (props.filters.from || props.filters.to) filterMode.value = 'range';
else filterMode.value = 'preset';

const filterForm = useForm({
    period: props.filters?.period ?? 'all',
    date: props.filters?.date ?? '',
    from: props.filters?.from ?? '',
    to: props.filters?.to ?? '',
    sort: props.filters?.sort ?? 'completed_at',
    dir: props.filters?.dir ?? 'desc',
});

watch(filterMode, (newMode) => {
    if (newMode === 'preset') {
        filterForm.date = '';
        filterForm.from = '';
        filterForm.to = '';
        filterForm.period = 'all';
    } else if (newMode === 'date') {
        filterForm.period = 'custom';
        filterForm.from = '';
        filterForm.to = '';
    } else if (newMode === 'range') {
        filterForm.period = 'custom';
        filterForm.date = '';
    }
});

const apply = () => {
    filterForm.get('/logs/completions', { preserveScroll: true, preserveState: true });
};

const setPreset = (p) => {
    filterForm.period = p;
    apply();
};

// --- DATA GROUPING ---
const groupedLogs = computed(() => {
    if (!props.logs.data) return {};
    const groups = {};
    props.logs.data.forEach((log) => {
        const dateKey = new Date(log.completed_at).toDateString();
        if (!groups[dateKey]) groups[dateKey] = [];
        groups[dateKey].push(log);
    });
    return groups;
});

const formatTime = (iso) => new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

// --- UPDATE LOGIC DATE ---
const formatGroupDate = (dateStr) => {
    const d = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    // Cek apakah tanggalnya sama persis dengan hari ini
    if (d.toDateString() === today.toDateString()) {
        return "Today's Conquests";
    }

    // Cek apakah tanggalnya sama persis dengan kemarin
    if (d.toDateString() === yesterday.toDateString()) {
        return "Yesterday's Feats";
    }

    // Sisanya format lengkap
    return d.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
};

const groupTotals = (groupLogs) => {
    let count = 0;
    let xp = 0;
    let gold = 0;

    for (const log of groupLogs || []) {
        count += 1;
        xp += Number(log.xp_awarded || 0);
        gold += Number(log.coin_awarded || 0);
    }

    return { count, xp, gold };
};

const isSameDay = (a, b) => a.toDateString() === b.toDateString();

const dateTone = (dateKey) => {
    const d = new Date(dateKey);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    if (isSameDay(d, today)) {
        return 'border-indigo-400/45 text-indigo-200 shadow-[0_0_16px_rgba(99,102,241,0.12)]';
    }

    if (isSameDay(d, yesterday)) {
        return 'border-slate-600 text-slate-200 shadow-none ';
    }

    return 'border-slate-700 text-slate-300 shadow-none';
};
</script>

<template>
    <Head title="Quest Chronicles" />

    <div class="mx-auto max-w-5xl space-y-8 p-4 text-gray-200 md:p-8">
        <div class="flex items-center gap-3 border-b border-slate-700 pb-4">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-600 text-2xl shadow-lg shadow-indigo-500/20"
            >
                📜
            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tight text-white">Adventure Chronicles</h1>
                <p class="text-sm text-slate-400">A complete record of your heroic deeds.</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-700 bg-slate-800 p-1 shadow-lg">
            <div class="mb-4 grid grid-cols-3 gap-1 rounded-lg bg-slate-900/50 p-1">
                <button
                    @click="filterMode = 'preset'"
                    class="rounded-md py-2 text-xs font-bold uppercase tracking-wider transition-all"
                    :class="
                        filterMode === 'preset'
                            ? 'bg-slate-700 text-white shadow'
                            : 'text-slate-500 hover:text-slate-300'
                    "
                >
                    Presets
                </button>
                <button
                    @click="filterMode = 'date'"
                    class="rounded-md py-2 text-xs font-bold uppercase tracking-wider transition-all"
                    :class="
                        filterMode === 'date'
                            ? 'bg-slate-700 text-white shadow'
                            : 'text-slate-500 hover:text-slate-300'
                    "
                >
                    Single Date
                </button>
                <button
                    @click="filterMode = 'range'"
                    class="rounded-md py-2 text-xs font-bold uppercase tracking-wider transition-all"
                    :class="
                        filterMode === 'range'
                            ? 'bg-slate-700 text-white shadow'
                            : 'text-slate-500 hover:text-slate-300'
                    "
                >
                    Date Range
                </button>
            </div>

            <div class="px-4 pb-4">
                <div v-if="filterMode === 'preset'" class="flex flex-wrap gap-2">
                    <button
                        v-for="p in ['all', 'today', '7d', 'month']"
                        :key="p"
                        @click="setPreset(p)"
                        class="rounded-lg border px-4 py-2 text-sm font-medium transition-all"
                        :class="
                            filterForm.period === p
                                ? 'border-indigo-500 bg-indigo-600 text-white shadow-md'
                                : 'border-slate-600 bg-slate-700 text-slate-400 hover:bg-slate-600'
                        "
                    >
                        {{ p === '7d' ? 'Last 7 Days' : p.toUpperCase() }}
                    </button>
                </div>
                <div v-if="filterMode === 'date'" class="animate-fade-in flex items-end gap-2">
                    <div class="flex-1">
                        <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">
                            Pick Date
                        </label>
                        <input type="date" v-model="filterForm.date" class="input-dark w-full" />
                    </div>
                    <button
                        @click="apply"
                        class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg hover:bg-indigo-500"
                    >
                        Go
                    </button>
                </div>
                <div v-if="filterMode === 'range'" class="animate-fade-in flex items-end gap-3">
                    <div class="flex-1">
                        <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">From</label>
                        <input type="date" v-model="filterForm.from" class="input-dark w-full" />
                    </div>
                    <div class="flex-1">
                        <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">To</label>
                        <input type="date" v-model="filterForm.to" class="input-dark w-full" />
                    </div>
                    <button
                        @click="apply"
                        class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg hover:bg-indigo-500"
                    >
                        Filter
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="logs.data.length === 0"
            class="rounded-2xl border-2 border-dashed border-slate-700 py-20 text-center opacity-50"
        >
            <div class="mb-4 text-6xl">📜</div>
            <div class="text-lg font-medium text-slate-300">The pages are blank...</div>
            <p class="mt-2 text-sm text-slate-500">Go forth and complete some quests!</p>
        </div>

        <div v-else class="space-y-12">
            <div v-for="(groupLogs, dateKey) in groupedLogs" :key="dateKey" class="relative">
                <div class="sticky top-[70px] z-20 mb-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <!-- DATE -->
                        <div class="flex justify-center sm:justify-start">
                            <span
                                class="rounded-full border bg-slate-900 px-6 py-2 text-xs font-bold uppercase tracking-widest shadow-xl ring-4 ring-slate-900"
                                :class="dateTone(dateKey)"
                            >
                                {{ formatGroupDate(dateKey) }}
                            </span>
                        </div>

                        <!-- STATS -->
                        <div class="flex justify-center sm:justify-end">
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-slate-700 bg-slate-900 px-3 py-2 text-[11px] font-bold text-slate-300"
                            >
                                <span class="font-mono">🗡️ {{ groupTotals(groupLogs).count }}</span>
                                <span class="hidden text-slate-700 sm:inline">•</span>

                                <span class="font-mono text-indigo-300">
                                    ✨ +{{ groupTotals(groupLogs).xp }}
                                </span>
                                <span class="hidden text-slate-700 sm:inline">•</span>

                                <span class="font-mono text-yellow-300">
                                    💰 +{{ groupTotals(groupLogs).gold }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="absolute bottom-0 left-6 top-12 w-0.5 bg-slate-800 md:left-8"></div>

                <div class="space-y-6">
                    <div v-for="log in groupLogs" :key="log.id" class="group relative pl-16 md:pl-20">
                        <div
                            class="absolute left-[18px] top-6 z-10 h-4 w-4 rounded-full border-4 border-slate-900 transition-colors duration-300 md:left-[26px]"
                            :class="getQuestVisuals(log.quest?.type).dot"
                        ></div>

                        <div
                            class="relative overflow-hidden rounded-xl border p-4 transition-all duration-300 hover:-translate-y-1"
                            :class="[
                                getQuestVisuals(log.quest?.type).border,
                                getQuestVisuals(log.quest?.type).bg,
                                getQuestVisuals(log.quest?.type).shadow,
                                'bg-slate-800/60 hover:bg-slate-800', // Hover normalize
                            ]"
                        >
                            <div
                                class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center"
                            >
                                <div class="flex items-start gap-4">
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-white/5 bg-slate-900/50 text-2xl"
                                    >
                                        {{ getQuestVisuals(log.quest?.type).icon }}
                                    </div>
                                    <div>
                                        <div class="mb-1 flex items-center gap-2">
                                            <span class="font-mono text-xs text-slate-500">
                                                {{ formatTime(log.completed_at) }}
                                            </span>
                                            <span
                                                class="text-[10px] font-black uppercase tracking-wider opacity-80"
                                                :class="getQuestVisuals(log.quest?.type).color"
                                            >
                                                {{ log.quest?.type }}
                                            </span>
                                        </div>
                                        <h3 class="text-lg font-bold leading-tight text-white">
                                            {{ log.quest?.name ?? 'Unknown Quest' }}
                                        </h3>
                                    </div>
                                </div>

                                <div
                                    class="mt-2 flex shrink-0 items-center gap-0 overflow-hidden rounded-lg border border-slate-700 bg-slate-900 shadow-sm md:mt-0"
                                >
                                    <div
                                        class="flex items-center gap-1.5 border-r border-slate-700 px-3 py-1.5"
                                    >
                                        <span class="text-xs font-black text-indigo-400">
                                            +{{ log.xp_awarded }}
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-500">XP</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 px-3 py-1.5">
                                        <span class="text-xs font-black text-yellow-400">
                                            +{{ log.coin_awarded }}
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-500">G</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 border-t border-white/5 pt-3">
                                <template v-if="editingId === log.id">
                                    <textarea
                                        v-model="editForm.note"
                                        rows="3"
                                        class="input-dark w-full resize-none border-slate-600 bg-slate-900 text-sm focus:border-indigo-500"
                                        placeholder="Journal entry..."
                                        autofocus
                                    ></textarea>
                                    <div class="mt-2 flex justify-end gap-2">
                                        <button
                                            @click="cancelEdit"
                                            class="px-3 py-1 text-xs text-slate-400 hover:text-white"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            @click="saveEdit"
                                            :disabled="editForm.processing"
                                            class="rounded bg-indigo-600 px-3 py-1 text-xs text-white shadow hover:bg-indigo-500"
                                        >
                                            Update Entry
                                        </button>
                                    </div>
                                </template>

                                <template v-else>
                                    <div v-if="log.note" class="flex gap-3">
                                        <div class="my-1 w-0.5 rounded-full bg-slate-600"></div>
                                        <div
                                            class="flex-1 whitespace-pre-wrap text-sm italic leading-relaxed text-slate-300"
                                        >
                                            {{ log.note }}
                                        </div>
                                    </div>
                                    <div v-else class="pl-2 text-xs italic text-slate-600">
                                        No journal entry recorded.
                                    </div>

                                    <div class="mt-2 pl-2">
                                        <button
                                            @click="startEdit(log)"
                                            class="translate-y-2 transform text-[10px] font-bold uppercase tracking-wider text-slate-500 opacity-0 transition-all hover:text-white group-hover:translate-y-0 group-hover:opacity-100"
                                        >
                                            {{ log.note ? 'Edit Journal ✏️' : '+ Write Journal' }}
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="logs.links.length > 3" class="flex justify-center pt-8">
            <div class="flex gap-1 rounded-lg border border-slate-700 bg-slate-800 p-1">
                <Link
                    v-for="(link, k) in logs.links"
                    :key="k"
                    :href="link.url || ''"
                    v-html="link.label"
                    class="rounded-md px-3 py-2 text-xs font-medium transition-colors"
                    :class="{
                        'bg-indigo-600 text-white shadow': link.active,
                        'text-slate-400 hover:bg-slate-700 hover:text-white': !link.active && link.url,
                        'cursor-not-allowed text-slate-600 opacity-50': !link.url,
                    }"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.input-dark {
    @apply rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 outline-none transition-all focus:ring-1 focus:ring-indigo-500;
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>
