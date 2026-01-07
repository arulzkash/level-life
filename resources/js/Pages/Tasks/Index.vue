<script setup>
import { router, useForm } from "@inertiajs/vue3";
defineProps({
    tasks: Array,
});
const form = useForm({
    title: "",
});

const toggleTask = (id) => {
    router.patch(
        `/tasks/${id}`,
        {},
        {
            preserveScroll: true,
        }
    );
};
const deleteTask = (id) => {
    if (!confirm("Are you sure you want to delete this task?")) return;

    router.delete(
        `/tasks/${id}`,
        {},
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <div>
        <h2>Tasks</h2>
        <form @submit.prevent="form.post('/tasks')">
            <input v-model="form.title" type="text" placeholder="New Task" />
            <button type="submit" :disabled="form.processing">Add Task</button>
        </form>

        <ul>
            <li v-for="task in tasks" :key="task.id">
                <input
                    type="checkbox"
                    :checked="task.completed"
                    @change="toggleTask(task.id)"
                />

                <span
                    :style="{
                        textDecoration: task.completed
                            ? 'line-through'
                            : 'none',
                    }"
                    >{{ task.title }}</span
                >

                <button
                    @click="deleteTask(task.id)"
                    style="margin-left: 8px; color: red"
                >
                    ❌
                </button>
            </li>
        </ul>
    </div>
</template>
