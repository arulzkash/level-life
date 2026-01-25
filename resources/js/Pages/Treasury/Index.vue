<script setup>
import { Link, useForm, router, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import confetti from 'canvas-confetti';
import { useAudio } from '@/Composables/useAudio';
import CoinIcon from '@/Components/Game/icons/CoinIcon.vue';

defineOptions({ layout: AppLayout });

const { playSfx } = useAudio();

const props = defineProps({
    profile: Object,
    rewards: Array,
    errors: Object,
});

// --- CREATE LOGIC ---
const showCreateForm = ref(false);
const createForm = useForm({
    name: '',
    cost_coin: 100,
});

const submitCreate = () => {
    createForm.post('/treasury/rewards', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreateForm.value = false;
            showToast('🎁 New Item added to shop!');
        },
    });
};

// --- BUY LOGIC (MODAL) ---
const buyingReward = ref(null);
const buyForm = useForm({
    qty: 1,
    note: '',
});

const openBuyModal = (reward) => {
    buyingReward.value = reward;
    buyForm.qty = 1;
    buyForm.note = '';
};

const closeBuyModal = () => {
    buyingReward.value = null;
    buyForm.reset();
};

const totalCost = computed(() => {
    if (!buyingReward.value) return 0;
    return buyingReward.value.cost_coin * buyForm.qty;
});

const canAfford = computed(() => {
    return props.profile.coin_balance >= totalCost.value;
});

const submitBuy = () => {
    if (!buyingReward.value || !canAfford.value) return;

    buyForm.patch(`/treasury/rewards/${buyingReward.value.id}/buy`, {
        preserveScroll: true,
        onSuccess: () => {
            closeBuyModal();
            triggerCoinSound(); // Visual effect only
            showToast(`🛍️ Purchased ${buyForm.qty}x ${buyingReward.value.name}`);
        },
        onError: () => {
            // Handle error saldo kurang dari backend (backup)
            alert('Not enough coins!');
        },
    });
};

// --- EDIT LOGIC (MODAL) ---
const editingReward = ref(null);
const editForm = useForm({ name: '', cost_coin: 0 });

const startEdit = (r) => {
    editingReward.value = r;
    editForm.name = r.name;
    editForm.cost_coin = r.cost_coin;
};

const submitEdit = () => {
    editForm.patch(`/treasury/rewards/${editingReward.value.id}`, {
        preserveScroll: true,
        onSuccess: () => (editingReward.value = null),
    });
};

const deleteReward = (r) => {
    if (confirm(`Remove "${r.name}" from shop?`)) {
        router.delete(`/treasury/rewards/${r.id}`, { preserveScroll: true });
    }
};

