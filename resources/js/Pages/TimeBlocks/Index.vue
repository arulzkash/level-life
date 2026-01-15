<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, router, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    week: Object,
    days: Array,
});

// --- NAVIGATION ---
const goWeek = (start) => {
    router.get('/timeblocks', { week_start: start }, { preserveState: true, preserveScroll: true });
};

const formatToLocalDate = (dateObj) => {
    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day = String(dateObj.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// Update navigasi
const prevWeek = () => {
    const d = new Date(props.week.start);
    d.setDate(d.getDate() - 7);
    goWeek(formatToLocalDate(d));
};

const nextWeek = () => {
    const d = new Date(props.week.start);
    d.setDate(d.getDate() + 7);
    goWeek(formatToLocalDate(d));
};

const isToday = (dateStr) => {
    return dateStr === formatToLocalDate(new Date());
};

const formatDateHeader = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        day: 'numeric',
    });
};

// --- CRUD LOGIC (MODAL) ---
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    date: '',
    start_time: '09:00',
    end_time: '10:00',
    title: '',
    note: '',
});

const openCreateModal = (dateStr) => {
    isEditing.value = false;
    form.reset();
    form.date = dateStr;
    showModal.value = true;
};

const openEditModal = (block) => {
    isEditing.value = true;
    editingId.value = block.id;
    form.date = block.date;
    form.start_time = block.start_time;
    form.end_time = block.end_time;
    form.title = block.title;
    form.note = block.note ?? '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingId.value = null;
};

