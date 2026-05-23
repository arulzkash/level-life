import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useVisualEffects } from '@/Composables/useVisualEffects';
import { useAudio } from '@/Composables/useAudio';

/**
 * Default quest type names used for duplicate validation.
 */
const DEFAULT_TYPE_NAMES = ['daily grind', 'main quest', 'side quest', 'boss fight', 'learning'];

/**
 * Composable for quest management actions: create, complete, toggle status, reorder, and type handling.
 * Internally uses useVisualEffects and useAudio composables.
 *
 * @param {Object} props - Component props containing customQuestTypes
 * @param {Object} [options] - Optional configuration
 * @returns {Object} Quest action methods and reactive state
 */
export function useQuestActions(props, options = {}) {
    const { triggerConfetti, triggerSlashEffect, showToast } = useVisualEffects();
    const { playSfx } = useAudio();

    // --- Reactive State ---
    const isCustomType = ref(false);

    const createForm = useForm({
        name: '',
        status: 'todo',
        type: 'Daily Grind',
        xp_reward: 50,
        coin_reward: 50,
        due_date: null,
        is_repeatable: true,
        custom_color: '#6366f1',
    });

    // --- Type Dropdown Management ---

    /**
     * Handle type dropdown change event.
     * Sets isCustomType when "Custom" is selected, auto-locks repeatable for Daily Grind.
     * @param {Event} event - The change event from the select element
     */
    const handleTypeChange = (event) => {
        const selectedType = event.target.value;

        if (selectedType === 'Custom') {
            isCustomType.value = true;
            createForm.type = '';
            createForm.is_repeatable = false;
        } else {
            isCustomType.value = false;
            createForm.type = selectedType;

            if (selectedType === 'Daily Grind') {
                createForm.is_repeatable = true;
            } else {
                createForm.is_repeatable = false;
            }
            createForm.custom_color = null;
        }
    };

    /**
     * Cancel custom type input and reset to default type.
     */
    const cancelCustomType = () => {
        isCustomType.value = false;
        createForm.type = 'Daily Grind';
    };

    // --- Quest Submission ---

    /**
     * Validate and submit a new quest.
     * Rejects duplicate type names (case-insensitive) against defaults and existing custom types.
     */
    const submitQuest = () => {
        if (isCustomType.value) {
            const name = createForm.type.trim();
            if (!name) {
                showToast('⚠️ Please enter a category name!');
                return;
            }

            const normalized = name.toLowerCase();

            if (DEFAULT_TYPE_NAMES.includes(normalized)) {
                showToast('⚠️ This is a default category. Please select it from the dropdown!');
                return;
            }

            const customTypes = props.customQuestTypes || [];
            const exists = customTypes.some((t) => t.name.toLowerCase() === normalized);
            if (exists) {
                showToast('⚠️ This category already exists. Please select it from the dropdown!');
                return;
            }
        }

        createForm.post('/quests', {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset('name', 'xp_reward', 'coin_reward', 'due_date');
                createForm.type = 'Daily Grind';
                createForm.is_repeatable = false;
                createForm.custom_color = '#6366f1';
                isCustomType.value = false;

                showToast('⚔️ Quest Posted to Board!');
            },
        });
    };

    // --- Quest Completion ---

    /**
     * Complete a quest by ID, triggering visual effects and sounds on success.
     * On server error: no visual effects are triggered, form state is preserved.
     * @param {number} id - Quest ID
     * @param {number} xpReward - XP reward amount
     * @param {number} coinReward - Coin reward amount
     */
    const completeQuest = (id, xpReward, coinReward) => {
        const form = useForm({ note: '' });
        form.patch(`/quests/${id}/complete`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('note');
                triggerConfetti();
                triggerSlashEffect();
                showToast(`⚔️ Slashed! +${xpReward} XP & +${coinReward} Gold!`);
                playSfx('complete');
                playSfx('slash');
            },
        });
    };

    // --- Quest Status Toggle ---

    /**
     * Toggle quest status between 'todo' and 'in_progress'.
     * @param {Object} quest - The quest object to toggle
     */
    const toggleQuestStatus = (quest) => {
        const newStatus = quest.status === 'todo' ? 'in_progress' : 'todo';
        router.patch(
            `/quests/${quest.id}`,
            { ...quest, status: newStatus },
            { preserveScroll: true }
        );
    };

    // --- Quest Reorder ---

    /**
     * Reorder quests by sending ordered IDs to the server.
     * Uses preserveScroll and preserveState to maintain UI state.
     * @param {number[]} orderedIds - Array of quest IDs in new order
     */
    const reorderQuests = (orderedIds) => {
        router.patch(
            '/quests/reorder',
            { ordered_ids: orderedIds },
            {
                preserveScroll: true,
                preserveState: true,
            }
        );
    };

    return {
        createForm,
        isCustomType,
        submitQuest,
        completeQuest,
        toggleQuestStatus,
        reorderQuests,
        handleTypeChange,
        cancelCustomType,
    };
}
