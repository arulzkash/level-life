<script setup>
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <div>
        <nav
            style="
                padding: 12px 16px;
                border-bottom: 1px solid #ddd;
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            "
        >
            <Link href="/dashboard">Dashboard</Link>
            <Link href="/quests">Quests</Link>
            <Link href="/logs/completions">Completion Log</Link>
            <Link href="/treasury">Treasury</Link>
            <Link href="/logs/treasury">Purchase Log</Link>
            <Link href="/habits">Habits</Link>
            <Link
                href="/logout"
                method="post"
                as="button"
                style="
                    background: none;
                    border: none;
                    padding: 0;
                    cursor: pointer;
                "
            >
                Logout
            </Link>
        </nav>

        <main style="padding: 16px">
            <slot />
        </main>
    </div>
</template>
