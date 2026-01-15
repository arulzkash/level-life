<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const profile = computed(() => page.props.auth.profile);

// State untuk Mobile Menu (Buka/Tutup)
const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-900 font-sans text-slate-300">
        <nav
            class="sticky top-0 z-50 w-full border-b border-slate-700/50 bg-slate-900/95 shadow-md backdrop-blur-md"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="-ml-2 flex items-center md:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white focus:outline-none"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <Link href="/dashboard" class="group flex flex-shrink-0 items-center gap-2">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-indigo-500 to-purple-600 font-bold text-white shadow-lg transition-all group-hover:shadow-indigo-500/50"
                            >
                                R
                            </div>
                            <span class="hidden text-lg font-bold tracking-tight text-white sm:block">
                                RPG Life
                            </span>
                        </Link>

                        <div class="ml-4 hidden items-center gap-1 md:flex">
                            <Link href="/dashboard" class="nav-item">Dashboard</Link>
                            <Link href="/quests" class="nav-item">Quests</Link>
                            <Link href="/treasury" class="nav-item">Treasury</Link>
                            <Link href="/habits" class="nav-item">Habits</Link>
                            <Link href="/timeblocks" class="nav-item">Timeline</Link>

                            <div class="group relative ml-1 flex h-16 items-center">
                                <button class="nav-item flex cursor-default items-center gap-1">
                                    Logs ▾
                                </button>
                                <div class="absolute left-0 top-12 z-50 hidden w-40 pt-2 group-hover:block">
                                    <div
                                        class="overflow-hidden rounded-lg border border-slate-700 bg-slate-800 shadow-xl ring-1 ring-black ring-opacity-5"
                                    >
                                        <Link
                                            href="/logs/completions"
                                            class="block border-b border-slate-700/50 px-4 py-2.5 text-xs font-medium transition-colors hover:bg-slate-700 hover:text-white"
                                        >
                                            📜 Quest Logs
                                        </Link>
                                        <Link
                                            href="/logs/treasury"
                                            class="block px-4 py-2.5 text-xs font-medium transition-colors hover:bg-slate-700 hover:text-white"
                                        >
                                            💰 Purchase Logs
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="ml-auto flex items-center gap-3 border-slate-700 pl-0 sm:gap-4 sm:border-l sm:pl-4"
                    >
                        <div v-if="profile" class="flex items-center gap-3">
                            <div
                                class="flex cursor-help items-center gap-1.5 rounded-full border border-slate-700/50 bg-slate-800 px-2 py-1.5 transition-colors hover:border-yellow-500/50 sm:px-3"
                                title="Your Gold Balance"
                            >
                                <span class="text-sm">🪙</span>
                                <span class="text-sm font-bold text-yellow-400">
                                    {{ profile.coin_balance }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2" title="Current Level Progress">
                                <div
                                    class="group relative h-9 w-9 overflow-hidden rounded-full border-2 border-slate-600 bg-slate-800 shadow-inner sm:h-10 sm:w-10"
                                >
                                    <span
                                        class="absolute inset-0 z-20 flex items-center justify-center text-xs font-black text-white drop-shadow-md"
                                    >
                                        {{ profile.level_data?.current_level ?? 1 }}
                                    </span>

                                    <div
                                        class="absolute bottom-0 left-0 z-10 w-full bg-gradient-to-t from-blue-600 to-cyan-500 opacity-90 transition-all duration-700 ease-in-out"
                                        :style="{ height: `${profile.level_data?.progress_percent ?? 0}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <div class="group relative hidden h-16 items-center sm:flex">
                            <button class="flex items-center gap-2 py-2 focus:outline-none">
                                <span
                                    class="max-w-[100px] truncate text-right text-sm font-medium text-slate-300 transition-colors group-hover:text-white"
                                >
                                    {{ user.name }}
                                </span>
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded border border-slate-600 bg-slate-700 text-xs shadow-md transition-colors group-hover:bg-indigo-600"
                                >
                                    👤
                                </div>
                            </button>

                            <div
                                class="absolute right-0 top-12 z-50 hidden w-48 origin-top-right transform pt-2 transition-all group-hover:block"
                            >
                                <div
                                    class="overflow-hidden rounded-lg border border-slate-700 bg-slate-800 shadow-xl ring-1 ring-black ring-opacity-5"
                                >
                                    <div class="border-b border-slate-700 bg-slate-800/50 px-4 py-3">
                                        <p class="text-xs text-slate-500">Signed in as</p>
                                        <p class="truncate text-sm font-bold text-white">{{ user.email }}</p>
                                    </div>

                                    <Link
                                        :href="route('profile.edit')"
                                        class="block w-full border-b border-slate-700/50 px-4 py-3 text-left text-sm text-slate-300 transition-colors hover:bg-slate-700 hover:text-white"
                                    >
                                        ⚙️ Character Sheet
                                    </Link>

                                    <Link
                                        href="/logout"
                                        method="post"
                                        as="button"
                                        class="block w-full px-4 py-3 text-left text-sm font-medium text-red-400 transition-colors hover:bg-slate-700 hover:text-red-300"
                                    >
                                        Log Out
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
                class="border-b border-slate-700 bg-slate-800 transition-all duration-200 ease-in-out md:hidden"
            >
                <div class="space-y-1 pb-3 pt-2">
                    <Link
                        href="/dashboard"
                        :class="
                            route().current('dashboard')
                                ? 'border-l-4 border-indigo-500 bg-indigo-900/50 text-indigo-300'
                                : 'text-slate-400 hover:bg-slate-700 hover:text-white'
                        "
                        class="block py-2 pl-3 pr-4 text-base font-medium transition-colors"
                    >
                        Dashboard
                    </Link>
                    <Link
                        href="/quests"
                        class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                    >
                        Quests
                    </Link>
                    <Link
                        href="/treasury"
                        class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                    >
                        Treasury
                    </Link>
                    <Link
                        href="/habits"
                        class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                    >
                        Habits
                    </Link>
                    <Link
                        href="/timeblocks"
                        class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                    >
                        Timeline
                    </Link>

                    <div class="my-2 border-t border-slate-700"></div>
                    <div class="px-4 py-2 text-xs font-bold uppercase text-slate-500">Logs</div>
                    <Link
                        href="/logs/completions"
                        class="block py-2 pl-6 pr-4 text-sm font-medium text-slate-400 hover:bg-slate-700 hover:text-white"
                    >
                        📜 Quest Logs
                    </Link>
                    <Link
                        href="/logs/treasury"
                        class="block py-2 pl-6 pr-4 text-sm font-medium text-slate-400 hover:bg-slate-700 hover:text-white"
                    >
                        💰 Purchase Logs
                    </Link>
                </div>

                <div class="border-t border-slate-700 pb-4 pt-4">
                    <div class="flex items-center gap-3 px-4">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded bg-slate-700 text-xs text-white"
                        >
                            👤
                        </div>
                        <div>
                            <div class="text-base font-medium text-white">{{ user.name }}</div>
                            <div class="text-sm font-medium text-slate-500">{{ user.email }}</div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="block w-full border-l-4 border-transparent py-2 pl-3 pr-4 text-left text-base font-medium text-red-400 transition-colors hover:bg-slate-700 hover:text-red-300"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <main>
            <slot />
        </main>

        <footer class="mt-12 border-t border-slate-800/50 py-8 text-center text-xs text-slate-600">
            <p>RPG Productivity System &copy; 2026</p>
        </footer>
    </div>
</template>

<style scoped>
.nav-item {
    @apply whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium text-slate-400 transition-all hover:bg-slate-800/80 hover:text-white;
}
</style>
