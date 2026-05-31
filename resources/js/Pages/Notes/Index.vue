<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

defineOptions({ layout: AppLayout });

const props = defineProps({
    notes: Object, // Paginated object: { data, current_page, links, total, ... }
    query: String, // current search query
});

const searchQuery = ref(props.query ?? '');

watch(
    () => props.query,
    (v) => {
        const next = v ?? '';
        if (next !== searchQuery.value) searchQuery.value = next;
    }
);

const runSearch = debounce(() => {
    router.get(
        '/notes',
        { q: searchQuery.value || undefined },
        { preserveScroll: true, preserveState: true }
    );
}, 500);

watch(searchQuery, () => runSearch());

const clearSearch = () => {
    searchQuery.value = '';
};

const deleteNote = (id, title) => {
    if (confirm(`Are you sure you want to delete note "${title}"?`)) {
        router.delete(`/notes/${id}`, { preserveScroll: true });
    }
};

// Helper colors for dynamic styles
const colorClasses = {
    slate: {
        border: 'border-l-slate-500 hover:border-slate-400',
        bg: 'bg-slate-950/20 hover:bg-slate-900/30',
        shadow: 'hover:shadow-slate-500/5',
        text: 'text-slate-400',
        glow: 'border-slate-800 focus:border-slate-500'
    },
    indigo: {
        border: 'border-l-indigo-500 hover:border-indigo-400',
        bg: 'bg-indigo-950/15 hover:bg-indigo-900/25',
        shadow: 'hover:shadow-indigo-500/10 hover:shadow-lg',
        text: 'text-indigo-400',
        glow: 'border-indigo-900/50 focus:border-indigo-500'
    },
    emerald: {
        border: 'border-l-emerald-500 hover:border-emerald-400',
        bg: 'bg-emerald-950/15 hover:bg-emerald-900/25',
        shadow: 'hover:shadow-emerald-500/10 hover:shadow-lg',
        text: 'text-emerald-400',
        glow: 'border-emerald-900/50 focus:border-emerald-500'
    },
    amber: {
        border: 'border-l-amber-500 hover:border-amber-400',
        bg: 'bg-amber-950/15 hover:bg-amber-900/25',
        shadow: 'hover:shadow-amber-500/10 hover:shadow-lg',
        text: 'text-amber-400',
        glow: 'border-amber-900/50 focus:border-amber-500'
    },
    rose: {
        border: 'border-l-rose-500 hover:border-rose-400',
        bg: 'bg-rose-950/15 hover:bg-rose-900/25',
        shadow: 'hover:shadow-rose-500/10 hover:shadow-lg',
        text: 'text-rose-400',
        glow: 'border-rose-900/50 focus:border-rose-500'
    },
    sky: {
        border: 'border-l-sky-500 hover:border-sky-400',
        bg: 'bg-sky-950/15 hover:bg-sky-900/25',
        shadow: 'hover:shadow-sky-500/10 hover:shadow-lg',
        text: 'text-sky-400',
        glow: 'border-sky-900/50 focus:border-sky-500'
    }
};
</script>

