<script setup>
import { Link, useForm, Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    logs: Object,
    filters: Object,
    rewardOptions: Array,
});

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
    editForm.patch(`/logs/treasury/${editingId.value}`, {
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
    reward_id: props.filters?.reward_id ?? '',
    sort: props.filters?.sort ?? 'purchased_at',
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
    filterForm.get('/logs/treasury', { preserveScroll: true, preserveState: true });
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
        const dateKey = new Date(log.purchased_at).toDateString();
        if (!groups[dateKey]) groups[dateKey] = [];
        groups[dateKey].push(log);
    });
    return groups;
});

const formatTime = (iso) => new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

const formatGroupDate = (dateStr) => {
    const d = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    if (d.toDateString() === today.toDateString()) return "Today's Expenses";
    if (d.toDateString() === yesterday.toDateString()) return "Yesterday's Spending";
    return d.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
};

const groupTotals = (groupLogs) => {
    let count = 0;
    let qty = 0;
    let spent = 0;

    for (const log of groupLogs || []) {
        count += 1;
        qty += Number(log.qty || 0);
        spent += Number(log.cost_coin || 0);
    }

    return { count, qty, spent };
};

const isSameDay = (a, b) => a.toDateString() === b.toDateString();

const dateTone = (dateKey) => {
    const d = new Date(dateKey);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    if (isSameDay(d, today)) {
        return 'border-yellow-400/55 text-yellow-200 shadow-[0_0_16px_rgba(234,179,8,0.12)]';
    }

    if (isSameDay(d, yesterday)) {
        return 'border-slate-600 text-slate-200 shadow-none';
    }

    return 'border-slate-700 text-slate-300 shadow-none';
};
</script>

