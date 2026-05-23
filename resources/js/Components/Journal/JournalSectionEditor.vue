<script setup>
defineProps({
    sections: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['add', 'remove', 'reorder', 'update']);

const onMoveUp = (idx) => {
    if (idx <= 0) return;
    emit('reorder', idx, idx - 1);
};

const onMoveDown = (idx, total) => {
    if (idx >= total - 1) return;
    emit('reorder', idx, idx + 1);
};

const onRemove = (idx) => {
    emit('remove', idx);
};

const onUpdateTitle = (idx, value) => {
    emit('update', idx, 'title', value);
};

const onUpdateContent = (idx, value) => {
    emit('update', idx, 'content', value);
};

const onAdd = () => {
    emit('add');
};
</script>

<template>
    <div class="space-y-4">
        <transition-group name="list" tag="div" class="space-y-4">
            <div
                v-for="(section, idx) in sections"
                :key="section.id"
                :id="`sec-${section.id}`"
                class="group relative overflow-hidden rounded-xl border border-slate-700 bg-slate-800/70 transition-all hover:-translate-y-0.5 hover:border-slate-600 hover:shadow-md hover:shadow-sky-500/10"
            >
                <div class="flex items-center gap-3 bg-slate-900/50 px-4 py-2">
                    <div
                        class="flex flex-col gap-0.5 opacity-50 transition-opacity group-hover:opacity-100"
                    >
                        <button
                            @click="onMoveUp(idx)"
                            class="text-[8px] text-slate-400 hover:text-white"
                        >
                            ▲
                        </button>
                        <button
                            @click="onMoveDown(idx, sections.length)"
                            class="text-[8px] text-slate-400 hover:text-white"
                        >
                            ▼
                        </button>
                    </div>
                    <input
                        :value="section.title"
                        @input="onUpdateTitle(idx, $event.target.value)"
                        class="w-full border-none bg-transparent p-0 text-sm font-bold uppercase tracking-wider text-sky-200 placeholder-slate-600 focus:ring-0"
                        placeholder="SECTION TITLE"
                    />
                    <button
                        @click="onRemove(idx)"
                        class="text-slate-600 transition-colors hover:text-red-400"
                    >
                        🗑️
                    </button>
                </div>

                <textarea
                    :value="section.content"
                    @input="onUpdateContent(idx, $event.target.value)"
                    rows="3"
                    class="w-full resize-y border-none bg-transparent px-4 py-3 text-sm text-slate-300 placeholder-slate-600 focus:ring-0"
                    placeholder="Write details..."
                ></textarea>
            </div>
        </transition-group>

        <button
            @click="onAdd"
            class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-700 bg-slate-800/50 py-4 text-sm font-bold text-slate-500 transition-all hover:border-slate-500 hover:bg-slate-800 hover:text-slate-300"
        >
            <span>+ Add New Section</span>
        </button>
    </div>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
    transition: all 0.4s ease;
}
.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
textarea {
    field-sizing: content;
}
</style>
