<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { formatDate, heatLevelClass } from './profileFormatters';
import { PROFILE_COPY } from '@/Utils/featureCopy';

const props = defineProps({
    heatmap: {
        type: Object,
        required: true,
    },
});

const heatWeeks = computed(() => props.heatmap?.weeks ?? []);
const heatLegend = computed(() => props.heatmap?.legend ?? []);

const dayLabelRows = [
    { short: 'Mon', full: 'Monday' },
    { short: '', full: 'Tuesday' },
    { short: 'Wed', full: 'Wednesday' },
    { short: '', full: 'Thursday' },
    { short: 'Fri', full: 'Friday' },
    { short: '', full: 'Saturday' },
    { short: '', full: 'Sunday' },
];

// 1. Buat ref untuk menargetkan container yang bisa di-scroll
const heatmapScrollContainer = ref(null);
const heatmapCardBody = ref(null);
const tooltipRef = ref(null);
const selectedDay = ref(null);
const selectedDayStyle = ref({});
const selectedArrowStyle = ref({});
const selectedTooltipPlacement = ref('top');

// 2. Jalankan fungsi scroll saat komponen selesai dimuat (mounted)
onMounted(() => {
    if (heatmapScrollContainer.value) {
        // Geser posisi scroll horizontal (scrollLeft) sejauh total lebar konten (scrollWidth)
        heatmapScrollContainer.value.scrollLeft = heatmapScrollContainer.value.scrollWidth;
    }

    document.addEventListener('pointerdown', handleOutsidePointerDown, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', handleOutsidePointerDown, true);
});

const dayTooltip = (day) => {
    const label = formatDate(day.date, day.date);

    if (day.is_future) {
        return `${label} - future`;
    }

    const questWord = day.count === 1 ? 'quest' : 'quests';

    return `${label} - ${day.count} ${questWord} completed`;
};

const showDayDetails = (day, event) => {
    if (day.is_future) {
        selectedDay.value = null;
        selectedDayStyle.value = {};
        selectedArrowStyle.value = {};
        selectedTooltipPlacement.value = 'top';

        return;
    }

    if (selectedDay.value?.date === day.date) {
        selectedDay.value = null;
        selectedDayStyle.value = {};
        selectedArrowStyle.value = {};
        selectedTooltipPlacement.value = 'top';

        return;
    }

    selectedDay.value = day;

    if (! event?.currentTarget || ! heatmapCardBody.value) {
        selectedDayStyle.value = {};
        selectedArrowStyle.value = {};
        selectedTooltipPlacement.value = 'top';

        return;
    }

    const buttonRect = event.currentTarget.getBoundingClientRect();
    const bodyRect = heatmapCardBody.value.getBoundingClientRect();

    const tooltipWidth = 168;
    const tooltipHeight = 64;
    const buttonCenter = buttonRect.left - bodyRect.left + (buttonRect.width / 2);
    const desiredLeft = buttonRect.left - bodyRect.left + (buttonRect.width / 2) - (tooltipWidth / 2);
    const desiredTop = buttonRect.top - bodyRect.top - tooltipHeight - 10;
    const desiredBottomTop = buttonRect.bottom - bodyRect.top + 10;

    const maxLeft = Math.max(8, bodyRect.width - tooltipWidth - 8);
    const left = Math.min(Math.max(8, desiredLeft), maxLeft);
    const showBelow = desiredTop < 8;
    const top = showBelow ? desiredBottomTop : desiredTop;

    selectedDayStyle.value = {
        left: `${left}px`,
        top: `${top}px`,
    };

    selectedArrowStyle.value = {
        left: `${Math.min(Math.max(14, buttonCenter - left - 5), tooltipWidth - 18)}px`,
    };
    selectedTooltipPlacement.value = showBelow ? 'bottom' : 'top';
};

const closeDayDetails = () => {
    selectedDay.value = null;
    selectedDayStyle.value = {};
    selectedArrowStyle.value = {};
    selectedTooltipPlacement.value = 'top';
};

const handleOutsidePointerDown = (event) => {
    if (! selectedDay.value) {
        return;
    }

    const target = event.target;

    if (tooltipRef.value?.contains(target)) {
        return;
    }

    if (target?.closest?.('[data-heatmap-day="true"]')) {
        return;
    }

    closeDayDetails();
};
</script>

<template>
    <section class="group relative flex w-full min-w-0 flex-1 flex-col overflow-hidden rounded-xl border border-slate-700 bg-slate-800/80 p-4 shadow-lg shadow-black/20 transition-all duration-300 hover:-translate-y-0.5 hover:border-cyan-400/20 hover:bg-slate-800/90 hover:shadow-[0_18px_50px_rgba(34,211,238,0.10)] md:p-5">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-cyan-500/8 via-transparent to-sky-500/8 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

        <div class="relative mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-lg font-black text-white">52-Week Quest Heatmap</h2>
                <p class="mt-0.5 text-xs text-slate-400">Daily completion history over the last 52 weeks.</p>
                <p class="mt-1 text-xs text-slate-500">{{ PROFILE_COPY.heatmapReflection }}</p>
            </div>

            <div class="flex items-center gap-1.5 text-[10px] font-medium text-slate-400">
                <span>Less</span>
                <div v-for="item in heatLegend" :key="item.label" class="h-3 w-3 rounded-sm transition duration-300 group-hover:scale-105" :class="heatLevelClass(item.level)"></div>
                <span>More</span>
            </div>
        </div>

        <div ref="heatmapCardBody" class="relative flex-1 rounded-lg bg-slate-900/50 p-3 ring-1 ring-inset ring-white/5 transition duration-300 group-hover:bg-slate-900/65 group-hover:ring-cyan-400/10 md:p-4" @click.self="closeDayDetails">
            <button
                v-if="selectedDay"
                type="button"
                class="absolute inset-0 z-[5] md:hidden"
                aria-label="Close heatmap day details"
                @click="closeDayDetails"
            ></button>

            <div 
            ref="heatmapScrollContainer"
            class="heatmap-scroll w-full overflow-x-auto overflow-y-hidden"
            @scroll="closeDayDetails"
            >
                <div class="mx-auto w-fit min-w-max">
                    <div class="flex gap-2 md:gap-3">
                        <div class="w-8 shrink-0"></div> <div class="flex gap-1">
                            <div
                                v-for="week in heatWeeks"
                                :key="`month-${week.week_start}`"
                                class="w-[12px] text-center text-[9px] font-bold uppercase tracking-wide text-slate-500"
                            >
                                {{ week.month_label }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 flex gap-2 md:gap-3">
                        <div class="flex w-8 shrink-0 flex-col gap-1 pt-[1px] text-[9px] font-bold uppercase tracking-wide text-slate-500">
                            <div v-for="label in dayLabelRows" :key="label.full" class="flex h-[12px] items-center">
                                {{ label.short }}
                            </div>
                        </div>

                        <div class="flex gap-1">
                            <div v-for="week in heatWeeks" :key="week.week_start" class="flex flex-col gap-1">
                                <button
                                    v-for="day in week.days"
                                    :key="day.date"
                                    type="button"
                                    data-heatmap-day="true"
                                    class="relative z-10 h-[12px] w-[12px] rounded-[3px] transition duration-200 hover:scale-125"
                                    :class="[
                                        heatLevelClass(day.level, day.is_future),
                                        day.is_today ? 'ring-1 ring-cyan-200/90 ring-offset-1 ring-offset-slate-900 shadow-[0_0_6px_rgba(103,232,249,0.22)]' : '',
                                        selectedDay?.date === day.date ? 'scale-125 ring-1 ring-sky-300/90 ring-offset-1 ring-offset-slate-900' : '',
                                    ]"
                                    :title="dayTooltip(day)"
                                    :aria-label="dayTooltip(day)"
                                    @click="showDayDetails(day, $event)"
                                ></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <transition name="mobile-tooltip">
                <div
                    v-if="selectedDay"
                    ref="tooltipRef"
                    class="absolute z-20 w-[168px] rounded-lg border border-cyan-400/25 bg-slate-950/95 px-3 py-2 text-xs text-slate-300 shadow-[0_0_20px_rgba(34,211,238,0.12)] md:hidden"
                    :style="selectedDayStyle"
                >
                    <div
                        class="absolute h-3 w-3 rotate-45 bg-slate-950/95"
                        :class="
                            selectedTooltipPlacement === 'bottom'
                                ? '-top-1.5 border-l border-t border-cyan-400/25'
                                : '-bottom-1.5 border-b border-r border-cyan-400/25'
                        "
                        :style="selectedArrowStyle"
                    ></div>
                    <div class="font-bold text-cyan-200">{{ formatDate(selectedDay.date, selectedDay.date) }}</div>
                    <div class="mt-1">
                        {{ selectedDay.count }}
                        {{ selectedDay.count === 1 ? 'quest completed' : 'quests completed' }}
                    </div>
                    <div class="mt-1 text-[10px] text-slate-500">Tap the same day again to close.</div>
                </div>
            </transition>
        </div>
    </section>
</template>

<style scoped>
.heatmap-scroll {
    scrollbar-width: none;
    -ms-overflow-style: none;
    -webkit-overflow-scrolling: touch;
}

.heatmap-scroll::-webkit-scrollbar {
    display: none;
}

.mobile-tooltip-enter-active,
.mobile-tooltip-leave-active {
    transition:
        opacity 0.18s ease,
        transform 0.18s ease;
}

.mobile-tooltip-enter-from,
.mobile-tooltip-leave-to {
    opacity: 0;
    transform: translateY(4px) scale(0.98);
}
</style>
