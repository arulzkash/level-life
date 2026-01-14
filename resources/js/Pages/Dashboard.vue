<script setup>
import { useForm, router, Link, Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { watch, ref } from "vue";
import XpProgressBar from "@/Components/Game/XpProgressBar.vue";
import StatCard from "@/Components/Game/StatCard.vue";
import confetti from "canvas-confetti";

defineOptions({ layout: AppLayout });

const props = defineProps({
    profile: Object,
    activeQuests: Array,
    habits: Array,
    habitSummary: Object,
    today: String,
    todayBlocks: Array,
});

// --- QUEST CREATION LOGIC ---
const isCustomType = ref(false);

const createForm = useForm({
    name: "",
    status: "todo",
    type: "Daily Grind",
    xp_reward: 50,
    coin_reward: 10,
    due_date: null,
    is_repeatable: false,
});

const handleTypeChange = (event) => {
    if (event.target.value === "Custom") {
        isCustomType.value = true;
        createForm.type = "";
    }
};

const cancelCustomType = () => {
    isCustomType.value = false;
    createForm.type = "Daily Grind";
};

const submitQuest = () => {
    createForm.post("/quests", {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset("name", "xp_reward", "coin_reward", "due_date");
            createForm.type = "Daily Grind";
            createForm.is_repeatable = false;
            isCustomType.value = false;

            showToast("⚔️ Quest Posted to Board!");
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
    if (!completeForms[id]) completeForms[id] = useForm({ note: "" });
    return completeForms[id];
};

const completeQuest = (id, xpReward, coinReward) => {
    const form = getCompleteForm(id);
    form.patch(`/quests/${id}/complete`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset("note");
            triggerConfetti();
            showToast(`✨ +${xpReward} XP & +${coinReward} Gold!`);
        },
    });
};

const toggleQuestStatus = (quest) => {
    const newStatus = quest.status === "todo" ? "in_progress" : "todo";
    router.patch(
        `/quests/${quest.id}`,
        {
            ...quest,
            status: newStatus,
        },
        { preserveScroll: true }
    );
};

// --- HABIT & TIMEBLOCK LOGIC ---
const toggleHabit = (id) => {
    router.patch(`/habits/${id}/toggle`, {}, { preserveScroll: true });
};

const timeblockForm = useForm({
    date: props.today,
    start_time: "09:00",
    end_time: "10:00",
    title: "",
    note: "",
});

const addTimeblock = () => {
    timeblockForm.post("/timeblocks", {
        preserveScroll: true,
        onSuccess: () => {
            timeblockForm.reset("title", "note");
            timeblockForm.date = props.today;
        },
    });
};

const deleteTimeblock = (id) => {
    if (confirm("Delete this timeblock?")) {
        router.delete(`/timeblocks/${id}`, { preserveScroll: true });
    }
};

// --- VISUAL EFFECTS ---
const showToast = (message) => {
    const toast = document.createElement("div");
    // Styling toast biar makin gaming (border glow)
    toast.className =
        "fixed top-4 right-4 bg-slate-800 border-l-4 border-emerald-500 text-white px-6 py-4 rounded shadow-2xl z-50 animate-bounce font-bold flex items-center gap-2";
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
const showLevelUpModal = ref(false);
// Simpan level awal saat page load
const previousLevel = ref(props.profile?.level_data?.current_level || 1);

// Pantau perubahan pada props.profile
watch(
    () => props.profile,
    (newProfile, oldProfile) => {
        if (!newProfile) return;

        const newLevel = newProfile.level_data.current_level;

        // Jika level baru lebih besar dari level yang kita ingat
        if (newLevel > previousLevel.value) {
            // 1. Munculkan Modal
            showLevelUpModal.value = true;

            // 2. Mainkan Efek Suara (Opsional, kalau mau simpel skip aja baris ini)
            // new Audio('/sounds/levelup.mp3').play();

            // 3. Ledakkan Confetti Spesial (Durasi lama)
            triggerLevelUpConfetti();

            // 4. Update ingatan level kita
            previousLevel.value = newLevel;
        }
    },
    { deep: true }
);

// Confetti Spesial Level Up (Fireworks Style)
const triggerLevelUpConfetti = () => {
    const duration = 3000;
    const end = Date.now() + duration;

    (function frame() {
        // Luncurkan dari kiri dan kanan layar
        confetti({
            particleCount: 5,
            angle: 60,
            spread: 55,
            origin: { x: 0 },
            colors: ["#fbbf24", "#f59e0b", "#ef4444"], // Nuansa Emas/Merah
        });
        confetti({
            particleCount: 5,
            angle: 120,
            spread: 55,
            origin: { x: 1 },
            colors: ["#3b82f6", "#8b5cf6", "#ec4899"], // Nuansa Biru/Ungu
        });

        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    })();
};
</script>

<template>
    <Head title="Command Center" />

    <div class="p-4 md:p-8 max-w-7xl mx-auto space-y-8 text-gray-200">
        <section
            v-if="profile"
            class="bg-slate-800 rounded-3xl p-6 border border-slate-700 shadow-2xl relative overflow-hidden"
        >
            <div
                class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-600 rounded-full mix-blend-screen filter blur-[100px] opacity-20 animate-pulse"
            ></div>

            <div
                class="relative z-10 grid grid-cols-1 md:grid-cols-12 gap-8 items-center"
            >
                <div
                    class="md:col-span-3 flex flex-col items-center justify-center"
                >
                    <div class="relative group">
                        <div
                            class="absolute inset-0 bg-gradient-to-tr from-cyan-400 to-indigo-500 rounded-full blur opacity-75 group-hover:opacity-100 transition duration-500"
                        ></div>
                        <div
                            class="relative w-28 h-28 rounded-full bg-slate-900 border-4 border-slate-700 flex items-center justify-center z-10"
                        >
                            <span
                                class="text-5xl font-black text-white drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]"
                            >
                                {{ profile.level_data.current_level }}
                            </span>
                        </div>
                        <div
                            class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 bg-slate-900 px-3 py-1 rounded-full border border-slate-600 text-[10px] font-bold uppercase tracking-widest text-indigo-400 z-20"
                        >
                            Level
                        </div>
                    </div>
                </div>

                <div class="md:col-span-9 space-y-6">
                    <XpProgressBar
                        :current="profile.level_data.xp_current"
                        :max="profile.level_data.xp_needed"
                        :percent="profile.level_data.progress_percent"
                    />
                    <div
                        class="flex flex-wrap gap-4 justify-center md:justify-start"
                    >
                        <StatCard
                            label="Treasury"
                            :value="profile.coin_balance"
                            icon="🪙"
                            colorClass="text-yellow-400"
                        />
                        <StatCard
                            label="Streak"
                            :value="profile.current_streak"
                            icon="🔥"
                            colorClass="text-orange-500"
                        />
                        <StatCard
                            label="Habits"
                            :value="`${habitSummary?.done_today ?? 0}/${
                                habitSummary?.total ?? 0
                            }`"
                            icon="✅"
                            colorClass="text-emerald-400"
                        />
                    </div>
                </div>
            </div>

            <div
                class="mt-8 pt-4 border-t border-slate-700/50 flex flex-wrap gap-3 justify-center md:justify-start"
            >
                <Link href="/quests" class="btn-secondary">📜 Quest Board</Link>
                <Link href="/logs/completions" class="btn-secondary"
                    >📒 Completion Log</Link
                >
                <Link href="/treasury" class="btn-secondary">💰 Merchant</Link>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <div
                    class="bg-slate-800 p-6 rounded-2xl border border-slate-700 shadow-lg"
                >
                    <h3
                        class="text-xl font-bold text-white mb-6 flex items-center gap-2"
                    >
                        <span class="text-2xl">⚔️</span> Post New Quest
                    </h3>

                    <form @submit.prevent="submitQuest" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-8">
                                <label class="label-text">Quest Name</label>
                                <input
                                    v-model="createForm.name"
                                    placeholder="e.g. Defeat the Bug in Production"
                                    class="input-dark w-full"
                                    required
                                />
                                <div
                                    v-if="createForm.errors.name"
                                    class="error-msg"
                                >
                                    {{ createForm.errors.name }}
                                </div>
                            </div>

                            <div class="md:col-span-4">
                                <label class="label-text">Initial Status</label>
                                <select
                                    v-model="createForm.status"
                                    class="input-dark w-full"
                                >
                                    <option value="todo">To Do</option>
                                    <option value="in_progress">
                                        In Progress
                                    </option>
                                    <option value="locked">Locked</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label-text">Quest Type</label>

                                <div v-if="!isCustomType">
                                    <select
                                        :value="createForm.type"
                                        @change="
                                            (e) => {
                                                createForm.type =
                                                    e.target.value;
                                                handleTypeChange(e);
                                            }
                                        "
                                        class="input-dark w-full"
                                    >
                                        <option value="Daily Grind">
                                            Daily Grind
                                        </option>
                                        <option value="Main Quest">
                                            Main Quest
                                        </option>
                                        <option value="Side Quest">
                                            Side Quest
                                        </option>
                                        <option value="Boss Fight">
                                            Boss Fight
                                        </option>
                                        <option value="Learning">
                                            Learning
                                        </option>
                                        <option
                                            value="Custom"
                                            class="font-bold text-indigo-400"
                                        >
                                            + Custom Type...
                                        </option>
                                    </select>
                                </div>

                                <div v-else class="flex gap-2 animate-fade-in">
                                    <input
                                        v-model="createForm.type"
                                        placeholder="Type custom category..."
                                        class="input-dark w-full border-indigo-500 ring-1 ring-indigo-500/50"
                                        autofocus
                                    />
                                    <button
                                        type="button"
                                        @click="cancelCustomType"
                                        class="bg-slate-700 px-3 rounded-lg text-slate-300 hover:text-white hover:bg-slate-600 transition-colors border border-slate-600"
                                        title="Cancel custom type"
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
                                        class="input-dark w-full text-indigo-400 font-bold"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label class="label-text"
                                        >Gold Reward</label
                                    >
                                    <input
                                        type="number"
                                        v-model.number="createForm.coin_reward"
                                        class="input-dark w-full text-yellow-400 font-bold"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-6 bg-slate-900/50 p-4 rounded-xl border border-slate-700/50"
                        >
                            <label
                                class="flex items-center gap-3 cursor-pointer select-none"
                            >
                                <div class="relative">
                                    <input
                                        type="checkbox"
                                        v-model="createForm.is_repeatable"
                                        class="peer sr-only"
                                    />
                                    <div
                                        class="w-10 h-6 bg-slate-700 rounded-full peer-checked:bg-indigo-600 transition-colors"
                                    ></div>
                                    <div
                                        class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4"
                                    ></div>
                                </div>
                                <span class="text-sm text-slate-300"
                                    >Repeatable Quest</span
                                >
                            </label>

                            <div
                                v-if="!createForm.is_repeatable"
                                class="flex-1 transition-all"
                            >
                                <input
                                    type="date"
                                    v-model="createForm.due_date"
                                    class="input-dark w-full text-sm"
                                />
                            </div>
                            <div
                                v-else
                                class="flex-1 text-xs text-slate-500 italic text-right"
                            >
                                Infinite repeats. No due date needed.
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="btn-primary w-full md:w-auto"
                            >
                                <span v-if="createForm.processing"
                                    >Summoning...</span
                                >
                                <span v-else>Create Quest</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="space-y-4">
                    <div
                        class="flex justify-between items-end border-b border-slate-700 pb-2"
                    >
                        <h3
                            class="text-lg font-bold text-slate-300 uppercase tracking-widest"
                        >
                            Active Missions
                        </h3>
                        <span class="text-xs text-slate-500"
                            >{{ activeQuests.length }} active</span
                        >
                    </div>

                    <div
                        v-if="activeQuests.length === 0"
                        class="text-center py-12 bg-slate-800/30 rounded-2xl border-2 border-dashed border-slate-700"
                    >
                        <p class="text-slate-500 italic">
                            "The quest board is empty. Adventure awaits!"
                        </p>
                    </div>

                    <ul v-else class="space-y-4">
                        <li
                            v-for="q in activeQuests"
                            :key="q.id"
                            class="bg-slate-800 border border-slate-700 rounded-xl p-5 hover:border-indigo-500/50 transition-all duration-300 shadow-md group relative overflow-hidden"
                        >
                            <div
                                class="absolute left-0 top-0 bottom-0 w-1"
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

                            <div
                                class="flex flex-col md:flex-row justify-between gap-4 relative z-10"
                            >
                                <div class="flex-1 pl-3">
                                    <div class="flex items-center gap-3 mb-1">
                                        <h4
                                            class="font-bold text-white text-lg group-hover:text-indigo-300 transition-colors"
                                        >
                                            {{ q.name }}
                                        </h4>
                                        <button
                                            @click="toggleQuestStatus(q)"
                                            class="text-[10px] px-2 py-0.5 rounded uppercase tracking-wider font-bold border transition-colors cursor-pointer hover:opacity-80"
                                            :class="{
                                                'bg-slate-700 text-slate-300 border-slate-600':
                                                    q.status === 'todo',
                                                'bg-indigo-900 text-indigo-300 border-indigo-700 animate-pulse':
                                                    q.status === 'in_progress',
                                            }"
                                        >
                                            {{
                                                q.status === "in_progress"
                                                    ? "⚡ In Progress"
                                                    : "🛑 To Do"
                                            }}
                                        </button>

                                        <span
                                            v-if="q.is_repeatable"
                                            class="text-[10px] bg-slate-700 text-slate-300 px-1.5 py-0.5 rounded uppercase tracking-wider"
                                            >Repeatable</span
                                        >
                                        <span
                                            v-if="q.type === 'Boss Fight'"
                                            class="text-[10px] bg-red-900/50 text-red-400 px-1.5 py-0.5 rounded uppercase tracking-wider font-bold"
                                            >BOSS</span
                                        >
                                    </div>

                                    <div
                                        class="flex flex-wrap gap-4 text-xs text-slate-400 mt-2"
                                    >
                                        <span class="flex items-center gap-1"
                                            >🏷️ {{ q.type }}</span
                                        >
                                        <span
                                            class="flex items-center gap-1 text-indigo-400 font-bold"
                                            >✨ {{ q.xp_reward }} XP</span
                                        >
                                        <span
                                            class="flex items-center gap-1 text-yellow-500 font-bold"
                                            >💰 {{ q.coin_reward }} G</span
                                        >
                                        <span
                                            v-if="q.due_date"
                                            class="flex items-center gap-1"
                                            :class="
                                                q.due_date < today
                                                    ? 'text-red-400 font-bold animate-pulse'
                                                    : ''
                                            "
                                        >
                                            📅 {{ q.due_date }}
                                            <span v-if="q.due_date < today"
                                                >(OVERDUE)</span
                                            >
                                        </span>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-2">
                                    <input
                                        v-model="getCompleteForm(q.id).note"
                                        placeholder="Completion Log / Note..."
                                        class="input-dark text-xs py-1.5 w-full md:w-48 placeholder-slate-600 focus:w-64 transition-all duration-300"
                                    />
                                    <button
                                        @click="
                                            completeQuest(
                                                q.id,
                                                q.xp_reward,
                                                q.coin_reward
                                            )
                                        "
                                        :disabled="
                                            getCompleteForm(q.id).processing
                                        "
                                        class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold uppercase tracking-wider px-4 py-2 rounded-lg shadow-lg shadow-emerald-900/20 active:scale-95 transition-all w-full md:w-auto flex items-center justify-center gap-2"
                                    >
                                        <span>✅ Complete Mission</span>
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="space-y-8">
                <div
                    class="bg-slate-800 p-6 rounded-2xl border border-slate-700 shadow-lg"
                >
                    <div class="flex justify-between items-center mb-6">
                        <h3
                            class="font-bold text-white flex items-center gap-2"
                        >
                            <span>🛡️</span> Daily Habits
                        </h3>
                        <span
                            class="text-xs bg-slate-900 text-slate-400 px-2 py-1 rounded-md border border-slate-700"
                        >
                            {{ habitSummary?.done_today ?? 0 }}/{{
                                habitSummary?.total ?? 0
                            }}
                        </span>
                    </div>
                    <ul v-if="habits.length > 0" class="space-y-2">
                        <li v-for="h in habits" :key="h.id" class="group">
                            <label
                                class="flex items-center gap-3 p-3 rounded-xl bg-slate-900/50 border border-transparent hover:border-slate-600 cursor-pointer transition-all"
                            >
                                <input
                                    type="checkbox"
                                    :checked="h.done_today"
                                    @change="toggleHabit(h.id)"
                                    class="w-5 h-5 rounded border-slate-600 bg-slate-800 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-0 focus:ring-offset-transparent cursor-pointer"
                                />
                                <div class="flex-1">
                                    <div
                                        class="text-sm font-medium text-slate-200 group-hover:text-white transition-colors"
                                        :class="{
                                            'line-through text-slate-500':
                                                h.done_today,
                                        }"
                                    >
                                        {{ h.name }}
                                    </div>
                                    <div
                                        class="text-[10px] text-slate-500 mt-0.5"
                                    >
                                        Current Streak:
                                        <span class="text-orange-400 font-bold"
                                            >{{ h.streak }} 🔥</span
                                        >
                                    </div>
                                </div>
                            </label>
                        </li>
                    </ul>
                    <div v-else class="text-center text-slate-500 text-xs py-4">
                        No active habits.
                    </div>
                    <div class="mt-4 text-center">
                        <Link
                            href="/habits"
                            class="text-xs text-indigo-400 hover:text-indigo-300 font-medium hover:underline"
                            >Manage Habits & View Calendar</Link
                        >
                    </div>
                </div>

                <div
                    class="bg-slate-800 p-6 rounded-2xl border border-slate-700 shadow-lg"
                >
                    <div class="flex justify-between items-center mb-4">
                        <h3
                            class="font-bold text-white flex items-center gap-2"
                        >
                            <span>⏳</span> Timeblocks
                        </h3>
                        <Link
                            href="/timeblocks"
                            class="text-xs text-indigo-400 hover:underline"
                            >Full Week</Link
                        >
                    </div>
                    <form
                        @submit.prevent="addTimeblock"
                        class="bg-slate-900/50 p-3 rounded-xl border border-slate-700/50 mb-6"
                    >
                        <div class="flex gap-2 mb-2">
                            <input
                                type="time"
                                v-model="timeblockForm.start_time"
                                class="input-dark text-xs p-1 flex-1 text-center"
                            />
                            <span class="text-slate-500 self-center">-</span>
                            <input
                                type="time"
                                v-model="timeblockForm.end_time"
                                class="input-dark text-xs p-1 flex-1 text-center"
                            />
                        </div>
                        <input
                            v-model="timeblockForm.title"
                            placeholder="Focus Block Title..."
                            class="input-dark text-xs w-full mb-2"
                        />
                        <textarea
                            v-model="timeblockForm.note"
                            placeholder="Note (opt)..."
                            class="input-dark text-xs w-full mb-2 resize-none"
                            rows="1"
                        ></textarea>
                        <button
                            type="submit"
                            class="w-full bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs py-1.5 rounded transition-colors border border-slate-600"
                        >
                            + Add Block
                        </button>
                    </form>
                    <div
                        class="space-y-0 relative pl-4 border-l-2 border-slate-700 ml-2"
                    >
                        <div
                            v-if="todayBlocks.length === 0"
                            class="text-xs text-slate-500 pl-4 italic"
                        >
                            No schedule set for today.
                        </div>
                        <div
                            v-for="b in todayBlocks"
                            :key="b.id"
                            class="relative pl-6 pb-6 group last:pb-0"
                        >
                            <div
                                class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-slate-800 border-2 border-indigo-500 group-hover:bg-indigo-500 transition-colors"
                            ></div>
                            <div
                                class="text-[10px] text-indigo-400 font-mono font-bold mb-0.5"
                            >
                                {{ b.start_time }} - {{ b.end_time }}
                            </div>
                            <div
                                class="text-sm text-slate-200 font-medium leading-tight mb-1"
                            >
                                {{ b.title }}
                            </div>
                            <div
                                v-if="b.note"
                                class="text-xs text-slate-500 bg-slate-900/50 p-2 rounded border border-slate-700/30 italic mb-1"
                            >
                                "{{ b.note }}"
                            </div>
                            <button
                                @click="deleteTimeblock(b.id)"
                                class="text-[10px] text-red-500/70 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                [Delete Block]
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showLevelUpModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-sm animate-fade-in"
        >
            <div class="text-center relative">
                <div
                    class="absolute inset-0 bg-yellow-500 blur-[100px] opacity-20 rounded-full animate-pulse"
                ></div>

                <h1
                    class="text-8xl md:text-9xl font-black text-transparent bg-clip-text bg-gradient-to-b from-yellow-300 via-yellow-500 to-yellow-700 drop-shadow-[0_0_25px_rgba(234,179,8,0.8)] animate-bounce-in relative z-10"
                >
                    LEVEL UP!
                </h1>

                <div
                    class="mt-8 text-white text-4xl font-bold tracking-widest uppercase animate-slide-up relative z-10"
                >
                    You reached Level
                    <span class="text-yellow-400 text-6xl">{{
                        profile.level_data.current_level
                    }}</span>
                </div>

                <button
                    @click="showLevelUpModal = false"
                    class="mt-12 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold py-4 px-12 rounded-full text-xl shadow-[0_0_30px_rgba(99,102,241,0.6)] transform hover:scale-105 transition-all duration-300 relative z-10"
                >
                    AWESOME!
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.input-dark {
    @apply bg-slate-900 border border-slate-700 text-slate-200 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all placeholder-slate-600;
}
.label-text {
    @apply block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5;
}
.btn-primary {
    @apply bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-6 rounded-lg shadow-lg shadow-indigo-500/30 transition-all active:scale-95;
}
.btn-secondary {
    @apply px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 text-sm font-medium rounded-lg transition-colors border border-slate-600;
}
.error-msg {
    @apply text-red-400 text-xs mt-1;
}

input[type="time"]::-webkit-calendar-picker-indicator,
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}
::-webkit-scrollbar {
    width: 8px;
}
::-webkit-scrollbar-track {
    @apply bg-slate-900;
}
::-webkit-scrollbar-thumb {
    @apply bg-slate-700 rounded-full hover:bg-slate-600;
}

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
    animation: slide-up 0.8s ease-out 0.5s both; /* Delay 0.5s biar muncul setelah teks LEVEL UP */
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>
