<script setup>
import { computed } from 'vue';

const props = defineProps({
    streakSummary: { type: Object, required: true },
    rankSummary: { type: Object, required: true },
    stats: { type: Object, required: true },
});

const metrics = computed(() => [
    {
        label: 'Current Streak',
        value: props.streakSummary?.current_streak ?? 0,
        sublabel: '🔥 Days',
        color: 'text-orange-400',
        glow: 'hover:border-orange-500/30 hover:shadow-orange-500/10',
        tint: 'from-orange-500/10 via-transparent to-transparent',
    },
    {
        label: 'Best Streak',
        value: props.streakSummary?.best_streak ?? props.stats?.best_streak ?? 0,
        sublabel: '🏆 Peak',
        color: 'text-yellow-400',
        glow: 'hover:border-amber-500/30 hover:shadow-amber-500/10',
        tint: 'from-amber-500/10 via-transparent to-transparent',
    },
    {
        label: '7D Active',
        value: props.stats?.active_days_7d ?? 0,
        sublabel: '⚡ Active',
        color: 'text-purple-400',
        glow: 'hover:border-purple-500/30 hover:shadow-purple-500/10',
        tint: 'from-purple-500/10 via-transparent to-transparent',
    },
    {
        label: '30D Active',
        value: props.stats?.active_days_30d ?? 0,
        sublabel: '📅 Days',
        color: 'text-cyan-400',
        glow: 'hover:border-cyan-500/30 hover:shadow-cyan-500/10',
        tint: 'from-cyan-500/10 via-transparent to-transparent',
    },
    {
        label: 'Rank',
        value: props.rankSummary?.current_rank ?? '—',
        isRank: true,
        sublabel: props.rankSummary?.current_rank && props.rankSummary?.current_rank !== '50+' ? '🏆 Global' : 'No Rank',
        color: 'text-indigo-400',
        glow: 'hover:border-indigo-500/30 hover:shadow-indigo-500/20',
        tint: 'from-indigo-500/12 via-transparent to-transparent',
    },
    {
        label: 'Total Quest Completed',
        value: props.stats?.total_quest_completions ?? 0,
        sublabel: '✅ Quests',
        color: 'text-slate-100',
        glow: 'hover:border-slate-500/70 hover:shadow-slate-500/10',
        tint: 'from-slate-400/10 via-transparent to-transparent',
    },
    {
        label: 'Best Day',
        value: props.stats?.best_day_count ?? 0,
        sublabel: '✨ In One Day',
        color: 'text-emerald-400',
        glow: 'hover:border-sky-500/30 hover:shadow-sky-500/10',
        tint: 'from-sky-500/10 via-transparent to-transparent',
    },
]);
</script>

<template>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7">
        <div
            v-for="item in metrics"
            :key="item.label"
            class="group relative flex flex-col items-center justify-center overflow-hidden rounded-2xl border border-slate-700 bg-slate-800/50 p-3 text-center shadow-sm transition-all duration-300 hover:scale-[1.02] hover:bg-slate-800 hover:shadow-lg"
            :class="item.glow"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-br opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                :class="item.tint"
            ></div>

            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                {{ item.label }}
            </span>

            <div class="relative mt-2 flex flex-col items-center justify-center gap-1">
                <span
                    class="text-xl font-black md:text-2xl"
                    :class="item.color"
                >
                    {{
                        item.isRank
                            ? (item.value !== '—' && item.value !== '50+' ? `#${item.value}` : item.value)
                            : item.value
                    }}
                </span>

                <span class="inline-flex items-center gap-1 text-xs text-slate-500 md:text-sm">
                    {{ item.sublabel }}
                </span>
            </div>
        </div>
    </div>
</template>
