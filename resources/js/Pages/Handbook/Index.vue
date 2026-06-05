<script setup>
import { Head } from '@inertiajs/vue3';
import HandbookReader from '@/Components/Handbook/HandbookReader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    markdown: { type: String, default: '' },
    isMissing: { type: Boolean, default: false },
    handbooks: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="Handbook" />
    <HandbookReader
        :markdown="markdown"
        :is-missing="isMissing"
        :handbooks="handbooks"
    />
</template>
