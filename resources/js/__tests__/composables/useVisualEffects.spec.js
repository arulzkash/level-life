import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useVisualEffects } from '@/Composables/useVisualEffects';

// Mock canvas-confetti
vi.mock('canvas-confetti', () => {
    const confettiFn = vi.fn();
    confettiFn.shapeFromText = vi.fn(() => 'mock-shape');
    return { default: confettiFn };
});

describe('useVisualEffects', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        document.body.innerHTML = '';
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.clearAllMocks();
    });

    it('returns triggerConfetti, triggerSlashEffect, and showToast functions', () => {
        const { triggerConfetti, triggerSlashEffect, showToast } = useVisualEffects();
        expect(typeof triggerConfetti).toBe('function');
        expect(typeof triggerSlashEffect).toBe('function');
        expect(typeof showToast).toBe('function');
    });

    describe('triggerConfetti', () => {
        it('calls canvas-confetti 5 times with correct particle counts summing to 200', async () => {
            const confetti = (await import('canvas-confetti')).default;
            const { triggerConfetti } = useVisualEffects();

            triggerConfetti();

            expect(confetti).toHaveBeenCalledTimes(5);

            // Verify particle counts: 200 * [0.25, 0.2, 0.35, 0.1, 0.1] = [50, 40, 70, 20, 20]
            const calls = confetti.mock.calls;
            expect(calls[0][0].particleCount).toBe(50);
            expect(calls[1][0].particleCount).toBe(40);
            expect(calls[2][0].particleCount).toBe(70);
            expect(calls[3][0].particleCount).toBe(20);
            expect(calls[4][0].particleCount).toBe(20);
        });

        it('uses origin y:0.7 for all stages', async () => {
            const confetti = (await import('canvas-confetti')).default;
            const { triggerConfetti } = useVisualEffects();

            triggerConfetti();

            const calls = confetti.mock.calls;
            for (const call of calls) {
                expect(call[0].origin.y).toBe(0.7);
            }
        });
    });

    describe('triggerSlashEffect', () => {
        it('fires slash1 immediately with angle 45 and origin x:0.3 y:0.7', async () => {
            const confetti = (await import('canvas-confetti')).default;
            const { triggerSlashEffect } = useVisualEffects();

            triggerSlashEffect();

            // First call is slash1 (immediate)
            const slash1 = confetti.mock.calls[0][0];
            expect(slash1.angle).toBe(45);
            expect(slash1.origin).toEqual({ x: 0.3, y: 0.7 });
        });

        it('fires slash2 at 100ms with angle 135 and origin x:0.7 y:0.7', async () => {
            const confetti = (await import('canvas-confetti')).default;
            const { triggerSlashEffect } = useVisualEffects();

            triggerSlashEffect();
            vi.advanceTimersByTime(100);

            const slash2 = confetti.mock.calls[1][0];
            expect(slash2.angle).toBe(135);
            expect(slash2.origin).toEqual({ x: 0.7, y: 0.7 });
        });

        it('fires impact at 200ms with origin x:0.5 y:0.5 and 40 particles', async () => {
            const confetti = (await import('canvas-confetti')).default;
            const { triggerSlashEffect } = useVisualEffects();

            triggerSlashEffect();
            vi.advanceTimersByTime(200);

            const impact = confetti.mock.calls[2][0];
            expect(impact.origin).toEqual({ x: 0.5, y: 0.5 });
            expect(impact.particleCount).toBe(40);
        });
    });

    describe('showToast', () => {
        it('creates a DOM element with z-index >= 50', () => {
            const { showToast } = useVisualEffects();

            showToast('Test message');

            const toasts = document.querySelectorAll('.fixed');
            expect(toasts.length).toBe(1);
            expect(toasts[0].className).toContain('z-50');
        });

        it('positions toast at fixed top-right', () => {
            const { showToast } = useVisualEffects();

            showToast('Test message');

            const toast = document.querySelector('.fixed');
            expect(toast.className).toContain('top-4');
            expect(toast.className).toContain('right-4');
        });

        it('removes toast from DOM after 4000ms', () => {
            const { showToast } = useVisualEffects();

            showToast('Test message');
            expect(document.querySelectorAll('.fixed').length).toBe(1);

            vi.advanceTimersByTime(4000);
            expect(document.querySelectorAll('.fixed').length).toBe(0);
        });

        it('truncates messages longer than 200 characters', () => {
            const { showToast } = useVisualEffects();
            const longMessage = 'A'.repeat(250);

            showToast(longMessage);

            const toast = document.querySelector('.fixed');
            // The displayed message should be truncated to 200 chars
            expect(toast.innerHTML).toContain('A'.repeat(200));
            expect(toast.innerHTML).not.toContain('A'.repeat(201));
        });

        it('multiple toasts coexist independently', () => {
            const { showToast } = useVisualEffects();

            showToast('Toast 1');
            showToast('Toast 2');
            showToast('Toast 3');

            expect(document.querySelectorAll('.fixed').length).toBe(3);
        });

        it('each toast is removed independently after its own 4000ms timeout', () => {
            const { showToast } = useVisualEffects();

            showToast('Toast 1');
            vi.advanceTimersByTime(2000);
            showToast('Toast 2');

            // At 2000ms: Toast 1 has 2000ms left, Toast 2 just created
            expect(document.querySelectorAll('.fixed').length).toBe(2);

            vi.advanceTimersByTime(2000);
            // At 4000ms: Toast 1 removed (4000ms elapsed), Toast 2 has 2000ms left
            expect(document.querySelectorAll('.fixed').length).toBe(1);

            vi.advanceTimersByTime(2000);
            // At 6000ms: Toast 2 also removed
            expect(document.querySelectorAll('.fixed').length).toBe(0);
        });
    });
});
