<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, onMounted, onBeforeUnmount, ref, watch, nextTick } from 'vue';
import debounce from 'lodash/debounce';
import JournalTemplateSelector from '@/Components/Journal/JournalTemplateSelector.vue';
import JournalSectionEditor from '@/Components/Journal/JournalSectionEditor.vue';
import { builtInTemplateOptions } from '@/Utils/journalTemplates';
import { useAudio } from '@/Composables/useAudio';

defineOptions({ layout: AppLayout });

const props = defineProps({
    note: Object, // null if creating, otherwise { id, title, body, sections, is_pinned, color }
    templates: Array, // user templates for importing sections
});

const isEditMode = computed(() => !!props.note);

const showTemplateSelector = ref(false);
const { playSfx } = useAudio();

const newId = () => {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
    return `sec_${Date.now()}_${Math.random().toString(16).slice(2)}`;
};

const draftKey = computed(() => isEditMode.value ? `note:draft:${props.note.id}` : 'note:draft:new');

// ---------- Form (single owner) ----------
const form = useForm({
    title: props.note?.title ?? '',
    body: props.note?.body ?? '',
    sections: props.note?.sections ?? [],
    is_pinned: props.note?.is_pinned ?? false,
    color: props.note?.color ?? 'slate',
});

// ---------- Auto-save draft to localStorage (debounce 500ms) ----------
const saveDraftLocal = debounce(() => {
    if (hasLocalDraft.value) return;
    localStorage.setItem(draftKey.value, JSON.stringify({
        title: form.title,
        body: form.body,
        sections: form.sections,
        is_pinned: form.is_pinned,
        color: form.color,
        savedAt: Date.now(),
    }));
}, 500);

watch(() => [form.title, form.body, form.sections, form.is_pinned, form.color], () => saveDraftLocal(), { deep: true });

// ---------- Draft detection / restore / clear ----------
const hasLocalDraft = ref(false);

const restoreDraft = () => {
    const raw = localStorage.getItem(draftKey.value);
    if (!raw) return;
    try {
        const d = JSON.parse(raw);
        form.title = d.title ?? '';
        form.body = d.body ?? '';
        form.sections = d.sections ?? [];
        form.is_pinned = !!d.is_pinned;
        form.color = d.color ?? 'slate';
        hasLocalDraft.value = false;
    } catch {}
};

const clearDraft = (keyToClear = null) => {
    const key = keyToClear || draftKey.value;
    localStorage.removeItem(key);
    hasLocalDraft.value = false;
};

onMounted(() => {
    const raw = localStorage.getItem(draftKey.value);
    if (!raw) return;
    try {
        const d = JSON.parse(raw);
        const originalTitle = props.note?.title ?? '';
        const originalBody = props.note?.body ?? '';
        const originalSections = props.note?.sections ?? [];
        const originalIsPinned = props.note?.is_pinned ?? false;
        const originalColor = props.note?.color ?? 'slate';

        const isDifferent =
            (d.title ?? '') !== originalTitle ||
            (d.body ?? '') !== originalBody ||
            JSON.stringify(d.sections ?? []) !== JSON.stringify(originalSections) ||
            !!d.is_pinned !== originalIsPinned ||
            (d.color ?? 'slate') !== originalColor;

        if (isDifferent) {
            hasLocalDraft.value = true;
        }
    } catch {}
});

onBeforeUnmount(() => {
    saveDraftLocal.flush();
});

// ---------- Template operations ----------
const myTemplates = computed(() =>
    (props.templates ?? []).map((t) => ({
        id: `user:${t.id}`,
        name: t.name,
        sections: (t.sections ?? []).map((s) => ({ title: s.title }))
    }))
);
const insertOptions = computed(() => [...builtInTemplateOptions, ...myTemplates.value]);

