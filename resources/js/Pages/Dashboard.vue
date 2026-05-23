<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { watch, ref, computed } from 'vue';
import LevelUpModal from '@/Components/Game/LevelUpModal.vue';
import DashboardHeroStats from '@/Components/Dashboard/DashboardHeroStats.vue';
import FocusGoalWidget from '@/Components/Dashboard/FocusGoalWidget.vue';
import QuestCreateForm from '@/Components/Dashboard/QuestCreateForm.vue';
import ActiveQuestList from '@/Components/Dashboard/ActiveQuestList.vue';
import HabitTrackerWidget from '@/Components/Dashboard/HabitTrackerWidget.vue';
import TimeBlockWidget from '@/Components/Dashboard/TimeBlockWidget.vue';
import { useVisualEffects } from '@/Composables/useVisualEffects.js';
import { useQuestActions } from '@/Composables/useQuestActions.js';
import { useQuestTypes } from '@/Composables/useQuestTypes.js';
import { useLevelUp } from '@/Composables/useLevelUp.js';
import { useAudio } from '@/Composables/useAudio.js';
import { useStreak } from '@/Composables/useStreak';
import { useGoalUrgency } from '@/Composables/useGoalUrgency';

defineOptions({ layout: AppLayout });

const props = defineProps({
    activeQuests: Array,
    activeGoals: Array,
    habits: Array,
    habitSummary: Object,
    today: String,
    journalTodayExists: Boolean,
    todayBlocks: Array,
    leaderboardData: Object,
    topBadge: Object,
    customQuestTypes: Object,
});

// --- Composables ---
const { playSfx } = useAudio();
const { showToast } = useVisualEffects();

const page = usePage();
const profile = computed(() => page.props.auth.profile);

const { createForm, isCustomType, submitQuest, completeQuest, toggleQuestStatus, reorderQuests, handleTypeChange, cancelCustomType } = useQuestActions(props);
const { showManageTypes, deleteCustomType, updateCustomTypeColor } = useQuestTypes(props.customQuestTypes, createForm);
const { showLevelUpModal } = useLevelUp(profile);
const { streakStatus, streakNumberClass, isRecoverable } = useStreak(profile, () => props.today);

// --- Goal Urgency ---
const { sortGoalsByUrgency } = useGoalUrgency();
const focusGoal = computed(() => {
    if (!props.activeGoals || props.activeGoals.length === 0) return null;
    return sortGoalsByUrgency(props.activeGoals)[0];
});
const nextFocusMilestone = computed(() => {
    if (!focusGoal.value || !focusGoal.value.rawGoal.milestones) return null;
    const pending = focusGoal.value.rawGoal.milestones.filter(m => !m.is_completed);
    if (!pending.length) return null;
    return pending.sort((a, b) => new Date(a.due_date) - new Date(b.due_date))[0];
});

// --- UI State ---
const showCreateQuestForm = ref(false);

const handleSubmitQuest = () => {
    submitQuest();
};

// Hide form when quest is successfully created (form name resets to empty)
watch(() => createForm.recentlySuccessful, (val) => {
    if (val) showCreateQuestForm.value = false;
});

// --- Watcher: clear due_date when repeatable ---
watch(() => createForm.is_repeatable, (val) => {
    if (val) createForm.due_date = null;
});

