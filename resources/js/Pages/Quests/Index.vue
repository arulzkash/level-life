<script setup>
import { ref, watch, computed } from 'vue';
import { router, Link, Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import debounce from 'lodash/debounce';
import confetti from 'canvas-confetti';
import { useAudio } from '@/Composables/useAudio';

defineOptions({ layout: AppLayout });

const { playSfx } = useAudio();

const props = defineProps({
    quests: Object,
    filters: Object,
    typeOptions: Array,
});

const page = usePage();

// --- 1. FILTER LOGIC (AUTO RELOAD) ---
const form = ref({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    type: props.filters.type ?? '',
    repeatable: props.filters.repeatable ?? '',
    sort: props.filters.sort ?? 'created_at',
    dir: props.filters.dir ?? 'desc',
});

// Watcher: Auto-reload saat filter berubah (tunggu 300ms)
watch(
    form,
    debounce(() => {
        router.get('/quests', form.value, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300),
    { deep: true }
);

// --- 2. LEVEL UP LOGIC (Global Watcher) ---
const showLevelUpModal = ref(false);
// Ambil data profile dari Shared Props (Middleware)
const globalProfile = computed(() => page.props.auth.profile);
const previousLevel = ref(globalProfile.value?.level_data?.current_level || 1);

// Pantau perubahan pada Global Profile
watch(
    globalProfile,
    (newP) => {
        if (!newP) return;
        const newLevel = newP.level_data.current_level;

        // Jika level naik
        if (newLevel > previousLevel.value) {
            setTimeout(() => {
                showLevelUpModal.value = true;
                triggerLevelUpConfetti();
                playSfx('levelup');
                previousLevel.value = newLevel;
            }, 2500); // Auto close setelah 5 detik
        }
    },
    { deep: true }
);

// --- 3. EDIT LOGIC ---
const editingQuest = ref(null);
const isEditCustomType = ref(false);

const editForm = useForm({
    name: '',
    status: '',
    type: '',
    xp_reward: 0,
    coin_reward: 0,
    due_date: null,
    is_repeatable: false,
});

const startEdit = (q) => {
    editingQuest.value = q;
    editForm.name = q.name;
    editForm.status = q.status;
    editForm.xp_reward = q.xp_reward;
    editForm.coin_reward = q.coin_reward;
    editForm.due_date = q.due_date;
    editForm.is_repeatable = !!q.is_repeatable;

    const standardTypes = ['Daily Grind', 'Main Quest', 'Side Quest', 'Boss Fight', 'Learning'];
    const availableOptions = [...new Set([...standardTypes, ...props.typeOptions])];

    if (availableOptions.includes(q.type)) {
        isEditCustomType.value = false;
        editForm.type = q.type;
    } else {
        isEditCustomType.value = true;
        editForm.type = q.type;
    }
};

const handleEditTypeChange = (e) => {
    if (e.target.value === 'Custom') {
        isEditCustomType.value = true;
        editForm.type = '';
    } else {
        editForm.type = e.target.value;
    }
};

const cancelEdit = () => {
    editingQuest.value = null;
    editForm.reset();
    isEditCustomType.value = false;
};

const saveEdit = () => {
    if (editForm.is_repeatable) editForm.due_date = null;
    editForm.patch(`/quests/${editingQuest.value.id}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

// --- 4. COMPLETION LOGIC ---
const completingQuest = ref(null);
const completionForm = useForm({ note: '' });

const openCompleteModal = (q) => {
    completingQuest.value = q;
    completionForm.note = '';
};

const closeCompleteModal = () => {
    completingQuest.value = null;
    completionForm.reset();
};

const submitComplete = () => {
    if (!completingQuest.value) return;
    const rewardXP = completingQuest.value.xp_reward;
    const rewardCoin = completingQuest.value.coin_reward;

    completionForm.patch(`/quests/${completingQuest.value.id}/complete`, {
        preserveScroll: true,
        onSuccess: () => {
            closeCompleteModal();
            triggerConfetti(); // Confetti biasa
            triggerSlashEffect();
            showToast(`✨ +${rewardXP} XP & +${rewardCoin} Gold!`);
            playSfx('complete');
            playSfx('slash');
        },
    });
};

// --- 5. VISUAL EFFECTS ---
// Confetti Biasa (Complete Quest)
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

// Confetti HEBOH (Level Up)
const triggerLevelUpConfetti = () => {
    const duration = 3000;
    const end = Date.now() + duration;
    (function frame() {
        confetti({
            particleCount: 5,
            angle: 60,
            spread: 55,
            origin: { x: 0 },
            colors: ['#fbbf24', '#f59e0b', '#ef4444'],
        });
        confetti({
            particleCount: 5,
            angle: 120,
            spread: 55,
            origin: { x: 1 },
            colors: ['#3b82f6', '#8b5cf6', '#ec4899'],
        });
        if (Date.now() < end) requestAnimationFrame(frame);
    })();
};

const showToast = (message) => {
    const toast = document.createElement('div');
    toast.className =
        'fixed top-4 right-4 bg-slate-800 border-l-4 border-emerald-500 text-white px-6 py-4 rounded shadow-2xl z-[100] animate-bounce font-bold flex items-center gap-2';
    toast.innerHTML = `<span>🎉</span> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
};

// --- HELPERS ---
const deleteQuest = (q) => {
    if (confirm(`Delete quest "${q.name}"?`)) {
        router.delete(`/quests/${q.id}`, { preserveScroll: true });
    }
};

const canComplete = (q) => {
    if (q.status === 'locked') return false;
    if (!q.is_repeatable && q.status === 'done') return false;
    return true;
};

const getTodayLocal = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const isOverdue = (dateString) => {
    if (!dateString) return false;
    const todayStr = getTodayLocal();
    return dateString < todayStr;
};

const statusColors = {
    todo: 'bg-slate-700 text-slate-300 border-slate-600',
    in_progress: 'bg-indigo-900/50 text-indigo-300 border-indigo-700 animate-pulse',
    done: 'bg-emerald-900/50 text-emerald-400 border-emerald-700 opacity-75',
    locked: 'bg-red-900/20 text-red-500 border-red-900/50 opacity-60',
};

const formatStatus = (s) => s.replace('_', ' ').toUpperCase();
</script>

<template>
    <Head title="Quest Board" />

    <div class="mx-auto max-w-7xl space-y-6 p-4 text-gray-200 md:p-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="flex items-center gap-3 text-3xl font-black tracking-tight text-white">
                    <span>📜</span>
                    Quest Board
                </h1>
                <p class="mt-1 text-sm text-slate-400">Select a mission and claim your rewards.</p>
            </div>

            <div class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800 p-1">
                <select v-model="form.sort" class="input-dark w-full">
                    <option value="created_at">Date Added</option>
                    <option value="due_date">Due Date</option>
                    <option value="xp_reward">XP Reward</option>
                    <option value="coin_reward">Gold Reward</option>
                </select>
                <button
                    @click="form.dir = form.dir === 'asc' ? 'desc' : 'asc'"
                    class="rounded px-2 py-1 text-xs transition-colors hover:bg-slate-700"
                >
                    {{ form.dir === 'asc' ? '⬆️' : '⬇️' }}
                </button>
            </div>
        </div>

        <div
            class="grid grid-cols-2 gap-4 rounded-xl border border-slate-700/50 bg-slate-800/80 p-4 shadow-lg backdrop-blur-sm md:grid-cols-4"
        >
            <div class="col-span-2 md:col-span-1">
                <input v-model="form.search" placeholder="Search quest..." class="input-dark w-full" />
            </div>

            <div>
                <select v-model="form.status" class="input-dark w-full">
                    <option value="">All Status</option>
                    <option value="todo">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="done">Completed</option>
                    <option value="locked">Locked</option>
                </select>
            </div>

            <div>
                <select v-model="form.type" class="input-dark w-full">
                    <option value="">All Types</option>
                    <option v-for="t in typeOptions" :key="t" :value="t">{{ t }}</option>
                </select>
            </div>

            <div>
                <select v-model="form.repeatable" class="input-dark w-full">
                    <option value="">Any Mode</option>
                    <option value="1">Repeatable</option>
                    <option value="0">One-time</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end">
            <Link
                href="/dashboard"
                class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-lg transition-colors hover:bg-indigo-500"
            >
                <span>+ Create New Quest</span>
            </Link>
        </div>

        <div
            v-if="quests.data.length === 0"
            class="rounded-2xl border-2 border-dashed border-slate-700 py-20 text-center opacity-50"
        >
            <div class="mb-4 text-5xl">🕸️</div>
            <h3 class="text-xl font-bold text-slate-400">No Quests Found</h3>
            <p class="text-sm text-slate-500">Try adjusting your filters.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="quest in quests.data"
                :key="quest.id"
                class="group relative flex flex-col overflow-hidden rounded-xl border border-slate-700 bg-slate-800 shadow-lg transition-all duration-300 hover:border-indigo-500/50 hover:shadow-indigo-500/20"
                :class="{ 'opacity-60 grayscale hover:grayscale-0': quest.status === 'done' }"
            >
                <div
                    class="absolute bottom-0 left-0 top-0 w-1.5"
                    :class="{
                        'bg-red-500': quest.type === 'Boss Fight',
                        'bg-yellow-400': quest.type === 'Main Quest',
                        'bg-blue-400': quest.type === 'Side Quest',
                        'bg-emerald-400': quest.type === 'Daily Grind',
                        'bg-slate-500': !['Boss Fight', 'Main Quest', 'Side Quest', 'Daily Grind'].includes(
                            quest.type
                        ),
                    }"
                ></div>

                <div class="flex flex-1 flex-col p-5 pl-6">
                    <div class="mb-3 flex items-start justify-between">
                        <span
                            class="rounded-full border border-slate-700 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest text-slate-400"
                        >
                            {{ quest.type }}
                        </span>
                        <span v-if="quest.is_repeatable" class="text-lg text-slate-500" title="Repeatable">
                            ↻
                        </span>
                    </div>

                    <div class="mb-4">
                        <h3
                            class="mb-2 text-lg font-bold leading-tight text-white transition-colors group-hover:text-indigo-400"
                        >
                            {{ quest.name }}
                        </h3>
                        <span
                            class="rounded border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                            :class="statusColors[quest.status] || 'border-slate-600 bg-slate-700'"
                        >
                            {{ formatStatus(quest.status) }}
                        </span>
                    </div>

                    <div class="mt-auto flex flex-col gap-2 border-t border-slate-700/50 pt-4">
                        <div class="flex gap-4 font-mono text-xs">
                            <div class="flex items-center gap-1.5 text-indigo-400">
                                <span>✨</span>
                                <span class="font-bold">+{{ quest.xp_reward }} XP</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-yellow-500">
                                <span>💰</span>
                                <span class="font-bold">+{{ quest.coin_reward }} G</span>
                            </div>
                        </div>

                        <div
                            v-if="quest.due_date"
                            class="flex items-center gap-1 text-xs"
                            :class="
                                isOverdue(quest.due_date) && quest.status !== 'done'
                                    ? 'animate-pulse font-bold text-red-400'
                                    : 'text-slate-400'
                            "
                        >
                            📅 {{ quest.due_date }}
                            <span v-if="isOverdue(quest.due_date) && quest.status !== 'done'">(OVERDUE)</span>
                        </div>
                        <div v-else class="text-xs italic text-slate-600">No deadline</div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between border-t border-slate-700/50 bg-slate-900/50 px-4 py-3"
                >
                    <button
                        v-if="canComplete(quest)"
                        @click="openCompleteModal(quest)"
                        class="rounded bg-emerald-900/30 px-3 py-1.5 text-xs font-bold uppercase text-emerald-400 transition-colors hover:bg-emerald-600 hover:text-white"
                    >
                        Complete
                    </button>
                    <span v-else class="text-xs italic text-slate-600">
                        {{ quest.status === 'done' ? 'Completed' : 'Locked' }}
                    </span>

                    <div class="flex gap-2">
                        <button
                            @click="startEdit(quest)"
                            class="text-slate-400 transition-colors hover:text-white"
                            title="Edit"
                        >
                            ✏️
                        </button>
                        <button
                            @click="deleteQuest(quest)"
                            class="text-slate-400 transition-colors hover:text-red-400"
                            title="Delete"
                        >
                            🗑️
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="quests.links.length > 3" class="mt-8 flex justify-center gap-2">
            <Component
                v-for="(link, k) in quests.links"
                :key="k"
                :is="link.url ? Link : 'span'"
                :href="link.url"
                v-html="link.label"
                class="rounded border px-3 py-1 text-sm transition-colors"
                :class="{
                    'border-indigo-500 bg-indigo-600 text-white': link.active,
                    'border-slate-700 bg-slate-800 text-slate-400 hover:bg-slate-700':
                        !link.active && link.url,
                    'border-transparent text-slate-600 opacity-50': !link.url,
                }"
            />
        </div>

        <div
            v-if="editingQuest"
            class="animate-fade-in fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-lg overflow-hidden rounded-xl border border-slate-600 bg-slate-800 shadow-2xl"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-700 bg-slate-900/50 px-6 py-4"
                >
                    <h3 class="text-lg font-bold text-white">Edit Quest</h3>
                    <button @click="cancelEdit" class="text-slate-400 hover:text-white">✕</button>
                </div>
                <form @submit.prevent="saveEdit" class="space-y-4 p-6">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-400">
                            Quest Name
                        </label>
                        <input v-model="editForm.name" class="input-dark w-full" />
                        <div v-if="editForm.errors.name" class="mt-1 text-xs text-red-400">
                            {{ editForm.errors.name }}
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-400">
                                Status
                            </label>
                            <select v-model="editForm.status" class="input-dark w-full">
                                <option value="todo">To Do</option>
                                <option value="in_progress">In Progress</option>
                                <option value="locked">Locked</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-400">Type</label>
                            <div v-if="!isEditCustomType">
                                <select
                                    :value="editForm.type"
                                    @change="handleEditTypeChange"
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
                            <div v-else class="flex gap-2">
                                <input
                                    v-model="editForm.type"
                                    placeholder="Type custom..."
                                    class="input-dark w-full border-indigo-500"
                                    autofocus
                                />
                                <button
                                    type="button"
                                    @click="
                                        isEditCustomType = false;
                                        editForm.type = 'Daily Grind';
                                    "
                                    class="rounded bg-slate-700 px-3 text-slate-300 hover:text-white"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-400">XP</label>
                            <input type="number" v-model="editForm.xp_reward" class="input-dark w-full" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase text-slate-400">Gold</label>
                            <input type="number" v-model="editForm.coin_reward" class="input-dark w-full" />
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-4 rounded border border-slate-700/50 bg-slate-900/50 p-3"
                    >
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                v-model="editForm.is_repeatable"
                                class="h-4 w-4 accent-indigo-500"
                            />
                            <span class="text-sm text-slate-300">Repeatable</span>
                        </label>
                        <div v-if="!editForm.is_repeatable" class="flex-1">
                            <input
                                type="date"
                                v-model="editForm.due_date"
                                class="input-dark w-full text-sm"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-700 pt-4">
                        <button
                            type="button"
                            @click="cancelEdit"
                            class="px-4 py-2 text-slate-400 transition-colors hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-lg bg-indigo-600 px-6 py-2 font-bold text-white shadow-lg transition-all hover:bg-indigo-500"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="completingQuest"
            class="animate-fade-in fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-xl border border-emerald-500/50 bg-slate-800 shadow-[0_0_30px_rgba(16,185,129,0.2)]"
            >
                <div class="border-b border-emerald-500/30 bg-emerald-900/20 px-6 py-4">
                    <h3 class="flex items-center gap-2 text-lg font-bold text-emerald-400">
                        <span>✅</span>
                        Mission Complete!
                    </h3>
                </div>
                <form @submit.prevent="submitComplete" class="space-y-4 p-6">
                    <p class="text-sm text-slate-300">
                        Completing:
                        <strong class="text-white">{{ completingQuest.name }}</strong>
                    </p>
                    <div>
                        <textarea
                            v-model="completionForm.note"
                            rows="3"
                            placeholder="Log your victory..."
                            class="input-dark w-full resize-none"
                            autofocus
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeCompleteModal"
                            class="px-4 py-2 text-slate-400 transition-colors hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="completionForm.processing"
                            class="flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2 font-bold text-white shadow-lg transition-all hover:bg-emerald-500"
                        >
                            <span>Claim Rewards</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="showLevelUpModal"
            class="animate-fade-in fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-sm"
        >
            <div class="relative text-center">
                <div
                    class="absolute inset-0 animate-pulse rounded-full bg-yellow-500 opacity-20 blur-[100px]"
                ></div>
                <h1
                    class="animate-bounce-in relative z-10 bg-gradient-to-b from-yellow-300 via-yellow-500 to-yellow-700 bg-clip-text text-8xl font-black text-transparent drop-shadow-[0_0_25px_rgba(234,179,8,0.8)] md:text-9xl"
                >
                    LEVEL UP!
                </h1>
                <div
                    class="animate-slide-up relative z-10 mt-8 text-4xl font-bold uppercase tracking-widest text-white"
                >
                    You reached Level
                    <span class="text-6xl text-yellow-400">{{ globalProfile.level_data.current_level }}</span>
                </div>
                <button
                    @click="showLevelUpModal = false"
                    class="relative z-10 mt-12 transform rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 px-12 py-4 text-xl font-bold text-white shadow-[0_0_30px_rgba(99,102,241,0.6)] transition-all duration-300 hover:scale-105 hover:from-indigo-500 hover:to-purple-500"
                >
                    AWESOME!
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.input-dark {
    @apply rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 outline-none transition-all focus:ring-1 focus:ring-indigo-500;
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out;
}
.animate-bounce-in {
    animation: bounce-in 0.8s cubic-bezier(0.215, 0.61, 0.355, 1) both;
}
.animate-slide-up {
    animation: slide-up 0.8s ease-out 0.5s both;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
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
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>
