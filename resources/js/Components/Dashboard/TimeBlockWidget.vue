<script setup>
import { Link } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    blocks: { type: Array, default: () => [] },
    today: { type: String, default: '' },
});

const emit = defineEmits(['add', 'delete']);

const form = reactive({
    date: props.today,
    start_time: '09:00',
    end_time: '10:00',
    title: '',
    note: '',
});

const handleAdd = () => {
    emit('add', { ...form });
};

const handleDelete = (id) => {
    emit('delete', id);
};

const resetForm = () => {
    form.title = '';
    form.note = '';
    form.date = props.today;
};

defineExpose({ resetForm });
</script>

<template>
    <div class="rounded-2xl border border-slate-700 bg-slate-800 p-6 shadow-lg">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="flex items-center gap-2 font-bold text-white">
                <span>⏳</span>
                Timeblocks
            </h3>
            <Link href="/timeblocks" class="text-xs text-indigo-400 hover:underline">
                Full Week
            </Link>
        </div>

        <form
            @submit.prevent="handleAdd"
            class="mb-6 rounded-xl border border-slate-700/50 bg-slate-900/50 p-3"
        >
            <div class="mb-2 flex gap-2">
                <input
                    type="time"
                    v-model="form.start_time"
                    class="input-dark flex-1 p-1 text-center text-xs"
                />
                <span class="self-center text-slate-500">-</span>
                <input
                    type="time"
                    v-model="form.end_time"
                    class="input-dark flex-1 p-1 text-center text-xs"
                />
            </div>
            <input
                v-model="form.title"
                placeholder="Focus Block Title..."
                class="input-dark mb-2 w-full text-xs"
            />
            <textarea
                v-model="form.note"
                placeholder="Note (opt)..."
                class="input-dark mb-2 w-full resize-none text-xs"
                rows="1"
            ></textarea>
            <button
                type="submit"
                class="w-full rounded border border-slate-600 bg-slate-700 py-1.5 text-xs text-slate-200 transition-colors hover:bg-slate-600"
            >
                + Add Block
            </button>
        </form>

        <div class="relative ml-2 space-y-0 border-l-2 border-slate-700 pl-4">
            <div v-if="blocks.length === 0" class="pl-4 text-xs italic text-slate-500">
                No schedule set for today.
            </div>
            <div v-for="b in blocks" :key="b.id" class="group relative pb-6 pl-6 last:pb-0">
                <div
                    class="absolute -left-[9px] top-0 h-4 w-4 rounded-full border-2 border-indigo-500 bg-slate-800 transition-colors group-hover:bg-indigo-500"
                ></div>
                <div class="mb-0.5 font-mono text-[10px] font-bold text-indigo-400">
                    {{ b.start_time }} - {{ b.end_time }}
                </div>
                <div class="mb-1 text-sm font-medium leading-tight text-slate-200">
                    {{ b.title }}
                </div>
                <div
                    v-if="b.note"
                    class="mb-1 rounded border border-slate-700/30 bg-slate-900/50 p-2 text-xs italic text-slate-500"
                >
                    "{{ b.note }}"
                </div>
                <button
                    @click="handleDelete(b.id)"
                    class="text-[10px] text-red-500/70 opacity-0 transition-opacity hover:text-red-400 group-hover:opacity-100"
                >
                    [Delete Block]
                </button>
            </div>
        </div>
    </div>
</template>
