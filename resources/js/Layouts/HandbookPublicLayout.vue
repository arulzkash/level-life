<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const openAppHref = computed(() => page.props.auth?.user ? '/dashboard' : '/login');
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-200">
        <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/90 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <Link href="/" class="group flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-cyan-500 shadow-[0_0_25px_rgba(99,102,241,0.35)] transition-all duration-300 group-hover:scale-105 group-hover:shadow-[0_0_35px_rgba(34,211,238,0.4)]">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="h-6 w-6 -rotate-12 text-white drop-shadow-sm"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.007Zm-7.037 5.25a.75.75 0 0 1 0 1.5H2.25a.75.75 0 0 1 0-1.5h1.5Zm0 4.5a.75.75 0 0 1 0 1.5H2.25a.75.75 0 0 1 0-1.5h1.5ZM6 17.25a.75.75 0 0 1 0 1.5H2.25a.75.75 0 0 1 0-1.5H6Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>

                    <div>
                        <div class="text-lg font-black tracking-tight text-white transition-colors group-hover:text-cyan-300">
                            Level Life
                        </div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-indigo-300/80">
                            Public Handbook
                        </div>
                    </div>
                </Link>

                <div class="flex items-center gap-2">
                    <Link
                        :href="openAppHref"
                        class="rounded-full border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-300 transition hover:border-slate-500 hover:text-white"
                    >
                        Open App
                    </Link>
                </div>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <footer class="border-t border-slate-900/70 py-8 text-center text-xs text-slate-500">
            Shareable handbook for understanding how Level Life works.
        </footer>
    </div>
</template>
