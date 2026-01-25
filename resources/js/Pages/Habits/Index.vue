<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, router, Head } from '@inertiajs/vue3';
import confetti from 'canvas-confetti';
import { useAudio } from '@/Composables/useAudio';

defineOptions({ layout: AppLayout });

const { playSfx } = useAudio();

const props = defineProps({
    habits: Array,
    filters: Object,
});

// --- CREATE FORM ---
const createForm = useForm({
    name: '',
    start_date: '',
});

const submitCreate = () => {
    createForm.post('/habits', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showToast('✨ New Ritual Established!');
        },
    });
};

const editingId = ref(null);
const editName = ref('');

const startRename = (h) => {
    editingId.value = h.id;
    editName.value = h.name || '';
};

const cancelRename = () => {
    editingId.value = null;
    editName.value = '';
};

const saveRename = (h) => {
    const nextName = editName.value.trim();
    if (!nextName || nextName === h.name) return cancelRename();

    router.patch(
        `/habits/${h.id}`,
        { name: nextName },
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelRename();
            },
        }
    );
};

// --- ACTIONS ---
const toggleHabit = (h) => {
    router.patch(
        `/habits/${h.id}/toggle`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                if (!h.done_today) {
                    // Kalau tadinya belum done, sekarang jadi done
                    playSfx('toggle-habit');
                }
            },
        }
    );
};

const archiveHabit = (h) => {
    if (confirm(`Archive ritual "${h.name}"? It will stop appearing in daily list.`)) {
        router.patch(`/habits/${h.id}/archive`, {}, { preserveScroll: true });
    }
};

const unarchiveHabit = (h) => {
    router.patch(`/habits/${h.id}/unarchive`, {}, { preserveScroll: true });
};

const setView = (view) => {
    router.get('/habits', { view }, { preserveScroll: true, preserveState: true });
};

// --- VISUALS ---