const insertTemplate = async (templateObj) => {
    if (!templateObj) return;
    const secs = templateObj.sections ?? [];
    if (!secs.length) return;
    const firstNewId = newId();
    form.sections.push({ id: firstNewId, title: secs[0].title ?? '', content: '' });
    for (let i = 1; i < secs.length; i++) {
        form.sections.push({ id: newId(), title: secs[i].title ?? '', content: '' });
    }
    showTemplateSelector.value = false;
    await nextTick();
    document.getElementById(`sec-${firstNewId}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

const saveAsTemplate = () => {
    const name = window.prompt('Template name?');
    if (!name) return;
    router.post('/journal/templates', {
        name,
        sections: (form.sections ?? []).map((s) => ({ title: s.title }))
    }, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['templates'] }),
    });
};

const deleteTemplate = (tpl) => {
    if (!tpl?.id) return;
    if (!confirm(`Delete template "${tpl.name}"?`)) return;
    router.delete(`/journal/templates/${tpl.id}`, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['templates'] })
    });
};

// ---------- Section event handlers ----------
const addSection = async () => {
    const id = newId();
    form.sections.push({ id, title: '', content: '' });
    await nextTick();
    document.getElementById(`sec-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

const removeSection = (idx) => {
    const s = form.sections[idx];
    if (!s?.content?.trim() || confirm('Remove this section?')) {
        form.sections.splice(idx, 1);
    }
};

const moveSection = (from, to) => {
    if (to < 0 || to >= form.sections.length) return;
    const item = form.sections.splice(from, 1)[0];
    form.sections.splice(to, 0, item);
};

const updateSection = (idx, field, value) => {
    if (form.sections[idx]) form.sections[idx][field] = value;
};

// ---------- Save to server ----------
const saveToServer = () => {
    const targetKey = draftKey.value;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            clearDraft(targetKey);
            playSfx('typing');
        },
    };

    if (isEditMode.value) {
        form.put(`/notes/${props.note.id}`, options);
    } else {
        form.post('/notes', options);
    }
};

// Helpers for style configurations
const colorOptions = [
    { value: 'slate', name: 'Slate', colorClass: 'bg-slate-500 border-slate-400' },
    { value: 'indigo', name: 'Indigo', colorClass: 'bg-indigo-500 border-indigo-400' },
    { value: 'emerald', name: 'Emerald', colorClass: 'bg-emerald-500 border-emerald-400' },
    { value: 'amber', name: 'Amber', colorClass: 'bg-amber-500 border-amber-400' },
    { value: 'rose', name: 'Rose', colorClass: 'bg-rose-500 border-rose-400' },
    { value: 'sky', name: 'Sky', colorClass: 'bg-sky-500 border-sky-400' },
];

const activeColorGlow = computed(() => {
    switch (form.color) {
        case 'indigo': return 'border-l-indigo-500 shadow-indigo-500/10 ring-indigo-500/10';
        case 'emerald': return 'border-l-emerald-500 shadow-emerald-500/10 ring-emerald-500/10';
        case 'amber': return 'border-l-amber-500 shadow-amber-500/10 ring-amber-500/10';
        case 'rose': return 'border-l-rose-500 shadow-rose-500/10 ring-rose-500/10';
        case 'sky': return 'border-l-sky-500 shadow-sky-500/10 ring-sky-500/10';
        default: return 'border-l-slate-500 shadow-slate-500/10 ring-slate-500/10';
    }
});

const activeBodyGlow = computed(() => {
    switch (form.color) {
        case 'indigo': return 'hover:shadow-indigo-500/10 ring-indigo-500/10';
        case 'emerald': return 'hover:shadow-emerald-500/10 ring-emerald-500/10';
        case 'amber': return 'hover:shadow-amber-500/10 ring-amber-500/10';
        case 'rose': return 'hover:shadow-rose-500/10 ring-rose-500/10';
        case 'sky': return 'hover:shadow-sky-500/10 ring-sky-500/10';
        default: return 'hover:shadow-slate-500/10 ring-slate-500/10';
    }
});
</script>

