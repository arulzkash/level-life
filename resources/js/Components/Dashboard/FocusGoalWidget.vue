<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { formatDate, getDaysLeft, getDaysLeftClass } from '@/Utils/dateFormatters';

const props = defineProps({
    focusGoal: { type: Object, required: true },
    nextMilestone: { type: Object, default: null },
});

const urgencyGlowClass = computed(() => {
    if (props.focusGoal.level === 4) return 'to-red-600';
    if (props.focusGoal.level === 3) return 'to-orange-600';
    if (props.focusGoal.level === 2) return 'to-amber-500';
    return 'to-emerald-600';
});

const urgencyBadgeClass = computed(() => {
    if (props.focusGoal.level === 4) return 'border-red-500/30 bg-red-500/10 text-red-400';
    if (props.focusGoal.level === 3) return 'border-orange-500/30 bg-orange-500/10 text-orange-400';
    if (props.focusGoal.level === 2) return 'border-amber-600/30 bg-amber-500/10 text-amber-400';
    return 'border-emerald-600/30 bg-emerald-500/10 text-emerald-400';
});

const milestoneDotClass = computed(() => {
    if (props.focusGoal.level === 4) return 'bg-red-500 shadow-[0_0_5px_rgba(239,68,68,0.8)]';
    if (props.focusGoal.level === 3) return 'bg-orange-500 shadow-[0_0_5px_rgba(249,115,22,0.8)]';
    if (props.focusGoal.level === 2) return 'bg-amber-500 shadow-[0_0_5px_rgba(251,191,36,0.8)]';
    return 'bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.8)]';
});

const progressBarClass = computed(() => {
    if (props.focusGoal.level === 4) return 'bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]';
    if (props.focusGoal.level === 3) return 'bg-orange-500 shadow-[0_0_8px_rgba(249,115,22,0.5)]';
    if (props.focusGoal.level === 2) return 'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]';
    return 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]';
});

const deadlineDaysClass = computed(() => {
    if (props.focusGoal.isGoalOverdue) return 'text-red-400';
    if (props.focusGoal.goalDaysLeft <= 2) return 'text-orange-400';
    if (props.focusGoal.goalDaysLeft <= 5) return 'text-amber-400';
    return 'text-emerald-400';
});

const navigateToGoal = () => {
    router.visit(`/goals/${props.focusGoal.id}`);
};
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 p-5 shadow-lg transition-all hover:border-slate-500 cursor-pointer"
        @click="navigateToGoal"
    >
        <!-- Visual Urgency Background Glow -->
        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-transparent opacity-10 transition-opacity group-hover:opacity-20"
            :class="[urgencyGlowClass]"
        ></div>

        <div class="relative z-10 mb-4 flex items-center justify-between">
            <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-300">
                <span class="text-base text-white">🎯</span> FOCUS GOAL
            </h3>
            <!-- URGENCY BADGE -->
            <div
                class="shrink-0 rounded border px-2 py-0.5 text-[10px] font-black uppercase tracking-widest shadow-sm"
                :class="focusGoal.colorClass || urgencyBadgeClass"
            >
                {{ focusGoal.stateName }}
            </div>
        </div>

        <div class="relative z-10">
            <h4 class="text-xl font-bold text-white transition-colors group-hover:text-indigo-300">
                {{ focusGoal.rawGoal.title }}
            </h4>

            <p
                v-if="focusGoal.rawGoal.personal_reason"
                class="mt-2 border-l-2 border-slate-700/60 pl-3 text-sm italic text-slate-400 whitespace-pre-wrap"
            >
                "{{ focusGoal.rawGoal.personal_reason }}"
            </p>

            <!-- Next Milestone Info -->
            <div
                v-if="nextMilestone"
                class="mt-4 rounded-lg border border-slate-700/50 bg-slate-900/60 p-3 shadow-inner"
            >
                <div class="mb-1 text-[10px] font-bold uppercase text-slate-500">Next Checkpoint</div>
                <div class="mb-2 flex items-start gap-2 text-sm font-semibold text-slate-200">
                    <span
                        class="mt-2 shrink-0 h-1.5 w-1.5 rounded-full"
                        :class="[milestoneDotClass]"
                    ></span>
                    <span class="leading-snug">{{ nextMilestone.title }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-slate-400">
                        Due: <span class="text-slate-300 font-bold">{{ formatDate(nextMilestone.due_date) }}</span>
                    </span>
                    <span
                        class="text-[10px] uppercase tracking-widest"
                        :class="getDaysLeftClass(nextMilestone.due_date)"
                    >
                        {{ getDaysLeft(nextMilestone.due_date) }}
                    </span>
                </div>
            </div>

            <!-- Overall Progress -->
            <div class="mt-5 space-y-1.5">
                <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-slate-500">
                    <span>{{ focusGoal.progress }}% Completed</span>
                    <div class="flex flex-col items-end">
                        <span class="text-slate-400 text-[9px]">
                            Target: {{ formatDate(focusGoal.rawGoal.deadline) }}
                        </span>
                        <span
                            class="text-[9px] font-black uppercase tracking-widest mt-0.5"
                            :class="[deadlineDaysClass]"
                        >
                            <template v-if="focusGoal.isGoalOverdue">
                                {{ Math.abs(focusGoal.goalDaysLeft) }} Days Overdue
                            </template>
                            <template v-else-if="focusGoal.goalDaysLeft === 0">
                                Due Today
                            </template>
                            <template v-else>
                                {{ focusGoal.goalDaysLeft }} Days Left
                            </template>
                        </span>
                    </div>
                </div>
                <!-- Progress Bar -->
                <div class="relative h-1.5 w-full overflow-hidden rounded-full border border-slate-700/50 bg-slate-900">
                    <div
                        class="absolute inset-y-0 left-0 h-full transition-all duration-1000 ease-out"
                        :class="[progressBarClass]"
                        :style="`width: ${focusGoal.progress}%`"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>
