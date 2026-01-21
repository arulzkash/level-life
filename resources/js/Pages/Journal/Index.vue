<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, onMounted, ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { onBeforeUnmount } from 'vue';
import { nextTick } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    date: String, // YYYY-MM-DD (selected)
    todayKey: String, // YYYY-MM-DD (Jakarta) from backend
    entry: Object, // null or entry payload
    templates: Array,
    title: String,
});

// ---------- Templates (PLAIN names) ----------
const TEMPLATES = [
    {
        name: 'Daily Review',
        sections: [
            { title: 'Gratitude (3)', content: '' },
            { title: 'Top 3 Wins', content: '' },
            { title: 'Challenges / Friction', content: '' },
            { title: 'Lessons / Insight', content: '' },
            { title: 'Tomorrow (Top 3)', content: '' },
            { title: 'One-line summary', content: '' },
        ],
    },

    {
        name: 'Morning Plan',
        sections: [
            { title: 'Intention', content: '' },
            { title: 'Must-Do (1–3)', content: '' },
            { title: 'Nice-to-Have', content: '' },
            { title: 'If-Then Plan', content: '' },
            { title: 'Distractions to avoid', content: '' },
        ],
    },

    {
        name: '2-Min Check-in',
        sections: [
            { title: 'Right now I feel…', content: '' },
            { title: 'One win', content: '' },
            { title: 'One next step', content: '' },
        ],
    },

    {
        name: 'Gratitude',
        sections: [
            { title: '3 Gratitudes', content: '' },
            { title: 'One person I appreciate', content: '' },
            { title: 'One small joy', content: '' },
        ],
    },

    {
        name: 'Brain Dump',
        sections: [{ title: 'Stream of thoughts (no filter)', content: '' }],
    },

    {
        name: 'CBT Thought Record',
        sections: [
            { title: 'Situation', content: '' },
            { title: 'Automatic thoughts', content: '' },
            { title: 'Feelings (0–100)', content: '' },
            { title: 'Evidence for', content: '' },
            { title: 'Evidence against', content: '' },
            { title: 'Balanced thought', content: '' },
            { title: 'Next action', content: '' },
        ],
    },

    {
        name: 'Stoic Reflection',
        sections: [
            { title: 'What was in my control', content: '' },
            { title: 'What wasn’t', content: '' },
            { title: 'What I did well', content: '' },
            { title: 'What to improve tomorrow', content: '' },
        ],
    },

    {
        name: 'Health & Energy',
        sections: [
            { title: 'Sleep / Energy', content: '' },
            { title: 'Food', content: '' },
            { title: 'Movement', content: '' },
            { title: 'Stress + source', content: '' },
            { title: 'One small health win tomorrow', content: '' },
        ],
    },

    {
        name: 'Idea to Ship',
        sections: [
            { title: 'Idea dump', content: '' },
            { title: 'One idea to ship', content: '' },
            { title: 'Next step', content: '' },
            { title: 'Things to research', content: '' },
        ],
    },

    {
        name: 'Weekly Review',
        sections: [
            { title: 'Top wins', content: '' },
            { title: 'Top lessons', content: '' },
            { title: 'Energized me', content: '' },
            { title: 'Drained me', content: '' },
            { title: 'Stop / Start / Continue', content: '' },
            { title: 'Next week focus', content: '' },
        ],
    },
];

const selectedTemplateId = ref('');
const showMyTemplates = ref(false);

// ---------- Helpers ----------
const newId = () => {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
    return `sec_${Date.now()}_${Math.random().toString(16).slice(2)}`;
};

const draftKey = computed(() => `journal:draft:${props.date}`);

// ---------- Form ----------
const form = useForm({
    date: props.date,
    title: props.entry?.title ?? '',
    mood_emoji: props.entry?.mood_emoji ?? '',
    is_favorite: props.entry?.is_favorite ?? false,
    body: props.entry?.body ?? '',
    sections: props.entry?.sections ?? [],
    // user-set reward (today only, one-time)
    xp_reward: 0,
    coin_reward: 0,
});

const isToday = computed(() => form.date === props.todayKey);
const isClaimed = computed(() => !!props.entry?.rewarded_at);

const goToDate = (d) => {
    router.get('/journal', { date: d }, { preserveScroll: true, preserveState: false });
};

// ---------- Local draft autosave (anti Render sleep) ----------
const saveDraftLocal = debounce(() => {
    const payload = {
        date: form.date,
        title: form.title,
        mood_emoji: form.mood_emoji,
        is_favorite: form.is_favorite,
        body: form.body,
        sections: form.sections,
        savedAt: Date.now(),
    };
    localStorage.setItem(draftKey.value, JSON.stringify(payload));
}, 500);

watch(
    () => [form.title, form.mood_emoji, form.is_favorite, form.body, form.sections],
    () => saveDraftLocal(),
    { deep: true }
);

