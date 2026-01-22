<script setup>
import { useForm, router, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { watch, ref, computed } from 'vue';
import XpProgressBar from '@/Components/Game/XpProgressBar.vue';
import StatCard from '@/Components/Game/StatCard.vue';
import LevelUpModal from '@/Components/Game/LevelUpModal.vue';
import confetti from 'canvas-confetti';
import { useAudio } from '@/Composables/useAudio';
import { useLevelUp } from '@/Composables/useLevelUp';
import HoldButton from '@/Components/Game/HoldButton.vue';

defineOptions({ layout: AppLayout });

const { playSfx, playBgm, stopSfx } = useAudio();

const props = defineProps({
    profile: Object,
    activeQuests: Array,
    habits: Array,
    habitSummary: Object,
    today: String,
    journalTodayExists: Boolean,
    todayBlocks: Array,
    leaderboardData: Object,
    topBadge: Object, // { name, icon, description }
});

// --- UI STATE ---
const showCreateQuestForm = ref(false);

// --- QUEST CREATION LOGIC ---
const isCustomType = ref(false);

const createForm = useForm({
    name: '',
    status: 'todo',
    type: 'Daily Grind',
    xp_reward: 50,
    coin_reward: 50,
    due_date: null,
    is_repeatable: true,
});

const handleTypeChange = (event) => {
    const selectedType = event.target.value;

    if (selectedType === 'Custom') {
        isCustomType.value = true;
        createForm.type = '';
        createForm.is_repeatable = false; // Reset default buat custom
    } else {
        // Logic Auto-Set Repeatable
        if (selectedType === 'Daily Grind') {
            createForm.is_repeatable = true; // Auto True
        } else {
            createForm.is_repeatable = false; // Auto False (tapi bisa diedit user nanti)
        }
    }
};

const cancelCustomType = () => {
    isCustomType.value = false;
    createForm.type = 'Daily Grind';
};

const submitQuest = () => {
    createForm.post('/quests', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset('name', 'xp_reward', 'coin_reward', 'due_date');
            createForm.type = 'Daily Grind';
            createForm.is_repeatable = false;
            isCustomType.value = false;

            showCreateQuestForm.value = false;

            showToast('⚔️ Quest Posted to Board!');
        },
    });
};

watch(
    () => createForm.is_repeatable,
    (val) => {
        if (val) createForm.due_date = null;
    }
);

// --- QUEST COMPLETION LOGIC ---
const completeForms = {};
const getCompleteForm = (id) => {
    if (!completeForms[id]) completeForms[id] = useForm({ note: '' });
    return completeForms[id];
};

const completeQuest = (id, xpReward, coinReward) => {
    const form = getCompleteForm(id);
    form.patch(`/quests/${id}/complete`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('note');
            triggerConfetti();
            triggerSlashEffect();
            showToast(`⚔️ Slashed! +${xpReward} XP & +${coinReward} Gold!`);
            playSfx('complete');
            playSfx('slash');
        },
    });
};

const toggleQuestStatus = (quest) => {
    const newStatus = quest.status === 'todo' ? 'in_progress' : 'todo';
    router.patch(`/quests/${quest.id}`, { ...quest, status: newStatus }, { preserveScroll: true });
};

// --- HABIT & TIMEBLOCK LOGIC ---
const toggleHabit = (id) => {
    const habit = props.habits.find((h) => h.id === id);
    if (!habit) return;

    const wasDone = habit.done_today;

    router.patch(
        `/habits/${id}/toggle`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                if (!wasDone) playSfx('toggle-habit');
            },
        }
    );
};

const timeblockForm = useForm({
    date: props.today,
    start_time: '09:00',
    end_time: '10:00',
    title: '',
    note: '',
});

const addTimeblock = () => {
    timeblockForm.post('/timeblocks', {
        preserveScroll: true,
        onSuccess: () => {
            timeblockForm.reset('title', 'note');
            timeblockForm.date = props.today;
        },
    });
};