// --- VISUALS ---
const showToast = (message) => {
    const toast = document.createElement('div');
    toast.className =
        'fixed top-4 right-4 bg-slate-800 border-l-4 border-yellow-500 text-white px-6 py-4 rounded shadow-2xl z-[100] animate-bounce font-bold flex items-center gap-2';
    toast.innerHTML = `<span>💰</span> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
};

// Simulasi efek suara/visual
const triggerCoinSound = () => {
    // Bisa tambah audio di sini nanti
    playSfx('coin');
    playSfx('purchase');
    confetti({
        particleCount: 50,
        spread: 40,
        origin: { y: 0.6 },
        colors: ['#fbbf24', '#f59e0b'], // Gold colors
    });
};
</script>

<template>
    <Head title="Treasury" />

    <div class="mx-auto max-w-7xl space-y-8 p-4 text-gray-200 md:p-8">
        <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="flex items-center gap-3 text-3xl font-black tracking-tight text-white">
                    <span>🛒</span>
                    The Treasury
                </h1>
                <p class="mt-1 text-sm text-slate-400">Exchange your hard-earned gold for rewards.</p>
            </div>

            <div
                class="flex items-center gap-3 rounded-xl border border-yellow-500/30 bg-slate-800 px-6 py-3 shadow-[0_0_20px_rgba(234,179,8,0.2)]"
            >
                <CoinIcon cls="w-8 h-8 md:w-10 md:h-10 drop-shadow-[0_0_6px_rgba(234,179,8,0.45)]" />
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest text-slate-400">
                        Current Balance
                    </div>
                    <div class="font-mono text-2xl font-black text-yellow-400">
                        {{ profile?.coin_balance ?? 0 }}
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showCreateForm"
            class="animate-fade-in rounded-2xl border border-slate-700 bg-slate-800 p-6 shadow-lg"
        >
            <h3 class="mb-4 text-lg font-bold text-white">Stock New Item</h3>
            <form @submit.prevent="submitCreate" class="flex flex-col items-end gap-4 md:flex-row">
                <div class="w-full flex-1">
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Item Name</label>
                    <input
                        v-model="createForm.name"
                        placeholder="e.g. 1 Hour Gaming"
                        class="input-dark w-full"
                        autofocus
                    />
                    <div v-if="createForm.errors.name" class="mt-1 text-xs text-red-400">
                        {{ createForm.errors.name }}
                    </div>
                </div>
                <div class="w-full md:w-32">
                    <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Price</label>
                    <input type="number" v-model="createForm.cost_coin" class="input-dark w-full" />
                </div>
                <div class="flex w-full gap-2 md:w-auto">
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="flex-1 rounded-lg bg-indigo-600 px-6 py-2.5 font-bold text-white transition-all hover:bg-indigo-500 md:flex-none"
                    >
                        Add Item
                    </button>
                    <button
                        type="button"
                        @click="showCreateForm = false"
                        class="rounded-lg bg-slate-700 px-4 py-2.5 text-slate-300 hover:bg-slate-600"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <div v-else class="flex justify-end">
            <button
                @click="showCreateForm = true"
                class="flex items-center gap-2 rounded-lg border border-slate-600 bg-slate-800 px-4 py-2 text-sm text-slate-300 transition-colors hover:bg-slate-700"
            >
                <span>+ Stock New Item</span>
            </button>
        </div>

        <div v-if="rewards.length === 0" class="py-16 text-center opacity-50">
            <div class="mb-4 text-5xl">🕸️</div>
            <h3 class="text-xl font-bold text-slate-400">Shop is Empty</h3>
            <p class="text-sm text-slate-500">Stock some rewards to motivate yourself.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div
                v-for="reward in rewards"
                :key="reward.id"
                class="group relative flex flex-col overflow-hidden rounded-xl border border-slate-700 bg-slate-800 shadow-lg transition-all hover:border-yellow-500/50 hover:shadow-[0_0_20px_rgba(234,179,8,0.1)]"
            >
                <div
                    class="flex h-32 items-center justify-center bg-slate-900/50 text-6xl transition-transform duration-500 group-hover:scale-110"
                >
                    🎁
                </div>

                <div class="flex flex-1 flex-col p-5">
                    <h3
                        class="mb-1 text-lg font-bold text-white transition-colors group-hover:text-yellow-400"
                    >
                        {{ reward.name }}
                    </h3>

                    <div class="mt-auto flex items-center justify-between pt-4">
                        <div class="flex items-center gap-1 font-mono text-xl font-bold text-yellow-500">
                            <CoinIcon cls="w-5 h-5 drop-shadow-[0_0_6px_rgba(234,179,8,0.45)]" />
                            {{ reward.cost_coin }}
                        </div>

                        <button
                            @click="openBuyModal(reward)"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-lg transition-all hover:bg-indigo-500 active:scale-95"
                        >
                            Buy
                        </button>
                    </div>
                </div>

                <div
                    class="absolute right-2 top-2 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <button
                        @click="startEdit(reward)"
                        class="rounded bg-slate-700/80 p-1.5 text-slate-300 hover:bg-indigo-600 hover:text-white"
                        title="Edit"
                    >
                        ✏️
                    </button>
                    <button
                        @click="deleteReward(reward)"
                        class="rounded bg-slate-700/80 p-1.5 text-slate-300 hover:bg-red-600 hover:text-white"
                        title="Delete"
                    >
                        🗑️
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="buyingReward"
            class="animate-fade-in fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-xl border border-yellow-500/30 bg-slate-800 shadow-[0_0_40px_rgba(234,179,8,0.1)]"
            >
                <div class="border-b border-slate-700 bg-slate-900/50 px-6 py-4">
                    <h3 class="text-lg font-bold text-white">Purchase Item</h3>
                </div>

                <form @submit.prevent="submitBuy" class="space-y-6 p-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-lg bg-slate-700 text-3xl"
                        >
                            🎁
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase text-slate-400">Buying</div>
                            <div class="text-xl font-bold text-white">{{ buyingReward.name }}</div>
                            <div class="font-mono text-sm text-yellow-500">
                                Price: {{ buyingReward.cost_coin }} coins
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Quantity</label>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="buyForm.qty = Math.max(1, buyForm.qty - 1)"
                                class="h-10 w-10 rounded bg-slate-700 font-bold text-white hover:bg-slate-600"
                            >
                                -
                            </button>
                            <input
                                type="number"
                                v-model="buyForm.qty"
                                class="input-dark w-20 text-center text-xl font-bold"
                                min="1"
                            />
                            <button
                                type="button"
                                @click="buyForm.qty++"
                                class="h-10 w-10 rounded bg-slate-700 font-bold text-white hover:bg-slate-600"
                            >
                                +
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">
                            Usage Note (Optional)
                        </label>
                        <textarea
                            v-model="buyForm.note"
                            rows="2"
                            placeholder="e.g. 'Watched Favorite Streamer...'"
                            class="input-dark w-full resize-none"
                        ></textarea>
                    </div>

                    <div
                        class="flex items-center justify-between rounded-lg border border-slate-700 bg-slate-900/50 p-4"
                    >
                        <div>
                            <div class="text-xs text-slate-500">Total Cost</div>
                            <div
                                class="font-mono text-xl font-bold"
                                :class="canAfford ? 'text-white' : 'text-red-400'"
                            >
                                🪙 {{ totalCost }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-slate-500">Your Balance</div>
                            <div class="text-sm font-bold text-yellow-500">{{ profile.coin_balance }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeBuyModal"
                            class="px-4 py-2 text-slate-400 transition-colors hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="!canAfford || buyForm.processing"
                            class="flex items-center gap-2 rounded-lg bg-yellow-600 px-6 py-2 font-bold text-white shadow-lg transition-all hover:bg-yellow-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span v-if="canAfford">Confirm Purchase</span>
                            <span v-else>Insufficient Funds</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="editingReward"
            class="animate-fade-in fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-sm rounded-xl border border-slate-600 bg-slate-800 p-6 shadow-2xl">
                <h3 class="mb-4 text-lg font-bold text-white">Edit Reward</h3>
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Name</label>
                        <input v-model="editForm.name" class="input-dark w-full" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Cost</label>
                        <input type="number" v-model="editForm.cost_coin" class="input-dark w-full" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="editingReward = null"
                            class="text-slate-400 hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded bg-indigo-600 px-4 py-2 font-bold text-white hover:bg-indigo-500"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.input-dark {
    @apply rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200 placeholder-slate-500 outline-none transition-all focus:ring-1 focus:ring-yellow-500;
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out;
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
</style>
