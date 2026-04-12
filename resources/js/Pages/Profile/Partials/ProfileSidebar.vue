<script setup>
import { computed } from 'vue';
import { buildStatusTone, formatDate } from './profileFormatters';

const props = defineProps({
    identity: { type: Object, required: true },
    streakSummary: { type: Object, required: true },
    stats: { type: Object, required: true },
});

const statusTone = computed(() => buildStatusTone(props.streakSummary?.status));
</script>

<template>
    <div class="group relative overflow-hidden rounded-xl border border-slate-700 bg-slate-800/80 p-5 shadow-lg shadow-black/20 transition-all duration-300 hover:-translate-y-0.5 hover:border-sky-400/25 hover:bg-slate-800/90 hover:shadow-[0_18px_48px_rgba(14,165,233,0.12)]">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-sky-500/8 via-transparent to-cyan-400/8 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

        <div class="relative flex items-center gap-4">
            <div class="relative">
                <div class="absolute inset-0 rounded-xl bg-sky-400/10 blur-lg transition duration-300 group-hover:bg-sky-400/20"></div>
                <div
                    class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border border-slate-600 bg-slate-700/60 text-2xl shadow-inner transition duration-300 group-hover:border-sky-400/30 group-hover:bg-slate-700/80"
                >
                    ⚔️
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <h1 class="truncate text-xl font-black text-white">{{ identity.name }}</h1>
                    <span
                        v-if="identity.is_owner"
                        class="shrink-0 rounded-lg border border-sky-400/30 bg-sky-500/10 px-2 py-0.5 text-[9px] font-bold uppercase tracking-widest text-sky-200"
                    >
                        You
                    </span>
                </div>
                <div class="font-mono text-sm text-sky-300">@{{ identity.username }}</div>
            </div>
        </div>

        <div class="mt-5 rounded-lg border border-slate-700/80 bg-slate-900/40 px-3 py-3 transition duration-300 group-hover:border-slate-600 group-hover:bg-slate-900/60">
            <p class="text-sm italic leading-relaxed text-slate-300">
                {{ identity.bio || 'daily grinder' }}
            </p>
        </div>

        <div class="mt-5 space-y-2.5">
            <div
                class="flex items-center justify-between rounded-lg border border-slate-700/70 bg-slate-900/50 px-3 py-2 text-sm transition duration-300 hover:border-slate-600 hover:bg-slate-900/70"
            >
                <span class="text-slate-400">Joined</span>
                <span class="font-medium text-white">{{ formatDate(identity.joined_at, 'Unknown') }}</span>
            </div>

            <div
                class="flex items-center justify-between rounded-lg border border-slate-700/70 bg-slate-900/50 px-3 py-2 text-sm transition duration-300 hover:border-slate-600 hover:bg-slate-900/70"
            >
                <span class="text-slate-400">Last Active</span>
                <span class="font-medium text-white">
                    {{ formatDate(streakSummary.last_active_date, 'No activity') }}
                </span>
            </div>

            <div
                class="flex items-center justify-between rounded-lg border border-slate-700/70 bg-slate-900/50 px-3 py-2 text-sm transition duration-300 hover:border-slate-600 hover:bg-slate-900/70"
            >
                <span class="text-slate-400">Status</span>
                <span
                    class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                    :class="statusTone.pill"
                >
                    {{ statusTone.icon }} {{ streakSummary.status }}
                </span>
            </div>
        </div>
    </div>
</template>
