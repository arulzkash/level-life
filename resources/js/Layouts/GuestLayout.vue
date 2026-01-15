<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-950 text-slate-300 relative overflow-hidden">
        
        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 pointer-events-none"></div>
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-indigo-600/20 rounded-full blur-[100px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-purple-600/20 rounded-full blur-[100px]"></div>

        <div class="relative z-10 mb-6 flex flex-col items-center">
            <Link href="/" class="group">
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center font-black text-4xl text-white shadow-[0_0_30px_rgba(99,102,241,0.5)] group-hover:scale-110 transition-transform duration-300">
                    R
                </div>
            </Link>
            <h1 class="mt-4 text-2xl font-black text-white tracking-tight">RPG Life</h1>
            <p class="text-sm text-slate-500">Gamify your existence.</p>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-8 py-8 bg-slate-900 border border-slate-800 shadow-2xl overflow-hidden sm:rounded-2xl relative z-10"
        >
            <slot />
        </div>
    </div>
</template>