// --- Habit Toggle ---
const toggleHabit = (habit) => {
    const wasDone = habit.done_today;
    router.patch(`/habits/${habit.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => { if (!wasDone) playSfx('toggle-habit'); },
    });
};

// --- Timeblock ---
const timeblockForm = useForm({
    date: props.today,
    start_time: '09:00',
    end_time: '10:00',
    title: '',
    note: '',
});

const addTimeblock = (formData) => {
    timeblockForm.date = formData.date || props.today;
    timeblockForm.start_time = formData.start_time;
    timeblockForm.end_time = formData.end_time;
    timeblockForm.title = formData.title;
    timeblockForm.note = formData.note;
    timeblockForm.post('/timeblocks', {
        preserveScroll: true,
        onSuccess: () => {
            timeblockForm.reset('title', 'note');
            timeblockForm.date = props.today;
        },
    });
};

const deleteTimeblock = (id) => {
    if (confirm('Delete this timeblock?')) {
        router.delete(`/timeblocks/${id}`, { preserveScroll: true });
    }
};

// --- Streak status object for DashboardHeroStats ---
const streakStatusObj = computed(() => ({
    streakStatus: streakStatus.value,
    streakNumberClass: streakNumberClass.value,
    isRecoverable: isRecoverable.value,
}));
</script>

<template>
    <Head title="Command Center" />

    <div class="mx-auto max-w-7xl space-y-8 p-4 text-gray-200 md:p-8">
        <!-- Hero Stats Section -->
        <DashboardHeroStats
            v-if="profile"
            :profile="profile"
            :streak-status="streakStatusObj"
            :habit-summary="habitSummary"
            :leaderboard-data="leaderboardData"
            :top-badge="topBadge"
        />

        <!-- Focus Goal Widget (Mobile) -->
        <div v-if="focusGoal" class="block lg:hidden mb-8">
            <FocusGoalWidget :focus-goal="focusGoal" :next-milestone="nextFocusMilestone" />
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Main Content: Quest Section -->
            <div class="space-y-6 lg:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-700 pb-2">
                    <div>
                        <h3 class="flex items-center gap-2 text-xl font-bold uppercase tracking-widest text-slate-300">
                            <span>⚔️</span> Active Missions
                        </h3>
                        <span class="text-xs text-slate-500">{{ activeQuests?.length ?? 0 }} active</span>
                    </div>
                    <button
                        v-if="!showCreateQuestForm"
                        @click="showCreateQuestForm = true"
                        class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-lg transition-all hover:bg-indigo-500 active:scale-95"
                    >
                        <span>+ New Quest</span>
                    </button>
                </div>

                <!-- Quest Create Form -->
                <div v-if="showCreateQuestForm" class="animate-fade-in relative overflow-hidden rounded-2xl border border-indigo-500/50 bg-slate-800 p-6 shadow-2xl">
                    <div class="mb-4 flex items-start justify-between">
                        <h4 class="text-lg font-bold text-white">Summon New Quest</h4>
                        <button @click="showCreateQuestForm = false" class="text-slate-400 transition-colors hover:text-white">✕</button>
                    </div>
                    <QuestCreateForm
                        :create-form="createForm"
                        :is-custom-type="isCustomType"
                        :custom-quest-types="customQuestTypes"
                        :show-manage-types="showManageTypes"
                        @submit="handleSubmitQuest"
                        @type-change="handleTypeChange"
                        @cancel-custom="cancelCustomType"
                        @toggle-manage-types="showManageTypes = !showManageTypes"
                        @delete-type="deleteCustomType"
                        @update-type-color="updateCustomTypeColor"
                        @cancel="showCreateQuestForm = false"
                    />
                </div>

                <!-- Empty State -->
                <div v-if="!activeQuests || activeQuests.length === 0" class="rounded-2xl border-2 border-dashed border-slate-700 bg-slate-800/30 py-12 text-center">
                    <p class="italic text-slate-500">"The quest board is empty. Adventure awaits!"</p>
                    <button @click="showCreateQuestForm = true" class="mt-2 text-sm text-indigo-400 underline hover:text-indigo-300">Create one now</button>
                </div>

                <!-- Active Quest List -->
                <ActiveQuestList
                    v-if="activeQuests && activeQuests.length > 0"
                    :quests="activeQuests"
                    :custom-quest-types="customQuestTypes"
                    @complete="completeQuest"
                    @toggle-status="toggleQuestStatus"
                    @reorder="reorderQuests"
                />
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                <!-- Focus Goal Widget (Desktop) -->
                <div v-if="focusGoal" class="hidden lg:block">
                    <FocusGoalWidget :focus-goal="focusGoal" :next-milestone="nextFocusMilestone" />
                </div>

                <!-- Habit Tracker -->
                <HabitTrackerWidget :habits="habits ?? []" @toggle="toggleHabit" />

                <!-- Timeblock Widget -->
                <TimeBlockWidget :blocks="todayBlocks ?? []" :today="today" @add="addTimeblock" @delete="deleteTimeblock" />

                <!-- Reflection Card -->
                <div class="rounded-2xl border border-slate-700 bg-slate-800 p-6 shadow-lg">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 font-bold text-white"><span>✍</span> Reflection</h3>
                        <div class="flex items-center gap-3">
                            <span class="rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                :class="journalTodayExists ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-slate-600 bg-slate-900 text-slate-400'">
                                {{ journalTodayExists ? 'LOGGED TODAY' : 'NOT YET LOGGED' }}
                            </span>
                            <Link href="/journal" class="text-xs text-indigo-400 hover:underline">Open Log</Link>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Write a short insight or story from today.</p>
                </div>
            </div>
        </div>

        <LevelUpModal v-model="showLevelUpModal" :current-level="profile?.level_data?.current_level || 1" />
    </div>
</template>

<style scoped>
input[type='time']::-webkit-calendar-picker-indicator,
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}
</style>
