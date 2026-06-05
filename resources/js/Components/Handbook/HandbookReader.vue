<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import MarkdownIt from 'markdown-it';

const props = defineProps({
    markdown: { type: String, default: '' },
    isMissing: { type: Boolean, default: false },
    handbooks: { type: Object, default: () => ({}) },
});

const STORAGE_KEY = 'levellife.handbook.lang';
const DEFAULT_LANG = 'id';

const langLabels = {
    id: 'ID',
    en: 'EN',
};

const availableLangs = computed(() => {
    const keys = Object.keys(props.handbooks || {}).filter(
        (key) => typeof props.handbooks[key]?.markdown === 'string',
    );

    return keys.length > 0 ? keys : [DEFAULT_LANG];
});

const lang = ref(DEFAULT_LANG);

const activeEntry = computed(() => props.handbooks?.[lang.value] ?? null);

const activeMarkdown = computed(() => activeEntry.value?.markdown ?? props.markdown ?? '');

const activeIsMissing = computed(() =>
    activeEntry.value ? Boolean(activeEntry.value.isMissing) : props.isMissing,
);

const setLang = (next) => {
    if (!availableLangs.value.includes(next) || next === lang.value) {
        return;
    }

    lang.value = next;

    if (typeof window !== 'undefined') {
        try {
            window.localStorage.setItem(STORAGE_KEY, next);
        } catch (error) {
            // Ignore storage failures (e.g. private mode).
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const md = new MarkdownIt({
    html: false,
    linkify: true,
    typographer: true,
});

const defaultValidateLink = md.validateLink.bind(md);
const allowedInlineImagePattern = /^data:image\/(?:png|jpe?g|gif|webp|svg\+xml);base64,[a-z0-9+/=]+$/i;

md.validateLink = (url) => {
    const normalized = String(url || '').trim();

    if (allowedInlineImagePattern.test(normalized)) {
        return true;
    }

    if (!defaultValidateLink(url)) {
        return false;
    }

    const lowered = normalized.toLowerCase();

    return !lowered.startsWith('javascript:') && !lowered.startsWith('data:');
};

const defaultLinkOpen =
    md.renderer.rules.link_open ||
    ((tokens, idx, options, env, self) => self.renderToken(tokens, idx, options));

md.renderer.rules.link_open = (tokens, idx, options, env, self) => {
    const href = tokens[idx].attrGet('href') || '';
    const isExternal = /^(https?:)?\/\//i.test(href);

    if (isExternal) {
        tokens[idx].attrSet('target', '_blank');
        tokens[idx].attrSet('rel', 'noopener noreferrer');
    }

    return defaultLinkOpen(tokens, idx, options, env, self);
};

md.renderer.rules.paragraph_open = (tokens, idx) => {
    const next = tokens[idx + 1];
    const afterNext = tokens[idx + 2];

    if (next?.type === 'inline' && afterNext?.type === 'paragraph_close') {
        const hasOnlyImage = next.children?.every((child) =>
            ['image', 'softbreak', 'hardbreak', 'text'].includes(child.type) &&
            (child.type !== 'text' || child.content.trim() === ''),
        );

        if (hasOnlyImage) {
            return '';
        }
    }

    return '<p>';
};

md.renderer.rules.paragraph_close = (tokens, idx) => {
    const prev = tokens[idx - 1];
    const beforePrev = tokens[idx - 2];

    if (prev?.type === 'inline' && beforePrev?.type === 'paragraph_open') {
        const hasOnlyImage = prev.children?.every((child) =>
            ['image', 'softbreak', 'hardbreak', 'text'].includes(child.type) &&
            (child.type !== 'text' || child.content.trim() === ''),
        );

        if (hasOnlyImage) {
            return '';
        }
    }

    return '</p>';
};

const renderedHtml = computed(() => md.render(activeMarkdown.value || ''));
const showBackToTop = ref(false);

const syncBackToTop = () => {
    if (typeof window === 'undefined') {
        return;
    }

    showBackToTop.value = window.scrollY > 560;
};

const backToTop = () => {
    if (typeof window === 'undefined') {
        return;
    }

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
};

onMounted(() => {
    try {
        const saved = window.localStorage.getItem(STORAGE_KEY);

        if (saved && availableLangs.value.includes(saved)) {
            lang.value = saved;
        }
    } catch (error) {
        // Ignore storage failures (e.g. private mode).
    }

    syncBackToTop();
    window.addEventListener('scroll', syncBackToTop, { passive: true });
});

onBeforeUnmount(() => {
    if (typeof window === 'undefined') {
        return;
    }

    window.removeEventListener('scroll', syncBackToTop);
});
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-[#090b14] text-slate-200">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.14),_transparent_24%),radial-gradient(circle_at_18%_24%,_rgba(245,158,11,0.08),_transparent_18%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.12),_transparent_24%)]"></div>
            <div class="absolute left-[-8%] top-0 h-72 w-72 rounded-full bg-blue-500/10 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-8%] h-80 w-80 rounded-full bg-blue-400/10 blur-[140px]"></div>
        </div>

        <main class="relative mx-auto w-full max-w-5xl px-4 py-8 md:px-6 md:py-10">
            <section class="relative overflow-hidden rounded-[28px] border border-[#31415f] bg-[#111827] shadow-[0_32px_110px_rgba(5,10,22,0.62)] ring-1 ring-inset ring-white/5">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.03),_transparent_32%)]"></div>
                    <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-blue-400/12 via-sky-400/8 to-transparent"></div>
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-amber-300/40 to-transparent"></div>
                    <div class="absolute -left-20 top-14 h-44 w-44 rounded-full bg-blue-500/10 blur-3xl"></div>
                    <div class="absolute right-10 top-16 h-28 w-28 rounded-full bg-blue-400/12 blur-3xl"></div>
                    <div class="absolute bottom-10 left-1/3 h-24 w-24 rounded-full bg-amber-300/8 blur-3xl"></div>
                    <div class="absolute left-5 top-5 h-20 w-20 rounded-tl-[22px] border-l border-t border-amber-200/8"></div>
                    <div class="absolute right-5 top-5 h-20 w-20 rounded-tr-[22px] border-r border-t border-blue-200/8"></div>
                    <div class="absolute bottom-5 left-5 h-16 w-16 rounded-bl-[18px] border-b border-l border-sky-200/6"></div>
                </div>

                <div class="relative px-10 py-10 md:px-10 md:py-10">
                    <div
                        v-if="availableLangs.length > 1"
                        class="mb-6 flex justify-end"
                    >
                        <div class="inline-flex items-center gap-1 rounded-full border border-[#31415f] bg-[#0b1120] p-1 text-xs font-bold">
                            <button
                                v-for="code in availableLangs"
                                :key="code"
                                type="button"
                                class="rounded-full px-3 py-1 transition"
                                :class="lang === code
                                    ? 'bg-blue-500/20 text-amber-200 shadow-[inset_0_0_0_1px_rgba(251,191,36,0.35)]'
                                    : 'text-slate-400 hover:text-slate-200'"
                                :aria-pressed="lang === code"
                                @click="setLang(code)"
                            >
                                {{ langLabels[code] ?? code.toUpperCase() }}
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="activeIsMissing"
                        class="mb-6 inline-flex rounded-full border border-amber-400/20 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-200"
                    >
                        Source file missing. Showing fallback content.
                    </p>

                    <article
                        class="handbook-content"
                        v-html="renderedHtml"
                    ></article>
                </div>
            </section>
        </main>

        <button
            v-if="showBackToTop"
            type="button"
            class="fixed bottom-5 right-5 z-30 rounded-full border border-[#31415f] bg-[#111827]/95 px-4 py-2 text-sm font-semibold text-slate-200 shadow-xl backdrop-blur transition hover:border-blue-300/40 hover:text-white md:bottom-6 md:right-6"
            @click="backToTop"
        >
            Back to top
        </button>
    </div>
</template>

<style scoped>
.handbook-content {
    color: rgb(231 227 240);
    line-height: 1.92;
    font-size: 1rem;
}

.handbook-content :deep(h1),
.handbook-content :deep(h2),
.handbook-content :deep(h3),
.handbook-content :deep(h4) {
    color: rgb(255 246 235);
    font-weight: 900;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.handbook-content :deep(h1) {
    margin-top: 0;
    position: relative;
    display: inline-block;
    margin-bottom: 1.6rem;
    font-size: 2.35rem;
    text-shadow:
        0 0 22px rgba(96, 165, 250, 0.14),
        0 0 40px rgba(14, 165, 233, 0.08);
}

.handbook-content :deep(h1)::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -0.45rem;
    width: 72%;
    height: 2px;
    border-radius: 9999px;
    background: linear-gradient(90deg, rgba(251, 191, 36, 0.7), rgba(96, 165, 250, 0.65), rgba(99, 102, 241, 0));
    box-shadow:
        0 0 14px rgba(96, 165, 250, 0.18),
        0 0 20px rgba(251, 191, 36, 0.1);
}

.handbook-content :deep(h2) {
    position: relative;
    margin-top: 4.25rem;
    margin-bottom: 1.35rem;
    padding-bottom: 0.7rem;
    font-size: 1.9rem;
    color: rgb(255 232 202);
    text-shadow: 0 0 16px rgba(251, 191, 36, 0.08);
}

.handbook-content :deep(h2)::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 132px;
    height: 2px;
    border-radius: 9999px;
    background: linear-gradient(90deg, rgba(251, 191, 36, 0.82), rgba(96, 165, 250, 0.78), rgba(14, 165, 233, 0));
    box-shadow: 0 0 16px rgba(96, 165, 250, 0.12);
}

