import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock @inertiajs/vue3
const mockPost = vi.fn();
const mockPatch = vi.fn();
const mockReset = vi.fn();
const mockFormData = {
    name: '',
    status: 'todo',
    type: 'Daily Grind',
    xp_reward: 50,
    coin_reward: 50,
    due_date: null,
    is_repeatable: true,
    custom_color: '#6366f1',
    post: mockPost,
    patch: mockPatch,
    reset: mockReset,
};

vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((defaults) => ({ ...defaults, ...mockFormData })),
    router: {
        patch: vi.fn(),
    },
}));

// Mock useVisualEffects
const mockTriggerConfetti = vi.fn();
const mockTriggerSlashEffect = vi.fn();
const mockShowToast = vi.fn();

vi.mock('@/Composables/useVisualEffects', () => ({
    useVisualEffects: () => ({
        triggerConfetti: mockTriggerConfetti,
        triggerSlashEffect: mockTriggerSlashEffect,
        showToast: mockShowToast,
    }),
}));

// Mock useAudio
const mockPlaySfx = vi.fn();

vi.mock('@/Composables/useAudio', () => ({
    useAudio: () => ({
        playSfx: mockPlaySfx,
    }),
}));

import { useQuestActions } from '@/Composables/useQuestActions';
import { router, useForm } from '@inertiajs/vue3';

