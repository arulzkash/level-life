<script setup>
defineProps({
    modelValue: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const MOOD_OPTIONS = ['😴', '😐', '🙂', '😀', '🤩', '🔥', '🧠', '😵‍💫', '😢', '😡'];

const selectMood = (emoji) => {
    emit('update:modelValue', emoji);
};
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-[10px] font-bold uppercase text-slate-500">Mood</span>
        <div class="flex flex-wrap gap-1">
            <button
                v-for="emoji in MOOD_OPTIONS"
                :key="emoji"
                @click="selectMood(emoji)"
                type="button"
                class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 text-xl transition-all hover:-translate-y-0.5 hover:border-sky-500 hover:bg-slate-700 hover:shadow-lg hover:shadow-sky-500/20 active:scale-95"
                :class="{
                    'border-sky-500 bg-sky-500/20 shadow-[0_0_10px_rgba(14,165,233,0.3)] ring-2 ring-sky-500':
                        modelValue === emoji,
                }"
            >
                <span v-twemoji="emoji"></span>
            </button>
        </div>
    </div>
</template>
