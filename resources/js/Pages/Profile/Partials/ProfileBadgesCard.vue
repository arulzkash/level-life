<script setup>
import { getBadgeIcon } from '@/Utils/badgeMeta';
import { formatDate } from './profileFormatters';

defineProps({
    badgeVault: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <section
        class="group relative w-full overflow-hidden rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-4 shadow-lg shadow-black/20 transition-all duration-300 hover:-translate-y-0.5 hover:border-indigo-400/20 hover:bg-slate-800/90 hover:shadow-[0_18px_52px_rgba(99,102,241,0.10)]"
    >
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-500/8 via-transparent to-sky-500/8 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

        <div class="relative flex items-end justify-between gap-3">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Badge Vault</div>
                <div class="text-xl font-black text-white">All Badges</div>
            </div>

            <div class="text-sm font-medium text-slate-300">
                {{ badgeVault.unlocked_count }} / {{ badgeVault.total_count }}
            </div>
        </div>

        <div class="relative mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
            <div
                v-for="badge in badgeVault.items"
                :key="badge.key"
                class="group relative overflow-hidden rounded-xl border p-3 transition-all duration-300"
                :class="
                    badge.is_unlocked
                        ? 'border-slate-600 bg-slate-900/80 hover:-translate-y-1 hover:border-sky-400/40 hover:bg-slate-900 hover:shadow-[0_0_22px_rgba(56,189,248,0.14)]'
                        : 'border-slate-800 bg-slate-950/70 opacity-90 hover:border-slate-700 hover:bg-slate-950/90'
                "
            >
                <!-- subtle glow -->
                <div
                    v-if="badge.is_unlocked"
                    class="pointer-events-none absolute inset-0 bg-gradient-to-br from-sky-400/5 via-transparent to-cyan-300/5 opacity-70 transition-opacity duration-300 group-hover:opacity-100"
                ></div>

                <div class="relative flex items-start justify-between gap-2">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border text-2xl shadow-inner transition"
                        :class="
                            badge.is_unlocked
                                ? 'border-slate-500 bg-slate-700/50 group-hover:border-sky-400/25 group-hover:bg-slate-700/80'
                                : 'border-slate-800 bg-slate-900/70 grayscale group-hover:border-slate-700'
                        "
                    >
                        {{ getBadgeIcon(badge.key) }}
                    </div>

                    <div class="flex flex-col items-end gap-1">
                        <div
                            v-if="badge.is_next"
                            class="rounded-full border border-amber-400/30 bg-amber-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.18em] text-amber-200"
                        >
                            Next
                        </div>

                        <div
                            class="rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.18em]"
                            :class="
                                badge.is_unlocked
                                    ? 'border-sky-400/30 bg-sky-500/10 text-sky-200'
                                    : 'border-slate-700 bg-slate-900 text-slate-400'
                            "
                        >
                            {{ badge.is_unlocked ? 'Unlocked' : 'Locked' }}
                        </div>
                    </div>
                </div>

                <div class="relative mt-3">
                    <div
                        class="truncate text-sm font-black"
                        :class="badge.is_unlocked ? 'text-white' : 'text-slate-300'"
                    >
                        {{ badge.name }}
                    </div>

                    <div class="mt-1 text-[9px] font-bold uppercase tracking-[0.18em] text-slate-500">
                        {{ badge.category }}
                    </div>

                    <p
                        class="mt-2 line-clamp-2 text-xs leading-5"
                        :class="badge.is_unlocked ? 'text-slate-400' : 'text-slate-500'"
                    >
                        {{ badge.description }}
                    </p>

                    <!-- Earned at -->
                    <div v-if="badge.is_unlocked && badge.earned_at" class="mt-2 text-[10px] text-slate-500">
                        Earned {{ formatDate(badge.earned_at, '') }}
                    </div>
                </div>

                <!-- locked overlay -->
                <div
                    v-if="!badge.is_unlocked"
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-black/25 to-transparent"
                ></div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
}
</style>
