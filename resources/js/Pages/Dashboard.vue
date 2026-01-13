<script setup>
import { useForm, router, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { watch } from "vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
    profile: Object,
    activeQuests: Array,
    habits: Array,
    habitSummary: Object,
    today: String,
    todayBlocks: Array,
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

watch(
    () => createForm.is_repeatable,
    (val) => {
        if (val) createForm.due_date = null;
    }
);

const timeblockForm = useForm({
    date: props.today, // auto today dari backend
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
            // biar date tetap hari ini
            timeblockForm.date = props.today;
        },
    });
};

const deleteTimeblock = (id) => {
    const ok = window.confirm("Delete timeblock?");
    if (!ok) return;

    router.delete(`/timeblocks/${id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div style="padding: 16px">
        <h2>Dashboard</h2>

        <section
            style="margin: 12px 0; display: flex; gap: 12px; flex-wrap: wrap"
        >
            <Link href="/quests" as="button" style="padding: 6px 10px"
                >View all quests</Link
            >

            <Link href="/logs/completions" as="button" style="padding: 6px 10px"
                >View completion logs</Link
            >
            <Link href="/treasury" as="button" style="padding: 6px 10px"
                >View treasury</Link
            >
        </section>

        <hr />

        <section style="margin: 12px 0">
            <h3>Profile</h3>
            <div>Level: {{ profile.level_data.current_level }}</div>
            <div>XP: {{ profile.level_data.xp_current }} / {{ profile.level_data.xp_needed }}</div>
            <div>Progress: {{ profile.level_data.progress_percent }}%</div>
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

                <div v-if="!createForm.is_repeatable">
                    <input type="date" v-model="createForm.due_date" />
                </div>

                <div
                    v-else
                    style="opacity: 0.7; font-size: 13px; margin-top: 6px"
                >
                    Repeatable quest: due date otomatis dianggap nggak dipakai.
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

        <hr />

        <section style="margin: 12px 0">
            <div
                style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 12px;
                    flex-wrap: wrap;
                "
            >
                <h3>Timeblocks (Today)</h3>
                <Link href="/timeblocks">View all</Link>
            </div>

            <form @submit.prevent="addTimeblock" style="margin-top: 10px">
                <div
                    style="
                        display: flex;
                        gap: 10px;
                        flex-wrap: wrap;
                        align-items: end;
                    "
                >
                    <div>
                        <div>Start</div>
                        <input type="time" v-model="timeblockForm.start_time" />
                        <div
                            v-if="timeblockForm.errors.start_time"
                            style="color: #b00020"
                        >
                            {{ timeblockForm.errors.start_time }}
                        </div>
                    </div>

                    <div>
                        <div>End</div>
                        <input type="time" v-model="timeblockForm.end_time" />
                        <div
                            v-if="timeblockForm.errors.end_time"
                            style="color: #b00020"
                        >
                            {{ timeblockForm.errors.end_time }}
                        </div>
                    </div>

                    <div style="min-width: 240px; flex: 1">
                        <div>Title</div>
                        <input
                            v-model="timeblockForm.title"
                            placeholder="e.g. Deep work Laravel"
                            style="width: 100%"
                        />
                        <div
                            v-if="timeblockForm.errors.title"
                            style="color: #b00020"
                        >
                            {{ timeblockForm.errors.title }}
                        </div>
                    </div>

                    <button type="submit" :disabled="timeblockForm.processing">
                        Add
                    </button>
                </div>

                <div style="margin-top: 10px">
                    <div>Note (optional)</div>
                    <textarea
                        v-model="timeblockForm.note"
                        rows="2"
                        style="width: 100%"
                    ></textarea>
                    <div
                        v-if="timeblockForm.errors.note"
                        style="color: #b00020"
                    >
                        {{ timeblockForm.errors.note }}
                    </div>
                </div>
            </form>

            <div
                v-if="!todayBlocks || todayBlocks.length === 0"
                style="opacity: 0.7"
            >
                Belum ada timeblock hari ini.
            </div>

            <ul v-else style="padding-left: 18px; margin-top: 8px">
                <li v-for="b in todayBlocks" :key="b.id" style="margin: 8px 0">
                    <div>
                        <strong>{{ b.start_time }} - {{ b.end_time }}</strong> —
                        {{ b.title }}
                    </div>
                    <div
                        v-if="b.note"
                        style="opacity: 0.7; font-size: 13px; margin-top: 2px"
                    >
                        {{ b.note }}
                    </div>

                    <button
                        type="button"
                        @click="deleteTimeblock(b.id)"
                        style="margin-top: 6px; color: #b00020"
                    >
                        Delete
                    </button>
                </li>
            </ul>
        </section>
    </div>
</template>