const hasLocalDraft = ref(false);

const restoreDraft = () => {
    const raw = localStorage.getItem(draftKey.value);
    if (!raw) return;
    try {
        const d = JSON.parse(raw);
        form.title = d.title ?? '';
        form.mood_emoji = d.mood_emoji ?? '';
        form.is_favorite = !!d.is_favorite;
        form.body = d.body ?? '';
        form.sections = d.sections ?? [];
        hasLocalDraft.value = false;
    } catch {}
};

const clearDraft = () => {
    localStorage.removeItem(draftKey.value);
    hasLocalDraft.value = false;
};

// detect draft on load
onMounted(() => {
    const raw = localStorage.getItem(draftKey.value);
    if (!raw) return;
    try {
        const d = JSON.parse(raw);
        const serverBody = props.entry?.body ?? '';
        const serverSections = JSON.stringify(props.entry?.sections ?? []);
        const serverTitle = props.entry?.title ?? '';
        const serverMood = props.entry?.mood_emoji ?? '';
        const serverFav = !!props.entry?.is_favorite;
        const localSections = JSON.stringify(d.sections ?? []);
        if (
            (d.title ?? '') !== serverTitle ||
            (d.mood_emoji ?? '') !== serverMood ||
            !!d.is_favorite !== serverFav ||
            (d.body ?? '') !== serverBody ||
            localSections !== serverSections
        ) {
            hasLocalDraft.value = true;
        }
    } catch {}
});

// ---------- Sections ops ----------
const addSection = async () => {
    const id = newId();
    form.sections.push({ id, title: '', content: '' });
    scrollToSection(id);
};

const removeSection = (idx) => {
    form.sections.splice(idx, 1);
};

const moveSection = (from, to) => {
    if (to < 0 || to >= form.sections.length) return;
    const item = form.sections.splice(from, 1)[0];
    form.sections.splice(to, 0, item);
};

const insertTemplate = async () => {
    const t = insertOptions.value.find((x) => x.id === selectedTemplateId.value);
    if (!t) return;

    const firstNewId = newId();
    const secs = t.sections ?? [];
    if (!secs.length) return;

    form.sections.push({ id: firstNewId, title: secs[0].title ?? '', content: '' });
    for (let i = 1; i < secs.length; i++) {
        form.sections.push({ id: newId(), title: secs[i].title ?? '', content: '' });
    }

    selectedTemplateId.value = '';
    scrollToSection(firstNewId);
};

const builtInTemplates = TEMPLATES.map((t) => ({
    id: `builtin:${t.name}`,
    name: t.name,
    sections: t.sections.map((s) => ({ title: s.title })),
}));

const myTemplates = computed(() =>
    (props.templates ?? []).map((t) => ({
        id: `user:${t.id}`,
        name: t.name,
        sections: (t.sections ?? []).map((s) => ({ title: s.title })),
    }))
);

const insertOptions = computed(() => [...builtInTemplates, ...myTemplates.value]);

const saveAsTemplate = () => {
    const name = window.prompt('Template name?');
    if (!name) return;

    router.post(
        '/journal/templates',
        {
            name,
            sections: (form.sections ?? []).map((s) => ({ title: s.title })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                // refresh props.templates
                router.reload({ only: ['templates'] });
            },
        }
    );
};

const deleteTemplate = (tpl) => {
    if (!tpl?.id) return;
    if (!confirm(`Delete template "${tpl.name}"?`)) return;

    router.delete(`/journal/templates/${tpl.id}`, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['templates'] }),
    });
};

// ---------- Server save (SAVE = AUTO CLAIM if eligible) ----------
const saveToServer = () => {
    // jangan kirim reward kalau bukan today atau sudah claimed
    form.transform((data) => ({
        ...data,
        xp_reward: isToday.value && !isClaimed.value ? (data.xp_reward ?? 0) : null,
        coin_reward: isToday.value && !isClaimed.value ? (data.coin_reward ?? 0) : null,
    }));

    form.put('/journal', {
        preserveScroll: true,
        onSuccess: () => {
            clearDraft();
            // optional: reset input reward biar ga kepake lagi
            if (isClaimed.value) {
                form.xp_reward = 0;
                form.coin_reward = 0;
            }
        },
        onFinish: () => {
            // reset transform supaya state form normal untuk next submit
            form.transform((d) => d);
        },
    });
};

