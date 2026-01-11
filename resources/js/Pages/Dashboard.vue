<script setup>
import { useForm, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
defineOptions({ layout: AppLayout });

const props = defineProps({
    profile: Object,
    activeQuests: Array,
    habits: Array,
    habitSummary: Object,
    level: Object,
    today: String,
});

const createForm = useForm({
    name: "",
    status: "todo",
    type: "Daily Grind",
    xp_reward: 70,
    coin_reward: 70,
    due_date: null,
    is_repeatable: true,
});

const completeForms = {};

const getCompleteForm = (id) => {
    if (!completeForms[id]) {
        completeForms[id] = useForm({ note: "" });
    }
    return completeForms[id];
};

const completeQuest = (id) => {
    const form = getCompleteForm(id);
    form.patch(`/quests/${id}/complete`, {
        preserveScroll: true,
        onSuccess: () => form.reset("note"),
    });
};

const toggleHabit = (id) => {
    router.patch(
        `/habits/${id}/toggle`,
        {},
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <div style="padding: 16px">
        <h2>Dashboard</h2>

        <section style="margin: 12px 0">
            <h3>Profile</h3>
            <div>Level: {{ level.level }}</div>
            <div>XP: {{ level.xp_into_level }} / {{ level.xp_needed }}</div>
            <div>Progress: {{ level.progress }}%</div>
            <div>Coins: {{ profile?.coin_balance }}</div>
            <div>Streak: {{ profile?.current_streak }}</div>
        </section>

        <hr />

        <section style="margin: 12px 0">
            <h3>Create Quest</h3>

            <form @submit.prevent="createForm.post('/quests')">
                <div>
                    <input v-model="createForm.name" placeholder="Quest name" />
                    <div v-if="createForm.errors.name">
                        {{ createForm.errors.name }}
                    </div>
                </div>

                <div>
                    <select v-model="createForm.status">
                        <option value="todo">todo</option>
                        <option value="in_progress">in_progress</option>
                        <option value="locked">locked</option>
                    </select>
                </div>

                <div>
                    <input v-model="createForm.type" placeholder="Type" />
                </div>

                <div>
                    <input
                        type="number"
                        v-model.number="createForm.xp_reward"
                    />
                    <input
                        type="number"
                        v-model.number="createForm.coin_reward"
                    />
                </div>

                <div>
                    <input type="date" v-model="createForm.due_date" />
                </div>

                <div>
                    <label>
                        <input
                            type="checkbox"
                            v-model="createForm.is_repeatable"
                        />
                        Repeatable
                    </label>
                </div>

                <button type="submit" :disabled="createForm.processing">
                    Create
                </button>
            </form>
        </section>

        <hr />

        <section style="margin: 12px 0">
            <h3>Active Quests</h3>

            <ul>
                <li
                    v-for="q in activeQuests"
                    :key="q.id"
                    style="margin-bottom: 14px"
                >
                    <div>
                        <strong>{{ q.name }}</strong>
                        <span>
                            — {{ q.status }} | {{ q.type }} | repeatable:
                            {{ q.is_repeatable ? "yes" : "no" }}
                        </span>
                    </div>

                    <div>
                        XP: {{ q.xp_reward }} | Coins: {{ q.coin_reward }}
                    </div>

                    <div v-if="q.due_date" style="margin-top: 4px">
                        Due: <strong>{{ q.due_date }}</strong>
                        <span
                            v-if="q.due_date < today"
                            style="color: #b00020; margin-left: 8px"
                        >
                            OVERDUE
                        </span>
                    </div>
                    <div v-else style="margin-top: 4px; opacity: 0.7">
                        No due date
                    </div>

                    <div style="margin-top: 6px">
                        <input
                            v-model="getCompleteForm(q.id).note"
                            placeholder="Note (optional)"
                            style="width: 60%"
                        />

                        <button
                            @click="completeQuest(q.id)"
                            :disabled="getCompleteForm(q.id).processing"
                            style="margin-left: 8px"
                        >
                            Complete
                        </button>
                    </div>

                    <div v-if="getCompleteForm(q.id).errors.note">
                        {{ getCompleteForm(q.id).errors.note }}
                    </div>
                </li>
            </ul>
        </section>

        <hr />

        <section style="margin: 16px 0">
            <div style="margin-bottom: 8px; opacity: 0.8">
                Done today: {{ habitSummary?.done_today ?? 0 }} /
                {{ habitSummary?.total ?? 0 }}
            </div>

            <h3>Habits (Today)</h3>

            <div v-if="!habits || habits.length === 0" style="opacity: 0.7">
                Belum ada habit aktif.
            </div>

            <ul v-else style="padding-left: 18px">
                <li v-for="h in habits" :key="h.id" style="margin: 10px 0">
                    <label
                        style="display: flex; gap: 10px; align-items: center"
                    >
                        <input
                            type="checkbox"
                            :checked="h.done_today"
                            @change="toggleHabit(h.id)"
                        />

                        <div>
                            <div>
                                <strong>{{ h.name }}</strong>
                                <span style="opacity: 0.7">
                                    (streak: {{ h.streak }})</span
                                >
                                <span style="margin-left: 8px"
                                    >Today:
                                    {{ h.done_today ? "✅" : "❌" }}</span
                                >
                            </div>

                            <div style="opacity: 0.7; font-size: 13px">
                                start: {{ h.start_date
                                }}<span v-if="h.end_date">
                                    | end: {{ h.end_date }}</span
                                >
                            </div>
                        </div>
                    </label>
                </li>
            </ul>
        </section>
    </div>
</template>
