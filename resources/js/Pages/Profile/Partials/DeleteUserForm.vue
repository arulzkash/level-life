<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-bold text-red-400">Permadeath (Delete Account)</h2>
            <p class="mt-1 text-sm text-slate-400">
                Once your character is deleted, all of its resources and data will be permanently destroyed.
                Before deleting, please download any data or information that you wish to retain.
            </p>
        </header>

        <button
            @click="confirmUserDeletion"
            class="rounded-lg bg-red-600 px-6 py-2 font-bold text-white shadow-lg shadow-red-500/20 transition-all hover:bg-red-500 active:scale-95"
        >
            Delete Account
        </button>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="rounded-lg border border-red-500/50 bg-slate-900 p-6">
                <h2 class="text-lg font-bold text-white">Are you sure you want to retire this character?</h2>

                <p class="mt-1 text-sm text-slate-300">
                    Once your account is deleted, all of its resources and data will be permanently destroyed.
                    Please enter your password to confirm you would like to permanently delete your account.
                </p>

                <div class="mt-6">
                    <label class="sr-only">Password</label>
                    <input
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-4 py-2 text-slate-200 placeholder-slate-600 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                    />
                    <div v-if="form.errors.password" class="mt-1 text-xs text-red-400">
                        {{ form.errors.password }}
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        @click="closeModal"
                        class="rounded-lg bg-slate-700 px-4 py-2 font-medium text-slate-200 transition-colors hover:bg-slate-600"
                    >
                        Cancel
                    </button>

                    <button
                        @click="deleteUser"
                        :disabled="form.processing"
                        class="rounded-lg bg-red-600 px-6 py-2 font-bold text-white shadow-lg shadow-red-500/20 transition-all hover:bg-red-500 active:scale-95"
                    >
                        Delete Account
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
