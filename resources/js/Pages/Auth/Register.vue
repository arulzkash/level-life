<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: GuestLayout });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Begin Adventure" />

    <form @submit.prevent="submit" class="space-y-5">
        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Hero Name</label>
            <input 
                id="name" 
                type="text" 
                class="w-full bg-slate-950 border border-slate-700 text-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-600"
                v-model="form.name" 
                required 
                autofocus 
                autocomplete="name"
                placeholder="Your character name"
            />
            <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Email Scroll</label>
            <input 
                id="email" 
                type="email" 
                class="w-full bg-slate-950 border border-slate-700 text-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-600"
                v-model="form.email" 
                required 
                autocomplete="username"
                placeholder="hero@example.com"
            />
            <div v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Secret Key (Password)</label>
            <input 
                id="password" 
                type="password" 
                class="w-full bg-slate-950 border border-slate-700 text-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-600"
                v-model="form.password" 
                required 
                autocomplete="new-password"
            />
            <div v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Confirm Secret Key</label>
            <input 
                id="password_confirmation" 
                type="password" 
                class="w-full bg-slate-950 border border-slate-700 text-slate-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all placeholder-slate-600"
                v-model="form.password_confirmation" 
                required 
                autocomplete="new-password"
            />
            <div v-if="form.errors.password_confirmation" class="text-red-400 text-xs mt-1">{{ form.errors.password_confirmation }}</div>
        </div>

        <div class="pt-2">
            <button 
                type="submit" 
                :disabled="form.processing"
                class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold py-3 rounded-lg shadow-lg shadow-indigo-500/30 transition-all transform active:scale-95"
            >
                Start Adventure
            </button>
        </div>

        <div class="text-center mt-6">
            <p class="text-sm text-slate-500">
                Already have a character? 
                <Link :href="route('login')" class="text-indigo-400 hover:text-white font-bold transition-colors">
                    Log In
                </Link>
            </p>
        </div>
    </form>
</template>