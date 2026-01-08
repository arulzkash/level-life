<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    quests: Object, // paginator object
});
</script>

<template>
    <div style="padding: 16px">
        <div
            style="
                display: flex;
                gap: 12px;
                align-items: center;
                justify-content: space-between;
            "
        >
            <h2>Quests</h2>

            <div style="display: flex; gap: 12px">
                <Link href="/dashboard">Dashboard</Link>
                <Link href="/logs/completions">Completion Log</Link>
            </div>
        </div>

        <p style="margin: 8px 0 16px">
            Ini list semua quest. Edit nanti kita tambahin step berikutnya.
        </p>

        <div v-if="quests.data.length === 0">Belum ada quest.</div>

        <table
            v-else
            border="1"
            cellpadding="8"
            cellspacing="0"
            style="width: 100%; border-collapse: collapse"
        >
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Type</th>
                    <th>XP</th>
                    <th>Coin</th>
                    <th>Repeatable</th>
                    <th>Due</th>
                    <th>Completed</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="q in quests.data" :key="q.id">
                    <td>{{ q.name }}</td>
                    <td>{{ q.status }}</td>
                    <td>{{ q.type }}</td>
                    <td>{{ q.xp_reward }}</td>
                    <td>{{ q.coin_reward }}</td>
                    <td>{{ q.is_repeatable ? "yes" : "no" }}</td>
                    <td>{{ q.due_date ?? "-" }}</td>
                    <td>{{ q.completed_at ?? "-" }}</td>
                </tr>
            </tbody>
        </table>

        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px">
            <Link
                v-for="link in quests.links"
                :key="link.label"
                :href="link.url || ''"
                v-html="link.label"
                :style="{
                    padding: '6px 10px',
                    border: '1px solid #ddd',
                    borderRadius: '6px',
                    textDecoration: 'none',
                    opacity: link.url ? 1 : 0.4,
                    pointerEvents: link.url ? 'auto' : 'none',
                    background: link.active ? '#eee' : 'transparent',
                }"
            />
        </div>
    </div>
</template>