describe('useQuestActions', () => {
    const defaultProps = {
        customQuestTypes: [
            { id: 1, name: 'Research', color: '#ff0000' },
            { id: 2, name: 'Workout', color: '#00ff00' },
        ],
    };

    beforeEach(() => {
        vi.clearAllMocks();
        document.body.innerHTML = '';
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    it('returns the expected API shape', () => {
        const result = useQuestActions(defaultProps);

        expect(result).toHaveProperty('createForm');
        expect(result).toHaveProperty('isCustomType');
        expect(typeof result.submitQuest).toBe('function');
        expect(typeof result.completeQuest).toBe('function');
        expect(typeof result.toggleQuestStatus).toBe('function');
        expect(typeof result.reorderQuests).toBe('function');
        expect(typeof result.handleTypeChange).toBe('function');
        expect(typeof result.cancelCustomType).toBe('function');
    });

    describe('handleTypeChange', () => {
        it('sets isCustomType to true when "Custom" is selected', () => {
            const { isCustomType, handleTypeChange } = useQuestActions(defaultProps);

            handleTypeChange({ target: { value: 'Custom' } });

            expect(isCustomType.value).toBe(true);
        });

        it('sets isCustomType to false when a standard type is selected', () => {
            const { isCustomType, handleTypeChange } = useQuestActions(defaultProps);

            // First set to custom
            handleTypeChange({ target: { value: 'Custom' } });
            expect(isCustomType.value).toBe(true);

            // Then select a standard type
            handleTypeChange({ target: { value: 'Main Quest' } });
            expect(isCustomType.value).toBe(false);
        });
    });

    describe('cancelCustomType', () => {
        it('resets isCustomType to false', () => {
            const { isCustomType, handleTypeChange, cancelCustomType } = useQuestActions(defaultProps);

            handleTypeChange({ target: { value: 'Custom' } });
            expect(isCustomType.value).toBe(true);

            cancelCustomType();
            expect(isCustomType.value).toBe(false);
        });
    });

    describe('submitQuest - type validation', () => {
        it('rejects default type names case-insensitively', () => {
            const { isCustomType, createForm, submitQuest } = useQuestActions(defaultProps);

            isCustomType.value = true;
            createForm.type = 'DAILY GRIND';

            submitQuest();

            expect(mockShowToast).toHaveBeenCalledWith(
                expect.stringContaining('default category')
            );
            expect(mockPost).not.toHaveBeenCalled();
        });

        it('rejects "main quest" in any case variation', () => {
            const { isCustomType, createForm, submitQuest } = useQuestActions(defaultProps);

            isCustomType.value = true;
            createForm.type = 'MaIn QuEsT';

            submitQuest();

            expect(mockShowToast).toHaveBeenCalledWith(
                expect.stringContaining('default category')
            );
            expect(mockPost).not.toHaveBeenCalled();
        });

        it('rejects existing custom type names case-insensitively', () => {
            const { isCustomType, createForm, submitQuest } = useQuestActions(defaultProps);

            isCustomType.value = true;
            createForm.type = 'RESEARCH';

            submitQuest();

            expect(mockShowToast).toHaveBeenCalledWith(
                expect.stringContaining('already exists')
            );
            expect(mockPost).not.toHaveBeenCalled();
        });

        it('rejects empty custom type name', () => {
            const { isCustomType, createForm, submitQuest } = useQuestActions(defaultProps);

            isCustomType.value = true;
            createForm.type = '   ';

            submitQuest();

            expect(mockShowToast).toHaveBeenCalledWith(
                expect.stringContaining('enter a category name')
            );
            expect(mockPost).not.toHaveBeenCalled();
        });

        it('allows valid custom type names', () => {
            const { isCustomType, createForm, submitQuest } = useQuestActions(defaultProps);

            isCustomType.value = true;
            createForm.type = 'New Category';

            submitQuest();

            expect(mockPost).toHaveBeenCalledWith('/quests', expect.any(Object));
        });

        it('allows submission when not in custom type mode', () => {
            const { isCustomType, submitQuest } = useQuestActions(defaultProps);

            isCustomType.value = false;

            submitQuest();

            expect(mockPost).toHaveBeenCalledWith('/quests', expect.any(Object));
        });
    });

    describe('completeQuest', () => {
        it('sends PATCH request to correct endpoint', () => {
            const { completeQuest } = useQuestActions(defaultProps);

            completeQuest(42, 100, 50);

            expect(mockPatch).toHaveBeenCalledWith(
                '/quests/42/complete',
                expect.objectContaining({
                    preserveScroll: true,
                })
            );
        });

        it('sends completion note with the PATCH request', () => {
            const { completeQuest } = useQuestActions(defaultProps);

            completeQuest(42, 100, 50, 'jalan kaki 5 menit');

            expect(useForm).toHaveBeenCalledWith({ note: 'jalan kaki 5 menit' });
        });

        it('triggers visual effects and sounds on success', () => {
            mockPatch.mockImplementation((url, options) => {
                if (options.onSuccess) options.onSuccess();
            });

            const { completeQuest } = useQuestActions(defaultProps);

            completeQuest(1, 75, 30);

            expect(mockTriggerConfetti).toHaveBeenCalled();
            expect(mockTriggerSlashEffect).toHaveBeenCalled();
            expect(mockPlaySfx).toHaveBeenCalledWith('complete');
            expect(mockPlaySfx).toHaveBeenCalledWith('slash');
            expect(mockShowToast).toHaveBeenCalledWith(
                expect.stringContaining('+75 XP')
            );
            expect(mockShowToast).toHaveBeenCalledWith(
                expect.stringContaining('+30 Gold')
            );
        });

        it('does NOT trigger visual effects on server error', () => {
            mockPatch.mockImplementation((url, options) => {
                // Simulate server error - onSuccess is never called
                if (options.onError) options.onError({ message: 'Server error' });
            });

            const { completeQuest } = useQuestActions(defaultProps);

            completeQuest(1, 75, 30);

            expect(mockTriggerConfetti).not.toHaveBeenCalled();
            expect(mockTriggerSlashEffect).not.toHaveBeenCalled();
            expect(mockPlaySfx).not.toHaveBeenCalled();
            expect(mockShowToast).not.toHaveBeenCalled();
        });
    });

    describe('toggleQuestStatus', () => {
        it('toggles from todo to in_progress', () => {
            const { toggleQuestStatus } = useQuestActions(defaultProps);
            const quest = { id: 5, status: 'todo', name: 'Test' };

            toggleQuestStatus(quest);

            expect(router.patch).toHaveBeenCalledWith(
                '/quests/5',
                expect.objectContaining({ status: 'in_progress' }),
                expect.objectContaining({ preserveScroll: true })
            );
        });

        it('toggles from in_progress to todo', () => {
            const { toggleQuestStatus } = useQuestActions(defaultProps);
            const quest = { id: 5, status: 'in_progress', name: 'Test' };

            toggleQuestStatus(quest);

            expect(router.patch).toHaveBeenCalledWith(
                '/quests/5',
                expect.objectContaining({ status: 'todo' }),
                expect.objectContaining({ preserveScroll: true })
            );
        });
    });

    describe('reorderQuests', () => {
        it('sends PATCH to /quests/reorder with ordered_ids', () => {
            const { reorderQuests } = useQuestActions(defaultProps);

            reorderQuests([3, 1, 2]);

            expect(router.patch).toHaveBeenCalledWith(
                '/quests/reorder',
                { ordered_ids: [3, 1, 2] },
                {
                    preserveScroll: true,
                    preserveState: true,
                }
            );
        });
    });
});
