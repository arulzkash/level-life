<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;
const profile = usePage().props.auth.profile;

const form = useForm({
    name: user.name,
    username: user.username ?? '',
    email: user.email,
    bio: profile?.bio ?? '',
});

const previewUsername = computed(() => {
    const normalized = (form.username || '').trim().toLowerCase();

    return normalized || user.username || '';
});

const publicProfileUrl = computed(() => {
    if (!previewUsername.value) {
        return '';
    }

    const path = `/u/${previewUsername.value}`;

    if (typeof window === 'undefined') {
        return path;
    }

    return `${window.location.origin}${path}`;
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-bold text-white">Public identity</h2>
            <p class="mt-1 text-sm text-slate-400">
                This controls how your profile appears to other people.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-6">
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Display Name</label>
                <input
                    id="name"
                    type="text"
                    class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-sky-400 focus:ring-2 focus:ring-sky-500/50"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <div v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Username</label>
                <input
                    id="username"
                    type="text"
                    class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-sky-400 focus:ring-2 focus:ring-sky-500/50"
                    v-model="form.username"
                    required
                    autocomplete="nickname"
                />
                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                    <span>Use lowercase letters, numbers, and underscore only.</span>
                    <span v-if="publicProfileUrl" class="truncate font-mono text-sky-200/90">
                        {{ publicProfileUrl }}
                    </span>
                </div>
                <div v-if="form.errors.username" class="mt-1 text-xs text-red-400">{{ form.errors.username }}</div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Email</label>
                <input
                    id="email"
                    type="email"
                    class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-sky-400 focus:ring-2 focus:ring-sky-500/50"
                    v-model="form.email"
                    required
                    autocomplete="email"
                />
                <div v-if="form.errors.email" class="mt-1 text-xs text-red-400">{{ form.errors.email }}</div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Bio</label>
                <textarea
                    id="bio"
                    v-model="form.bio"
                    rows="4"
                    class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-200 placeholder-slate-600 outline-none transition-all focus:border-sky-400 focus:ring-2 focus:ring-sky-500/50"
                    placeholder="Short public bio for your profile page."
                ></textarea>
                <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                    <span>Short, visible to anyone who opens your public page.</span>
                    <span>{{ form.bio?.length ?? 0 }}/280</span>
                </div>
                <div v-if="form.errors.bio" class="mt-1 text-xs text-red-400">{{ form.errors.bio }}</div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-slate-300">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sky-300 underline hover:text-sky-200 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-slate-900"
                    >
                        Re-send verification email.
                    </Link>
                </p>
                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-400"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button
                    :disabled="form.processing"
                    class="rounded-full bg-sky-500 px-6 py-2.5 font-bold text-slate-950 shadow-[0_12px_30px_rgba(14,165,233,0.28)] transition-all hover:bg-sky-400 active:scale-95 disabled:opacity-50"
                >
                    Save profile
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
