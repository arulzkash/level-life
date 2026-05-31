<script setup>
import { Head } from '@inertiajs/vue3';
import HandbookReader from '@/Components/Handbook/HandbookReader.vue';
import HandbookPublicLayout from '@/Layouts/HandbookPublicLayout.vue';

defineOptions({ layout: HandbookPublicLayout });

defineProps({
    markdown: { type: String, default: '' },
    isMissing: { type: Boolean, default: false },
});
</script>

<template>
    <Head title="Level Life — The Player’s Handbook">
        <meta
            name="description"
            content="Turn your life into an RPG. Build consistency, track quests, habits, goals, XP, streaks, and personal progress with Level Life."
        >
        <meta property="og:title" content="Level Life — The Player’s Handbook">
        <meta
            property="og:description"
            content="Turn your life into an RPG. Build consistency, track quests, habits, goals, XP, streaks, and personal progress with Level Life."
        >
        <meta property="og:type" content="website">
        <meta property="og:url" content="/">
        <link rel="canonical" href="/">
    </Head>
    <HandbookReader
        :markdown="markdown"
        :is-missing="isMissing"
    />
</template>