.handbook-content :deep(h3) {
    margin-top: 2.35rem;
    margin-bottom: 0.95rem;
    font-size: 1.38rem;
    color: rgb(244 227 205);
}

.handbook-content :deep(h4) {
    margin-top: 1.85rem;
    margin-bottom: 0.75rem;
    font-size: 1.05rem;
}

.handbook-content :deep(p),
.handbook-content :deep(ul),
.handbook-content :deep(ol),
.handbook-content :deep(blockquote),
.handbook-content :deep(pre),
.handbook-content :deep(table),
.handbook-content :deep(img) {
    margin-top: 1.15rem;
    margin-bottom: 1.15rem;
}

.handbook-content :deep(ul),
.handbook-content :deep(ol) {
    padding-left: 1.5rem;
}

.handbook-content :deep(li) {
    margin: 0.4rem 0;
}

.handbook-content :deep(blockquote) {
    border-left: 3px solid rgb(251 191 36 / 0.55);
    background: linear-gradient(135deg, rgba(24, 29, 43, 0.88), rgba(17, 24, 39, 0.78));
    padding: 1rem 1.1rem;
    color: rgb(254 240 200);
    border-radius: 0 0.9rem 0.9rem 0;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
}

.handbook-content :deep(a) {
    color: rgb(125 211 252);
    text-decoration: underline;
    text-decoration-color: rgb(125 211 252 / 0.35);
    text-underline-offset: 0.2rem;
    text-shadow: 0 0 10px rgba(59, 130, 246, 0.12);
}

