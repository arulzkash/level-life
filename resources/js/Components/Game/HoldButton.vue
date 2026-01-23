<script setup>
import { ref, computed, onUnmounted } from 'vue';
const props = defineProps({
    duration: { type: Number, default: 800 },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['complete']);
const progress = ref(0);
const isHolding = ref(false);
let animationFrame = null;
let startTime = 0;
const startHold = () => {
    if (props.disabled) return;
    isHolding.value = true;
    startTime = performance.now();
    progress.value = 0;
    const animate = (currentTime) => {
        const elapsed = currentTime - startTime;
        progress.value = Math.min((elapsed / props.duration) * 100, 100);
        if (progress.value < 100) {
            animationFrame = requestAnimationFrame(animate);
        } else {
            finishHold();
        }
    };
    animationFrame = requestAnimationFrame(animate);
};
const cancelHold = () => {
    isHolding.value = false;
    progress.value = 0;
    if (animationFrame) cancelAnimationFrame(animationFrame);
};
const finishHold = () => {
    cancelHold();
    emit('complete');
};
onUnmounted(() => {
    if (animationFrame) cancelAnimationFrame(animationFrame);
});
</script>
<template>
    <button
        type="button"
        @pointerdown="startHold"
        @pointerup="cancelHold"
        @pointerleave="cancelHold"
        @pointercancel="cancelHold"
        @contextmenu.prevent
        :disabled="disabled"
        class="hold-btn group relative h-8 w-full touch-none select-none overflow-hidden rounded border bg-emerald-600 text-[10px] font-bold uppercase tracking-widest text-emerald-50 shadow-sm transition-all md:w-auto"
        :class="[
            disabled
                ? 'cursor-not-allowed border-emerald-900 opacity-50 grayscale'
                : 'cursor-pointer border-emerald-500 hover:bg-emerald-500',
            isHolding ? 'is-holding' : '',
            isHolding && progress > 70 ? 'rumble' : '',
        ]"
        :style="{ '--p': progress }"
    >
        <div class="pointer-events-none absolute inset-0 z-0 opacity-60 mix-blend-soft-light">
            <svg
                class="cracks h-full w-full text-emerald-50/70"
                viewBox="0 0 100 32"
                preserveAspectRatio="none"
            >
                <g
                    stroke="currentColor"
                    stroke-width="0.7"
                    fill="none"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path
                        pathLength="100"
                        d="M50,16 L40,10 M50,16 L60,10 M50,16 L50,6 M50,16 L50,26 M50,16 L35,16 M50,16 L65,16"
                    />
                    <path
                        pathLength="100"
                        d="M40,10 L30,0 M60,10 L70,0 M50,6 L50,0 M50,26 L50,32 M35,16 L20,20 M65,16 L80,12 M40,10 L50,6 L60,10 L65,16 L50,26 L35,16 Z"
                    />
                    <path
                        pathLength="100"
                        d="M30,0 L10,5 M70,0 L90,5 M20,20 L5,15 M80,12 L95,20 M10,5 L0,0 M90,5 L100,0 M50,26 L40,32 M50,26 L60,32 M25,8 L35,4 M75,8 L65,4"
                    />
                </g>
            </svg>
        </div>
        <div
            class="relative z-10 flex h-full items-center justify-center gap-2 px-4 transition-transform will-change-transform"
            :class="{ 'animate-violent-shake': isHolding && progress > 0 }"
        >
            <slot><span class="drop-shadow-sm">Slash</span></slot>
        </div>
    </button>
</template>
<style scoped>
.hold-btn {
    will-change: transform;
    transform: translateZ(0);

    /* --- MOBILE SAFETY PACK --- */

    /* 1. Hilangkan kotak abu-abu saat tap di Android/iOS */
    -webkit-tap-highlight-color: transparent;

    /* 2. Matikan menu popup (Copy/Share) khusus iOS saat hold lama */
    -webkit-touch-callout: none;

    /* 3. Matikan seleksi teks (Backup untuk class select-none) */
    -webkit-user-select: none;
    user-select: none;
}

/* charge fill */
.hold-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: rgba(255, 255, 255, 0.14);
    transform-origin: left;
    transform: scaleX(calc(var(--p) / 100));
    transition: transform 80ms linear;
    opacity: 0.85;
}

/* slash glint diagonal (ngikut progress, bukan infinite anim) */
.hold-btn::after {
    content: '';
    position: absolute;
    inset: -40% -60%;
    pointer-events: none;
    background: linear-gradient(
        120deg,
        rgba(255, 255, 255, 0) 42%,
        rgba(236, 253, 245, 0.55) 50%,
        rgba(255, 255, 255, 0) 58%
    );
    mix-blend-mode: overlay;
    transform: translateX(calc(-80% + (var(--p) * 1.6%))) rotate(12deg);
    opacity: min(1, calc(var(--p) / 85));
}

/* holding feel: charge sedikit, murah */
.hold-btn.is-holding {
    filter: brightness(1.06);
}

/* crack reveal: stroke digambar bertahap (smooth) */
.cracks {
    opacity: min(1, calc(var(--p) / 55));
}

.cracks path {
    stroke-dasharray: 100;
    stroke-dashoffset: calc(100 - var(--p));
    transition:
        stroke-dashoffset 70ms linear,
        opacity 120ms linear;
    opacity: 0.9;
}

/* Opsional: micro-rumble hanya di akhir (lebih hemat dari violent 0.1s) */
@keyframes rumble-steps {
    0% {
        transform: translate(0, 0) rotate(0deg);
    }
    25% {
        transform: translate(-1px, 0) rotate(0.2deg);
    }
    50% {
        transform: translate(1px, 0) rotate(-0.2deg);
    }
    75% {
        transform: translate(0, 1px) rotate(0.2deg);
    }
    100% {
        transform: translate(0, 0) rotate(0deg);
    }
}
.hold-btn.rumble .relative.z-10 {
    animation: rumble-steps 0.22s steps(2, end) infinite;
}

/* Respect reduced motion */
@media (prefers-reduced-motion: reduce) {
    .hold-btn::before,
    .hold-btn::after,
    .cracks path {
        transition: none;
    }
    .hold-btn.rumble .relative.z-10 {
        animation: none;
    }
}
</style>
