import confetti from 'canvas-confetti';

/**
 * Composable for visual effects: confetti, slash effect, and toast notifications.
 * Extracted from Dashboard.vue to be reusable across pages.
 */
export function useVisualEffects() {
    /**
     * Trigger confetti effect with 200 particles in 5 stages.
     * Ratios: 0.25, 0.2, 0.35, 0.1, 0.1 from origin y:0.7
     */
    const triggerConfetti = () => {
        const count = 200;
        const defaults = { origin: { y: 0.7 } };

        function fire(particleRatio, opts) {
            confetti(
                Object.assign({}, defaults, opts, {
                    particleCount: Math.floor(count * particleRatio),
                })
            );
        }

        fire(0.25, { spread: 26, startVelocity: 55 });
        fire(0.2, { spread: 60 });
        fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
        fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
        fire(0.1, { spread: 120, startVelocity: 45 });
    };

    /**
     * Trigger cross-slash sword effect with 3 sequential effects:
     * - Slash 1 at 0ms: angle 45°, origin x:0.3 y:0.7
     * - Slash 2 at 100ms: angle 135°, origin x:0.7 y:0.7
     * - Impact at 200ms: origin x:0.5 y:0.5, 40 particles
     */
    const triggerSlashEffect = () => {
        const swordShape = confetti.shapeFromText({ text: '🗡️', scalar: 4 });

        const slashConfig = {
            shapes: [swordShape],
            colors: ['#ffffff', '#e2e8f0'],
            ticks: 30,
            gravity: 0,
            decay: 0.95,
            startVelocity: 90,
            scalar: 3,
            flat: true,
            drift: 0,
        };

        // Slash 1: angle 45°, origin x:0.3 y:0.7
        confetti({
            ...slashConfig,
            particleCount: 10,
            angle: 45,
            spread: 5,
            origin: { x: 0.3, y: 0.7 },
        });

        // Slash 2: angle 135°, origin x:0.7 y:0.7 at 100ms
        setTimeout(() => {
            confetti({
                ...slashConfig,
                particleCount: 10,
                angle: 135,
                spread: 5,
                origin: { x: 0.7, y: 0.7 },
            });
        }, 100);

        // Impact at center at 200ms: 40 particles
        setTimeout(() => {
            confetti({
                shapes: ['square', 'circle'],
                colors: ['#ef4444', '#f87171', '#ffffff'],
                particleCount: 40,
                spread: 100,
                origin: { x: 0.5, y: 0.5 },
                startVelocity: 30,
                gravity: 0.8,
                ticks: 50,
                scalar: 0.8,
            });
        }, 200);
    };

    /**
     * Show a toast notification at the top-right of the viewport.
     * - Truncates messages longer than 200 characters
     * - Auto-removes after 4000ms
     * - Multiple toasts coexist independently
     * @param {string} message - The message to display
     */
    const showToast = (message) => {
        const truncatedMessage = message && message.length > 200 ? message.slice(0, 200) : message;

        const toast = document.createElement('div');
        toast.className =
            'fixed top-4 right-4 bg-slate-800 border-l-4 border-emerald-500 text-white px-6 py-4 rounded shadow-2xl z-50 animate-bounce font-bold flex items-center gap-2';
        const icon = document.createElement('span');
        icon.textContent = '🎉';
        toast.appendChild(icon);
        toast.appendChild(document.createTextNode(' ' + (truncatedMessage ?? '')));
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    };

    return {
        triggerConfetti,
        triggerSlashEffect,
        showToast,
    };
}
