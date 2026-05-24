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
    <Head title="Level Life Handbook" />
    <HandbookReader
        :markdown="markdown"
        :is-missing="isMissing"
    />
</template>
