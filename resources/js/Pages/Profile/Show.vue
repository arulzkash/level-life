<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

import ProfileSidebar from './Partials/ProfileSidebar.vue';
import ProfileStatsGrid from './Partials/ProfileStatsGrid.vue';
import ProfileHeatmapCard from './Partials/ProfileHeatmapCard.vue';
import ProfileBadgesCard from './Partials/ProfileBadgesCard.vue';

defineOptions({ layout: AppLayout });

defineProps({
    identity: { type: Object, required: true },
    streakSummary: { type: Object, required: true },
    rankSummary: { type: Object, required: true },
    stats: { type: Object, required: true },
    heatmap: { type: Object, required: true },
    badgeVault: { type: Object, required: true },
});
</script>

<template>
    <Head :title="`${identity.name} (@${identity.username})`" />

    <div class="relative min-h-screen overflow-hidden bg-[#0F172A] text-slate-200">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-24 left-[-8%] h-72 w-72 rounded-full bg-indigo-500/10 blur-[110px]"></div>
            <div class="absolute right-[-8%] top-28 h-80 w-80 rounded-full bg-cyan-400/10 blur-[130px]"></div>
            <div class="absolute bottom-[-12%] left-[24%] h-72 w-72 rounded-full bg-sky-500/8 blur-[120px]"></div>
        </div>

        <main class="relative mx-auto w-full max-w-[1400px] space-y-4 px-4 py-6 md:px-6">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <!-- Row 1 -->
                <div class="lg:col-span-3">
                    <ProfileSidebar :identity="identity" :streak-summary="streakSummary" :stats="stats" />
                </div>

                <div class="lg:col-span-9">
                    <ProfileStatsGrid
                        :streak-summary="streakSummary"
                        :rank-summary="rankSummary"
                        :stats="stats"
                    />
                </div>

                <!-- Row 2 -->
                <div class="lg:col-span-12">
                    <ProfileHeatmapCard :heatmap="heatmap" />
                </div>

                <!-- Row 3 -->
                <div class="lg:col-span-12">
                    <ProfileBadgesCard :badge-vault="badgeVault" />
                </div>
            </div>
        </main>
    </div>
</template>
