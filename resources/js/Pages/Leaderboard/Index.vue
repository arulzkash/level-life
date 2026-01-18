<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const props = defineProps({
    items: Array,
    me: Object,
});
</script>

<template>
    <Head title="Leaderboard" />

    <div class="mx-auto max-w-4xl space-y-6 p-4 text-slate-200 md:p-8">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-black text-white">🏆 Leaderboard</h1>
        </div>

        <div v-if="me" class="rounded-xl border border-slate-700 bg-slate-800 p-4">
            <div class="text-xs font-bold uppercase text-slate-400">You</div>
            <div class="mt-2 flex items-center justify-between">
                <div class="font-bold text-white">#{{ me.rank }} — {{ me.user.name }}</div>
                <div class="text-sm text-slate-300">
                    Current:
                    <span class="font-bold text-orange-400">{{ me.streak_current }}</span>
                    • Best:
                    <span class="font-bold text-indigo-400">{{ me.streak_best }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-700 bg-slate-800">
            <div
                class="grid grid-cols-12 bg-slate-900/50 px-4 py-3 text-xs font-bold uppercase text-slate-400"
            >
                <div class="col-span-2">Rank</div>
                <div class="col-span-6">Player</div>
                <div class="col-span-2 text-right">Current</div>
                <div class="col-span-2 text-right">Best</div>
            </div>

            <div
                v-for="row in items"
                :key="row.user.id"
                class="grid grid-cols-12 border-t border-slate-700 px-4 py-3 text-sm"
            >
                <div class="col-span-2 font-mono text-slate-400">#{{ row.rank }}</div>
                <div class="col-span-6 truncate font-bold text-white">{{ row.user.name }}</div>
                <div class="col-span-2 text-right font-bold text-orange-400">{{ row.streak_current }}</div>
                <div class="col-span-2 text-right font-bold text-indigo-400">{{ row.streak_best }}</div>
            </div>
        </div>
    </div>
</template>
