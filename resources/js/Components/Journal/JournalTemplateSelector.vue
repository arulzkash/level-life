<script setup>
defineProps({
    templates: {
        type: Array,
        default: () => [],
    },
    customTemplates: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['select-template', 'save-as-template', 'delete-template']);

const selectTemplate = (template) => {
    emit('select-template', template);
};

const saveAsTemplate = () => {
    emit('save-as-template');
};

const deleteTemplate = (tpl) => {
    emit('delete-template', tpl);
};
</script>

<template>
    <div
        class="rounded-xl border border-slate-700 bg-slate-800/70 p-4 shadow-inner transition-all hover:border-slate-600 hover:shadow-sky-500/10"
    >
        <div class="mb-4 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-300">Available Blueprints</span>
            <button
                @click="saveAsTemplate"
                class="text-[10px] text-slate-500 underline hover:text-white"
            >
                + Save current as template
            </button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
            <button
                v-for="t in templates"
                :key="t.id"
                @click="selectTemplate(t)"
                class="group flex flex-col items-start rounded-lg border border-slate-700 bg-slate-800/80 p-3 text-left transition-all hover:border-sky-500 hover:bg-slate-700 hover:shadow-md hover:shadow-sky-500/10"
            >
                <span class="font-bold text-slate-200 group-hover:text-white">
                    {{ t.name }}
                </span>
                <span class="text-[10px] text-slate-500">
                    {{ t.sections.length }} sections
                </span>
            </button>
        </div>

        <div v-if="customTemplates.length > 0" class="mt-4 border-t border-slate-800 pt-4">
            <p class="mb-2 text-xs font-bold text-slate-500">Manage Custom</p>
            <div class="flex flex-wrap gap-2">
                <div
                    v-for="t in customTemplates"
                    :key="t.id"
                    class="flex items-center gap-2 rounded border border-slate-800 bg-slate-900 px-2 py-1 text-xs"
                >
                    <span class="text-slate-300">{{ t.name }}</span>
                    <button
                        @click="deleteTemplate(t)"
                        class="font-bold text-red-500 hover:text-red-400"
                    >
                        ×
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
