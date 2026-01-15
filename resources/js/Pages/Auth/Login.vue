<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: GuestLayout });

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log In" />

    <div v-if="status" class="mb-4 font-medium text-sm text-green-400">
        {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-5">
        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Email / Identity</label>
            <input 
                id="email" 
                type="email" 
                class="w-full bg-slate-950 border border-slate-700 text-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-600"
                v-model="form.email" 
                required 
                autofocus 
                autocomplete="username"
                placeholder="hero@example.com"
            />
            <div v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Password</label>
            <input 
                id="password" 
                type="password" 
                class="w-full bg-slate-950 border border-slate-700 text-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-600"
                v-model="form.password" 
                required 
                autocomplete="current-password"
                placeholder="••••••••"
            />
            <div v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</div>
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.remember" class="rounded bg-slate-800 border-slate-600 text-indigo-600 shadow-sm focus:ring-indigo-500 focus:ring-offset-slate-900">
                <span class="text-sm text-slate-400 select-none">Remember me</span>
            </label>

            <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors"
            >
                Forgot password?
            </Link>
        </div>

        <div class="pt-2">
            <button 
                type="submit" 
                :disabled="form.processing"
                class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold py-3 rounded-lg shadow-lg shadow-indigo-500/30 transition-all transform active:scale-95 flex justify-center items-center gap-2"
            >
                <span v-if="form.processing">Connecting...</span>
                <span v-else>Enter World ➜</span>
            </button>
        </div>

        <div class="text-center mt-6">
            <p class="text-sm text-slate-500">
                New adventurer? 
                <Link :href="route('register')" class="text-indigo-400 hover:text-white font-bold transition-colors">
                    Create Account
                </Link>
            </p>
        </div>
    </form>
</template>