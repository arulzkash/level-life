<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, router, Head } from '@inertiajs/vue3';
import { useGoalUrgency } from '@/Composables/useGoalUrgency';
import { GOAL_COPY } from '@/Utils/featureCopy';

defineOptions({ layout: AppLayout });

const props = defineProps({
    goal: Object,
});

const { analyzeGoal, getLocalYMD } = useGoalUrgency();
const analyzed = computed(() => analyzeGoal(props.goal));

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const localDateStr = getLocalYMD(dateString);
    const [year, month, day] = localDateStr.split('-');
    const date = new Date(year, month - 1, day);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getDaysLeft = (dateString) => {
    if (!dateString) return '';
    const due = new Date(dateString);
    due.setHours(0, 0, 0, 0);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diffTime = due - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return `${Math.abs(diffDays)} days overdue`;
    if (diffDays === 0) return 'Due today';
    if (diffDays === 1) return 'Due tomorrow';
    return `${diffDays} days left`;
};

const getDaysLeftClass = (dateString, isCompleted) => {
    if (isCompleted) return 'text-emerald-500 font-bold';
    if (!dateString) return 'text-slate-500';

    const due = new Date(dateString);
    due.setHours(0, 0, 0, 0);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diffDays = Math.ceil((due - today) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return 'text-red-700 font-bold';
    if (diffDays === 0) return 'text-red-500 font-bold';
    if (diffDays <= 2) return 'text-orange-400 font-bold';
    if (diffDays <= 5) return 'text-amber-400 font-bold';
    return 'text-emerald-400 font-medium';
};

const nextMilestone = computed(() => {
    if (!props.goal.milestones || props.goal.milestones.length === 0) return null;
    const pending = props.goal.milestones.filter((m) => !m.is_completed);
    if (!pending.length) return null;
    return pending.sort((a, b) => new Date(a.due_date) - new Date(b.due_date))[0];
});

const isEditing = ref(false);

const editForm = useForm({
    title: props.goal.title,
    description: props.goal.description,
    personal_reason: props.goal.personal_reason,
    deadline: getLocalYMD(props.goal.deadline),
    milestones: (props.goal.milestones || []).map((m) => ({
        ...m,
        due_date: getLocalYMD(m.due_date),
    })),
});

const toggleEdit = () => {
    isEditing.value = !isEditing.value;
    if (!isEditing.value) {
        // Reset to original props on cancel
        editForm.title = props.goal.title;
        editForm.description = props.goal.description;
        editForm.personal_reason = props.goal.personal_reason;
        editForm.deadline = getLocalYMD(props.goal.deadline);
        editForm.milestones = (props.goal.milestones || []).map((m) => ({
            ...m,
            due_date: getLocalYMD(m.due_date),
        }));
    }
};

const addMilestoneRow = () => {
    editForm.milestones.push({ id: null, title: '', due_date: '', is_completed: false });
};

const removeMilestoneRow = (index) => {
    editForm.milestones.splice(index, 1);
};

const submitEdit = () => {
    editForm.patch(`/goals/${props.goal.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};

const toggleMilestone = (m) => {
    if (isEditing.value || props.goal.status === 'completed') return;
    router.patch(`/goals/milestones/${m.id}/toggle`, {}, { preserveScroll: true });
};

const completeGoal = () => {
    if (!confirm('Are you sure you want to complete this goal? Make sure all milestones are done!')) return;
    router.post(`/goals/${props.goal.id}/complete`);
};

const deleteGoal = () => {
    if (!confirm('Are you sure you want to completely delete this goal? This action cannot be undone.'))
        return;
    router.delete(`/goals/${props.goal.id}`);
};
</script>

<template>
    <Head :title="goal.title" />

    <div class="mx-auto max-w-4xl space-y-8 p-4 text-gray-200 md:p-8">
        <Link href="/goals" class="text-sm font-bold text-indigo-400 transition-colors hover:text-indigo-300">
            &larr; Back to Goals
        </Link>

        <!-- HEADER / MAIN INFO -->
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-700/50 p-6 shadow-2xl transition-all duration-300 md:p-8"
            :class="[
                goal.status === 'completed'
                    ? 'border-l-4 border-l-emerald-700 bg-gradient-to-br from-slate-800/60 to-slate-900/60 shadow-slate-900/20 opacity-90'
                    : analyzed.level === 4
                        ? 'border-l-4 border-l-red-500 bg-gradient-to-br from-slate-800 to-red-900/50 shadow-red-900/30'
                        : analyzed.level === 3
                          ? 'border-l-4 border-l-orange-500 bg-gradient-to-br from-slate-800 to-orange-900/40 shadow-orange-900/20'
                          : analyzed.level === 2
                            ? 'border-l-4 border-l-amber-500 bg-gradient-to-br from-slate-800 to-amber-900/30 shadow-amber-900/10'
                            : 'border-l-4 border-l-emerald-500 bg-gradient-to-br from-slate-800 to-emerald-900/30 shadow-emerald-900/10',
            ]"
        >
            <!-- Edit Toggle Button (active goals only) -->
            <div class="absolute right-4 top-4 z-20">
                <button
                    v-if="goal.status !== 'completed'"
                    @click="toggleEdit"
                    class="rounded-lg bg-slate-700/50 px-3 py-1 text-xs font-bold text-slate-300 transition hover:bg-slate-600 hover:text-white"
                >
                    {{ isEditing ? '✕ Cancel' : '✏️ Edit Goal' }}
                </button>
                <span v-else class="rounded-lg border border-emerald-700/40 bg-emerald-900/20 px-3 py-1 text-xs font-bold text-emerald-500">
                    🏆 CONQUERED
                </span>
            </div>

            <!-- VIEW MODE -->
            <div v-if="!isEditing">
                <div class="mb-4 flex flex-col items-start gap-4 pr-20">
                    <!-- Status badge: shows urgency for active, CONQUERED for completed -->
                    <div
                        v-if="goal.status !== 'completed'"
                        class="inline-block rounded border px-3 py-1.5 text-[11px] font-black uppercase tracking-widest shadow-sm"
                        :class="[
                            analyzed.level === 4
                                ? 'border-red-500/30 bg-red-500/10 text-red-400'
                                : analyzed.level === 3
                                  ? 'border-orange-500/30 bg-orange-500/10 text-orange-400'
                                  : analyzed.level === 2
                                    ? 'border-amber-600/30 bg-amber-500/10 text-amber-400'
                                    : 'border-emerald-600/30 bg-emerald-500/10 text-emerald-400',
                            ]"
                        :title="analyzed.stateHelper"
                    >
                        {{ analyzed.stateName }}
                        <span class="mx-1 opacity-50">|</span>
                        {{ analyzed.stateMessage }}
                    </div>
                    <p v-if="goal.status !== 'completed'" class="text-xs text-slate-500">
                        {{ analyzed.stateHelper }}
                    </p>
                    <div
                        v-else
                        class="inline-flex items-center gap-2 rounded border border-emerald-700/40 bg-emerald-900/20 px-3 py-1.5 text-[11px] font-black uppercase tracking-widest text-emerald-400"
                    >
                        ✓ Goal Completed
                        <span v-if="goal.completed_at" class="font-normal normal-case text-emerald-600 text-[10px]">{{ formatDate(goal.completed_at) }}</span>
                    </div>
                    <div>
                        <h1
                            class="text-3xl font-black text-white transition-all duration-300 md:text-4xl"
                            :class="[
                                goal.status === 'completed'
                                    ? 'drop-shadow-[0_0_8px_rgba(52,211,153,0.4)]'
                                    : analyzed.level === 4
                                        ? 'drop-shadow-[0_0_8px_rgba(248,113,113,0.5)]'
                                        : analyzed.level === 3
                                          ? 'drop-shadow-[0_0_8px_rgba(251,146,60,0.5)]'
                                          : analyzed.level === 2
                                            ? 'drop-shadow-[0_0_8px_rgba(251,191,36,0.5)]'
                                            : 'drop-shadow-[0_0_8px_rgba(52,211,153,0.5)]',
                            ]"
                        >
                            {{ goal.title }}
                        </h1>
                        <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-400">
                            <span>
                                Target Deadline:
                                <span class="font-bold text-slate-200">🏁 {{ formatDate(goal.deadline) }}</span>
                            </span>
                            <span
                                v-if="goal.status !== 'completed'"
                                class="rounded border border-transparent bg-slate-900 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest shadow-sm"
                                :class="[
                                    analyzed.isGoalOverdue
                                        ? 'border-red-500/20 bg-red-950/30 text-red-400'
                                        : analyzed.goalDaysLeft <= 2
                                          ? 'border-orange-500/20 bg-orange-950/30 text-orange-400'
                                          : analyzed.goalDaysLeft <= 5
                                            ? 'border-amber-500/20 bg-amber-950/30 text-amber-400'
                                            : 'border-emerald-500/20 bg-emerald-950/30 text-emerald-400',
                                ]"
                            >
                                <template v-if="analyzed.isGoalOverdue">
                                    {{ Math.abs(analyzed.goalDaysLeft) }} Days Overdue
                                </template>
                                <template v-else-if="analyzed.goalDaysLeft === 0">
                                    Due Today
                                </template>
                                <template v-else>
                                    {{ analyzed.goalDaysLeft }} Days Left
                                </template>
                            </span>
                        </p>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-2 flex w-full max-w-sm items-center gap-3">
                        <div
                            class="relative h-2 flex-1 overflow-hidden rounded-full border border-slate-700/50 bg-slate-900 shadow-inner"
                        >
                            <div
                                class="absolute inset-y-0 left-0 h-full transition-all duration-1000 ease-out"
                                :class="[
                                    goal.status === 'completed'
                                        ? 'bg-emerald-600 shadow-[0_0_8px_rgba(16,185,129,0.4)]'
                                        : analyzed.level === 4
                                            ? 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]'
                                            : analyzed.level === 3
                                              ? 'bg-orange-500 shadow-[0_0_8px_rgba(249,115,22,0.5)]'
                                              : analyzed.level === 2
                                                ? 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]'
                                                : 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]',
                                ]"
                                :style="`width: ${analyzed.progress}%`"
                            ></div>
                        </div>
                        <span class="font-mono text-xs font-bold text-slate-300">
                            {{ analyzed.progress }}%
                        </span>
                    </div>
                    <!-- Complete + Delete buttons (active goals only) -->
                    <div v-if="goal.status !== 'completed'" class="mt-2 flex shrink-0 gap-2">
                        <button
                            @click="completeGoal"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-lg transition-colors hover:bg-emerald-500"
                        >
                            ✓ Complete Goal
                        </button>
                        <button
                            @click="deleteGoal"
                            class="rounded-lg bg-red-900/40 px-3 py-2 text-sm font-bold text-red-300 transition-colors hover:bg-red-800/60 hover:text-white"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <div
                    class="my-6 rounded-xl border-l-2 bg-slate-900/40 p-6 shadow-inner"
                    :class="[
                        goal.status === 'completed'
                            ? 'border-emerald-700'
                            : analyzed.level === 4
                                ? 'border-red-500'
                                : analyzed.level === 3
                                  ? 'border-orange-500'
                                  : analyzed.level === 2
                                    ? 'border-amber-500'
                                    : 'border-emerald-500',
                    ]"
                >
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">
                        My Reason
                    </p>
                    <p class="mb-3 text-xs text-slate-500">
                        {{ GOAL_COPY.personalReasonHelper }}
                    </p>
                    <blockquote
                        class="whitespace-pre-wrap text-lg italic leading-relaxed text-slate-300 md:text-xl"
                    >
                        "{{ goal.personal_reason }}"
                    </blockquote>
                </div>

                <p v-if="goal.description" class="whitespace-pre-line text-base text-slate-300">
                    {{ goal.description }}
                </p>
            </div>

            <!-- EDIT MODE -->
            <div v-else class="animate-fade-in mt-4">
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-500">
                                Goal Title
                            </label>
                            <input v-model="editForm.title" class="input-dark w-full" required />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-500">
                                Deadline
                            </label>
                            <input
                                type="date"
                                v-model="editForm.deadline"
                                class="input-dark w-full text-sm"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">
                            The "Why" (Personal Reason)
                        </label>
                        <p class="mb-2 text-xs text-slate-500">
                            {{ GOAL_COPY.personalReasonHelper }}
                        </p>
                        <textarea
                            v-model="editForm.personal_reason"
                            class="input-dark min-h-[80px] w-full text-sm italic transition-all duration-300 focus:min-h-[120px]"
                            rows="1"
                            required
                        ></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">
                            Description (Optional)
                        </label>
                        <textarea
                            v-model="editForm.description"
                            class="input-dark min-h-[80px] w-full text-sm transition-all duration-300 focus:min-h-[120px]"
                            rows="1"
                        ></textarea>
                    </div>

                    <div class="border-t border-slate-700 pt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <label class="block text-xs font-bold uppercase text-slate-500">
                                Manage Milestones
                            </label>
                            <button
                                type="button"
                                @click="addMilestoneRow"
                                class="text-xs font-bold text-indigo-400 transition hover:text-indigo-300"
                            >
                                + Add Checkpoint
                            </button>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(m, index) in editForm.milestones"
                                :key="index"
                                class="flex items-center gap-2"
                            >
                                <input
                                    v-model="m.title"
                                    placeholder="Milestone Name"
                                    class="input-dark flex-1"
                                    required
                                />
                                <input
                                    type="date"
                                    v-model="m.due_date"
                                    class="input-dark w-32 text-sm md:w-40"
                                    required
                                />
                                <button
                                    type="button"
                                    @click="removeMilestoneRow(index)"
                                    class="text-red-400 hover:text-red-300"
                                    title="Delete Milestone"
                                >
                                    ✕
                                </button>
                            </div>
                            <div
                                v-if="editForm.milestones.length === 0"
                                class="text-xs italic text-slate-500"
                            >
                                No milestones defined.
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button
                            type="button"
                            @click="toggleEdit"
                            class="mr-3 px-4 py-2 text-sm font-bold text-slate-400 hover:text-slate-300"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-bold text-white transition hover:bg-indigo-500 disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MILESTONES (View Mode only) -->
        <div v-if="!isEditing">
            <h3 class="mb-4 flex items-center gap-2 text-xl font-bold text-white">
                📑 Actionable Milestones
                <span class="text-sm font-normal text-slate-400">
                    ({{ goal.milestones.filter((m) => m.is_completed).length }} /
                    {{ goal.milestones.length }})
                </span>
            </h3>
            <p class="mb-4 text-sm text-slate-500">
                {{ GOAL_COPY.milestoneHelper }}
            </p>

            <div class="relative space-y-3">
                <!-- Progress Line indicator purely visual -->
                <div
                    class="absolute bottom-6 left-6 top-6 z-0 hidden w-0.5 rounded-full bg-slate-700/50 md:block"
                ></div>

                <div
                    v-for="m in goal.milestones"
                    :key="m.id"
                    class="group relative z-10 flex flex-col justify-between gap-3 rounded-xl border p-4 transition-all md:flex-row md:items-center"
                    :class="[
                        m.is_completed
                            ? 'border-slate-700 bg-slate-800/50 opacity-60'
                            : nextMilestone && nextMilestone.id === m.id
                              ? analyzed.level === 4
                                  ? 'border-red-500/80 bg-red-900/20 shadow-[0_0_15px_rgba(239,68,68,0.15)] md:scale-[1.02]'
                                  : analyzed.level === 3
                                    ? 'border-orange-500/80 bg-orange-900/20 shadow-[0_0_15px_rgba(249,115,22,0.15)] md:scale-[1.02]'
                                    : analyzed.level === 2
                                      ? 'border-amber-500/80 bg-amber-900/20 shadow-[0_0_15px_rgba(251,191,36,0.15)] md:scale-[1.02]'
                                      : 'border-emerald-500/80 bg-emerald-900/20 shadow-[0_0_15px_rgba(16,185,129,0.15)] md:scale-[1.02]'
                              : 'border-slate-700 bg-slate-800 hover:border-slate-500',
                    ]"
                >
                    <div class="flex items-center gap-4">
                        <label
                            class="relative flex shrink-0 items-center"
                            :class="goal.status === 'completed' ? 'cursor-not-allowed' : 'cursor-pointer'"
                        >
                            <input
                                type="checkbox"
                                :checked="m.is_completed"
                                @change="toggleMilestone(m)"
                                :disabled="goal.status === 'completed'"
                                class="peer sr-only"
                            />
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-slate-600 bg-slate-900 transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-500 peer-checked:shadow-[0_0_10px_rgba(16,185,129,0.3)] peer-disabled:opacity-50"
                            >
                                <span
                                    class="text-white opacity-0 transition-opacity peer-checked:opacity-100"
                                >
                                    ✓
                                </span>
                            </div>
                        </label>
                        <div>
                            <span
                                class="text-lg font-bold text-white transition-all"
                                :class="{ 'text-slate-500 line-through': m.is_completed }"
                            >
                                {{ m.title }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="ml-12 shrink-0 text-sm text-slate-400 md:ml-0 md:text-right"
                        :class="[
                            m.is_completed
                                ? 'opacity-50'
                                : nextMilestone && nextMilestone.id === m.id && analyzed.level === 4
                                  ? 'font-bold text-red-400'
                                  : nextMilestone && nextMilestone.id === m.id && analyzed.level === 3
                                    ? 'font-bold text-orange-400'
                                    : nextMilestone && nextMilestone.id === m.id && analyzed.level === 2
                                      ? 'font-bold text-amber-400'
                                      : nextMilestone && nextMilestone.id === m.id
                                        ? 'font-bold text-emerald-400'
                                        : '',
                        ]"
                    >
                        <div>
                            Due:
                            <span
                                class="font-bold text-slate-300"
                                :class="!m.is_completed ? 'text-inherit' : ''"
                            >
                                {{ formatDate(m.due_date) }}
                            </span>
                        </div>
                        <div
                            class="mt-0.5 text-[10px] uppercase tracking-widest opacity-90"
                            :class="getDaysLeftClass(m.due_date, m.is_completed)"
                        >
                            {{ m.is_completed ? 'Completed' : getDaysLeft(m.due_date) }}
                        </div>
                    </div>
                </div>

                <div
                    v-if="goal.milestones.length === 0"
                    class="rounded-xl border border-dashed border-slate-700 p-8 text-center italic text-slate-500"
                >
                    No milestones defined yet. Click "Edit Goal" to add some.
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.input-dark {
    @apply rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50;
}
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>
