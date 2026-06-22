<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    meta: Object,
});

const jumpPage = ref(props.meta.current_page);
const showJumpMobile = ref(false);

watch(() => props.meta.current_page, (newVal) => {
    jumpPage.value = newVal;
});

const decodePaginationLabel = (label) => String(label ?? '')
    .replaceAll('&laquo;', '«')
    .replaceAll('&raquo;', '»')
    .replaceAll('&amp;', '&');
const handleJump = () => {
    const page = parseInt(jumpPage.value);
    
    if (!page || page < 1 || page > props.meta.last_page) {
        jumpPage.value = props.meta.current_page; 
        return;
    }

    const currentParams = Object.fromEntries(new URLSearchParams(window.location.search));
    const newParams = { ...currentParams, page: page };

    router.get(props.meta.path, newParams, { 
        preserveState: true,
        preserveScroll: false
    });
    
    showJumpMobile.value = false;
};
</script>

<template>
    <div v-if="meta.last_page > 1" class="w-full mt-6">
        
        <div class="flex flex-col gap-3 sm:hidden">
            <div class="flex items-center justify-between bg-slate-800 p-2 rounded-lg border border-slate-700 shadow-lg">
                <Component
                    :is="meta.prev_page_url ? Link : 'button'"
                    :href="meta.prev_page_url"
                    :disabled="!meta.prev_page_url"
                    class="px-3 py-2 rounded-md text-xs font-bold uppercase tracking-wider transition-colors"
                    :class="meta.prev_page_url 
                        ? 'bg-slate-700 text-slate-300 hover:bg-indigo-600 hover:text-white' 
                        : 'bg-slate-900/50 text-slate-600 cursor-not-allowed'"
                >
                    Prev
                </Component>

                <div class="flex flex-col items-center cursor-pointer select-none" @click="showJumpMobile = !showJumpMobile">
                    <span class="text-[10px] uppercase text-slate-500 font-bold">Page</span>
                    <span class="text-sm font-bold text-white">
                        {{ meta.current_page }} <span class="text-slate-500">/</span> {{ meta.last_page }}
                    </span>
                </div>

                <Component
                    :is="meta.next_page_url ? Link : 'button'"
                    :href="meta.next_page_url"
                    :disabled="!meta.next_page_url"
                    class="px-3 py-2 rounded-md text-xs font-bold uppercase tracking-wider transition-colors"
                    :class="meta.next_page_url 
                        ? 'bg-slate-700 text-slate-300 hover:bg-indigo-600 hover:text-white' 
                        : 'bg-slate-900/50 text-slate-600 cursor-not-allowed'"
                >
                    Next
                </Component>
            </div>

            <div v-if="showJumpMobile" class="animate-fade-in flex items-center justify-between gap-2 bg-slate-800 p-2 rounded-lg border border-slate-700 shadow-xl">
                <span class="text-xs font-bold text-slate-400 pl-2">Go to:</span>
                <div class="flex gap-2">
                    <input 
                        type="number" 
                        v-model="jumpPage"
                        @keydown.enter="handleJump"
                        class="w-16 bg-slate-900 border border-slate-600 rounded px-2 py-1 text-center text-sm text-white focus:border-indigo-500 outline-none"
                        :min="1"
                        :max="meta.last_page"
                    >
                    <button 
                        @click="handleJump"
                        class="bg-indigo-600 active:bg-indigo-700 text-white px-4 py-1 rounded text-xs font-bold transition-colors"
                    >
                        GO
                    </button>
                </div>
            </div>
        </div>

        <div class="hidden sm:flex flex-col md:flex-row items-center justify-between gap-4 bg-slate-800/80 p-3 rounded-xl border border-slate-700/50 backdrop-blur-sm shadow-sm">
            
            <div class="text-xs text-slate-400 font-mono">
                SHOWING <span class="font-bold text-white">{{ meta.from }}-{{ meta.to }}</span> OF <span class="font-bold text-white">{{ meta.total }}</span>
            </div>

            <div class="flex items-center gap-4">
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    <template v-for="(link, k) in meta.links" :key="k">
                        <Component
                            :is="link.url ? Link : 'span'"
                            :href="link.url"
                            class="relative inline-flex items-center px-3 py-1.5 text-sm font-semibold focus:z-20 border transition-all duration-200"
                            :class="{
                                'z-10 bg-indigo-600 text-white border-indigo-500 shadow-[0_0_10px_rgba(79,70,229,0.4)]': link.active,
                                'text-slate-300 bg-slate-800 border-slate-600 hover:bg-slate-700 hover:text-white hover:border-slate-500': !link.active && link.url,
                                'text-slate-600 bg-slate-900/50 border-slate-700 cursor-default': !link.url,
                                'rounded-l-md': k === 0,
                                'rounded-r-md': k === meta.links.length - 1,
                            }"
                        >{{ decodePaginationLabel(link.label) }}</Component>
                    </template>
                </nav>

                <div class="flex items-center gap-1 border-l border-slate-700 pl-4 ml-2">
                    <span class="text-[10px] text-slate-500 uppercase font-bold mr-1">Go to</span>
                    <div class="relative flex items-center">
                        <input 
                            type="number" 
                            v-model="jumpPage"
                            @keydown.enter="handleJump"
                            class="w-14 bg-slate-900 border border-slate-600 rounded-l px-2 py-1 text-center text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-all placeholder-slate-600"
                            :min="1"
                            :max="meta.last_page"
                        >
                        <button 
                            @click="handleJump"
                            title="Jump to page"
                            class="bg-slate-700 hover:bg-indigo-600 border border-l-0 border-slate-600 rounded-r px-2 py-1 text-white transition-colors h-[30px] flex items-center justify-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>