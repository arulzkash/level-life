<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    habits: { type: Array, default: () => [] },
});

const emit = defineEmits(['toggle']);

const doneCount = computed(() => props.habits.filter((h) => h.done_today).length);
const totalCount = computed(() => props.habits.length);

const onToggle = (habit) => {
    emit('toggle', habit);
};
</script>

<template>
    <div class="rounded-2xl border border-slate-700 bg-slate-800 p-6 shadow-lg">
        <div class="mb-6 flex items-center justify-between">
            <h3 class="flex items-center gap-2 font-bold text-white">
                <span>🛡️</span>
                Daily Habits
            </h3>
            <span
                class="rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-xs text-slate-400"
            >
                {{ doneCount }}/{{ totalCount }}
            </span>
        </div>
        <ul v-if="habits.length > 0" class="space-y-2">
            <li v-for="h in habits" :key="h.id" class="group">
                <label
                    class="flex cursor-pointer items-center gap-3 rounded-xl border border-transparent bg-slate-900/50 p-3 transition-all hover:border-slate-600"
                >
                    <input
                        type="checkbox"
                        :checked="h.done_today"
                        @change="onToggle(h)"
                        class="h-5 w-5 cursor-pointer rounded border-slate-600 bg-slate-800 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-0 focus:ring-offset-transparent"
                    />
                    <div class="flex-1">
                        <div
                            class="text-sm font-medium text-slate-200 transition-colors group-hover:text-white"
                            :class="{
                                'text-slate-500 line-through': h.done_today,
                            }"
                        >
                            {{ h.name }}
                        </div>
                        <div class="mt-0.5 text-[10px] text-slate-500">
                            Current Streak:
                            <span class="font-bold text-orange-400">{{ h.streak }} 🔥</span>
                        </div>
                    </div>
                </label>
            </li>
        </ul>
        <div v-else class="py-4 text-center text-xs text-slate-500">No active habits.</div>
        <div class="mt-4 text-center">
            <Link
                href="/habits"
                class="text-xs font-medium text-indigo-400 hover:text-indigo-300 hover:underline"
            >
                Manage Habits & View Calendar
            </Link>
        </div>
    </div>
</template>