<template>
    <Head :title="isEditMode ? `Editing Note: ${form.title || 'Untitled'}` : 'New Note'" />

    <div class="min-h-screen bg-slate-900 pb-20 text-slate-200">
        <!-- Sticky Editor Header -->
        <div class="sticky top-0 z-40 border-b border-slate-800 bg-slate-900/80 backdrop-blur-md transition-all">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-lg font-black tracking-tight text-white md:text-xl uppercase">
                            {{ isEditMode ? 'EDIT NOTE' : 'NEW NOTE' }}
                        </h1>
                        <div class="hidden text-[9px] font-bold uppercase tracking-[0.2em] text-slate-400 md:block">
                            Drafting documents inside your archives.
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Back Action -->
                    <Link
                        href="/notes"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-bold uppercase text-slate-400 hover:bg-slate-800 hover:text-white transition-all active:scale-95"
                    >
                        BACK TO LIST
                    </Link>

                    <!-- Save Action -->
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
            <!-- Unsaved Draft Prompt -->
            <transition name="fade">
                <div v-if="hasLocalDraft" class="flex items-center justify-between rounded-xl border border-sky-500/30 bg-sky-500/10 p-4 backdrop-blur-sm">
                    <div class="flex items-center gap-3">
                        <div>
                            <div class="text-sm font-bold text-sky-200">Unsaved draft found</div>
                            <div class="text-xs text-sky-200/70">From your previous session</div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="restoreDraft" class="rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-sky-500">Restore</button>
                        <button @click="clearDraft" class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-bold text-slate-300 hover:bg-slate-700">Dismiss</button>
                    </div>
                </div>
            </transition>

            <!-- Main Editor Form -->
            <div
                class="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-800/80 to-slate-900/70 p-5 shadow-lg shadow-slate-950/40 ring-1 transition-all hover:border-y-slate-700 hover:border-r-slate-700"
                :class="activeColorGlow"
            >
                <div class="mb-2 flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-widest text-slate-500">Title</label>

                    <div class="flex items-center gap-3">
                        <!-- Pin Note Toggle -->
                        <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-400 transition-colors hover:text-sky-400">
                            <input type="checkbox" v-model="form.is_pinned" class="hidden" />
                            <span class="text-[10px] font-bold uppercase tracking-widest transition-all" :class="form.is_pinned ? 'text-sky-400 drop-shadow-[0_0_8px_rgba(56,189,248,0.5)]' : ''">{{ form.is_pinned ? '📌 PINNED' : '📌 PIN NOTE' }}</span>
                        </label>

                        <div class="h-4 w-px bg-slate-800"></div>

                        <!-- Color Picker Dropdown/Selector -->
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Color Aksen:</span>
                            <div class="flex gap-1.5 bg-slate-900/50 p-1.5 rounded-lg border border-slate-800">
                                <button
                                    v-for="opt in colorOptions"
                                    :key="opt.value"
                                    @click="form.color = opt.value"
                                    type="button"
                                    class="h-4 w-4 rounded-full border transition-all hover:scale-125"
                                    :class="[
                                        opt.colorClass,
                                        form.color === opt.value ? 'ring-2 ring-white ring-offset-1 ring-offset-slate-900 border-white' : 'border-transparent'
                                    ]"
                                    :title="opt.name"
                                ></button>
                            </div>
                        </div>
                    </div>
                </div>
                <input
                    v-model="form.title"
                    class="w-full border-none bg-transparent p-0 text-2xl font-black text-white placeholder-slate-600 focus:ring-0 md:text-3xl"
                    placeholder="Give this note a title..."
                />
            </div>

            <!-- Editor Body Textarea -->
            <div
                class="relative min-h-[300px] overflow-hidden rounded-2xl border border-slate-700 bg-gradient-to-br from-slate-800/80 to-slate-900/70 shadow-2xl shadow-slate-950/50 ring-1 transition-all duration-300 hover:-translate-y-0.5 hover:border-slate-600"
                :class="activeBodyGlow"
            >
                <!-- Left Color border glow -->
                <div
                    class="absolute left-0 top-0 h-full w-1.5 bg-gradient-to-b opacity-85"
                    :class="[
                        form.color === 'indigo' ? 'from-indigo-400 via-indigo-600 to-indigo-500' :
                        form.color === 'emerald' ? 'from-emerald-400 via-emerald-600 to-emerald-500' :
                        form.color === 'amber' ? 'from-amber-400 via-amber-600 to-amber-500' :
                        form.color === 'rose' ? 'from-rose-400 via-rose-600 to-rose-500' :
                        form.color === 'sky' ? 'from-sky-400 via-sky-600 to-sky-500' :
                        'from-slate-400 via-slate-600 to-slate-500'
                    ]"
                ></div>
                <div class="p-6 md:p-8">
                    <textarea
                        v-model="form.body"
                        class="min-h-[400px] w-full resize-none border-none bg-transparent p-0 text-base leading-relaxed text-slate-200 placeholder-slate-600 focus:ring-0 md:text-lg"
                        placeholder="Capture anything you want to keep: ideas, links, checklists, references, progress logs, reminders, or quick notes..."
                    ></textarea>
                </div>
            </div>

            <!-- Expansion Modules (Journal Templates Re-used) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <h3 class="text-sm font-black uppercase tracking-widest text-slate-400">
                        Expansion Modules <span class="text-xs font-normal text-slate-600">({{ form.sections.length }})</span>
                    </h3>
                    <button
                        @click="showTemplateSelector = !showTemplateSelector"
                        class="flex items-center gap-1 text-xs font-bold text-sky-400 hover:text-sky-300"
                    >
                        {{ showTemplateSelector ? 'Close' : 'Open' }} Library
                    </button>
                </div>

                <transition name="slide">
                    <JournalTemplateSelector
                        v-if="showTemplateSelector"
                        :templates="insertOptions"
                        :custom-templates="props.templates ?? []"
                        @select-template="insertTemplate"
                        @save-as-template="saveAsTemplate"
                        @delete-template="deleteTemplate"
                    />
                </transition>

                <JournalSectionEditor
                    :sections="form.sections"
                    @add="addSection"
                    @remove="removeSection"
                    @reorder="moveSection"
                    @update="updateSection"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
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
textarea {
    field-sizing: content;
}
</style>