.handbook-content :deep(a:hover) {
    color: rgb(186 230 253);
}

.handbook-content :deep(strong) {
    color: rgb(255 248 240);
    font-weight: 800;
}

.handbook-content :deep(hr) {
    border: 0;
    border-top: 1px solid rgb(49 65 95);
    margin: 2.75rem 0;
}

.handbook-content :deep(code) {
    background: rgb(15 23 42);
    border: 1px solid rgb(49 65 95 / 0.82);
    border-radius: 0.5rem;
    padding: 0.15rem 0.4rem;
    font-size: 0.9em;
    color: rgb(250 204 21);
}

.handbook-content :deep(pre) {
    overflow-x: auto;
    border-radius: 1rem;
    border: 1px solid rgb(49 65 95 / 0.88);
    background: rgb(9 14 27);
    padding: 1rem;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
}

.handbook-content :deep(pre code) {
    background: transparent;
    border: 0;
    padding: 0;
    color: inherit;
}

.handbook-content :deep(img) {
    display: block;
    max-width: 100%;
    margin-top: 1.5rem;
    margin-bottom: 1.75rem;
    border-radius: 1.1rem;
    border: 1px solid rgb(49 65 95 / 0.85);
    background: linear-gradient(180deg, rgba(17, 24, 39, 0.92), rgba(9, 14, 27, 0.9));
    box-shadow:
        0 24px 60px rgba(4, 8, 18, 0.38),
        0 0 0 1px rgba(255, 255, 255, 0.02) inset;
}

@media (max-width: 768px) {
    .handbook-content {
        font-size: 1rem;
        line-height: 1.88;
    }

    .handbook-content :deep(h1) {
        font-size: 1.95rem;
    }

    .handbook-content :deep(h2) {
        font-size: 1.55rem;
        margin-top: 3rem;
    }

    .handbook-content :deep(h3) {
        font-size: 1.28rem;
    }

    .handbook-content :deep(p),
    .handbook-content :deep(ul),
    .handbook-content :deep(ol) {
        margin-top: 1rem;
        margin-bottom: 1rem;
    }
}
</style>
