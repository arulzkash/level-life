<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const page = usePage();
const user = computed(() => page.props.auth.user);
const profile = computed(() => page.props.auth.profile);

// State untuk Mobile Menu (Buka/Tutup)
const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-900 text-slate-300 font-sans">
        
        <nav class="sticky top-0 z-50 w-full bg-slate-900/95 backdrop-blur-md border-b border-slate-700/50 shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    
                    <div class="flex items-center gap-4">
                        
                        <div class="-ml-2 flex items-center md:hidden">
                            <button 
                                @click="showingNavigationDropdown = !showingNavigationDropdown" 
                                class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none transition"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <Link href="/dashboard" class="flex-shrink-0 flex items-center gap-2 group">
                            <div class="w-8 h-8 rounded bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white shadow-lg group-hover:shadow-indigo-500/50 transition-all">
                                R
                            </div>
                            <span class="font-bold text-white text-lg tracking-tight hidden sm:block">RPG Life</span>
                        </Link>

                        <div class="hidden md:flex items-center gap-1 ml-4">
                            <Link href="/dashboard" class="nav-item">Dashboard</Link>
                            <Link href="/quests" class="nav-item">Quests</Link>
                            <Link href="/treasury" class="nav-item">Treasury</Link>
                            <Link href="/habits" class="nav-item">Habits</Link>
                            <Link href="/timeblocks" class="nav-item">Timeline</Link>
                            
                            <div class="relative group h-16 flex items-center ml-1">
                                <button class="nav-item flex items-center gap-1 cursor-default">
                                    Logs ▾
                                </button>
                                <div class="absolute top-12 left-0 w-40 pt-2 hidden group-hover:block z-50">
                                    <div class="bg-slate-800 border border-slate-700 rounded-lg shadow-xl overflow-hidden ring-1 ring-black ring-opacity-5">
                                        <Link href="/logs/completions" class="block px-4 py-2.5 text-xs font-medium hover:bg-slate-700 hover:text-white transition-colors border-b border-slate-700/50">📜 Quest Logs</Link>
                                        <Link href="/logs/treasury" class="block px-4 py-2.5 text-xs font-medium hover:bg-slate-700 hover:text-white transition-colors">💰 Purchase Logs</Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 sm:gap-4 pl-0 sm:pl-4 sm:border-l border-slate-700 ml-auto">
                        
                        <div v-if="profile" class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5 bg-slate-800 px-2 sm:px-3 py-1.5 rounded-full border border-slate-700/50 hover:border-yellow-500/50 transition-colors cursor-help" title="Your Gold Balance">
                                <span class="text-sm">🪙</span>
                                <span class="text-sm font-bold text-yellow-400">{{ profile.coin_balance }}</span>
                            </div>

                            <div class="flex items-center gap-2" title="Current Level Progress">
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-slate-800 border-2 border-slate-600 relative overflow-hidden shadow-inner group">
                                    <span class="absolute inset-0 flex items-center justify-center text-xs font-black text-white z-20 drop-shadow-md">
                                        {{ profile.level_data?.current_level ?? 1 }}
                                    </span>

                                    <div 
                                        class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-blue-600 to-cyan-500 transition-all duration-700 ease-in-out opacity-90 z-10"
                                        :style="{ height: `${profile.level_data?.progress_percent ?? 0}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <div class="relative group h-16 hidden sm:flex items-center">
                            <button class="flex items-center gap-2 focus:outline-none py-2">
                                <span class="text-sm font-medium text-slate-300 group-hover:text-white transition-colors text-right max-w-[100px] truncate">
                                    {{ user.name }}
                                </span>
                                <div class="w-8 h-8 rounded bg-slate-700 flex items-center justify-center text-xs group-hover:bg-indigo-600 transition-colors shadow-md border border-slate-600">
                                    👤
                                </div>
                            </button>
                            <div class="absolute right-0 top-12 w-48 pt-2 hidden group-hover:block transform origin-top-right transition-all z-50">
                                <div class="bg-slate-800 border border-slate-700 rounded-lg shadow-xl overflow-hidden ring-1 ring-black ring-opacity-5">
                                    <div class="px-4 py-3 border-b border-slate-700 bg-slate-800/50">
                                        <p class="text-xs text-slate-500">Signed in as</p>
                                        <p class="text-sm font-bold text-white truncate">{{ user.email }}</p>
                                    </div>
                                    <Link href="/logout" method="post" as="button" class="block w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-slate-700 hover:text-red-300 transition-colors font-medium">Log Out</Link>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="md:hidden bg-slate-800 border-b border-slate-700 transition-all duration-200 ease-in-out">
                <div class="pt-2 pb-3 space-y-1">
                    <Link href="/dashboard" :class="route().current('dashboard') ? 'bg-indigo-900/50 text-indigo-300 border-l-4 border-indigo-500' : 'text-slate-400 hover:bg-slate-700 hover:text-white'" class="block pl-3 pr-4 py-2 text-base font-medium transition-colors">
                        Dashboard
                    </Link>
                    <Link href="/quests" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        Quests
                    </Link>
                    <Link href="/treasury" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        Treasury
                    </Link>
                    <Link href="/habits" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        Habits
                    </Link>
                    <Link href="/timeblocks" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-slate-400 hover:bg-slate-700 hover:text-white transition-colors">
                        Timeline
                    </Link>
                    
                    <div class="border-t border-slate-700 my-2"></div>
                    <div class="px-4 py-2 text-xs font-bold text-slate-500 uppercase">Logs</div>
                    <Link href="/logs/completions" class="block pl-6 pr-4 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-700">📜 Quest Logs</Link>
                    <Link href="/logs/treasury" class="block pl-6 pr-4 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-700">💰 Purchase Logs</Link>
                </div>

                <div class="pt-4 pb-4 border-t border-slate-700">
                    <div class="px-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded bg-slate-700 flex items-center justify-center text-xs text-white">👤</div>
                        <div>
                            <div class="font-medium text-base text-white">{{ user.name }}</div>
                            <div class="font-medium text-sm text-slate-500">{{ user.email }}</div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <Link href="/logout" method="post" as="button" class="block w-full text-left pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-red-400 hover:text-red-300 hover:bg-slate-700 transition-colors">
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <main>
            <slot />
        </main>
        
        <footer class="py-8 text-center text-xs text-slate-600 border-t border-slate-800/50 mt-12">
            <p>RPG Productivity System &copy; 2026</p>
        </footer>
    </div>
</template>

<style scoped>
.nav-item {
    @apply px-3 py-2 rounded-md text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800/80 transition-all whitespace-nowrap;
}
</style>