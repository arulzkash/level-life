<script setup>
defineProps({
    viewOptions: {
        type: Array,
        required: true,
    },
    currentView: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['update:currentView']);
</script>

<template>
    <div class="scrollbar-hide mask-linear-x flex gap-2 overflow-x-auto px-1 pb-1 md:pb-0">
        <button
            v-for="v in viewOptions"
            :key="v.key"
            @click="emit('update:currentView', v.key)"
            class="group relative flex shrink-0 items-center gap-1.5 overflow-hidden rounded-full border px-3 py-1.5 text-[11px] font-black uppercase tracking-wider transition-all duration-300"
            :class="
                currentView === v.key
                    ? 'border-indigo-400/50 bg-indigo-500/20 text-indigo-100 shadow-[0_0_15px_rgba(99,102,241,0.28)]'
                    : 'border-slate-700/50 bg-slate-900/60 text-slate-400 hover:border-slate-500 hover:bg-slate-800 hover:text-slate-200'
            "
        >
            <div
                class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/10 to-transparent transition-transform duration-700 group-hover:translate-x-full"
                :class="{ 'translate-x-full': currentView === v.key }"
            ></div>
            <span class="relative z-10 drop-shadow-sm filter">{{ v.icon }}</span>
            <span class="relative z-10 hidden sm:inline">{{ v.label }}</span>
            <span class="relative z-10 sm:hidden">{{ v.mobileLabel }}</span>
        </button>
    </div>
</template>
