<script setup>
const props = defineProps({
    createForm: {
        type: Object,
        required: true,
    },
    isCustomType: {
        type: Boolean,
        default: false,
    },
    customQuestTypes: {
        type: [Array, Object],
        default: () => [],
    },
    showManageTypes: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    'submit',
    'type-change',
    'cancel-custom',
    'toggle-manage-types',
    'delete-type',
    'update-type-color',
    'cancel',
]);
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
            <div class="md:col-span-8">
                <label class="label-text">Quest Name</label>
                <input
                    v-model="createForm.name"
                    placeholder="e.g. Defeat the Bug"
                    class="input-dark w-full"
                    required
                    autofocus
                />
                <div v-if="createForm.errors.name" class="error-msg">
                    {{ createForm.errors.name }}
                </div>
            </div>
            <div class="md:col-span-4">
                <label class="label-text">Initial Status</label>
                <select v-model="createForm.status" class="input-dark w-full">
                    <option value="todo">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="locked">Locked</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <div class="mb-1 flex items-center justify-between">
                    <label class="label-text">Quest Type</label>
                    <button
                        v-if="customQuestTypes && Object.keys(customQuestTypes).length > 0"
                        type="button"
                        @click="emit('toggle-manage-types')"
                        class="text-[10px] font-bold uppercase tracking-widest text-slate-500 transition-colors hover:text-indigo-400"
                    >
                        {{ showManageTypes ? '▲ Hide' : '⚙ Manage' }}
                    </button>
                </div>

                <!-- Manage Custom Types Panel -->
                <div
                    v-if="
                        showManageTypes &&
                        customQuestTypes &&
                        Object.keys(customQuestTypes).length > 0
                    "
                    class="animate-fade-in mb-2 rounded-lg border border-slate-700 bg-slate-900/60 p-2"
                >
                    <p class="mb-1.5 text-[9px] uppercase tracking-widest text-slate-500">
                        Saved Custom Types
                    </p>
                    <div class="space-y-1">
                        <div
                            v-for="qt in customQuestTypes"
                            :key="qt.id"
                            class="flex items-center justify-between rounded px-2 py-1 hover:bg-slate-800"
                        >
                            <div class="flex items-center gap-2">
                                <input
                                    type="color"
                                    :value="qt.color || '#64748b'"
                                    @change="(e) => emit('update-type-color', qt.id, e)"
                                    class="h-5 w-5 cursor-pointer rounded border border-slate-600 bg-transparent p-0"
                                    title="Change Color"
                                />
                                <span class="text-xs text-slate-300">{{ qt.name }}</span>
                            </div>
                            <button
                                type="button"
                                @click="emit('delete-type', qt.id)"
                                class="text-slate-600 transition-colors hover:text-red-400"
                                title="Delete this type"
                            >
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="!isCustomType">
                    <select
                        :value="createForm.type"
                        @change="
                            (e) => {
                                createForm.type = e.target.value;
                                emit('type-change', e);
                            }
                        "
                        class="input-dark w-full"
                    >
                        <!-- Default Types -->
                        <optgroup label="Default">
                            <option value="Daily Grind">Daily Grind</option>
                            <option value="Main Quest">Main Quest</option>
                            <option value="Side Quest">Side Quest</option>
                            <option value="Boss Fight">Boss Fight</option>
                            <option value="Learning">Learning</option>
                        </optgroup>
                        <!-- Custom Saved Types -->
                        <optgroup
                            v-if="
                                customQuestTypes && Object.keys(customQuestTypes).length > 0
                            "
                            label="My Custom Types"
                        >
                            <option
                                v-for="qt in customQuestTypes"
                                :key="qt.id"
                                :value="qt.name"
                            >
                                {{ qt.name }}
                            </option>
                        </optgroup>
                        <!-- New Custom -->
                        <option value="Custom" class="font-bold text-indigo-400">
                            + New Custom Type...
                        </option>
                    </select>
                </div>
                <div v-else class="animate-fade-in flex flex-col gap-2">
                    <div class="flex gap-2">
                        <input
                            v-model="createForm.type"
                            placeholder="Type custom category..."
                            class="input-dark w-full border-indigo-500 ring-1 ring-indigo-500/50"
                            autofocus
                        />
                        <button
                            type="button"
                            @click="emit('cancel-custom')"
                            class="rounded-lg border border-slate-600 bg-slate-700 px-3 text-slate-300 hover:text-white"
                        >
                            ✕
                        </button>
                    </div>
                    <div class="mt-2 rounded-lg border border-slate-700 bg-slate-800 p-2 text-center">
                        <label class="flex cursor-pointer items-center justify-center gap-3">
                            <span class="text-[10px] uppercase tracking-widest text-slate-400">
                                Choose Category Color
                            </span>
                            <input
                                type="color"
                                v-model="createForm.custom_color"
                                class="h-8 w-12 cursor-pointer rounded border border-slate-600 bg-transparent p-0"
                            />
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="label-text">XP Reward</label>
                    <input
                        type="number"
                        v-model.number="createForm.xp_reward"
                        class="input-dark w-full font-bold text-indigo-400"
                    />
                </div>
                <div class="flex-1">
                    <label class="label-text">Gold Reward</label>
                    <input
                        type="number"
                        v-model.number="createForm.coin_reward"
                        class="input-dark w-full font-bold text-yellow-400"
                    />
                </div>
            </div>
        </div>

        <div
            class="flex items-center gap-6 rounded-xl border border-slate-700/50 bg-slate-900/50 p-4"
        >
            <label
                class="flex select-none items-center gap-3 transition-opacity"
                :class="{
                    'cursor-not-allowed opacity-60': createForm.type === 'Daily Grind',
                    'cursor-pointer': createForm.type !== 'Daily Grind',
                }"
            >
                <div class="relative">
                    <input
                        type="checkbox"
                        v-model="createForm.is_repeatable"
                        :disabled="createForm.type === 'Daily Grind'"
                        class="peer sr-only"
                    />
                    <div
                        class="h-6 w-10 rounded-full bg-slate-700 transition-colors peer-checked:bg-indigo-600"
                    ></div>
                    <div
                        class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-4"
                    ></div>
                </div>

                <div class="flex flex-col">
                    <span class="text-sm text-slate-300">Repeatable Quest</span>
                    <span
                        v-if="createForm.type === 'Daily Grind'"
                        class="text-[10px] italic text-indigo-400"
                    >
                        (Locked for Daily Grind)
                    </span>
                </div>
            </label>
            <div v-if="!createForm.is_repeatable" class="flex-1 transition-all">
                <input
                    type="date"
                    v-model="createForm.due_date"
                    class="input-dark w-full text-sm"
                />
            </div>
            <div v-else class="flex-1 text-right text-xs italic text-slate-500">
                Infinite repeats. No due date needed.
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button
                type="button"
                @click="emit('cancel')"
                class="px-4 py-2 text-slate-400 transition-colors hover:text-white"
            >
                Cancel
            </button>
            <button
                type="submit"
                :disabled="createForm.processing"
                class="btn-primary w-full md:w-auto"
            >
                <span v-if="createForm.processing">Summoning...</span>
                <span v-else>Confirm</span>
            </button>
        </div>
    </form>
</template>
