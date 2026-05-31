import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useVisualEffects } from '@/Composables/useVisualEffects';

/**
 * Composable for custom quest type management.
 * Handles delete and color update operations with confirmation dialogs and rollback.
 *
 * @param {Object} customQuestTypes - Reactive prop containing custom quest types array/object
 * @param {Object} createForm - Inertia form instance for quest creation (used to reset type on delete)
 * @returns {{ showManageTypes: Ref<boolean>, deleteCustomType: Function, updateCustomTypeColor: Function }}
 */
export function useQuestTypes(customQuestTypes, createForm) {
    const { showToast } = useVisualEffects();

    /** Reactive boolean to toggle the manage types panel visibility */
    const showManageTypes = ref(false);

    /**
     * Delete a custom quest type after user confirmation.
     * Shows a confirmation dialog before sending DELETE request to server.
     * On success, resets the form type to 'Daily Grind' if the deleted type was selected.
     *
     * @param {number} id - The ID of the custom type to delete
     */
    const deleteCustomType = (id) => {
        if (!confirm('Hapus custom type ini?')) return;

        router.delete(`/quest-types/${id}`, {
            preserveScroll: true,
            onSuccess: () => {
                // If the deleted type was currently selected in the form, reset to default
                const deletedType = Array.isArray(customQuestTypes.value || customQuestTypes)
                    ? (customQuestTypes.value || customQuestTypes).find(t => t.id === id)
                    : null;

                if (deletedType && createForm && createForm.type === deletedType.name) {
                    createForm.type = 'Daily Grind';
                }
                showToast('🗑️ Custom type deleted!');
            },
        });
    };

    /**
     * Update a custom quest type's color after user confirmation.
     * Shows a confirmation dialog and rolls back the color if user cancels or server returns error.
     *
     * @param {number} id - The ID of the custom type to update
     * @param {Event} event - The change event from the color input element
     */
    const updateCustomTypeColor = (id, event) => {
        const newColor = event.target.value;
        const types = customQuestTypes.value || customQuestTypes;
        const typeEntry = Array.isArray(types) ? types.find(t => t.id === id) : null;
        const oldColor = typeEntry?.color || '#64748b';

        if (newColor === oldColor) return;

        if (confirm('Change this category\u2019s color? All quests of this type will be updated.')) {
            router.patch(`/quest-types/${id}`, { color: newColor }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => showToast('🎨 Color updated!'),
                onError: () => {
                    // Rollback color on server error
                    event.target.value = oldColor;
                },
            });
        } else {
            // Rollback color on user cancel
            event.target.value = oldColor;
        }
    };

    return {
        showManageTypes,
        deleteCustomType,
        updateCustomTypeColor,
    };
}