<template>
    <Head title="Spending Log" />

    <div class="mx-auto max-w-5xl space-y-8 p-4 text-gray-200 md:p-8">
        <div class="flex items-center gap-3 border-b border-slate-700 pb-4">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-600 text-2xl shadow-lg shadow-yellow-500/20"
            >
                🧾
            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tight text-white">Merchant's Ledger</h1>
                <p class="text-sm text-slate-400">Track where your hard-earned gold goes.</p>
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

            <div class="space-y-4 px-4 pb-4">
                <div>
                    <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">
                        Filter by Item
                    </label>
                    <select v-model="filterForm.reward_id" @change="apply" class="input-dark w-full">
                        <option value="">Show All Transactions</option>
                        <option v-for="r in rewardOptions" :key="r.id" :value="String(r.id)">
                            {{ r.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <div v-if="filterMode === 'preset'" class="flex flex-wrap gap-2">
                        <button
                            v-for="p in ['all', 'today', '7d', 'month']"
                            :key="p"
                            @click="setPreset(p)"
                            class="rounded-lg border px-4 py-2 text-sm font-medium transition-all"
                            :class="
                                filterForm.period === p
                                    ? 'border-yellow-500 bg-yellow-600 text-white shadow-md'
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
                            class="rounded-lg bg-yellow-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg hover:bg-yellow-500"
                        >
                            Go
                        </button>
                    </div>

                    <div v-if="filterMode === 'range'" class="animate-fade-in flex items-end gap-3">
                        <div class="flex-1">
                            <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">
                                From
                            </label>
                            <input type="date" v-model="filterForm.from" class="input-dark w-full" />
                        </div>
                        <div class="flex-1">
                            <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">
                                To
                            </label>
                            <input type="date" v-model="filterForm.to" class="input-dark w-full" />
                        </div>
                        <button
                            @click="apply"
                            class="rounded-lg bg-yellow-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg hover:bg-yellow-500"
                        >
                            Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="logs.data.length === 0"
            class="rounded-2xl border-2 border-dashed border-slate-700 py-20 text-center opacity-50"
        >
            <div class="mb-4 text-6xl">💰</div>
            <div class="text-lg font-medium text-slate-300">No transactions recorded.</div>
            <p class="mt-2 text-sm text-slate-500">Your gold is safe... for now.</p>
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
                                <span class="font-mono">🎁 {{ groupTotals(groupLogs).count }}</span>
                                <span class="hidden text-slate-700 sm:inline">•</span>

                                <span class="font-mono text-slate-200">
                                    🧾 x{{ groupTotals(groupLogs).qty }}
                                </span>
                                <span class="hidden text-slate-700 sm:inline">•</span>

                                <span class="font-mono text-yellow-300">
                                    💰 -{{ groupTotals(groupLogs).spent }} G
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="absolute bottom-0 left-6 top-12 w-0.5 bg-slate-800 md:left-8"></div>

                <div class="space-y-4">
                    <div v-for="log in groupLogs" :key="log.id" class="group relative pl-16 md:pl-20">
                        <div
                            class="absolute left-[18px] top-6 z-10 h-4 w-4 rounded-full border-4 border-slate-900 bg-yellow-500 shadow-[0_0_10px_rgba(234,179,8,0.5)] md:left-[26px]"
                        ></div>

                        <div
                            class="flex flex-col justify-between gap-4 rounded-xl border border-slate-700/50 bg-slate-800/50 p-4 transition-all hover:border-yellow-500/30 hover:bg-slate-800 hover:shadow-md md:flex-row"
                        >
                            <div class="flex flex-1 items-start gap-4">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-white/5 bg-slate-900/50 text-2xl"
                                >
                                    🎁
                                </div>
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <span class="font-mono text-xs text-slate-500">
                                            {{ formatTime(log.purchased_at) }}
                                        </span>
                                        <span
                                            class="rounded border border-slate-600 bg-slate-700 px-2 py-0.5 text-[10px] text-slate-300"
                                        >
                                            Qty: {{ log.qty }}
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-200">
                                        {{ log.reward?.name ?? '(Discontinued Item)' }}
                                    </h3>

                                    <div class="mt-2">
                                        <template v-if="editingId === log.id">
                                            <textarea
                                                v-model="editForm.note"
                                                rows="2"
                                                class="input-dark w-full resize-none border-slate-600 bg-slate-900 text-sm"
                                                placeholder="Details..."
                                            ></textarea>
                                            <div class="mt-2 flex justify-end gap-2">
                                                <button
                                                    @click="cancelEdit"
                                                    class="px-2 text-xs text-slate-400 hover:text-white"
                                                >
                                                    Cancel
                                                </button>
                                                <button
                                                    @click="saveEdit"
                                                    :disabled="editForm.processing"
                                                    class="rounded bg-indigo-600 px-3 py-1 text-xs text-white hover:bg-indigo-500"
                                                >
                                                    Save
                                                </button>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <div
                                                v-if="log.note"
                                                class="border-l-2 border-slate-600 pl-3 text-sm italic leading-relaxed text-slate-400"
                                            >
                                                "{{ log.note }}"
                                            </div>
                                            <button
                                                @click="startEdit(log)"
                                                class="mt-1 text-[10px] font-bold uppercase tracking-wider text-slate-600 opacity-0 transition-opacity hover:text-yellow-500 group-hover:opacity-100"
                                            >
                                                {{ log.note ? 'Edit Note' : '+ Add Note' }}
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex min-w-[100px] flex-col items-end justify-center border-t border-slate-700/50 pt-3 md:border-l md:border-t-0 md:pl-4 md:pt-0"
                            >
                                <div class="mb-0.5 text-[10px] font-bold uppercase text-slate-500">
                                    Total Paid
                                </div>
                                <div class="font-mono text-xl font-black text-yellow-500">
                                    -{{ log.cost_coin }} G
                                </div>
                                <div class="font-mono text-[10px] text-slate-600">
                                    {{ log.unit_cost_coin }} G / unit
                                </div>
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
                        'bg-yellow-600 text-white shadow': link.active,
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
    @apply rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 outline-none transition-all focus:ring-1 focus:ring-yellow-500;
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
/* Calendar Icon White Fix */
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>