const scrollToSection = async (id) => {
    await nextTick();
    const el = document.getElementById(`sec-${id}`);
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

let keepaliveTimer = null;

onMounted(() => {
    keepaliveTimer = setInterval(
        () => {
            fetch('/journal/ping', {
                method: 'HEAD',
                cache: 'no-store',
                credentials: 'same-origin',
            }).catch(() => {});
        },
        9 * 60 * 1000
    ); // 9 menit
});

onBeforeUnmount(() => {
    if (keepaliveTimer) clearInterval(keepaliveTimer);
});
</script>

<template>
    <Head title="Journal" />

    <div class="mx-auto max-w-5xl space-y-8 p-4 text-gray-200 md:p-8">
        <!-- Header -->
        <div class="flex items-center gap-3 border-b border-slate-700 pb-4">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-700 text-2xl shadow-lg shadow-slate-500/10"
            >
                📖
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-black tracking-tight text-white">Journal</h1>
                <p class="text-sm text-slate-400">
                    One entry per day. Sections optional. Reward today only (one-time).
                </p>
            </div>
        </div>

        <!-- Draft banner -->
        <div
            v-if="hasLocalDraft"
            class="flex items-center justify-between gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4"
        >
            <div class="text-sm text-amber-200">Draft found (saved locally). Restore it?</div>
            <div class="flex gap-2">
                <button
                    @click="restoreDraft"
                    class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-500"
                >
                    Restore
                </button>
                <button
                    @click="clearDraft"
                    class="rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-bold text-slate-200 hover:bg-slate-600"
                >
                    Dismiss
                </button>
            </div>
        </div>

        <!-- Date + Save + Reward (AUTO) -->
        <div class="space-y-3 rounded-xl border border-slate-700 bg-slate-800 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex-1">
                    <label class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Date</label>
                    <input
                        type="date"
                        v-model="form.date"
                        class="input-dark w-full"
                        @change="goToDate(form.date)"
                    />
                    <div v-if="form.errors.date" class="mt-1 text-xs text-red-400">
                        {{ form.errors.date }}
                    </div>
                </div>

                <div class="flex gap-2 sm:justify-end">
                    <button
                        @click="saveToServer"
                        :disabled="form.processing"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-lg hover:bg-indigo-500 disabled:opacity-60"
                    >
                        Save
                    </button>
                </div>
            </div>

            <!-- Reward info (no claim button) -->
            <div class="rounded-xl border border-slate-700/70 bg-slate-900/40 p-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-slate-300">
                        Reward:
                        <span class="text-slate-400">auto on Save</span>
                        <span v-if="!isToday" class="text-slate-500">(not today)</span>
                        <span v-else-if="isClaimed" class="text-emerald-300">
                            (claimed: +{{ props.entry?.xp_awarded ?? 0 }} XP, +{{
                                props.entry?.coin_awarded ?? 0
                            }}
                            G)
                        </span>
                        <span v-else class="text-indigo-300">(available)</span>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="flex gap-2">
                            <input
                                type="number"
                                min="0"
                                v-model.number="form.xp_reward"
                                class="input-dark w-28"
                                placeholder="XP"
                                :disabled="!isToday || isClaimed"
                            />
                            <input
                                type="number"
                                min="0"
                                v-model.number="form.coin_reward"
                                class="input-dark w-28"
                                placeholder="Gold"
                                :disabled="!isToday || isClaimed"
                            />
                        </div>

                        <div class="text-[11px] text-slate-500">
                            {{
                                isToday && !isClaimed
                                    ? 'Fill XP/Gold then Save.'
                                    : 'You can still edit & Save.'
                            }}
                        </div>
                    </div>
                </div>

                <div
                    v-if="form.errors.xp_reward || form.errors.coin_reward"
                    class="mt-2 text-xs text-red-400"
                >
                    <div v-if="form.errors.xp_reward">{{ form.errors.xp_reward }}</div>
                    <div v-if="form.errors.coin_reward">{{ form.errors.coin_reward }}</div>
                </div>
            </div>
        </div>

        <div class="space-y-2 rounded-xl border border-slate-700 bg-slate-800 p-4">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Title (optional)</div>
            <input
                v-model="form.title"
                class="input-dark w-full"
                placeholder='e.g. "Shipping day", "Feeling low", "Big win"'
                maxlength="160"
            />
        </div>

        <div class="grid gap-3 rounded-xl border border-slate-700 bg-slate-800 p-4 sm:grid-cols-2">
            <div class="space-y-2">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Mood (emoji)</div>
                <input
                    v-model="form.mood_emoji"
                    class="input-dark w-full"
                    placeholder="🙂"
                    maxlength="16"
                    list="mood-suggestions"
                />
                <datalist id="mood-suggestions">
                    <option value="🙂"></option>
                    <option value="😀"></option>
                    <option value="😐"></option>
                    <option value="😢"></option>
                    <option value="😡"></option>
                    <option value="😴"></option>
                    <option value="🤩"></option>
                </datalist>
            </div>
            <div class="space-y-2">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Favorite</div>
                <label class="flex items-center gap-2 text-sm text-slate-200">
                    <input
                        type="checkbox"
                        v-model="form.is_favorite"
                        class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-amber-400 focus:ring-1 focus:ring-amber-400"
                    />
                    Mark as favorite
                </label>
            </div>
        </div>

        <!-- Free writing -->
        <div class="space-y-2 rounded-xl border border-slate-700 bg-slate-800 p-4">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Free Writing</div>
            <textarea
                v-model="form.body"
                rows="8"
                class="input-dark w-full resize-none"
                placeholder="Write anything..."
            />
        </div>

        <!-- Sections -->
        <div class="space-y-4 rounded-xl border border-slate-700 bg-slate-800 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">
                    Sections (optional)
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <div class="flex gap-2">
                        <select v-model="selectedTemplateId" class="input-dark w-full sm:w-56">
                            <option value="">Insert template…</option>
                            <optgroup label="Built-in">
                                <option v-for="t in builtInTemplates" :key="t.id" :value="t.id">
                                    {{ t.name }}
                                </option>
                            </optgroup>

                            <optgroup v-if="myTemplates.length" label="My Templates">
                                <option v-for="t in myTemplates" :key="t.id" :value="t.id">
                                    {{ t.name }}
                                </option>
                            </optgroup>
                        </select>

                        <button
                            @click="insertTemplate"
                            class="rounded-lg bg-slate-700 px-3 py-2 text-sm font-bold text-slate-200 hover:bg-slate-600"
                        >
                            Insert
                        </button>
                    </div>
                    <button
                        @click="addSection"
                        class="rounded-lg bg-slate-700 px-3 py-2 text-sm font-bold text-slate-200 hover:bg-slate-600"
                    >
                        + Add Section
                    </button>
                    <button
                        @click="saveAsTemplate"
                        class="rounded-lg bg-slate-700 px-3 py-2 text-sm font-bold text-slate-200 hover:bg-slate-600"
                    >
                        Save as Template
                    </button>
                </div>
            </div>

            <div class="mt-2 flex items-center justify-between">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">My Templates</div>
                <button
                    v-if="(templates?.length || 0) > 0"
                    @click="showMyTemplates = !showMyTemplates"
                    class="rounded bg-slate-700 px-2 py-1 text-[10px] font-bold text-slate-200 hover:bg-slate-600"
                >
                    {{ showMyTemplates ? 'Hide' : 'Manage' }}
                </button>
            </div>

            <div
                v-if="showMyTemplates && (templates?.length || 0) > 0"
                class="mt-2 rounded-xl border border-slate-700 bg-slate-900/30 p-3"
            >
                <div class="flex flex-col gap-2">
                    <div
                        v-for="tpl in templates"
                        :key="tpl.id"
                        class="flex items-center justify-between gap-2 rounded-lg border border-slate-700/60 bg-slate-900 px-3 py-2"
                    >
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold text-slate-200">
                                {{ tpl.name }}
                            </div>
                            <div class="text-[11px] text-slate-500">
                                {{ tpl.sections?.length || 0 }} sections
                            </div>
                        </div>

                        <button
                            @click="deleteTemplate(tpl)"
                            class="rounded bg-red-600/20 px-2 py-1 text-xs font-bold text-red-200 hover:bg-red-600/30"
                            title="Delete template"
                        >
                            x
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="form.sections.length === 0" class="text-sm italic text-slate-500">
                No sections yet. You can use free writing only.
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="(s, idx) in form.sections"
                    :key="s.id"
                    :id="`sec-${s.id}`"
                    class="rounded-xl border border-slate-700/60 bg-slate-900/30 p-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <input
                            v-model="s.title"
                            class="input-dark w-full"
                            placeholder="Section title (optional)"
                        />
                        <div class="flex gap-1">
                            <button
                                @click="moveSection(idx, idx - 1)"
                                class="rounded bg-slate-700 px-2 py-1 text-xs font-bold text-slate-200 hover:bg-slate-600"
                                title="Move up"
                            >
                                ↑
                            </button>
                            <button
                                @click="moveSection(idx, idx + 1)"
                                class="rounded bg-slate-700 px-2 py-1 text-xs font-bold text-slate-200 hover:bg-slate-600"
                                title="Move down"
                            >
                                ↓
                            </button>
                            <button
                                @click="removeSection(idx)"
                                class="rounded bg-red-600/30 px-2 py-1 text-xs font-bold text-red-200 hover:bg-red-600/40"
                                title="Remove"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <textarea
                        v-model="s.content"
                        rows="4"
                        class="input-dark mt-2 w-full resize-none"
                        placeholder="Write..."
                    />
                </div>
            </div>

            <div v-if="form.errors.sections" class="text-xs text-red-400">{{ form.errors.sections }}</div>
        </div>
    </div>
</template>

<style scoped>
.input-dark {
    @apply rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 outline-none transition-all focus:ring-1 focus:ring-indigo-500;
}
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>