<template>
    <Head title="Notes Library" />

    <div class="min-h-screen bg-slate-900 pb-20 text-slate-200">
        <!-- Sticky Header -->
        <div class="sticky top-0 z-40 border-b border-slate-800 bg-slate-900/80 backdrop-blur-md">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 md:py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-900 text-xl shadow-lg shadow-slate-950/40 md:h-11 md:w-11 md:text-2xl">
                        📝
                    </div>
                    <div>
                        <div class="text-lg font-black tracking-tight text-white md:text-2xl">Notes Library</div>
                        <div class="hidden text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500 md:block">
                            Save references, brainstorm goals, and store outlines.
                        </div>
                    </div>
                </div>

                <Link
                    href="/notes/create"
                    class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-sky-500/15 hover:bg-sky-500 transition-all active:scale-95"
                >
                    + New Note
                </Link>
            </div>
        </div>

        <div class="mx-auto mt-6 max-w-4xl px-4">
            <!-- Search & Count Bar -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative flex-1">
                    <input
                        v-model="searchQuery"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950/40 px-4 py-3 pl-10 text-sm text-slate-200 placeholder-slate-500 outline-none transition-all focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                        placeholder="Search title, body content, or section modules..."
                    />
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm">🔍</span>
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg border border-slate-700 bg-slate-900 px-2 py-1 text-[10px] font-bold text-slate-300 hover:bg-slate-800"
                    >
                        CLEAR
                    </button>
                </div>
                <div class="text-[11px] font-bold uppercase tracking-widest text-slate-500">
                    Total Notes: <span class="text-white font-mono text-sm">{{ notes.total }}</span>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="notes.data.length === 0" class="rounded-2xl border border-dashed border-slate-800 bg-slate-950/10 p-12 text-center">
                <div class="text-4xl mb-3">📁</div>
                <div class="text-lg font-black text-white">No notes found</div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ searchQuery ? 'Try adjusting your search criteria.' : 'Create your first note to start documenting your journey!' }}
                </p>
                <Link
                    v-if="!searchQuery"
                    href="/notes/create"
                    class="mt-4 inline-flex items-center gap-1 rounded-lg bg-slate-800 border border-slate-700 px-4 py-2 text-xs font-bold text-slate-300 hover:bg-slate-700 hover:text-white"
                >
                    Create Note
                </Link>
            </div>

            <!-- Notes List -->
            <div v-else class="space-y-4">
                <Link
                    v-for="note in notes.data"
                    :key="note.id"
                    :href="`/notes/${note.id}`"
                    class="group relative flex overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/15 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-900/30 hover:border-slate-700"
                    :class="[
                        colorClasses[note.color]?.border,
                        colorClasses[note.color]?.bg,
                        colorClasses[note.color]?.shadow,
                        note.is_pinned ? 'ring-1 ring-sky-500/20' : ''
                    ]"
                >
                    <!-- Left Accent Color strip -->
                    <div class="w-1.5 shrink-0 rounded-l-2xl border-l-4" :class="colorClasses[note.color]?.border"></div>

                    <!-- Card Body -->
                    <div class="flex-1 min-w-0 p-4 md:p-5 pr-12">
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Static Pin Indicator -->
                            <span v-if="note.is_pinned" class="text-xs text-sky-400 drop-shadow-[0_0_8px_rgba(56,189,248,0.5)]" title="Pinned Note">📌</span>

                            <h3 class="text-base font-black text-white md:text-lg truncate group-hover:text-sky-300 transition-colors">
                                {{ note.title }}
                            </h3>
                        </div>

                        <!-- Date modified -->
                        <div class="mt-1 flex items-center gap-1.5 font-mono text-[10px] text-slate-500">
                            <span>Edited {{ note.updated_at_human }}</span>
                        </div>

                        <!-- Note Snippet -->
                        <p class="mt-2 line-clamp-3 text-xs leading-relaxed text-slate-400 md:text-sm whitespace-pre-wrap">
                            {{ note.headline || 'Empty note content...' }}
                        </p>
                    </div>

                    <!-- Floating Actions (Trash) -->
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <button
                            @click.prevent.stop="deleteNote(note.id, note.title)"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-500/20 bg-red-950/20 text-xs font-bold text-red-400 transition hover:bg-red-900/40 hover:text-white"
                            title="Delete Note"
                        >
                            🗑️
                        </button>
                    </div>
                </Link>
            </div>

            <!-- PAGINATION -->
            <div v-if="notes.links && notes.links.length > 3" class="mt-8 flex justify-center gap-2">
                <Link
                    v-for="(link, k) in notes.links"
                    :key="k"
                    class="rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest transition-all"
                    :class="[
                        link.active ? 'bg-sky-600 text-white shadow-lg shadow-sky-500/20' : 'bg-slate-800 text-slate-500 hover:bg-slate-700 hover:text-slate-300',
                        !link.url ? 'opacity-30 cursor-not-allowed' : ''
                    ]"
                    :href="link.url ? link.url : '#'"
                    v-html="link.label"
                    preserve-scroll
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
