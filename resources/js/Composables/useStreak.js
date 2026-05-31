import { computed, toValue } from 'vue';

export function useStreak(profileInput, todayDateInput) {
    const profile = computed(() => toValue(profileInput));
    const todayDate = computed(() => toValue(todayDateInput));

    /**
     * Determines if the streak is technically recoverable based on the 2-freeze-per-week rule.
     * This mimics the backend logic in QuestController.php
     */
    const isRecoverable = computed(() => {
        const p = profile.value;
        const tDate = todayDate.value;

        if (!p?.last_active_date) return true;

        const lastActiveDate = new Date(p.last_active_date);
        const today = new Date(tDate);

        // Reset time to start of day for accurate day diffs
        lastActiveDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);

        const diffInDays = Math.floor((today - lastActiveDate) / (1000 * 60 * 60 * 24));

        // Same day or yesterday: definitely recoverable (or already active)
        if (diffInDays <= 1) return true;

        // --- SIMULATION START ---

        // Helper to get Monday of a date (Y-M-D string)
        const getMondayStr = (d) => {
            const date = new Date(d);
            const day = date.getDay(); // 0 is Sunday
            const diff = date.getDate() - day + (day === 0 ? -6 : 1);
            date.setDate(diff);
            return date.toISOString().split('T')[0];
        };

        const currentWeekStart = getMondayStr(today);
        const lastActiveWeekStart = getMondayStr(lastActiveDate);

        let cursor = new Date(lastActiveDate);
        cursor.setDate(cursor.getDate() + 1); // Start from first missed day

        const endGap = new Date(today);
        endGap.setDate(endGap.getDate() - 1); // End at yesterday

        let freezesUsedThisWeek =
            lastActiveWeekStart === getMondayStr(cursor) ? p.freezes_used_count || 0 : 0;

        let activeWeekStart = getMondayStr(cursor);

        while (cursor <= endGap) {
            const ws = getMondayStr(cursor);

            // New week starts
            if (activeWeekStart !== ws) {
                activeWeekStart = ws;
                freezesUsedThisWeek = 0;
            }

            freezesUsedThisWeek++;

            if (freezesUsedThisWeek > 2) {
                return false; // STREAK DEAD: Exceeded 2 freezes in a week
            }

            cursor.setDate(cursor.getDate() + 1);
        }

        return true; // SAVABLE
    });

    const streakStatus = computed(() => {
        const p = profile.value;
        const tDate = todayDate.value;

        if (!p?.last_active_date) return 'Cold';

        if (p.last_active_date === tDate) return 'On Fire';

        const d = new Date(tDate);
        d.setDate(d.getDate() - 1);
        const yesterday = d.toISOString().split('T')[0];

        if (p.last_active_date === yesterday) return 'Pending';

        return 'Cold';
    });

    const streakNumberClass = computed(() => {
        if (streakStatus.value === 'On Fire') {
            // Bright vibrant orange
            return 'text-orange-400 drop-shadow-[0_0_10px_rgba(249,115,22,0.6)] font-black';
        }

        if (streakStatus.value === 'Pending') {
            return 'text-slate-600 grayscale opacity-60';
        }

        if (streakStatus.value === 'Cold' && !isRecoverable.value) {
            // UNRECOVERABLE: Deep gray/dim
            return 'text-slate-800 opacity-40';
        }

        // COLD but recoverable: Frozen Blue
        return 'text-blue-400 drop-shadow-[0_0_10px_rgba(96,165,250,0.5)] shadow-blue-500/20';
    });

    return {
        streakStatus,
        isRecoverable,
        streakNumberClass,
    };
}
