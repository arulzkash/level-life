<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import MarkdownIt from 'markdown-it';

const props = defineProps({
    markdown: { type: String, default: '' },
    isMissing: { type: Boolean, default: false },
});

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

const renderedHtml = computed(() => md.render(props.markdown || ''));
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
    <div class="relative min-h-screen overflow-hidden bg-slate-950 text-slate-200">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute left-[-8%] top-0 h-72 w-72 rounded-full bg-indigo-500/10 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-8%] h-80 w-80 rounded-full bg-cyan-400/10 blur-[140px]"></div>
        </div>

        <main class="relative mx-auto w-full max-w-5xl px-4 py-8 md:px-6 md:py-10">
            <section class="overflow-hidden rounded-[28px] border border-slate-800 bg-slate-900/80 shadow-[0_24px_80px_rgba(15,23,42,0.55)] ring-1 ring-inset ring-white/5">
                <div class="px-6 py-8 md:px-10 md:py-10">
                    <p
                        v-if="isMissing"
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
            class="fixed bottom-5 right-5 z-30 rounded-full border border-slate-700 bg-slate-900/95 px-4 py-2 text-sm font-semibold text-slate-200 shadow-[0_12px_30px_rgba(15,23,42,0.45)] backdrop-blur transition hover:border-cyan-400/40 hover:text-white md:bottom-6 md:right-6"
            @click="backToTop"
        >
            Back to top
        </button>
    </div>
</template>

<style scoped>
.handbook-content {
    color: rgb(226 232 240);
    line-height: 1.92;
    font-size: 1rem;
}

.handbook-content :deep(h1),
.handbook-content :deep(h2),
.handbook-content :deep(h3),
.handbook-content :deep(h4) {
    color: rgb(248 250 252);
    font-weight: 900;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.handbook-content :deep(h1) {
    margin-top: 0;
    margin-bottom: 1.25rem;
    font-size: 2.35rem;
}

.handbook-content :deep(h2) {
    margin-top: 4.25rem;
    margin-bottom: 1.2rem;
    font-size: 1.9rem;
}

.handbook-content :deep(h3) {
    margin-top: 2.35rem;
    margin-bottom: 0.95rem;
    font-size: 1.38rem;
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
    border-left: 3px solid rgb(34 211 238 / 0.5);
    background: rgb(15 23 42 / 0.55);
    padding: 1rem 1.1rem;
    color: rgb(186 230 253);
    border-radius: 0 0.9rem 0.9rem 0;
}

.handbook-content :deep(a) {
    color: rgb(103 232 249);
    text-decoration: underline;
    text-decoration-color: rgb(34 211 238 / 0.4);
    text-underline-offset: 0.2rem;
}

.handbook-content :deep(a:hover) {
    color: rgb(165 243 252);
}

.handbook-content :deep(strong) {
    color: rgb(248 250 252);
    font-weight: 800;
}

.handbook-content :deep(hr) {
    border: 0;
    border-top: 1px solid rgb(51 65 85);
    margin: 2.75rem 0;
}

.handbook-content :deep(code) {
    background: rgb(15 23 42);
    border: 1px solid rgb(51 65 85 / 0.8);
    border-radius: 0.5rem;
    padding: 0.15rem 0.4rem;
    font-size: 0.9em;
    color: rgb(196 181 253);
}

.handbook-content :deep(pre) {
    overflow-x: auto;
    border-radius: 1rem;
    border: 1px solid rgb(51 65 85 / 0.9);
    background: rgb(2 6 23);
    padding: 1rem;
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
    border: 1px solid rgb(51 65 85 / 0.75);
    background: rgb(15 23 42 / 0.55);
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.28);
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