const showToast = (message) => {
    const toast = document.createElement('div');
    toast.className =
        'fixed top-4 right-4 bg-slate-800 border-l-4 border-emerald-500 text-white px-6 py-4 rounded shadow-2xl z-[100] animate-bounce font-bold flex items-center gap-2';
    toast.innerHTML = `<span>🎉</span> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
};
</script>

<template>
    <Head title="Rituals" />

    <div class="mx-auto max-w-7xl space-y-8 p-4 text-gray-200 md:p-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="flex items-center gap-3 text-3xl font-black tracking-tight text-white">
                    <span>🛡️</span>
                    Daily Rituals
                </h1>
                <p class="mt-1 text-sm text-slate-400">
                    Consistency builds character. Maintain your streaks.
                </p>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 p-6 shadow-lg">
            <div
                class="pointer-events-none absolute right-0 top-0 h-32 w-32 rounded-full bg-emerald-500/10 blur-3xl"
            ></div>

            <h3 class="mb-4 text-lg font-bold text-white">Establish New Ritual</h3>
            <form @submit.prevent="submitCreate" class="flex flex-col items-end gap-4 md:flex-row">
                <div class="w-full flex-1">
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Ritual Name</label>
                    <input
                        v-model="createForm.name"
                        placeholder="e.g. Morning Meditation"
                        class="input-dark w-full"
                    />
                    <div v-if="createForm.errors.name" class="mt-1 text-xs text-red-400">
                        {{ createForm.errors.name }}
                    </div>
                </div>
                <div class="w-full md:w-48">
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Start Date</label>
                    <input type="date" v-model="createForm.start_date" class="input-dark w-full text-sm" />
                </div>
                <button
                    type="submit"
                    :disabled="createForm.processing"
                    class="w-full rounded-lg bg-emerald-600 px-6 py-2.5 font-bold text-white shadow-lg transition-all hover:bg-emerald-500 active:scale-95 md:w-auto"
                >
                    + Add
                </button>
            </form>
        </div>

        <div class="flex gap-2 border-b border-slate-700 pb-1">
            <button
                @click="setView('active')"
                class="relative rounded-t-lg px-4 py-2 text-sm font-bold transition-colors"
                :class="
                    filters?.view === 'active' || !filters?.view
                        ? 'border-x border-t border-slate-700 bg-slate-800 text-emerald-400'
                        : 'text-slate-500 hover:text-slate-300'
                "
            >
                Active Rituals
                <div
                    v-if="filters?.view === 'active' || !filters?.view"
                    class="absolute bottom-[-5px] left-0 h-1 w-full bg-slate-800"
                ></div>
            </button>
            <button
                @click="setView('archived')"
                class="relative rounded-t-lg px-4 py-2 text-sm font-bold transition-colors"
                :class="
                    filters?.view === 'archived'
                        ? 'border-x border-t border-slate-700 bg-slate-800 text-emerald-400'
                        : 'text-slate-500 hover:text-slate-300'
                "
            >
                Archived
                <div
                    v-if="filters?.view === 'archived'"
                    class="absolute bottom-[-5px] left-0 h-1 w-full bg-slate-800"
                ></div>
            </button>
            <button
                @click="setView('all')"
                class="relative rounded-t-lg px-4 py-2 text-sm font-bold transition-colors"
                :class="
                    filters?.view === 'all'
                        ? 'border-x border-t border-slate-700 bg-slate-800 text-emerald-400'
                        : 'text-slate-500 hover:text-slate-300'
                "
            >
                All Records
                <div
                    v-if="filters?.view === 'all'"
                    class="absolute bottom-[-5px] left-0 h-1 w-full bg-slate-800"
                ></div>
            </button>
        </div>

        <div
            v-if="habits.length === 0"
            class="rounded-2xl border-2 border-dashed border-slate-700 py-16 text-center opacity-50"
        >
            <div class="mb-4 text-5xl">🍃</div>
            <h3 class="text-xl font-bold text-slate-400">No rituals found.</h3>
            <p class="text-sm text-slate-500">Start small. Consistency is key.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4">
            <div
                v-for="habit in habits"
                :key="habit.id"
                class="group flex items-center justify-between rounded-xl border border-slate-700 bg-slate-800 p-4 shadow-md transition-all hover:border-emerald-500/30"
                :class="{
                    'opacity-60 grayscale': habit.end_date,
                    'ring-1 ring-emerald-500/50': editingId === habit.id,
                }"
            >
                <div class="flex min-w-0 flex-1 items-center gap-4">
                    <label class="relative shrink-0 cursor-pointer">
                        <input
                            type="checkbox"
                            :checked="habit.done_today"
                            @change="toggleHabit(habit)"
                            class="peer sr-only"
                        />
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl border-2 border-slate-600 bg-slate-900 transition-all group-hover:border-slate-500 peer-checked:border-emerald-400 peer-checked:bg-emerald-500 peer-checked:shadow-[0_0_15px_rgba(16,185,129,0.5)]"
                        >
                            <span
                                class="transform text-2xl opacity-0 transition-opacity peer-checked:scale-110 peer-checked:opacity-100"
                            >
                                ✓
                            </span>
                        </div>
                    </label>

                    <div class="mr-4 min-w-0 flex-1">
                        <div v-if="editingId === habit.id" class="mb-1">
                            <input
                                v-model="editName"
                                class="input-dark w-full border-emerald-500 bg-slate-950 px-3 py-1.5 text-sm font-bold focus:ring-0"
                                @keydown.enter.prevent="saveRename(habit)"
                                @keydown.esc.prevent="cancelRename"
                                @vue:mounted="({ el }) => el.focus()"
                            />
                        </div>
                        <h4
                            v-else
                            class="cursor-pointer truncate text-lg font-bold text-white transition-colors group-hover:text-emerald-300"
                            @click="startRename(habit)"
                        >
                            {{ habit.name }}
                        </h4>

                        <div class="mt-1 flex items-center gap-4 text-xs text-slate-400">
                            <div
                                class="flex items-center gap-1"
                                :class="habit.streak > 0 ? 'font-bold text-orange-400' : 'text-slate-600'"
                            >
                                <span class="text-base">🔥</span>
                                <span>{{ habit.streak }} Day Streak</span>
                            </div>

                            <div class="ml-1 hidden border-l border-slate-700 pl-3 sm:block">
                                Start: {{ habit.start_date }}
                            </div>

                            <span
                                v-if="habit.end_date"
                                class="rounded bg-slate-700 px-1.5 py-0.5 text-[10px] font-bold uppercase text-slate-400"
                            >
                                Archived
                            </span>
                        </div>
                    </div>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-2">
                    <template v-if="editingId === habit.id">
                        <button
                            @click="saveRename(habit)"
                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-lg transition-colors hover:bg-emerald-500"
                        >
                            Save
                        </button>
                        <button
                            @click="cancelRename"
                            class="rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-bold text-slate-300 transition-colors hover:bg-slate-600"
                        >
                            Cancel
                        </button>
                    </template>

                    <template v-else>
                        <Link
                            :href="`/habits/${habit.id}`"
                            class="flex items-center gap-1 rounded-lg bg-slate-700 px-3 py-2 text-xs font-medium text-slate-300 transition-colors hover:bg-slate-600"
                        >
                            📅
                            <span class="hidden sm:inline">Calendar</span>
                        </Link>

                        <button
                            @click="startRename(habit)"
                            class="rounded-lg bg-slate-700/50 p-2 text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                            title="Rename"
                        >
                            ✏️
                        </button>

                        <button
                            v-if="!habit.end_date"
                            @click="archiveHabit(habit)"
                            class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-800 hover:text-red-400"
                            title="Archive"
                        >
                            📦
                        </button>
                        <button
                            v-else
                            @click="unarchiveHabit(habit)"
                            class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-800 hover:text-emerald-400"
                            title="Unarchive"
                        >
                            ♻️
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.input-dark {
    @apply rounded-lg border border-slate-700 bg-slate-900 px-4 py-2.5 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/50;
}
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>
