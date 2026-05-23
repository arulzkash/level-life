<script setup>
import { ref, watch, reactive, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import HoldButton from '@/Components/Game/HoldButton.vue';
import QuestSubtasks from '@/Components/Game/QuestSubtasks.vue';

const props = defineProps({
    quests: { type: Array, default: () => [] },
    customQuestTypes: { type: [Object, Array], default: () => ({}) },
});

const emit = defineEmits(['complete', 'toggle-status', 'reorder']);

const page = usePage();
const today = computed(() => page.props.today);

// Completion notes per quest (local state)
const completionNotes = reactive({});

// Clone props to local state for draggable manipulation
const localQuests = ref([...props.quests]);

// Sync local state when server data changes
watch(
    () => props.quests,
    (newVal) => {
        localQuests.value = [...newVal];
    },
    { deep: true }
);

// When user finishes dragging (drop)
const onDragEnd = () => {
    const orderedIds = localQuests.value.map((q) => q.id);
    emit('reorder', orderedIds);
};

// Check if all subtasks are complete
const isSubtasksComplete = (q) => {
    if (!q.subtasks || q.subtasks.length === 0) return true;
    return q.subtasks.every((t) => t.is_done);
};

// Get the color bar class based on quest type
const getTypeColorClass = (q) => {
    if (q.type === 'Boss Fight') return 'bg-quest-boss';
    if (q.type === 'Main Quest') return 'bg-quest-main';
    if (q.type === 'Side Quest') return 'bg-quest-side';
    if (q.type === 'Daily Grind') return 'bg-quest-daily';

    // Check if it's a custom type with no matching color
    const customTypes = Object.values(props.customQuestTypes || {});
    const matchingCustom = customTypes.find(ct => ct.name === q.type);
    if (!matchingCustom) return 'bg-quest-default';
    return '';
};

// Get custom type color style
const getTypeColorStyle = (q) => {
    const customTypes = Object.values(props.customQuestTypes || {});
    const matchingCustom = customTypes.find(ct => ct.name === q.type);
    if (matchingCustom?.color) {
        return { backgroundColor: matchingCustom.color };
    }
    return {};
};
</script>

<template>
    <draggable
        v-model="localQuests"
        item-key="id"
        tag="ul"
        class="space-y-4"
        handle=".drag-handle"
        ghost-class="ghost-card"
        :animation="200"
        @end="onDragEnd"
    >
        <template #item="{ element: q }">
            <li
                class="group relative overflow-hidden rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-md transition-all duration-300 hover:border-indigo-500/50"
            >
                <div
                    class="absolute bottom-0 left-0 top-0 w-1"
                    :class="getTypeColorClass(q)"
                    :style="getTypeColorStyle(q)"
                ></div>

                <div class="relative z-10 flex flex-col justify-between gap-4 md:flex-row">
                    <div
                        class="drag-handle absolute right-0 top-0 -mr-2 -mt-2 cursor-grab p-3 text-slate-600 opacity-100 transition-opacity hover:text-slate-200 active:cursor-grabbing sm:opacity-40 sm:group-hover:opacity-100"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="9" cy="12" r="1" />
                            <circle cx="9" cy="5" r="1" />
                            <circle cx="9" cy="19" r="1" />
                            <circle cx="15" cy="12" r="1" />
                            <circle cx="15" cy="5" r="1" />
                            <circle cx="15" cy="19" r="1" />
                        </svg>
                    </div>

                    <div class="flex-1 pl-3 pr-6">
                        <div class="mb-1 flex items-center gap-3">
                            <h4
                                class="text-lg font-bold text-white transition-colors group-hover:text-indigo-300"
                            >
                                {{ q.name }}
                            </h4>
                            <button
                                @click="emit('toggle-status', q)"
                                class="cursor-pointer rounded border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider transition-colors hover:opacity-80"
                                :class="
                                    q.status === 'in_progress'
                                        ? 'border-indigo-700 bg-indigo-900 text-indigo-300 shadow-glow-in-progress ring-1 ring-indigo-500/40'
                                        : 'border-slate-600 bg-slate-700 text-slate-300'
                                "
                            >
                                {{ q.status === 'in_progress' ? '⚡ In Progress' : '🛑 To Do' }}
                            </button>
                            <span
                                v-if="q.is_repeatable"
                                class="rounded bg-slate-700 px-1.5 py-0.5 text-[10px] uppercase tracking-wider text-slate-300"
                            >
                                Repeatable
                            </span>
                            <span
                                v-if="q.type === 'Boss Fight'"
                                class="rounded bg-red-900/50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-400"
                            >
                                BOSS
                            </span>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-4 text-xs text-slate-400">
                            <span class="flex items-center gap-1">🏷️ {{ q.type }}</span>
                            <span class="flex items-center gap-1 font-bold text-indigo-400">
                                ✨ {{ q.xp_reward }} XP
                            </span>
                            <span class="flex items-center gap-1 font-bold text-yellow-500">
                                💰 {{ q.coin_reward }} G
                            </span>
                            <span
                                v-if="q.due_date"
                                class="flex items-center gap-1"
                                :class="
                                    q.due_date < today
                                        ? 'rounded bg-red-500/10 px-1 font-bold text-red-400'
                                        : ''
                                "
                            >
                                📅 {{ q.due_date }}
                                <span v-if="q.due_date < today">(OVERDUE)</span>
                            </span>
                        </div>
                        <QuestSubtasks :quest="q" />
                    </div>

                    <div class="flex flex-col items-end gap-2 pt-2 md:pt-0">
                        <textarea
                            v-model="completionNotes[q.id]"
                            placeholder="Completion Note"
                            rows="1"
                            class="input-dark w-full resize-none overflow-hidden py-2 text-xs placeholder-slate-600 transition-all duration-300 focus:w-64 md:w-48"
                        ></textarea>

                        <div
                            v-if="!isSubtasksComplete(q)"
                            class="cursor-not-allowed rounded border border-slate-700 bg-slate-800 px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-500 opacity-70"
                        >
                            🔒 Pending Steps
                        </div>

                        <HoldButton
                            v-else
                            class="w-full md:w-auto"
                            @complete="emit('complete', q.id, q.xp_reward, q.coin_reward)"
                        >
                            <span>⚔️ Hold to Slash</span>
                        </HoldButton>
                    </div>
                </div>
            </li>
        </template>
    </draggable>
</template>
