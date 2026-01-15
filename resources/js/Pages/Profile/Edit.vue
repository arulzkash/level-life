<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

defineOptions({ layout: AppLayout });
</script>

<template>
    <Head title="Character Sheet" />

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-12 text-gray-200 md:px-8">
        <div class="flex items-center gap-4 border-b border-slate-700 pb-6">
            <div
                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-3xl shadow-[0_0_20px_rgba(79,70,229,0.4)]"
            >
                👤
            </div>
            <div>
                <h2 class="text-2xl font-black tracking-tight text-white">Character Sheet</h2>
                <p class="text-sm text-slate-400">Manage your identity and security spells.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8">
            <div
                class="relative overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 p-4 shadow-xl sm:p-8"
            >
                <div
                    class="pointer-events-none absolute right-0 top-0 h-32 w-32 rounded-full bg-indigo-500/10 blur-[50px]"
                ></div>
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="relative z-10 max-w-xl"
                />
            </div>

            <div
                class="relative overflow-hidden rounded-2xl border border-slate-700 bg-slate-800 p-4 shadow-xl sm:p-8"
            >
                <div
                    class="pointer-events-none absolute left-0 top-0 h-32 w-32 rounded-full bg-emerald-500/10 blur-[50px]"
                ></div>
                <UpdatePasswordForm class="relative z-10 max-w-xl" />
            </div>

            <div
                class="relative overflow-hidden rounded-2xl border border-red-900/30 bg-red-950/20 p-4 shadow-xl sm:p-8"
            >
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </div>
</template>
