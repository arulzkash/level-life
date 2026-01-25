<script setup>
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, onMounted, ref, watch, onBeforeUnmount, nextTick } from 'vue';
import debounce from 'lodash/debounce';
import LevelUpModal from '@/Components/Game/LevelUpModal.vue';
import { useAudio } from '@/Composables/useAudio';
import { useLevelUp } from '@/Composables/useLevelUp';

defineOptions({ layout: AppLayout });

const props = defineProps({
    date: String, // YYYY-MM-DD (selected)
    todayKey: String, // YYYY-MM-DD (Jakarta) from backend
    entry: Object, // null or entry payload
    templates: Array,
    title: String,
});

const {playSfx} = useAudio();
const page = usePage();
const globalProfile = computed(() => page.props.auth.profile);
const { showLevelUpModal } = useLevelUp(globalProfile, undefined, { delayMs: 0 });

// ---------- Constants ----------
const MOOD_OPTIONS = ['😴', '😐', '🙂', '😀', '🤩', '🔥', '🧠', '😵‍💫', '😢', '😡'];

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
const showTemplateSelector = ref(false); // Toggle untuk panel template

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
    xp_reward: 0,
    coin_reward: 0,
});

const isToday = computed(() => form.date === props.todayKey);
const isClaimed = computed(() => !!props.entry?.rewarded_at);

const goToDate = (d) => {
    router.get('/journal', { date: d }, { preserveScroll: true, preserveState: false });
};

// ---------- Local draft autosave ----------
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

