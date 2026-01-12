<script setup>
import { Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
defineOptions({ layout: AppLayout });

const props = defineProps({
    quests: Object,
    filters: Object,
    typeOptions: Array,
});

const editingId = ref(null);
const editForm = ref(null);

const startEdit = (q) => {
    editingId.value = q.id;
    editForm.value = useForm({
        name: q.name,
        status: q.status,
        type: q.type,
        xp_reward: q.xp_reward,
        coin_reward: q.coin_reward,
        due_date: q.due_date, // string YYYY-MM-DD atau null
        is_repeatable: !!q.is_repeatable,
    });
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.value = null;
};

const saveEdit = () => {
    editForm.value.patch(`/quests/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

const canComplete = (q) => {
    if (q.status === "locked") return false;
    if (!q.is_repeatable && q.status === "done") return false;
    return true;
};

const completeQuest = (q) => {
    if (!canComplete(q)) return;

    const note = window.prompt("Note (optional):", "") ?? "";

    router.patch(
        `/quests/${q.id}/complete`,
        { note },
        {
            preserveScroll: true,
        }
    );
};

const deleteQuest = (q) => {
    const ok = window.confirm(`Delete quest "${q.name}"?`);
    if (!ok) return;

    router.delete(`/quests/${q.id}`, {
        preserveScroll: true,
    });
};

const filterForm = useForm({
    status: props.filters?.status ?? "",
    type: props.filters?.type ?? "",
    repeatable: props.filters?.repeatable ?? "",
    sort: props.filters?.sort ?? "created_at",
    dir: props.filters?.dir ?? "desc",
});

const applyFilters = () => {
    router.get(
        "/quests",
        {
            ...filterForm.data(),
        },
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
};

const resetFilters = () => {
    filterForm.status = "";
    filterForm.type = "";
    filterForm.repeatable = "";
    filterForm.sort = "created_at";
    filterForm.dir = "desc";

    applyFilters();
};
</script>

<template>
    <div style="padding: 16px">
        <div
            style="
                display: flex;
                gap: 12px;
                align-items: center;
                justify-content: space-between;
            "
        >
            <h2>Quests</h2>
        </div>

        <p style="margin: 8px 0 16px">
            Edit quest dilakukan di sini. Status "done" hanya lewat complete.
        </p>

        <div
            v-if="$page.props.errors?.delete"
            style="color: #b00020; margin: 10px 0"
        >
            {{ $page.props.errors.delete }}
        </div>

        <section
            style="
                margin: 12px 0;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
            "
        >
            <strong>Filter & Sort</strong>

            <div
                style="
                    display: flex;
                    gap: 12px;
                    flex-wrap: wrap;
                    margin-top: 10px;
                    align-items: end;
                "
            >
                <div>
                    <div>Status</div>
                    <select v-model="filterForm.status">
                        <option value="">All</option>
                        <option value="todo">todo</option>
                        <option value="in_progress">in_progress</option>
                        <option value="locked">locked</option>
                        <option value="done">done</option>
                    </select>
                </div>

                <div>
                    <div>Type</div>
                    <select v-model="filterForm.type">
                        <option value="">All</option>
                        <option v-for="t in typeOptions" :key="t" :value="t">
                            {{ t }}
                        </option>
                    </select>
                </div>

                <div>
                    <div>Repeatable</div>
                    <select v-model="filterForm.repeatable">
                        <option value="">All</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div>
                    <div>Sort</div>
                    <select v-model="filterForm.sort">
                        <option value="created_at">created_at</option>
                        <option value="name">name</option>
                        <option value="due_date">due_date</option>
                        <option value="xp_reward">xp_reward</option>
                        <option value="coin_reward">coin_reward</option>
                        <option value="completed_at">completed_at</option>
                    </select>
                </div>

                <div>
                    <div>Dir</div>
                    <select v-model="filterForm.dir">
                        <option value="desc">desc</option>
                        <option value="asc">asc</option>
                    </select>
                </div>

                <div style="display: flex; gap: 8px">
                    <button type="button" @click="applyFilters">Apply</button>
                    <button type="button" @click="resetFilters">Reset</button>
                </div>
            </div>
        </section>

        <div v-if="quests.data.length === 0">Belum ada quest.</div>

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
                    <th>Status</th>
                    <th>Type</th>
                    <th>XP</th>
                    <th>Coin</th>
                    <th>Repeatable</th>
                    <th>Due</th>
                    <th>Completed</th>
                    <th>Complete</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="q in quests.data" :key="q.id">
                    <!-- NAME -->
                    <td>
                        <template v-if="editingId === q.id">
                            <input
                                v-model="editForm.name"
                                style="width: 100%"
                            />
                            <div
                                v-if="editForm.errors.name"
                                style="color: #b00020"
                            >
                                {{ editForm.errors.name }}
                            </div>
                        </template>
                        <template v-else>
                            {{ q.name }}
                        </template>
                    </td>

                    <!-- STATUS -->
                    <td>
                        <template v-if="editingId === q.id">
                            <select v-model="editForm.status">
                                <option value="todo">todo</option>
                                <option value="in_progress">in_progress</option>
                                <option value="locked">locked</option>
                            </select>
                            <div
                                v-if="editForm.errors.status"
                                style="color: #b00020"
                            >
                                {{ editForm.errors.status }}
                            </div>
                        </template>
                        <template v-else>
                            {{ q.status }}
                        </template>
                    </td>

                    <!-- TYPE -->
                    <td>
                        <template v-if="editingId === q.id">
                            <input
                                v-model="editForm.type"
                                style="width: 100%"
                            />
                            <div
                                v-if="editForm.errors.type"
                                style="color: #b00020"
                            >
                                {{ editForm.errors.type }}
                            </div>
                        </template>
                        <template v-else>
                            {{ q.type }}
                        </template>
                    </td>

                    <!-- XP -->
                    <td>
                        <template v-if="editingId === q.id">
                            <input
                                type="number"
                                v-model.number="editForm.xp_reward"
                                style="width: 80px"
                            />
                            <div
                                v-if="editForm.errors.xp_reward"
                                style="color: #b00020"
                            >
                                {{ editForm.errors.xp_reward }}
                            </div>
                        </template>
                        <template v-else>
                            {{ q.xp_reward }}
                        </template>
                    </td>

                    <!-- COIN -->
                    <td>
                        <template v-if="editingId === q.id">
                            <input
                                type="number"
                                v-model.number="editForm.coin_reward"
                                style="width: 80px"
                            />
                            <div
                                v-if="editForm.errors.coin_reward"
                                style="color: #b00020"
                            >
                                {{ editForm.errors.coin_reward }}
                            </div>
                        </template>
                        <template v-else>
                            {{ q.coin_reward }}
                        </template>
                    </td>

                    <!-- REPEATABLE -->
                    <td style="text-align: center">
                        <template v-if="editingId === q.id">
                            <input
                                type="checkbox"
                                v-model="editForm.is_repeatable"
                            />
                            <div
                                v-if="editForm.errors.is_repeatable"
                                style="color: #b00020"
                            >
                                {{ editForm.errors.is_repeatable }}
                            </div>
                        </template>
                        <template v-else>
                            {{ q.is_repeatable ? "yes" : "no" }}
                        </template>
                    </td>

                    <!-- DUE -->
                    <td>
                        <template v-if="editingId === q.id">
                            <input type="date" v-model="editForm.due_date" />
                            <div
                                v-if="editForm.errors.due_date"
                                style="color: #b00020"
                            >
                                {{ editForm.errors.due_date }}
                            </div>
                        </template>
                        <template v-else>
                            {{ q.due_date ?? "-" }}
                        </template>
                    </td>

                    <!-- COMPLETED -->
                    <td>
                        {{ q.completed_at ?? "-" }}
                    </td>

                    <td>
                        <button
                            @click="completeQuest(q)"
                            :disabled="!canComplete(q)"
                            type="button"
                        >
                            Complete
                        </button>
                    </td>

                    <!-- ACTION -->
                    <td>
                        <template v-if="editingId === q.id">
                            <button
                                @click="saveEdit"
                                :disabled="editForm.processing"
                            >
                                Save
                            </button>
                            <button
                                @click="cancelEdit"
                                type="button"
                                style="margin-left: 8px"
                            >
                                Cancel
                            </button>
                        </template>

                        <template v-else>
                            <button @click="startEdit(q)" type="button">
                                Edit
                            </button>

                            <button
                                @click="deleteQuest(q)"
                                type="button"
                                style="margin-left: 8px; color: #b00020"
                            >
                                Delete
                            </button>
                        </template>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px">
            <Link
                v-for="link in quests.links"
                :key="link.label"
                :href="link.url || ''"
                v-html="link.label"
                :style="{
                    padding: '6px 10px',
                    border: '1px solid #ddd',
                    borderRadius: '6px',
                    textDecoration: 'none',
                    opacity: link.url ? 1 : 0.4,
                    pointerEvents: link.url ? 'auto' : 'none',
                    background: link.active ? '#eee' : 'transparent',
                }"
            />
        </div>
    </div>
</template>
