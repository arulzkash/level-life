<script setup>
import { Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    logs: Object,
    filters: Object,
});

const editingId = ref(null);
const editForm = ref(null);

const startEdit = (log) => {
    editingId.value = log.id;
    editForm.value = useForm({
        note: log.note ?? "",
    });
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.value = null;
};

const saveEdit = () => {
    editForm.value.patch(`/logs/completions/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

const filterForm = useForm({
    period: props.filters?.period ?? "all",
    date: props.filters?.date ?? "",
    from: props.filters?.from ?? "",
    to: props.filters?.to ?? "",
    sort: props.filters?.sort ?? "completed_at",
    dir: props.filters?.dir ?? "desc",
});

const apply = () => {
    router.get("/logs/completions", filterForm.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearCustomDates = () => {
    filterForm.date = "";
    filterForm.from = "";
    filterForm.to = "";
};

const setPeriod = (p) => {
    filterForm.period = p;
    clearCustomDates();
    apply();
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
            <h2>Completion Log</h2>
            <div style="display: flex; gap: 12px">
                <Link href="/dashboard">Dashboard</Link>
                <Link href="/quests">Quests</Link>
            </div>
        </div>

        <section
            style="
                margin: 12px 0;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
            "
        >
            <strong>Filter</strong>

            <div
                style="
                    display: flex;
                    gap: 10px;
                    margin-top: 8px;
                    flex-wrap: wrap;
                "
            >
                <button type="button" @click="setPeriod('all')">All</button>
                <button type="button" @click="setPeriod('today')">Today</button>
                <button type="button" @click="setPeriod('7d')">
                    Last 7 days
                </button>
                <button type="button" @click="setPeriod('month')">
                    This month
                </button>
            </div>

            <div
                style="
                    display: flex;
                    gap: 12px;
                    flex-wrap: wrap;
                    margin-top: 12px;
                    align-items: end;
                "
            >
                <div>
                    <div>Pick a date</div>
                    <input type="date" v-model="filterForm.date" />
                </div>

                <div>
                    <div>From</div>
                    <input type="date" v-model="filterForm.from" />
                </div>

                <div>
                    <div>To</div>
                    <input type="date" v-model="filterForm.to" />
                </div>

                <div>
                    <div>Sort</div>
                    <select v-model="filterForm.sort">
                        <option value="completed_at">completed_at</option>
                        <option value="xp_awarded">xp_awarded</option>
                        <option value="coin_awarded">coin_awarded</option>
                        <option value="created_at">created_at</option>
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
                    <button type="button" @click="apply">Apply</button>
                    <button
                        type="button"
                        @click="
                            clearCustomDates();
                            filterForm.period = 'all';
                            apply();
                        "
                    >
                        Reset
                    </button>
                </div>
            </div>

            <div style="margin-top: 8px; opacity: 0.7">
                Note: kalau date / from-to diisi, itu override period.
            </div>
        </section>

        <div v-if="logs.data.length === 0">Belum ada completion.</div>

        <ul v-else>
            <li
                v-for="log in logs.data"
                :key="log.id"
                style="margin-bottom: 14px"
            >
                <div>
                    <strong>{{ log.quest?.name ?? "(Quest deleted)" }}</strong>
                    <span> — {{ log.quest?.type }}</span>
                </div>

                <div>
                    XP: {{ log.xp_awarded }} | Coins: {{ log.coin_awarded }}
                </div>
                <div style="opacity: 0.7">
                    Completed at: {{ log.completed_at }}
                </div>

                <!-- NOTE -->
                <div style="margin-top: 8px">
                    <template v-if="editingId === log.id">
                        <textarea
                            v-model="editForm.note"
                            rows="2"
                            style="width: 100%"
                        ></textarea>
                        <div v-if="editForm.errors.note" style="color: #b00020">
                            {{ editForm.errors.note }}
                        </div>
                        <div style="display: flex; gap: 8px; margin-top: 6px">
                            <button
                                @click="saveEdit"
                                :disabled="editForm.processing"
                                type="button"
                            >
                                Save
                            </button>
                            <button @click="cancelEdit" type="button">
                                Cancel
                            </button>
                        </div>
                    </template>

                    <template v-else>
                        <div v-if="log.note">Note: {{ log.note }}</div>
                        <div v-else style="opacity: 0.7">(No note)</div>
                        <button
                            @click="startEdit(log)"
                            type="button"
                            style="margin-top: 6px"
                        >
                            Edit note
                        </button>
                    </template>
                </div>

                <hr style="margin-top: 10px" />
            </li>
        </ul>

        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px">
            <Link
                v-for="link in logs.links"
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
