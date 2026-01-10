<script setup>
import { Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
defineOptions({ layout: AppLayout });

const props = defineProps({
    profile: Object,
    rewards: Array,
    errors: Object, // inertia biasanya inject errors juga, tapi aman kita handle
});

const createForm = useForm({
    name: "",
    cost_coin: 0,
});

const buyReward = (reward) => {
    const qtyStr = window.prompt("Qty:", "1");
    if (qtyStr === null) return;

    const qty = parseInt(qtyStr, 10);
    if (!Number.isInteger(qty) || qty < 1) return;

    const note = window.prompt("Note (optional):", "") ?? "";

    router.patch(
        `/treasury/rewards/${reward.id}/buy`,
        { qty, note },
        {
            preserveScroll: true,
        }
    );
};

const editingId = ref(null);
const editForm = ref(null);

const startEdit = (r) => {
    editingId.value = r.id;
    editForm.value = useForm({
        name: r.name,
        cost_coin: r.cost_coin,
    });
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.value = null;
};

const saveEdit = () => {
    editForm.value.patch(`/treasury/rewards/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

const deleteReward = (r) => {
    const ok = window.confirm(`Delete reward "${r.name}"?`);
    if (!ok) return;

    router.delete(`/treasury/rewards/${r.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div style="padding: 16px">
        <div
            style="
                display: flex;
                justify-content: space-between;
                align-items: center;
            "
        >
            <h2>Treasury</h2>
        </div>

        <section style="margin: 12px 0">
            <h3>Balance</h3>
            <div>
                Coins: <strong>{{ profile?.coin_balance ?? 0 }}</strong>
            </div>
        </section>

        <section
            v-if="$page.props.errors?.coin"
            style="margin: 12px 0; color: #b00020"
        >
            {{ $page.props.errors.coin }}
        </section>

        <hr />

        <section style="margin: 12px 0">
            <h3>Create Reward</h3>

            <form @submit.prevent="createForm.post('/treasury/rewards')">
                <div
                    style="
                        display: flex;
                        gap: 10px;
                        align-items: end;
                        flex-wrap: wrap;
                    "
                >
                    <div>
                        <div>Name</div>
                        <input
                            v-model="createForm.name"
                            placeholder="e.g. Kopi"
                        />
                        <div
                            v-if="createForm.errors.name"
                            style="color: #b00020"
                        >
                            {{ createForm.errors.name }}
                        </div>
                    </div>

                    <div>
                        <div>Cost (coin)</div>
                        <input
                            type="number"
                            v-model.number="createForm.cost_coin"
                            style="width: 120px"
                        />
                        <div
                            v-if="createForm.errors.cost_coin"
                            style="color: #b00020"
                        >
                            {{ createForm.errors.cost_coin }}
                        </div>
                    </div>

                    <button type="submit" :disabled="createForm.processing">
                        Add
                    </button>
                </div>
            </form>
        </section>

        <hr />

        <section style="margin: 12px 0">
            <h3>Rewards</h3>

            <div v-if="rewards.length === 0">Belum ada reward.</div>

            <table
                v-else
                border="1"
                cellpadding="8"
                cellspacing="0"
                style="width: 100%; border-collapse: collapse"
            >
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Cost</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="r in rewards" :key="r.id">
                        <td>
                            <template v-if="editingId === r.id">
                                <input v-model="editForm.name" />
                                <div
                                    v-if="editForm.errors.name"
                                    style="color: #b00020"
                                >
                                    {{ editForm.errors.name }}
                                </div>
                            </template>
                            <template v-else>
                                {{ r.name }}
                            </template>
                        </td>

                        <td>
                            <template v-if="editingId === r.id">
                                <input
                                    type="number"
                                    v-model.number="editForm.cost_coin"
                                    style="width: 120px"
                                />
                                <div
                                    v-if="editForm.errors.cost_coin"
                                    style="color: #b00020"
                                >
                                    {{ editForm.errors.cost_coin }}
                                </div>
                            </template>
                            <template v-else>
                                {{ r.cost_coin }}
                            </template>
                        </td>

                        <td style="display: flex; gap: 8px; flex-wrap: wrap">
                            <button type="button" @click="buyReward(r)">
                                Buy
                            </button>

                            <template v-if="editingId === r.id">
                                <button
                                    type="button"
                                    @click="saveEdit"
                                    :disabled="editForm.processing"
                                >
                                    Save
                                </button>
                                <button type="button" @click="cancelEdit">
                                    Cancel
                                </button>
                            </template>
                            <template v-else>
                                <button type="button" @click="startEdit(r)">
                                    Edit
                                </button>
                            </template>

                            <button type="button" @click="deleteReward(r)">
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
