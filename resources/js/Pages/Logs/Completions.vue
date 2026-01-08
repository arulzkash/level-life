<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    logs: Object, // paginator object
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
            <h2>Completion Log</h2>
            <Link href="/dashboard">Back to Dashboard</Link>
        </div>

        <p style="margin: 8px 0 16px">
            Ini semua catatan completion (repeatable & non-repeatable).
        </p>

        <div v-if="logs.data.length === 0">Belum ada completion.</div>

        <ul v-else>
            <li
                v-for="log in logs.data"
                :key="log.id"
                style="margin-bottom: 14px"
            >
                <div>
                    <strong>{{ log.quest?.name ?? "(Quest deleted)" }}</strong>
                    <span> — {{ log.quest?.type }}</span>
                </div>

                <div>
                    XP: {{ log.xp_awarded }} | Coins: {{ log.coin_awarded }}
                </div>

                <div v-if="log.note">Note: {{ log.note }}</div>

                <div style="opacity: 0.7">
                    Completed at: {{ log.completed_at }}
                </div>

                <hr style="margin-top: 10px" />
            </li>
        </ul>

        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px">
            <Link
                v-for="link in logs.links"
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
