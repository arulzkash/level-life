<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-bold text-white">Security Spell</h2>
            <p class="mt-1 text-sm text-slate-400">
                Ensure your account is using a long, random password to stay secure.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-slate-400">Current Password</label>
                <input
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                    autocomplete="current-password"
                />
                <div v-if="form.errors.current_password" class="mt-1 text-xs text-red-400">
                    {{ form.errors.current_password }}
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-slate-400">New Password</label>
                <input
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                    autocomplete="new-password"
                />
                <div v-if="form.errors.password" class="mt-1 text-xs text-red-400">
                    {{ form.errors.password }}
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-slate-400">Confirm Password</label>
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500"
                    autocomplete="new-password"
                />
                <div v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-400">
                    {{ form.errors.password_confirmation }}
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button
                    :disabled="form.processing"
                    class="rounded-lg bg-emerald-600 px-6 py-2 font-bold text-white shadow-lg shadow-emerald-500/30 transition-all hover:bg-emerald-500 active:scale-95 disabled:opacity-50"
                >
                    Update Password
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm font-bold text-green-400">Secured! 🔒</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