onMounted(() => {
    const raw = localStorage.getItem(draftKey.value);
    if (!raw) return;
    try {
        const d = JSON.parse(raw);
        const serverBody = props.entry?.body ?? '';
        // Simple check: if local body, section, title, emoji is different and not empty, prompt user
        const serverSections = props.entry?.sections ?? [];
        const localSections = d.sections ?? [];
        const serverTitle = props.entry?.title ?? '';
        const localTitle = d.title ?? '';
        const serverEmoji = props.entry?.mood_emoji ?? '';
        const localEmoji = d.mood_emoji ?? '';

        const isDifferent =
            (d.body ?? '') !== serverBody && (d.body ?? '').length > 0 ||
            JSON.stringify(localSections) !== JSON.stringify(serverSections) && localSections.length > 0 ||
            localTitle !== serverTitle && localTitle.length > 0 ||
            localEmoji !== serverEmoji && localEmoji.length > 0;

        if (isDifferent) {
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
    const s = form.sections[idx];
    const hasContent = !!s?.content?.trim();
    if (!hasContent || confirm('Remove this section?')) {
        form.sections.splice(idx, 1);
    }
};

const moveSection = (from, to) => {
    if (to < 0 || to >= form.sections.length) return;
    const item = form.sections.splice(from, 1)[0];
    form.sections.splice(to, 0, item);
};

const insertTemplate = async (templateObj = null) => {
    // Bisa dipanggil langsung dengan object template, atau via dropdown ID
    let t = templateObj;

    if (!t && selectedTemplateId.value) {
        t = insertOptions.value.find((x) => x.id === selectedTemplateId.value);
    }

    if (!t) return;

    const secs = t.sections ?? [];
    if (!secs.length) return;

    // Tambahkan section baru
    const firstNewId = newId();
    form.sections.push({ id: firstNewId, title: secs[0].title ?? '', content: '' });
    for (let i = 1; i < secs.length; i++) {
        form.sections.push({ id: newId(), title: secs[i].title ?? '', content: '' });
    }

    // Reset UI state
    selectedTemplateId.value = '';
    showTemplateSelector.value = false;
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

const saveToServer = () => {
    form.transform((data) => ({
        ...data,
        xp_reward: isToday.value && !isClaimed.value ? (data.xp_reward ?? 0) : null,
        coin_reward: isToday.value && !isClaimed.value ? (data.coin_reward ?? 0) : null,
    }));

    form.put('/journal', {
        preserveScroll: true,
        onSuccess: () => {
            clearDraft();
            if (isClaimed.value) {
                form.xp_reward = 0;
                form.coin_reward = 0;
            }
            playSfx('typing');
        },
        onFinish: () => {
            form.transform((d) => d);
        },
    });
};

const scrollToSection = async (id) => {
    await nextTick();
    const el = document.getElementById(`sec-${id}`);
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

</script>

<template>
    <Head title="Journal Log" />

    <div class="min-h-screen bg-slate-900 pb-20 text-slate-200">
        <div
            class="sticky top-0 z-40 border-b border-slate-800 bg-slate-900/80 backdrop-blur-md transition-all"
        >
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center rounded-lg border border-slate-700 bg-slate-900 p-1 shadow-sm"
                    >
                        <input
                            type="date"
                            v-model="form.date"
                            class="cursor-pointer border-none bg-transparent p-1 text-sm font-bold uppercase tracking-widest text-slate-200 focus:ring-0"
                            @change="goToDate(form.date)"
                        />
                    </div>

                    <div class="hidden md:block">
                        <h1 class="text-2xl font-black tracking-tight text-white">
                            <span v-if="isToday" class="text-sky-400">Today</span>
                            DAILY LOG
                        </h1>
                        <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">
                            Every day leaves a mark on your legend.
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Link
                        href="/journal/archive"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-bold uppercase text-slate-400 hover:bg-slate-800 hover:text-white"
                    >
                        PAST LOGS
                    </Link>

                    <button
                        @click="saveToServer"
                        :disabled="form.processing"
                        class="group flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-1.5 text-sm font-bold text-white shadow-lg shadow-sky-500/20 transition-all hover:bg-sky-500 active:scale-95 disabled:opacity-50"
                    >
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>SAVE</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="mx-auto mt-6 max-w-4xl space-y-6 px-4 md:mt-8">
            <transition name="fade">
                <div
                    v-if="hasLocalDraft"
                    class="flex items-center justify-between rounded-xl border border-sky-500/30 bg-sky-500/10 p-4 backdrop-blur-sm"
                >
                    <div class="flex items-center gap-3">
                        <div>
                            <div class="text-sm font-bold text-sky-200">Unsaved draft found</div>
                            <div class="text-xs text-sky-200/70">From your previous session</div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="restoreDraft"
                            class="rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-sky-500"
                        >
                            Restore
                        </button>
                        <button
                            @click="clearDraft"
                            class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-bold text-slate-300 hover:bg-slate-700"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>
            </transition>

            <div
                class="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-800/80 to-slate-900/70 p-5 shadow-lg shadow-slate-950/40 ring-1 ring-sky-500/10 transition-all hover:border-slate-700"
            >
                <div class="mb-1 flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-widest text-slate-500">Title</label>
                    <label
                        class="flex cursor-pointer items-center gap-2 text-xs text-slate-400 transition-colors hover:text-sky-400"
                    >
                        <input type="checkbox" v-model="form.is_favorite" class="hidden" />
                        <span
                            class="text-[10px] font-bold uppercase tracking-widest transition-all"
                            :class="
                                form.is_favorite
                                    ? 'text-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.5)]'
                                    : ''
                            "
                        >
                            {{ form.is_favorite ? '★ FAVORITE' : '☆ MARK FAVORITE' }}
                        </span>
                    </label>
                </div>

                <input
                    v-model="form.title"
                    class="w-full border-none bg-transparent p-0 text-2xl font-black text-white placeholder-slate-600 focus:ring-0 md:text-3xl"
                    placeholder="Give this day a title..."
                />

                <div class="my-4 h-px w-full bg-slate-700/50"></div>

                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-bold uppercase text-slate-500">Mood</span>
                        <div class="flex flex-wrap gap-1">
                            <button
                                v-for="emoji in MOOD_OPTIONS"
                                :key="emoji"
                                @click="form.mood_emoji = emoji"
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 text-xl transition-all hover:-translate-y-0.5 hover:border-sky-500 hover:bg-slate-700 hover:shadow-lg hover:shadow-sky-500/20 active:scale-95"
                                :class="{
                                    'border-sky-500 bg-sky-500/20 shadow-[0_0_10px_rgba(14,165,233,0.3)] ring-2 ring-sky-500':
                                        form.mood_emoji === emoji,
                                }"
                            >
                                <span v-twemoji="emoji"></span>
                            </button>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-3 rounded-lg border border-slate-800/50 bg-slate-900/40 p-1.5 backdrop-blur-sm"
                    >
                        <div class="flex items-center gap-2">
                            <span class="pl-1 text-[10px] font-black text-sky-500">XP</span>
                            <input
                                type="number"
                                v-model.number="form.xp_reward"
                                :disabled="!isToday || isClaimed"
                                class="w-16 rounded border border-slate-700 bg-slate-900 py-1 text-center font-mono text-xs font-bold text-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500 disabled:opacity-50"
                                placeholder="0"
                            />
                        </div>

                        <div class="h-4 w-px bg-slate-700"></div>

                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black text-amber-500">GOLD</span>
                            <input
                                type="number"
                                v-model.number="form.coin_reward"
                                :disabled="!isToday || isClaimed"
                                class="w-16 rounded border border-slate-700 bg-slate-900 py-1 text-center font-mono text-xs font-bold text-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500 disabled:opacity-50"
                                placeholder="0"
                            />
                        </div>

                        <div
                            v-if="isClaimed"
                            class="rounded border border-emerald-500/20 bg-emerald-500/10 px-2 text-[9px] font-bold text-emerald-400"
                        >
                            CLAIMED
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="relative min-h-[300px] overflow-hidden rounded-2xl border border-slate-700 bg-gradient-to-br from-slate-800/80 to-slate-900/70 shadow-2xl shadow-slate-950/50 ring-1 ring-sky-500/10 transition-all hover:-translate-y-0.5 hover:border-slate-600 hover:shadow-sky-500/10"
            >
                <div
                    class="absolute left-0 top-0 h-full w-1.5 bg-gradient-to-b from-sky-400 via-blue-600 to-sky-500 opacity-80"
                ></div>

                <div class="p-6 md:p-8">
                    <textarea
                        v-model="form.body"
                        class="min-h-[400px] w-full resize-none border-none bg-transparent p-0 text-base leading-relaxed text-slate-200 placeholder-slate-600 focus:ring-0 md:text-lg"
                        placeholder="Log your journey here... What happened? What did you learn?"
                    ></textarea>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <h3 class="text-sm font-black uppercase tracking-widest text-slate-400">
                        Expansion Modules
                        <span class="text-xs font-normal text-slate-600">({{ form.sections.length }})</span>
                    </h3>

                    <div class="flex gap-2">
                        <button
                            @click="showTemplateSelector = !showTemplateSelector"
                            class="flex items-center gap-1 text-xs font-bold text-sky-400 hover:text-sky-300"
                        >
                            <span>{{ showTemplateSelector ? 'Close' : 'Open' }} Library</span>
                        </button>
                    </div>
                </div>

                <transition name="slide">
                    <div
                        v-if="showTemplateSelector"
                        class="rounded-xl border border-slate-700 bg-slate-800/70 p-4 shadow-inner transition-all hover:border-slate-600 hover:shadow-sky-500/10"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-300">Available Blueprints</span>
                            <button
                                @click="saveAsTemplate"
                                class="text-[10px] text-slate-500 underline hover:text-white"
                            >
                                + Save current as template
                            </button>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                            <button
                                v-for="t in insertOptions"
                                :key="t.id"
                                @click="insertTemplate(t)"
                                class="group flex flex-col items-start rounded-lg border border-slate-700 bg-slate-800/80 p-3 text-left transition-all hover:border-sky-500 hover:bg-slate-700 hover:shadow-md hover:shadow-sky-500/10"
                            >
                                <span class="font-bold text-slate-200 group-hover:text-white">
                                    {{ t.name }}
                                </span>
                                <span class="text-[10px] text-slate-500">
                                    {{ t.sections.length }} sections
                                </span>
                            </button>
                        </div>

                        <div v-if="myTemplates.length > 0" class="mt-4 border-t border-slate-800 pt-4">
                            <p class="mb-2 text-xs font-bold text-slate-500">Manage Custom</p>
                            <div class="flex flex-wrap gap-2">
                                <div
                                    v-for="t in props.templates"
                                    :key="t.id"
                                    class="flex items-center gap-2 rounded border border-slate-800 bg-slate-900 px-2 py-1 text-xs"
                                >
                                    <span class="text-slate-300">{{ t.name }}</span>
                                    <button
                                        @click="deleteTemplate(t)"
                                        class="font-bold text-red-500 hover:text-red-400"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>

                <transition-group name="list" tag="div" class="space-y-4">
                    <div
                        v-for="(section, idx) in form.sections"
                        :key="section.id"
                        :id="`sec-${section.id}`"
                        class="group relative overflow-hidden rounded-xl border border-slate-700 bg-slate-800/70 transition-all hover:-translate-y-0.5 hover:border-slate-600 hover:shadow-md hover:shadow-sky-500/10"
                    >
                        <div class="flex items-center gap-3 bg-slate-900/50 px-4 py-2">
                            <div
                                class="flex flex-col gap-0.5 opacity-50 transition-opacity group-hover:opacity-100"
                            >
                                <button
                                    @click="moveSection(idx, idx - 1)"
                                    class="text-[8px] text-slate-400 hover:text-white"
                                >
                                    ▲
                                </button>
                                <button
                                    @click="moveSection(idx, idx + 1)"
                                    class="text-[8px] text-slate-400 hover:text-white"
                                >
                                    ▼
                                </button>
                            </div>
                            <input
                                v-model="section.title"
                                class="w-full border-none bg-transparent p-0 text-sm font-bold uppercase tracking-wider text-sky-200 placeholder-slate-600 focus:ring-0"
                                placeholder="SECTION TITLE"
                            />
                            <button
                                @click="removeSection(idx)"
                                class="text-slate-600 transition-colors hover:text-red-400"
                            >
                                🗑️
                            </button>
                        </div>

                        <textarea
                            v-model="section.content"
                            rows="3"
                            class="w-full resize-y border-none bg-transparent px-4 py-3 text-sm text-slate-300 placeholder-slate-600 focus:ring-0"
                            placeholder="Write details..."
                        ></textarea>
                    </div>
                </transition-group>

                <button
                    @click="addSection"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-700 bg-slate-800/50 py-4 text-sm font-bold text-slate-500 transition-all hover:border-slate-500 hover:bg-slate-800 hover:text-slate-300"
                >
                    <span>+ Add New Section</span>
                </button>
            </div>
        </div>

        <LevelUpModal
            v-model="showLevelUpModal"
            :current-level="globalProfile?.level_data?.current_level || 1"
        />
    </div>
</template>

<style scoped>
/* Transisi Halus */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
    max-height: 500px;
    opacity: 1;
    overflow: hidden;
}
.slide-enter-from,
.slide-leave-to {
    max-height: 0;
    opacity: 0;
    padding-top: 0;
    padding-bottom: 0;
}

.list-enter-active,
.list-leave-active {
    transition: all 0.4s ease;
}
.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* Custom Inputs to reset browser styles */
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
textarea {
    field-sizing: content; /* Modern CSS for auto-grow if supported, fallback to resize-y */
}
</style>
