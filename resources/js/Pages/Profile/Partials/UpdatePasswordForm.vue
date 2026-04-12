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
            <h2 class="text-lg font-bold text-white">Password</h2>
            <p class="mt-1 text-sm text-slate-400">
                Use a strong password so only you can access this account.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase text-slate-400">Current Password</label>
                <input
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-sky-400 focus:ring-2 focus:ring-sky-500/50"
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
                    class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-sky-400 focus:ring-2 focus:ring-sky-500/50"
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
                    class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-sky-400 focus:ring-2 focus:ring-sky-500/50"
                    autocomplete="new-password"
                />
                <div v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-400">
                    {{ form.errors.password_confirmation }}
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button
                    :disabled="form.processing"
                    class="rounded-full bg-cyan-400 px-6 py-2.5 font-bold text-slate-950 shadow-[0_12px_30px_rgba(34,211,238,0.25)] transition-all hover:bg-cyan-300 active:scale-95 disabled:opacity-50"
                >
                    Update Password
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm font-bold text-emerald-400">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
