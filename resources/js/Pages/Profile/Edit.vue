<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import NotificationSettings from './Partials/NotificationSettings.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    notifications: {
        type: Object,
        default: () => ({
            vapidPublicKey: null,
            subscriptionCount: 0,
        }),
    },
});

defineOptions({ layout: AppLayout });

const page = usePage();
const user = computed(() => page.props.auth.user);
const publicProfileHref = computed(() => {
    const username = user.value?.username;

    return username ? `/u/${username}` : null;
});
</script>

<template>
    <Head title="Profile Settings" />

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 text-slate-100 md:px-6">
        <section
            class="overflow-hidden rounded-[1.75rem] border border-sky-300/15 bg-slate-900/95 shadow-[0_18px_60px_rgba(14,165,233,0.08)]"
        >
            <div class="grid gap-4 px-5 py-5 md:grid-cols-[1.2fr_0.8fr] md:px-6">
                <div class="space-y-3">
                    <div class="text-[11px] font-black uppercase tracking-[0.28em] text-sky-200/70">
                        Settings
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-white md:text-3xl">
                            Profile settings
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">
                            Manage your public identity and account access. Username and bio will appear on your
                            public profile page.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-1">
                    <div class="rounded-3xl border border-sky-300/15 bg-slate-950/70 p-4">
                        <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">
                            Public URL
                        </div>
                        <div class="mt-2 truncate font-mono text-sm text-sky-200">
                            {{ publicProfileHref || 'Set a username to unlock your public page.' }}
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 rounded-3xl border border-slate-800 bg-slate-950/60 p-4">
                        <Link
                            v-if="publicProfileHref"
                            :href="publicProfileHref"
                            class="inline-flex items-center rounded-full border border-sky-300/25 bg-sky-400/10 px-4 py-2 text-sm font-semibold text-sky-100 transition hover:border-sky-200/40 hover:bg-sky-400/15"
                        >
                            View public profile
                        </Link>

                        <Link
                            href="/dashboard"
                            class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-600 hover:text-white"
                        >
                            Back to dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
            <div class="rounded-[1.75rem] border border-sky-300/15 bg-slate-900/95 p-5 shadow-[0_18px_60px_rgba(14,165,233,0.08)] md:p-6">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </div>

            <div class="space-y-6">
                <div class="rounded-[1.75rem] border border-emerald-300/15 bg-slate-900/95 p-5 shadow-[0_18px_50px_rgba(16,185,129,0.06)] md:p-6">
                    <NotificationSettings
                        :vapid-public-key="notifications.vapidPublicKey"
                        :initial-subscription-count="notifications.subscriptionCount"
                    />
                </div>

                <div class="rounded-[1.75rem] border border-cyan-300/15 bg-slate-900/95 p-5 shadow-[0_18px_50px_rgba(34,211,238,0.06)] md:p-6">
                    <UpdatePasswordForm />
                </div>

                <div class="rounded-[1.75rem] border border-red-900/35 bg-red-950/15 p-5 shadow-[0_18px_50px_rgba(127,29,29,0.18)] md:p-6">
                    <DeleteUserForm />
                </div>
            </div>
        </section>
    </div>
</template>