const submitForm = () => {
    if (isEditing.value) {
        form.patch(`/timeblocks/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/timeblocks', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteBlock = () => {
    if (confirm('Abort this tactical operation?')) {
        router.delete(`/timeblocks/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const getDuration = (start, end) => {
    const [h1, m1] = start.split(':').map(Number);
    const [h2, m2] = end.split(':').map(Number);
    const diff = h2 * 60 + m2 - (h1 * 60 + m1);
    const h = Math.floor(diff / 60);
    const m = diff % 60;
    if (h > 0 && m > 0) return `${h}h ${m}m`;
    if (h > 0) return `${h}h`;
    return `${m}m`;
};
</script>

<template>
    <Head title="Battle Plan" />

    <div class="flex min-h-screen flex-col p-4 md:p-6">
        <div class="mb-6 flex flex-col items-center justify-between gap-4 md:flex-row">
            <div>
                <h1 class="flex items-center gap-2 text-3xl font-black tracking-tight text-white">
                    <span>⏳</span>
                    Weekly Battle Plan
                </h1>
                <div class="mt-1 flex items-center gap-2 font-mono text-sm text-slate-400">
                    <span>{{ week.start }}</span>
                    <span class="text-slate-600">➜</span>
                    <span>{{ week.end }}</span>
                </div>
            </div>

            <div class="flex rounded-lg border border-slate-700 bg-slate-800 p-1">
                <button
                    @click="prevWeek"
                    class="rounded-md px-4 py-2 text-slate-300 transition-colors hover:bg-slate-700"
                >
                    ◀ Prev
                </button>
                <button
                    @click="goWeek(formatToLocalDate(new Date()))"
                    class="rounded-md border-x border-slate-700 px-4 py-2 font-bold text-indigo-400 transition-colors hover:bg-slate-700"
                >
                    Today
                </button>
                <button
                    @click="nextWeek"
                    class="rounded-md px-4 py-2 text-slate-300 transition-colors hover:bg-slate-700"
                >
                    Next ▶
                </button>
            </div>
        </div>

        <div class="grid flex-1 grid-cols-1 gap-4 md:grid-cols-7">
            <div v-for="day in days" :key="day.date" class="flex min-h-[200px] flex-col gap-2">
                <div
                    class="group relative flex flex-col items-center justify-center overflow-hidden rounded-xl border p-3 transition-colors"
                    :class="
                        isToday(day.date)
                            ? 'border-indigo-500 bg-indigo-900/30 text-indigo-300 shadow-[0_0_15px_rgba(99,102,241,0.2)]'
                            : 'border-slate-700 bg-slate-800 text-slate-400'
                    "
                >
                    <div class="text-xs font-bold uppercase tracking-widest">
                        {{ formatDateHeader(day.date).split(' ')[0] }}
                    </div>
                    <div class="text-xl font-black">
                        {{ formatDateHeader(day.date).split(' ')[1] }}
                    </div>

                    <button
                        @click="openCreateModal(day.date)"
                        class="absolute inset-0 flex items-center justify-center bg-indigo-500/0 transition-colors hover:bg-indigo-500/10"
                        title="Add Task"
                    >
                        <span class="text-2xl font-bold text-white opacity-0 group-hover:opacity-100">+</span>
                    </button>
                </div>

                <div class="flex-1 space-y-2">
                    <div
                        v-for="block in day.items"
                        :key="block.id"
                        @click="openEditModal(block)"
                        class="group relative cursor-pointer overflow-hidden rounded-r-xl border-l-4 border-indigo-500 bg-slate-800 p-3 shadow-md transition-all hover:translate-x-1 hover:bg-slate-700"
                    >
                        <div
                            class="pointer-events-none absolute right-0 top-0 -mr-8 -mt-8 h-16 w-16 rounded-full bg-white/5 blur-2xl"
                        ></div>

                        <div class="mb-1 flex items-baseline gap-1.5">
                            <span
                                class="font-mono text-xl font-black leading-none tracking-tighter text-white"
                            >
                                {{ block.start_time }}
                            </span>

                            <span class="text-xs font-bold text-slate-500">➜</span>

                            <span class="font-mono text-sm font-bold text-slate-400">
                                {{ block.end_time }}
                            </span>
                        </div>

                        <div class="pr-4 text-sm font-bold leading-tight text-indigo-300">
                            {{ block.title }}
                        </div>

                        <div class="mt-2 flex items-end justify-between">
                            <div
                                v-if="block.note"
                                class="max-w-[70%] truncate text-[10px] italic text-slate-500"
                            >
                                "{{ block.note }}"
                            </div>
                            <div v-else></div>

                            <div
                                class="rounded border border-slate-700/50 bg-slate-900/50 px-1.5 py-0.5 text-[9px] font-bold text-slate-400"
                            >
                                {{ getDuration(block.start_time, block.end_time) }}
                            </div>
                        </div>
                    </div>

                    <button
                        v-if="day.items.length === 0"
                        @click="openCreateModal(day.date)"
                        class="group flex h-full min-h-[100px] w-full flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-800 text-slate-600 transition-colors hover:border-slate-600 hover:text-slate-400"
                    >
                        <span class="text-2xl opacity-50 transition-transform group-hover:scale-110">+</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest">Free</span>
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="showModal"
            class="animate-fade-in fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-xl border border-slate-600 bg-slate-800 shadow-2xl"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-700 bg-slate-900/50 px-6 py-4"
                >
                    <h3 class="text-lg font-bold text-white">
                        {{ isEditing ? 'Edit Operation' : 'New Operation' }}
                    </h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4 p-6">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Date</label>
                        <input type="date" v-model="form.date" class="input-dark w-full" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Start</label>
                            <input
                                type="time"
                                v-model="form.start_time"
                                class="input-dark w-full text-center font-bold tracking-widest"
                                required
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-500">End</label>
                            <input
                                type="time"
                                v-model="form.end_time"
                                class="input-dark w-full text-center font-bold tracking-widest"
                                required
                            />
                        </div>
                    </div>
                    <div v-if="form.errors.end_time" class="text-center text-xs text-red-400">
                        {{ form.errors.end_time }}
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">
                            Mission Title
                        </label>
                        <input
                            v-model="form.title"
                            placeholder="e.g. Deep Work: Backend"
                            class="input-dark w-full font-bold text-white"
                            required
                        />
                        <div v-if="form.errors.title" class="mt-1 text-xs text-red-400">
                            {{ form.errors.title }}
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">
                            Briefing Note
                        </label>
                        <textarea
                            v-model="form.note"
                            rows="3"
                            class="input-dark w-full resize-none"
                            placeholder="Details..."
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-700 pt-4">
                        <button
                            v-if="isEditing"
                            type="button"
                            @click="deleteBlock"
                            class="text-xs font-bold uppercase text-red-400 hover:text-red-300 hover:underline"
                        >
                            Delete
                        </button>
                        <div v-else></div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-slate-400 transition-colors hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-indigo-600 px-6 py-2 font-bold text-white shadow-lg transition-all hover:bg-indigo-500"
                            >
                                {{ isEditing ? 'Update Plan' : 'Confirm Plan' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.input-dark {
    @apply rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200 placeholder-slate-500 outline-none transition-all focus:ring-1 focus:ring-indigo-500;
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
input[type='date']::-webkit-calendar-picker-indicator,
input[type='time']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>
