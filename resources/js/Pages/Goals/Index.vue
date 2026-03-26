<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { useGoalUrgency } from '@/Composables/useGoalUrgency';

defineOptions({ layout: AppLayout });

const props = defineProps({
    activeGoals: Array,
    completedGoals: Object, // Paginated object
    filters: Object,
});

const { sortGoalsByUrgency, getLocalYMD } = useGoalUrgency();

const activeTab = ref(props.filters.tab || 'active');

// Analyze and sort all goals Reactively
const analyzedGoals = computed(() => {
    return sortGoalsByUrgency(props.activeGoals);
});

const showCreateGoalForm = ref(false);

const createForm = useForm({
    title: '',
    description: '',
    personal_reason: '',
    deadline: '',
    milestones: [],
});

const addMilestoneRow = () => {
    createForm.milestones.push({ title: '', due_date: '' });
};

const removeMilestoneRow = (index) => {
    createForm.milestones.splice(index, 1);
};

const submitCreate = () => {
    createForm.post('/goals', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreateGoalForm.value = false;
            showToast('✨ New Goal Established!');
        },
    });
};

const showToast = (message) => {
    const toast = document.createElement('div');
    toast.className =
        'fixed top-4 right-4 bg-slate-800 border-l-4 border-indigo-500 text-white px-6 py-4 rounded shadow-2xl z-[100] animate-bounce font-bold flex items-center gap-2';
    toast.innerHTML = `<span>🎯</span> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
};

// Formatting helpers
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const localDateStr = getLocalYMD(dateString);
    const [year, month, day] = localDateStr.split('-');
    const date = new Date(year, month - 1, day);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatDaysLeft = (daysLeft) => {
    if (daysLeft === 9999) return '';
    if (daysLeft < 0) return `${Math.abs(daysLeft)} DAYS OVERDUE`;
    if (daysLeft === 0) return 'DUE TODAY';
    if (daysLeft === 1) return 'DUE TOMORROW';
    return `${daysLeft} DAYS LEFT`;
};

// Helper to get nearest pending milestone
const getNearestMilestoneInfo = (goalObj) => {
    if (!goalObj.rawGoal.milestones || goalObj.rawGoal.milestones.length === 0) return null;
    const pending = goalObj.rawGoal.milestones.filter((m) => !m.is_completed);
    if (pending.length === 0) return null;
    // Sort by chronological due date
    pending.sort((a, b) => new Date(a.due_date) - new Date(b.due_date));
    return pending[0];
};
</script>

<template>
    <Head title="Goals" />

    <div class="mx-auto max-w-5xl space-y-6 p-4 text-gray-200 md:p-8">
        <!-- HEADER SECTION -->
        <div
            class="flex flex-col items-start justify-between gap-4 border-b border-slate-700/50 pb-4 md:flex-row md:items-end"
        >
            <div>
                <h1
                    class="flex items-center gap-3 text-3xl font-black tracking-tight text-white drop-shadow-sm"
                >
                    <span>🎯</span>
                    Grinding Goals
                </h1>
                <p class="mt-1 text-sm text-slate-400">
                    What are you fighting for? Don't let your targets slip away.
                </p>
            </div>

            <button
                v-if="!showCreateGoalForm && activeTab === 'active'"
                @click="showCreateGoalForm = true"
                class="flex shrink-0 items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow shadow-indigo-500/10 transition-all hover:bg-indigo-500 hover:shadow-indigo-500/30 active:scale-95"
            >
                <span>+ Declare Goal</span>
            </button>
        </div>

        <!-- TABS -->
        <div class="flex items-center gap-1 rounded-lg border border-slate-700/50 bg-slate-900/40 p-1 w-fit">
            <button
                @click="activeTab = 'active'"
                class="flex items-center gap-2 rounded-md px-4 py-1.5 text-xs font-bold uppercase tracking-wider transition-all"
                :class="activeTab === 'active'
                    ? 'bg-slate-700 text-white shadow'
                    : 'text-slate-500 hover:text-slate-300'"
            >
                ⚔️ Active
                <span class="rounded px-1.5 py-0.5 text-[10px]" :class="activeTab === 'active' ? 'bg-indigo-600 text-white' : 'bg-slate-700 text-slate-400'">
                    {{ activeGoals?.length ?? 0 }}
                </span>
            </button>
            <button
                @click="activeTab = 'completed'"
                class="flex items-center gap-2 rounded-md px-4 py-1.5 text-xs font-bold uppercase tracking-wider transition-all"
                :class="activeTab === 'completed'
                    ? 'bg-slate-700 text-white shadow'
                    : 'text-slate-500 hover:text-slate-300'"
            >
                🏆 Completed
                <span class="rounded px-1.5 py-0.5 text-[10px]" :class="activeTab === 'completed' ? 'bg-emerald-700 text-white' : 'bg-slate-700 text-slate-400'">
                    {{ completedGoals.total ?? 0 }}
                </span>
            </button>
        </div>

        <!-- CREATE FORM SECTION (Quest Style) -->
        <div
            v-if="showCreateGoalForm"
            class="animate-fade-in relative mb-8 rounded-2xl border border-indigo-500/50 bg-slate-800 p-6 shadow-2xl"
        >
            <div class="mb-6 flex items-start justify-between">
                <h3 class="text-xl font-bold text-white">Declare New Goal</h3>
                <button
                    @click="showCreateGoalForm = false"
                    class="text-slate-400 transition-colors hover:text-white"
                >
                    ✕
                </button>
            </div>

            <form @submit.prevent="submitCreate" class="space-y-6">
                <div class="flex flex-col gap-5 md:flex-row">
                    <div class="flex-1 space-y-2">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Target Name</label>
                        <input
                            v-model="createForm.title"
                            placeholder="e.g. Defeat the Exam"
                            class="input-quest w-full text-sm font-medium"
                            required
                        />
                    </div>
                    <div class="w-full space-y-2 md:w-1/3 border-l border-slate-700/30 pl-0 md:pl-5">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Goal Deadline</label>
                        <input
                            type="date"
                            v-model="createForm.deadline"
                            class="input-quest w-full text-sm"
                            required
                        />
                    </div>
                </div>

                <div class="space-y-5 border-t border-slate-700/30 pt-5">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Personal Reason (The "Why")</label>
                        <textarea
                            v-model="createForm.personal_reason"
                            placeholder="Why must you achieve this?"
                            class="input-quest min-h-[42px] w-full text-sm italic transition-all duration-300 focus:min-h-[120px]"
                            rows="1"
                            required
                        ></textarea>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Description</label>
                        <textarea
                            v-model="createForm.description"
                            placeholder="Optional details or context"
                            class="input-quest min-h-[42px] w-full text-sm transition-all duration-300 focus:min-h-[120px]"
                            rows="1"
                        ></textarea>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-700/50 bg-slate-900/50 p-5 shadow-inner">
                    <div class="mb-4 flex items-center justify-between">
                        <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Checkpoints
                        </h4>
                        <button
                            type="button"
                            @click="addMilestoneRow"
                            class="text-[10px] font-bold uppercase tracking-wider text-indigo-400 transition flex items-center gap-1 hover:text-indigo-300"
                        >
                            <span>+</span>
                            <span>Add</span>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(m, i) in createForm.milestones" :key="i" class="flex gap-3 relative group">
                            <input
                                v-model="m.title"
                                placeholder="Step title"
                                class="input-quest flex-1 px-3 py-2 text-sm"
                                required
                            />
                            <input
                                type="date"
                                v-model="m.due_date"
                                class="input-quest w-36 px-3 py-2 text-sm md:w-48"
                                required
                            />
                            <button
                                type="button"
                                @click="removeMilestoneRow(i)"
                                class="flex items-center justify-center px-1 text-slate-500 hover:text-red-400 opacity-50 transition hover:opacity-100"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div
                            v-if="createForm.milestones.length === 0"
                            class="py-2 text-[11px] italic text-slate-500 text-center rounded border border-dashed border-slate-700/50 bg-slate-800/30"
                        >
                            Break it down into manageable checkpoints.
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-5 pt-3">
                    <button
                        type="button"
                        @click="showCreateGoalForm = false"
                        class="text-sm font-medium text-slate-400 hover:text-white"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-900/30 transition-all hover:bg-indigo-500 hover:shadow-indigo-900/50 disabled:opacity-50 min-w-[120px]"
                    >
                        {{ createForm.processing ? 'Saving...' : 'Confirm Goal' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- ACTIVE TAB CONTENT -->
        <div v-if="activeTab === 'active'" class="space-y-4">

        <!-- EMPTY STATE -->
        <div
            v-if="analyzedGoals.length === 0"
            class="rounded-2xl border border-slate-700 bg-slate-800/50 py-12 text-center shadow-inner"
        >
            <div class="mb-3 text-4xl opacity-30">🔭</div>
            <h3 class="text-lg font-bold text-slate-400">Zero Targets Active.</h3>
            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                You're floating without direction. Set a burning goal to ignite your grinding phase.
            </p>
        </div>

        <!-- GOALS LIST (ROW BASED LAYOUT) -->
        <div v-else class="space-y-4">
            <Link
                v-for="(item, index) in analyzedGoals"
                :key="item.id"
                :href="`/goals/${item.id}`"
                class="group flex flex-col items-stretch overflow-hidden rounded-xl border border-slate-700/50 shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                :class="[
                    item.level === 4
                        ? 'border-l-4 border-l-red-500 bg-gradient-to-br from-slate-800 to-red-900/50 hover:to-red-900/70 hover:shadow-red-900/40'
                        : item.level === 3
                          ? 'border-l-4 border-l-orange-500 bg-gradient-to-br from-slate-800 to-orange-900/40 hover:to-orange-900/60 hover:shadow-orange-900/30'
                          : item.level === 2
                            ? 'border-l-4 border-l-amber-500 bg-gradient-to-br from-slate-800 to-amber-900/30 hover:to-amber-900/50 hover:shadow-amber-900/20'
                            : 'border-l-4 border-l-emerald-500 bg-gradient-to-br from-slate-800 to-emerald-900/30 hover:to-emerald-900/50 hover:shadow-emerald-900/20',
                ]"
            >
                <!-- TOP BAR: Title & Badge -->
                <div
                    class="flex items-center justify-between border-b border-slate-700/50 bg-slate-900/30 px-5 py-4"
                >
                    <div class="flex items-center gap-3">
                        <h4
                            class="text-xl font-bold transition-all duration-300"
                            :class="[
                                item.level === 4
                                    ? 'text-slate-100 group-hover:text-red-400 group-hover:drop-shadow-[0_0_8px_rgba(248,113,113,0.5)]'
                                    : item.level === 3
                                      ? 'text-slate-100 group-hover:text-orange-400 group-hover:drop-shadow-[0_0_8px_rgba(251,146,60,0.5)]'
                                      : item.level === 2
                                        ? 'text-slate-100 group-hover:text-amber-400 group-hover:drop-shadow-[0_0_8px_rgba(251,191,36,0.5)]'
                                        : 'text-slate-100 group-hover:text-emerald-400 group-hover:drop-shadow-[0_0_8px_rgba(52,211,153,0.5)]',
                            ]"
                        >
                            {{ item.rawGoal.title }}
                        </h4>
                    </div>

                    <div class="flex flex-col items-end gap-1">
                        <div
                            class="shrink-0 rounded border px-2 py-0.5 text-[10px] font-black uppercase tracking-widest"
                            :class="[
                                item.level === 4
                                    ? 'border-red-500/30 bg-red-500/10 text-red-400'
                                    : item.level === 3
                                      ? 'border-orange-500/30 bg-orange-500/10 text-orange-400'
                                      : item.level === 2
                                        ? 'border-amber-600/30 bg-amber-500/10 text-amber-400'
                                        : 'border-emerald-600/30 bg-emerald-500/10 text-emerald-400',
                            ]"
                        >
                            {{ item.stateName }}
                        </div>
                    </div>
                </div>

                <!-- MAIN CONTENT AREA -->
                <div class="flex flex-col divide-y divide-slate-700/50 md:flex-row md:divide-x md:divide-y-0">
                    <!-- Left: Personal Reason & Progress -->
                    <div class="flex-1 p-5">
                        <div
                            class="relative mb-4 border-l-2 py-1 pl-4"
                            :class="[
                                item.level === 4
                                    ? 'border-red-500'
                                    : item.level === 3
                                      ? 'border-orange-500'
                                      : item.level === 2
                                        ? 'border-amber-500'
                                        : 'border-slate-600',
                            ]"
                        >
                            <p class="whitespace-pre-wrap text-sm italic leading-relaxed text-slate-300">
                                "{{ item.rawGoal.personal_reason }}"
                            </p>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-4 flex items-center gap-3">
                            <div
                                class="relative h-1.5 flex-1 overflow-hidden rounded-full border border-slate-700/30 bg-slate-900"
                            >
                                <div
                                    class="absolute inset-y-0 left-0 h-full transition-all duration-1000 ease-out"
                                    :class="[
                                        item.level === 4
                                            ? 'bg-red-500'
                                            : item.level === 3
                                              ? 'bg-orange-500'
                                              : item.level === 2
                                                ? 'bg-amber-400'
                                                : 'bg-emerald-500',
                                    ]"
                                    :style="`width: ${item.progress}%`"
                                ></div>
                            </div>
                            <span class="w-10 text-right font-mono text-xs font-bold text-slate-400">
                                {{ item.progress }}%
                            </span>
                        </div>
                    </div>

                    <!-- Right: Deadline Stats -->
                    <div class="flex w-full flex-col justify-center bg-slate-800/20 p-5 md:w-1/3">
                        <div class="space-y-4">
                            <!-- Nearest Milestone -->
                            <div v-if="getNearestMilestoneInfo(item)">
                                <span
                                    class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                >
                                    Next Checkpoint
                                </span>
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold leading-snug text-slate-200">
                                        {{ getNearestMilestoneInfo(item).title }}
                                    </span>
                                    <span
                                        class="mt-0.5 font-mono text-xs"
                                        :class="[
                                            item.level === 4
                                                ? 'font-bold text-red-400'
                                                : item.level === 3
                                                  ? 'font-bold text-orange-400'
                                                  : 'text-emerald-300',
                                        ]"
                                    >
                                        {{ formatDate(getNearestMilestoneInfo(item).due_date) }}
                                        <span class="ml-1 text-[10px] font-normal uppercase opacity-80">
                                            ({{ item.stateMessage }})
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div v-else-if="item.progress < 100">
                                <span
                                    class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                >
                                    Next Checkpoint
                                </span>
                                <span class="text-xs italic text-slate-500">No checkpoints defined.</span>
                                <span
                                    class="mt-0.5 block font-mono text-xs"
                                    :class="[
                                        item.level === 4
                                            ? 'font-bold text-red-400'
                                            : item.level === 3
                                              ? 'font-bold text-orange-400'
                                              : 'text-emerald-300',
                                    ]"
                                >
                                    {{ item.stateMessage }}
                                </span>
                            </div>
                            <div v-else>
                                <span
                                    class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-green-500"
                                >
                                    Status
                                </span>
                                <span class="flex items-center gap-1 text-sm font-bold text-green-400">
                                    ✓ All milestones completed
                                </span>
                            </div>

                            <!-- Ultimate Deadline -->
                            <div class="border-t border-slate-700/50 pt-3">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="block text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                    >
                                        Goal Deadline
                                    </span>
                                    <div class="flex flex-col items-end">
                                        <span class="font-mono text-xs font-bold text-slate-300">
                                            🏁 {{ formatDate(item.rawGoal.deadline) }}
                                        </span>
                                        <span
                                            v-if="item.goalDaysLeft !== 9999"
                                            class="mt-0.5 text-[9px] font-bold uppercase tracking-widest"
                                            :class="[
                                                item.goalDaysLeft < 0 ? 'text-red-700' :
                                                item.goalDaysLeft === 0 ? 'text-red-500' :
                                                item.goalDaysLeft <= 2 ? 'text-orange-400' :
                                                item.goalDaysLeft <= 5 ? 'text-amber-400' :
                                                'text-slate-500'
                                            ]"
                                        >
                                            {{ formatDaysLeft(item.goalDaysLeft) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Link>
        </div>
        </div> <!-- end active tab -->

        <!-- COMPLETED TAB CONTENT -->
        <div v-if="activeTab === 'completed'" class="space-y-4">

            <!-- EMPTY STATE -->
            <div
                v-if="!completedGoals || completedGoals.length === 0"
                class="rounded-2xl border border-slate-700 bg-slate-800/50 py-12 text-center shadow-inner"
            >
                <div class="mb-3 text-4xl opacity-30">🏆</div>
                <h3 class="text-lg font-bold text-slate-400">No Completed Goals Yet.</h3>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                    Keep grinding. When you finish a goal, it'll be archived here.
                </p>
            </div>

            <!-- COMPLETED GOALS LIST -->
            <Link
                v-for="goal in completedGoals.data"
                :key="goal.id"
                :href="`/goals/${goal.id}`"
                class="group flex flex-col items-stretch overflow-hidden rounded-xl border border-slate-700/30 bg-slate-800/40 shadow transition-all hover:bg-slate-800/70"
            >
                <div class="flex items-center justify-between border-b border-slate-700/30 bg-slate-900/20 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="text-emerald-400 text-lg">✓</span>
                        <h4 class="text-lg font-bold text-slate-400 transition-colors group-hover:text-slate-200 line-through decoration-slate-600">
                            {{ goal.title }}
                        </h4>
                    </div>
                    <span class="shrink-0 rounded border border-emerald-700/30 bg-emerald-900/20 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest text-emerald-500">
                        CONQUERED
                    </span>
                </div>
                <div class="flex flex-col gap-3 p-5 md:flex-row md:items-center md:justify-between">
                    <p v-if="goal.personal_reason" class="text-sm italic text-slate-500 border-l-2 border-slate-700/50 pl-3 line-clamp-2">
                        "{{ goal.personal_reason }}"
                    </p>
                    <div class="flex shrink-0 flex-col items-end gap-1 text-xs text-slate-500">
                        <span class="font-medium">🏁 Deadline: <span class="text-slate-400">{{ formatDate(goal.deadline) }}</span></span>
                        <span v-if="goal.completed_at" class="text-emerald-600">✓ Completed: {{ formatDate(goal.completed_at) }}</span>
                        <span class="text-slate-600">
                            {{ (goal.milestones || []).filter(m => m.is_completed).length }}/{{ (goal.milestones || []).length }} checkpoints done
                        </span>
                    </div>
                </div>
            </Link>

            <!-- PAGINATION -->
            <div v-if="completedGoals.links && completedGoals.links.length > 3" class="mt-8 flex justify-center gap-2">
                <Link
                    v-for="(link, k) in completedGoals.links"
                    :key="k"
                    class="rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest transition-all"
                    :class="[
                        link.active ? 'bg-indigo-600 text-white shadow-lg' : 'bg-slate-800 text-slate-500 hover:bg-slate-700 hover:text-slate-300',
                        !link.url ? 'opacity-30 cursor-not-allowed' : ''
                    ]"
                    :href="link.url ? `${link.url}&tab=completed` : '#'"
                    v-html="link.label"
                    preserve-scroll
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.input-quest {
    @apply rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500;
}
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-2px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
