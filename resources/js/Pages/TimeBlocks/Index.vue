<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
    week: Object,
    days: Array,
});

const form = useForm({
    date: props.week.start,
    start_time: "09:00",
    end_time: "10:00",
    title: "",
    note: "",
});

const goWeek = (start) => {
    router.get(
        "/timeblocks",
        { week_start: start },
        { preserveState: true, preserveScroll: true }
    );
};

const prevWeek = () => {
    const d = new Date(props.week.start);
    d.setDate(d.getDate() - 7);
    const ymd = d.toISOString().slice(0, 10);
    goWeek(ymd);
};

const nextWeek = () => {
    const d = new Date(props.week.start);
    d.setDate(d.getDate() + 7);
    const ymd = d.toISOString().slice(0, 10);
    goWeek(ymd);
};

const deleteBlock = (id) => {
    const ok = window.confirm("Delete time block?");
    if (!ok) return;
    router.delete(`/timeblocks/${id}`, { preserveScroll: true });
};

const editingId = ref(null);
const editForm = ref(null);

const startEdit = (b) => {
    editingId.value = b.id;
    editForm.value = useForm({
        date: b.date,
        start_time: b.start_time,
        end_time: b.end_time,
        title: b.title,
        note: b.note ?? "",
    });
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.value = null;
};

const saveEdit = () => {
    editForm.value.patch(`/timeblocks/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
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
                gap: 12px;
                flex-wrap: wrap;
            "
        >
            <h2>Timeblocks</h2>
            <div style="display: flex; gap: 8px">
                <button type="button" @click="prevWeek">Prev week</button>
                <button type="button" @click="nextWeek">Next week</button>
            </div>
        </div>

        <div style="opacity: 0.7; margin: 6px 0 14px">
            Week: {{ week.start }} → {{ week.end }}
        </div>

        <section
            style="
                margin: 12px 0;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
            "
        >
            <strong>Create Timeblock</strong>

            <form
                @submit.prevent="
                    form.post('/timeblocks', { preserveScroll: true })
                "
                style="margin-top: 10px"
            >
                <div
                    style="
                        display: flex;
                        gap: 10px;
                        flex-wrap: wrap;
                        align-items: end;
                    "
                >
                    <div>
                        <div>Date</div>
                        <input type="date" v-model="form.date" />
                        <div v-if="form.errors.date" style="color: #b00020">
                            {{ form.errors.date }}
                        </div>
                    </div>

                    <div>
                        <div>Start</div>
                        <input type="time" v-model="form.start_time" />
                        <div
                            v-if="form.errors.start_time"
                            style="color: #b00020"
                        >
                            {{ form.errors.start_time }}
                        </div>
                    </div>

                    <div>
                        <div>End</div>
                        <input type="time" v-model="form.end_time" />
                        <div v-if="form.errors.end_time" style="color: #b00020">
                            {{ form.errors.end_time }}
                        </div>
                    </div>

                    <div style="min-width: 240px">
                        <div>Title</div>
                        <input
                            v-model="form.title"
                            placeholder="e.g. Deep work Vue"
                            style="width: 100%"
                        />
                        <div v-if="form.errors.title" style="color: #b00020">
                            {{ form.errors.title }}
                        </div>
                    </div>

                    <button type="submit" :disabled="form.processing">
                        Add
                    </button>
                </div>

                <div style="margin-top: 10px">
                    <div>Note (optional)</div>
                    <textarea
                        v-model="form.note"
                        rows="2"
                        style="width: 100%"
                    ></textarea>
                    <div v-if="form.errors.note" style="color: #b00020">
                        {{ form.errors.note }}
                    </div>
                </div>
            </form>
        </section>

        <section style="margin-top: 16px">
            <h3>Week View</h3>

            <div
                v-for="day in days"
                :key="day.date"
                style="
                    margin: 12px 0;
                    padding: 10px;
                    border: 1px solid #eee;
                    border-radius: 8px;
                "
            >
                <div
                    style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    "
                >
                    <strong>{{ day.date }}</strong>
                    <span style="opacity: 0.7"
                        >{{ day.items.length }} blocks</span
                    >
                </div>

                <div
                    v-if="day.items.length === 0"
                    style="opacity: 0.7; margin-top: 6px"
                >
                    No blocks.
                </div>

                <ul v-else style="padding-left: 18px; margin-top: 8px">
                    <li
                        v-for="b in day.items"
                        :key="b.id"
                        style="margin: 8px 0"
                    >
                        <template v-if="editingId === b.id">
                            <div
                                style="
                                    display: flex;
                                    gap: 10px;
                                    flex-wrap: wrap;
                                    align-items: end;
                                "
                            >
                                <div>
                                    <div>Date</div>
                                    <input
                                        type="date"
                                        v-model="editForm.date"
                                    />
                                </div>

                                <div>
                                    <div>Start</div>
                                    <input
                                        type="time"
                                        v-model="editForm.start_time"
                                    />
                                </div>

                                <div>
                                    <div>End</div>
                                    <input
                                        type="time"
                                        v-model="editForm.end_time"
                                    />
                                </div>

                                <div style="min-width: 240px; flex: 1">
                                    <div>Title</div>
                                    <input
                                        v-model="editForm.title"
                                        style="width: 100%"
                                    />
                                </div>
                            </div>

                            <div style="margin-top: 10px">
                                <div>Note</div>
                                <textarea
                                    v-model="editForm.note"
                                    rows="2"
                                    style="width: 100%"
                                ></textarea>
                            </div>

                            <div
                                style="
                                    margin-top: 10px;
                                    display: flex;
                                    gap: 8px;
                                    flex-wrap: wrap;
                                "
                            >
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
                            </div>

                            <div
                                v-if="editForm.errors.end_time"
                                style="color: #b00020; margin-top: 6px"
                            >
                                {{ editForm.errors.end_time }}
                            </div>
                            <div
                                v-if="editForm.errors.title"
                                style="color: #b00020; margin-top: 6px"
                            >
                                {{ editForm.errors.title }}
                            </div>
                        </template>

                        <template v-else>
                            <div>
                                <strong
                                    >{{ b.start_time }} -
                                    {{ b.end_time }}</strong
                                >
                                — {{ b.title }}
                            </div>
                            <div
                                v-if="b.note"
                                style="
                                    opacity: 0.7;
                                    font-size: 13px;
                                    margin-top: 2px;
                                "
                            >
                                {{ b.note }}
                            </div>

                            <div
                                style="
                                    margin-top: 6px;
                                    display: flex;
                                    gap: 8px;
                                    flex-wrap: wrap;
                                "
                            >
                                <button type="button" @click="startEdit(b)">
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    @click="deleteBlock(b.id)"
                                    style="color: #b00020"
                                >
                                    Delete
                                </button>
                            </div>
                        </template>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>
