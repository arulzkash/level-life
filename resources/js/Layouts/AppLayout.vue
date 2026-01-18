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

                        <Link href="/dashboard" class="group flex flex-shrink-0 items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-cyan-500 shadow-[0_0_15px_rgba(99,102,241,0.4)] transition-all duration-300 group-hover:scale-110 group-hover:shadow-[0_0_20px_rgba(34,211,238,0.6)]"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    class="h-5 w-5 -rotate-12 text-white drop-shadow-sm"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.007Zm-7.037 5.25a.75.75 0 0 1 0 1.5H2.25a.75.75 0 0 1 0-1.5h1.5Zm0 4.5a.75.75 0 0 1 0 1.5H2.25a.75.75 0 0 1 0-1.5h1.5ZM6 17.25a.75.75 0 0 1 0 1.5H2.25a.75.75 0 0 1 0-1.5H6Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>

                            <div class="hidden flex-col sm:flex">
                                <span
                                    class="text-lg font-black tracking-tight text-white transition-colors group-hover:text-cyan-300"
                                >
                                    Level Life
                                </span>
                                <span
                                    class="-mt-1 text-[10px] font-medium uppercase tracking-widest text-indigo-300/80"
                                >
                                    Productivity RPG
                                </span>
                            </div>
                        </Link>

                        <div class="ml-4 hidden items-center gap-1 md:flex">
                            <Link href="/dashboard" class="nav-item">Dashboard</Link>
                            <Link href="/quests" class="nav-item">Quests</Link>
                            <Link href="/treasury" class="nav-item">Treasury</Link>
                            <Link href="/habits" class="nav-item">Habits</Link>
                            <Link href="/timeblocks" class="nav-item">Timeline</Link>
                            <Link href="/leaderboard" class="nav-item group flex items-center gap-1.5">
                                <span
                                    class="text-yellow-500/80 transition-colors group-hover:text-yellow-400"
                                >
                                    🏆
                                </span>
                                Legends
                            </Link>

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
                        @click="showingNavigationDropdown = false"
                    >
                        Dashboard
                    </Link>
                    <Link
                        href="/quests"
                        class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                        @click="showingNavigationDropdown = false"
                    >
                        Quests
                    </Link>
                    <Link
                        href="/treasury"
                        class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                        @click="showingNavigationDropdown = false"
                    >
                        Treasury
                    </Link>
                    <Link
                        href="/habits"
                        class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                        @click="showingNavigationDropdown = false"
                    >
                        Habits
                    </Link>
                    <Link
                        href="/timeblocks"
                        class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                        @click="showingNavigationDropdown = false"
                    >
                        Timeline
                    </Link>
                    <Link
                        href="/leaderboard"
                        class="block border-l-4 border-transparent py-2 pl-3 pr-4 text-base font-medium text-slate-400 transition-colors hover:bg-slate-700 hover:text-white"
                        @click="showingNavigationDropdown = false"
                    >
                        🏆 Hall of Legends
                    </Link>
                    <div class="my-2 border-t border-slate-700"></div>
                    <div class="px-4 py-2 text-xs font-bold uppercase text-slate-500">Logs</div>
                    <Link
                        href="/logs/completions"
                        class="block py-2 pl-6 pr-4 text-sm font-medium text-slate-400 hover:bg-slate-700 hover:text-white"
                        @click="showingNavigationDropdown = false"
                    >
                        📜 Quest Logs
                    </Link>
                    <Link
                        href="/logs/treasury"
                        class="block py-2 pl-6 pr-4 text-sm font-medium text-slate-400 hover:bg-slate-700 hover:text-white"
                        @click="showingNavigationDropdown = false"
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
                            :href="route('profile.edit')"
                            class="block w-full border-l-4 border-transparent py-2 pl-3 pr-4 text-left text-base font-medium text-slate-300 transition-colors hover:bg-slate-700 hover:text-white"
                            @click="showingNavigationDropdown = false"
                        >
                            ⚙️ Character Sheet
                        </Link>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="block w-full border-l-4 border-transparent py-2 pl-3 pr-4 text-left text-base font-medium text-red-400 transition-colors hover:bg-slate-700 hover:text-red-300"
                            @click="showingNavigationDropdown = false"
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
