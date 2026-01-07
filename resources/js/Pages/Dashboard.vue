<script setup>
// import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// import { Head } from '@inertiajs/vue3';

import { useForm } from "@inertiajs/vue3";
defineProps({
    message: String,
    time: String,
});

const form = useForm({
    message: "",
});
</script>

<template>
    <h2>Dashboard</h2>

    <form @submit.prevent="form.post('dashboard/message')">
        <input v-model="form.message" type="text" placeholder="ketik pesan" />

        <button type="submit" :disabled="form.processing">Kirim</button>

        <p v-if="form.errors.message" style="color:red">
            {{ form.errors.message }}
        </p>
    </form>
</template>

<!-- <template>
     <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        You're logged in!
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> -->