const deleteTimeblock = (id) => {
    if (confirm('Delete this timeblock?')) {
        router.delete(`/timeblocks/${id}`, { preserveScroll: true });
    }
};

// --- VISUAL EFFECTS ---
const showToast = (message) => {
    const toast = document.createElement('div');
    toast.className =
        'fixed top-4 right-4 bg-slate-800 border-l-4 border-emerald-500 text-white px-6 py-4 rounded shadow-2xl z-50 animate-bounce font-bold flex items-center gap-2';
    toast.innerHTML = `<span>🎉</span> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
};

const triggerConfetti = () => {
    const count = 200;
    const defaults = { origin: { y: 0.7 } };
    function fire(particleRatio, opts) {
        confetti(
            Object.assign({}, defaults, opts, {
                particleCount: Math.floor(count * particleRatio),
            })
        );
    }
    fire(0.25, { spread: 26, startVelocity: 55 });
    fire(0.2, { spread: 60 });
    fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
    fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
    fire(0.1, { spread: 120, startVelocity: 45 });
};

// --- LEVEL UP LOGIC ---
const profileRef = computed(() => props.profile);
const { showLevelUpModal } = useLevelUp(profileRef);

// Efek Tebasan Pedang (Cross Slash)
const triggerSlashEffect = () => {
    // 1. Definisikan Bentuk
    const swordShape = confetti.shapeFromText({ text: '🗡️', scalar: 4 });

    // Settingan Dasar "Tebasan"
    const slashConfig = {
        shapes: [swordShape],
        colors: ['#ffffff', '#e2e8f0'], // Warna kilatan besi
        ticks: 30, // Hilang SANGAT cepat (efek instan)
        gravity: 0, // 0 Gravitasi = Terbang Lurus
        decay: 0.95, // Kecepatan konstan
        startVelocity: 90, // Kecepatan tinggi
        scalar: 3, // Ukuran pedang besar
        flat: true, // 2D Rotation (biar pipih tajam)
        drift: 0,
    };

    // Slash 1: Kiri Bawah -> Menembak ke Kanan Atas (Membentuk garis /)
    confetti({
        ...slashConfig,
        particleCount: 10, // Sedikit aja biar jadi garis
        angle: 45, // Sudut diagonal kanan
        spread: 5, // SANGAT SEMPIT (biar jadi garis lurus)
        origin: { x: 0.3, y: 0.7 }, // Start agak pinggir
    });

    // Slash 2: Kanan Bawah -> Menembak ke Kiri Atas (Membentuk garis \)
    setTimeout(() => {
        confetti({
            ...slashConfig,
            particleCount: 10,
            angle: 135, // Sudut diagonal kiri
            spread: 5,
            origin: { x: 0.7, y: 0.7 },
        });
    }, 100);

    // Efek Benturan di Tengah (Impact)
    setTimeout(() => {
        confetti({
            shapes: ['square', 'circle'], // Percikan impact
            colors: ['#ef4444', '#f87171', '#ffffff'], // Merah darah & Putih
            particleCount: 40,
            spread: 100, // Menyebar ke segala arah
            origin: { x: 0.5, y: 0.5 }, // Di tengah layar
            startVelocity: 30,
            gravity: 0.8, // Yang ini boleh jatuh
            ticks: 50,
            scalar: 0.8,
        });
    }, 200);
};

const getBadgeIcon = (key) => {
    const icons = {
        // Streak Badges
        streak_3: '👞', // Warm-up (Running shoe)
        streak_7: '🔥', // Consistent (Fire)
        streak_14: '⚔️', // Disciplined (Swords)
        streak_30: '🛡️', // Iron Will (Shield)
        streak_60: '💎', // Unbreakable (Diamond)
        streak_100: '👑', // Legend (Crown)

        // Recovery Badges
        second_wind: '🍃', // Second Wind (Leaf/Wind)
        comeback_kid: '❤️‍🔥', // Comeback Kid (Heart on fire)
    };

    // Default fallback if key not found
    return icons[key] || '🎖️';
};

const getRankClass = (rank) => {
    // Rank 1: Gold + Glow
    if (rank === 1) {
        return 'text-transparent bg-clip-text bg-gradient-to-b from-yellow-200 via-yellow-400 to-yellow-600 drop-shadow-[0_0_10px_rgba(234,179,8,0.6)]';
    }
    // Rank 2: Silver + Glow
    if (rank === 2) {
        return 'text-transparent bg-clip-text bg-gradient-to-b from-slate-100 via-slate-300 to-slate-500 drop-shadow-[0_0_10px_rgba(203,213,225,0.4)]';
    }
    // Rank 3: Bronze + Glow
    if (rank === 3) {
        return 'text-transparent bg-clip-text bg-gradient-to-b from-orange-200 via-orange-400 to-orange-700 drop-shadow-[0_0_10px_rgba(249,115,22,0.4)]';
    }
    // Rank 4+: Clean White (Standard but sharp)
    return 'text-white drop-shadow-sm group-hover:text-indigo-200 transition-colors';
};
</script>

<template>
    <Head title="Command Center" />

    <div class="mx-auto max-w-7xl space-y-8 p-4 text-gray-200 md:p-8">
        <section
            v-if="profile"
            class="relative overflow-hidden rounded-3xl border border-slate-700 bg-slate-800 p-6 shadow-2xl"
        >
            <div
                class="absolute -right-24 -top-24 h-96 w-96 animate-pulse rounded-full bg-indigo-600 opacity-20 mix-blend-screen blur-[100px] filter"
            ></div>

            <div class="relative z-10 grid grid-cols-1 items-center gap-8 md:grid-cols-12">
                <div class="flex flex-col items-center justify-center md:col-span-3">
                    <div class="group relative">
                        <div
                            class="absolute inset-0 rounded-full bg-gradient-to-tr from-cyan-400 to-indigo-500 opacity-75 blur transition duration-500 group-hover:opacity-100"
                        ></div>
                        <div
                            class="relative z-10 flex h-28 w-28 items-center justify-center rounded-full border-4 border-slate-700 bg-slate-900"
                        >
                            <span
                                class="text-5xl font-black text-white drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]"
                            >
                                {{ profile.level_data.current_level }}
                            </span>
                        </div>
                        <div
                            class="absolute -bottom-3 left-1/2 z-20 -translate-x-1/2 transform rounded-full border border-slate-600 bg-slate-900 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-indigo-400"
                        >
                            Level
                        </div>
                    </div>
                </div>

                <div class="space-y-6 md:col-span-9">
                    <XpProgressBar
                        :current="profile.level_data.xp_current"
                        :max="profile.level_data.xp_needed"
                        :percent="profile.level_data.progress_percent"
                    />

                    <div class="grid grid-cols-6 gap-3 md:grid-cols-5 md:gap-4">
                        <div
                            class="col-span-2 flex flex-col items-center justify-center rounded-2xl border border-slate-700 bg-slate-800/50 p-3 text-center shadow-sm transition-all duration-300 hover:scale-105 hover:bg-slate-800 hover:shadow-lg hover:shadow-yellow-500/10 md:col-span-1 md:p-5"
                        >
                            <span
                                class="text-[10px] font-bold uppercase tracking-widest text-slate-400 md:text-xs"
                            >
                                Treasury
                            </span>
                            <div class="mt-1 flex flex-col items-center justify-center gap-1 md:mt-2">
                                <span class="text-xl font-black text-yellow-400 md:text-3xl">
                                    {{ profile.coin_balance }}
                                </span>
                                <span class="text-xs text-slate-500 md:text-sm">🪙 Gold</span>
                            </div>
                        </div>

                        <div
                            class="col-span-2 flex flex-col items-center justify-center rounded-2xl border border-slate-700 bg-slate-800/50 p-3 text-center shadow-sm transition-all duration-300 hover:scale-105 hover:bg-slate-800 hover:shadow-lg hover:shadow-orange-500/10 md:col-span-1 md:p-5"
                        >
                            <span
                                class="text-[10px] font-bold uppercase tracking-widest text-slate-400 md:text-xs"
                            >
                                Streak
                            </span>
                            <div class="mt-1 flex flex-col items-center justify-center gap-1 md:mt-2">
                                <span class="text-xl font-black text-orange-500 md:text-3xl">
                                    {{ profile.current_streak }}
                                </span>
                                <span class="text-xs text-slate-500 md:text-sm">🔥 Days</span>
                            </div>
                        </div>

                        <div
                            class="col-span-2 flex flex-col items-center justify-center rounded-2xl border border-slate-700 bg-slate-800/50 p-3 text-center shadow-sm transition-all duration-300 hover:scale-105 hover:bg-slate-800 hover:shadow-lg hover:shadow-emerald-500/10 md:col-span-1 md:p-5"
                        >
                            <span
                                class="text-[10px] font-bold uppercase tracking-widest text-slate-400 md:text-xs"
                            >
                                Habits
                            </span>
                            <div class="mt-1 flex flex-col items-center justify-center gap-1 md:mt-2">
                                <span class="text-xl font-black text-emerald-400 md:text-3xl">
                                    {{ habitSummary?.done_today }}
                                    <span class="text-lg text-slate-600">/{{ habitSummary?.total }}</span>
                                </span>
                                <span class="text-xs text-slate-500 md:text-sm">✅ Done</span>
                            </div>
                        </div>

                        <Link
                            href="/leaderboard"
                            class="group col-span-3 flex flex-col items-center justify-between rounded-2xl border border-slate-700 bg-slate-800/50 p-3 text-center shadow-sm transition-all duration-300 hover:scale-105 hover:bg-slate-800 hover:shadow-lg hover:shadow-indigo-500/20 md:col-span-1 md:p-5"
                        >
                            <span
                                class="text-[10px] font-bold uppercase tracking-widest text-slate-400 transition-colors group-hover:text-indigo-300 md:text-xs"
                            >
                                Global Rank
                            </span>

                            <div class="mt-1 flex flex-col items-center justify-center md:mt-2">
                                <span
                                    class="text-3xl font-black md:text-4xl"
                                    :class="getRankClass(leaderboardData?.rank)"
                                >
                                    #{{ leaderboardData?.rank ?? '-' }}
                                </span>

                                <div class="mt-1 flex items-center justify-center gap-1">
                                    <span class="text-xs">🏆</span>

                                    <span
                                        v-if="leaderboardData?.rank === 1"
                                        class="text-[10px] font-bold text-yellow-500 md:text-xs"
                                    >
                                        King
                                    </span>

                                    <span
                                        v-else-if="leaderboardData?.rival"
                                        class="truncate text-[10px] text-slate-500 md:text-xs"
                                    >
                                        Vs:
                                        <span class="text-slate-300 group-hover:text-white">
                                            {{ leaderboardData.rival.name }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </Link>

                        <div
                            v-if="topBadge"
                            class="group col-span-3 flex flex-col items-center justify-between rounded-2xl border border-slate-700 bg-slate-800/50 p-3 text-center shadow-sm transition-all duration-300 hover:scale-105 hover:bg-slate-800 hover:shadow-lg hover:shadow-indigo-500/10 md:col-span-1 md:p-5"
                            title="Latest Achievement"
                        >
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="text-[10px] font-bold uppercase tracking-widest text-slate-400 transition-colors group-hover:text-indigo-300 md:text-xs"
                                >
                                    Honor
                                </span>
                            </div>

                            <div class="mt-1 flex w-full flex-col items-center justify-center gap-1 md:mt-2">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="max-w-[120px] truncate text-sm font-black text-white group-hover:text-indigo-200 md:text-lg"
                                    >
                                        {{ topBadge.name }}
                                    </span>
                                    <span
                                        class="text-sm grayscale filter transition-all group-hover:grayscale-0 md:text-lg"
                                    >
                                        {{ getBadgeIcon(topBadge.key) }}
                                    </span>
                                </div>

                                <div
                                    class="line-clamp-2 text-[9px] leading-tight text-slate-500 group-hover:text-slate-400 md:text-[10px]"
                                >
                                    {{ topBadge.description }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="mt-8 flex flex-wrap justify-center gap-3 border-t border-slate-700/50 pt-4 md:justify-start"
            >
                <Link href="/quests" class="btn-secondary">📜 Quest Board</Link>
                <Link href="/logs/completions" class="btn-secondary">📒 Completion Log</Link>
                <Link href="/treasury" class="btn-secondary">💰 Merchant</Link>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-700 pb-2">
                    <div>
                        <h3
                            class="flex items-center gap-2 text-xl font-bold uppercase tracking-widest text-slate-300"
                        >
                            <span>⚔️</span>
                            Active Missions
                        </h3>
                        <span class="text-xs text-slate-500">{{ activeQuests.length }} active</span>
                    </div>

                    <button
                        v-if="!showCreateQuestForm"
                        @click="showCreateQuestForm = true"
                        class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-lg transition-all hover:bg-indigo-500 active:scale-95"
                    >
                        <span>+ New Quest</span>
                    </button>
                </div>

                <div
                    v-if="showCreateQuestForm"
                    class="animate-fade-in relative overflow-hidden rounded-2xl border border-indigo-500/50 bg-slate-800 p-6 shadow-2xl"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <h4 class="text-lg font-bold text-white">Summon New Quest</h4>
                        <button
                            @click="showCreateQuestForm = false"
                            class="text-slate-400 transition-colors hover:text-white"
                        >
                            ✕
                        </button>
                    </div>

                    <form @submit.prevent="submitQuest" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                            <div class="md:col-span-8">
                                <label class="label-text">Quest Name</label>
                                <input
                                    v-model="createForm.name"
                                    placeholder="e.g. Defeat the Bug"
                                    class="input-dark w-full"
                                    required
                                    autofocus
                                />
                                <div v-if="createForm.errors.name" class="error-msg">
                                    {{ createForm.errors.name }}
                                </div>
                            </div>
                            <div class="md:col-span-4">
                                <label class="label-text">Initial Status</label>
                                <select v-model="createForm.status" class="input-dark w-full">
                                    <option value="todo">To Do</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="locked">Locked</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="label-text">Quest Type</label>
                                <div v-if="!isCustomType">
                                    <select
                                        :value="createForm.type"
                                        @change="
                                            (e) => {
                                                createForm.type = e.target.value;
                                                handleTypeChange(e);
                                            }
                                        "
                                        class="input-dark w-full"
                                    >
                                        <option value="Daily Grind">Daily Grind</option>
                                        <option value="Main Quest">Main Quest</option>
                                        <option value="Side Quest">Side Quest</option>
                                        <option value="Boss Fight">Boss Fight</option>
                                        <option value="Learning">Learning</option>
                                        <option value="Custom" class="font-bold text-indigo-400">
                                            + Custom Type...
                                        </option>
                                    </select>
                                </div>
                                <div v-else class="animate-fade-in flex gap-2">
                                    <input
                                        v-model="createForm.type"
                                        placeholder="Type custom category..."
                                        class="input-dark w-full border-indigo-500 ring-1 ring-indigo-500/50"
                                        autofocus
                                    />
                                    <button
                                        type="button"
                                        @click="cancelCustomType"
                                        class="rounded-lg border border-slate-600 bg-slate-700 px-3 text-slate-300 hover:text-white"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="label-text">XP Reward</label>
                                    <input
                                        type="number"
                                        v-model.number="createForm.xp_reward"
                                        class="input-dark w-full font-bold text-indigo-400"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label class="label-text">Gold Reward</label>
                                    <input
                                        type="number"
                                        v-model.number="createForm.coin_reward"
                                        class="input-dark w-full font-bold text-yellow-400"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-6 rounded-xl border border-slate-700/50 bg-slate-900/50 p-4"
                        >
                            <label
                                class="flex select-none items-center gap-3 transition-opacity"
                                :class="{
                                    'cursor-not-allowed opacity-60': createForm.type === 'Daily Grind',
                                    'cursor-pointer': createForm.type !== 'Daily Grind',
                                }"
                            >
                                <div class="relative">
                                    <input
                                        type="checkbox"
                                        v-model="createForm.is_repeatable"
                                        :disabled="createForm.type === 'Daily Grind'"
                                        class="peer sr-only"
                                    />
                                    <div
                                        class="h-6 w-10 rounded-full bg-slate-700 transition-colors peer-checked:bg-indigo-600"
                                    ></div>
                                    <div
                                        class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-4"
                                    ></div>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-sm text-slate-300">Repeatable Quest</span>
                                    <span
                                        v-if="createForm.type === 'Daily Grind'"
                                        class="text-[10px] italic text-indigo-400"
                                    >
                                        (Locked for Daily Grind)
                                    </span>
                                </div>
                            </label>
                            <div v-if="!createForm.is_repeatable" class="flex-1 transition-all">
                                <input
                                    type="date"
                                    v-model="createForm.due_date"
                                    class="input-dark w-full text-sm"
                                />
                            </div>
                            <div v-else class="flex-1 text-right text-xs italic text-slate-500">
                                Infinite repeats. No due date needed.
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="showCreateQuestForm = false"
                                class="px-4 py-2 text-slate-400 transition-colors hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="btn-primary w-full md:w-auto"
                            >
                                <span v-if="createForm.processing">Summoning...</span>
                                <span v-else>Confirm</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div
                    v-if="activeQuests.length === 0"
                    class="rounded-2xl border-2 border-dashed border-slate-700 bg-slate-800/30 py-12 text-center"
                >
                    <p class="italic text-slate-500">"The quest board is empty. Adventure awaits!"</p>
                    <button
                        @click="showCreateQuestForm = true"
                        class="mt-2 text-sm text-indigo-400 underline hover:text-indigo-300"
                    >
                        Create one now
                    </button>
                </div>

                <ul v-else class="space-y-4">
                    <li
                        v-for="q in activeQuests"
                        :key="q.id"
                        class="group relative overflow-hidden rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-md transition-all duration-300 hover:border-indigo-500/50"
                    >
                        <div
                            class="absolute bottom-0 left-0 top-0 w-1"
                            :class="{
                                'bg-red-500': q.type === 'Boss Fight',
                                'bg-yellow-400': q.type === 'Main Quest',
                                'bg-blue-400': q.type === 'Side Quest',
                                'bg-emerald-400': q.type === 'Daily Grind',
                                'bg-slate-500': ![
                                    'Boss Fight',
                                    'Main Quest',
                                    'Side Quest',
                                    'Daily Grind',
                                ].includes(q.type),
                            }"
                        ></div>

                        <div class="relative z-10 flex flex-col justify-between gap-4 md:flex-row">
                            <div class="flex-1 pl-3">
                                <div class="mb-1 flex items-center gap-3">
                                    <h4
                                        class="text-lg font-bold text-white transition-colors group-hover:text-indigo-300"
                                    >
                                        {{ q.name }}
                                    </h4>
                                    <button
                                        @click="toggleQuestStatus(q)"
                                        class="cursor-pointer rounded border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider transition-colors hover:opacity-80"
                                        :class="
                                            q.status === 'in_progress'
                                                ? 'animate-pulse border-indigo-700 bg-indigo-900 text-indigo-300'
                                                : 'border-slate-600 bg-slate-700 text-slate-300'
                                        "
                                    >
                                        {{ q.status === 'in_progress' ? '⚡ In Progress' : '🛑 To Do' }}
                                    </button>
                                    <span
                                        v-if="q.is_repeatable"
                                        class="rounded bg-slate-700 px-1.5 py-0.5 text-[10px] uppercase tracking-wider text-slate-300"
                                    >
                                        Repeatable
                                    </span>
                                    <span
                                        v-if="q.type === 'Boss Fight'"
                                        class="rounded bg-red-900/50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-400"
                                    >
                                        BOSS
                                    </span>
                                </div>

                                <div class="mt-2 flex flex-wrap gap-4 text-xs text-slate-400">
                                    <span class="flex items-center gap-1">🏷️ {{ q.type }}</span>
                                    <span class="flex items-center gap-1 font-bold text-indigo-400">
                                        ✨ {{ q.xp_reward }} XP
                                    </span>
                                    <span class="flex items-center gap-1 font-bold text-yellow-500">
                                        💰 {{ q.coin_reward }} G
                                    </span>
                                    <span
                                        v-if="q.due_date"
                                        class="flex items-center gap-1"
                                        :class="
                                            q.due_date < today ? 'animate-pulse font-bold text-red-400' : ''
                                        "
                                    >
                                        📅 {{ q.due_date }}
                                        <span v-if="q.due_date < today">(OVERDUE)</span>
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <textarea
                                    v-model="getCompleteForm(q.id).note"
                                    placeholder="Completion Note"
                                    rows="1"
                                    class="input-dark w-full resize-none overflow-hidden py-2 text-xs placeholder-slate-600 transition-all duration-300 focus:w-64 md:w-48"
                                ></textarea>

                                <HoldButton
                                    class="w-full md:w-auto"
                                    :disabled="getCompleteForm(q.id).processing"
                                    @complete="completeQuest(q.id, q.xp_reward, q.coin_reward)"
                                >
                                    <span>⚔️ Hold to Slash</span>
                                </HoldButton>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="space-y-8">
                <div class="rounded-2xl border border-slate-700 bg-slate-800 p-6 shadow-lg">
                    <div class="mb-6 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 font-bold text-white">
                            <span>🛡️</span>
                            Daily Habits
                        </h3>
                        <span
                            class="rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-xs text-slate-400"
                        >
                            {{ habitSummary?.done_today ?? 0 }}/{{ habitSummary?.total ?? 0 }}
                        </span>
                    </div>
                    <ul v-if="habits.length > 0" class="space-y-2">
                        <li v-for="h in habits" :key="h.id" class="group">
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-xl border border-transparent bg-slate-900/50 p-3 transition-all hover:border-slate-600"
                            >
                                <input
                                    type="checkbox"
                                    :checked="h.done_today"
                                    @change="toggleHabit(h.id)"
                                    class="h-5 w-5 cursor-pointer rounded border-slate-600 bg-slate-800 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-0 focus:ring-offset-transparent"
                                />
                                <div class="flex-1">
                                    <div
                                        class="text-sm font-medium text-slate-200 transition-colors group-hover:text-white"
                                        :class="{
                                            'text-slate-500 line-through': h.done_today,
                                        }"
                                    >
                                        {{ h.name }}
                                    </div>
                                    <div class="mt-0.5 text-[10px] text-slate-500">
                                        Current Streak:
                                        <span class="font-bold text-orange-400">{{ h.streak }} 🔥</span>
                                    </div>
                                </div>
                            </label>
                        </li>
                    </ul>
                    <div v-else class="py-4 text-center text-xs text-slate-500">No active habits.</div>
                    <div class="mt-4 text-center">
                        <Link
                            href="/habits"
                            class="text-xs font-medium text-indigo-400 hover:text-indigo-300 hover:underline"
                        >
                            Manage Habits & View Calendar
                        </Link>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-700 bg-slate-800 p-6 shadow-lg">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 font-bold text-white">
                            <span>⏳</span>
                            Timeblocks
                        </h3>
                        <Link href="/timeblocks" class="text-xs text-indigo-400 hover:underline">
                            Full Week
                        </Link>
                    </div>

                    <form
                        @submit.prevent="addTimeblock"
                        class="mb-6 rounded-xl border border-slate-700/50 bg-slate-900/50 p-3"
                    >
                        <div class="mb-2 flex gap-2">
                            <input
                                type="time"
                                v-model="timeblockForm.start_time"
                                class="input-dark flex-1 p-1 text-center text-xs"
                            />
                            <span class="self-center text-slate-500">-</span>
                            <input
                                type="time"
                                v-model="timeblockForm.end_time"
                                class="input-dark flex-1 p-1 text-center text-xs"
                            />
                        </div>
                        <input
                            v-model="timeblockForm.title"
                            placeholder="Focus Block Title..."
                            class="input-dark mb-2 w-full text-xs"
                        />
                        <textarea
                            v-model="timeblockForm.note"
                            placeholder="Note (opt)..."
                            class="input-dark mb-2 w-full resize-none text-xs"
                            rows="1"
                        ></textarea>
                        <button
                            type="submit"
                            class="w-full rounded border border-slate-600 bg-slate-700 py-1.5 text-xs text-slate-200 transition-colors hover:bg-slate-600"
                        >
                            + Add Block
                        </button>
                    </form>

                    <div class="relative ml-2 space-y-0 border-l-2 border-slate-700 pl-4">
                        <div v-if="todayBlocks.length === 0" class="pl-4 text-xs italic text-slate-500">
                            No schedule set for today.
                        </div>
                        <div v-for="b in todayBlocks" :key="b.id" class="group relative pb-6 pl-6 last:pb-0">
                            <div
                                class="absolute -left-[9px] top-0 h-4 w-4 rounded-full border-2 border-indigo-500 bg-slate-800 transition-colors group-hover:bg-indigo-500"
                            ></div>
                            <div class="mb-0.5 font-mono text-[10px] font-bold text-indigo-400">
                                {{ b.start_time }} - {{ b.end_time }}
                            </div>
                            <div class="mb-1 text-sm font-medium leading-tight text-slate-200">
                                {{ b.title }}
                            </div>
                            <div
                                v-if="b.note"
                                class="mb-1 rounded border border-slate-700/30 bg-slate-900/50 p-2 text-xs italic text-slate-500"
                            >
                                "{{ b.note }}"
                            </div>
                            <button
                                @click="deleteTimeblock(b.id)"
                                class="text-[10px] text-red-500/70 opacity-0 transition-opacity hover:text-red-400 group-hover:opacity-100"
                            >
                                [Delete Block]
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-700 bg-slate-800 p-6 shadow-lg">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 font-bold text-white">
                            <span>✍</span>
                            Reflection
                        </h3>
                        <div class="flex items-center gap-3">
                            <span
                                class="rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                :class="
                                    journalTodayExists
                                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
                                        : 'border-slate-600 bg-slate-900 text-slate-400'
                                "
                            >
                                {{ journalTodayExists ? 'LOGGED TODAY' : 'NOT YET LOGGED' }}
                            </span>
                            <Link href="/journal" class="text-xs text-indigo-400 hover:underline">
                                Open Log
                            </Link>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Write a short insight or story from today.</p>
                </div>
            </div>
        </div>

        <LevelUpModal v-model="showLevelUpModal" :current-level="profile?.level_data?.current_level || 1" />
    </div>
</template>

<style scoped>
.input-dark {
    @apply rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50;
}
.label-text {
    @apply mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400;
}
.btn-primary {
    @apply rounded-lg bg-indigo-600 px-6 py-2 font-bold text-white shadow-lg shadow-indigo-500/30 transition-all hover:bg-indigo-500 active:scale-95;
}
.btn-secondary {
    @apply rounded-lg border border-slate-600 bg-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition-colors hover:bg-slate-600;
}
.error-msg {
    @apply mt-1 text-xs text-red-400;
}
input[type='time']::-webkit-calendar-picker-indicator,
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
/* Level Up Anim */
@keyframes bounce-in {
    0% {
        transform: scale(0.3);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
@keyframes slide-up {
    0% {
        transform: translateY(50px);
        opacity: 0;
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
}
.animate-bounce-in {
    animation: bounce-in 0.8s cubic-bezier(0.215, 0.61, 0.355, 1) both;
}
.animate-slide-up {
    animation: slide-up 0.8s ease-out 0.5s both;
}
</style>
