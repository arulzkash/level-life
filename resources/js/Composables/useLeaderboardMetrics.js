import { ref, computed } from 'vue';

/**
 * Composable for leaderboard metrics, sorting, tier calculation, and formatting.
 * Extracts all leaderboard logic from Leaderboard/Index.vue into a reusable composable.
 *
 * @param {Array} items - Reactive prop or ref containing leaderboard row items
 * @param {Object} me - Reactive prop or ref containing the current user's leaderboard data
 * @returns {Object} Leaderboard metrics API
 */
export function useLeaderboardMetrics(items, me) {
    /** Current active view mode */
    const currentView = ref('current');

    /** Available view options for the leaderboard */
    const viewOptions = [
        { key: 'current', label: 'Streak', icon: '🔥', mobileLabel: 'Streak' },
        { key: 'best', label: 'Best Streak', icon: '🏆', mobileLabel: 'Best' },
        { key: 'active7', label: 'This Week', icon: '⚡', mobileLabel: 'Week' },
        { key: 'recent', label: 'Last Seen', icon: '🕒', mobileLabel: 'Seen' },
    ];

    /** Reactive timestamp for relative time calculations, updated every 30s */
    const nowMs = ref(Date.now());
    let nowTicker = null;

    /** Start the ticker that updates nowMs every 30 seconds (for "recent" view) */
    const startNowTicker = () => {
        if (nowTicker) return;
        nowMs.value = Date.now();
        nowTicker = (typeof window !== 'undefined')
            ? window.setInterval(() => { nowMs.value = Date.now(); }, 30_000)
            : null;
    };

    /** Stop the nowMs ticker */
    const stopNowTicker = () => {
        if (!nowTicker) return;
        if (typeof window !== 'undefined') window.clearInterval(nowTicker);
        nowTicker = null;
    };

    // --- Streak milestones for meter progress ---
    const STREAK_MILESTONES = [3, 7, 14, 30, 60, 100, 150, 200, 365, 500];

    // =====================
    // FORMATTING FUNCTIONS
    // =====================

    /**
     * Format an ISO date string as a relative time ago string.
     * @param {string|null} iso - ISO date string
     * @returns {string} Relative time (e.g., "NOW", "5m", "3h", "2d") or "—" if null
     */
    const formatAgo = (iso) => {
        if (!iso) return '—';
        const diff = nowMs.value - new Date(iso).getTime();
        if (diff < 60_000) return 'NOW';
        const minutes = Math.floor(diff / 60_000);
        if (minutes < 60) return `${minutes}m`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h`;
        const days = Math.floor(hours / 24);
        return `${days}d`;
    };

    // =====================
    // TIER CALCULATION
    // =====================

    /**
     * Calculate tier from streak value.
     * 0-2 common, 3-6 uncommon, 7-13 rare, 14-29 epic, 30+ legendary
     * @param {number} n - Streak count
     * @returns {string} Tier name
     */
    const tierFromStreak = (n) => {
        if (n >= 30) return 'legendary';
        if (n >= 14) return 'epic';
        if (n >= 7) return 'rare';
        if (n >= 3) return 'uncommon';
        return 'common';
    };

    /**
     * Calculate tier from active days count (0-7 scale).
     * 0-1 common, 2-3 uncommon, 4-5 rare, 6 epic, 7 legendary
     * @param {number} n - Active days count
     * @returns {string} Tier name
     */
    const tierFromActive = (n) => {
        if (n >= 7) return 'legendary';
        if (n >= 6) return 'epic';
        if (n >= 4) return 'rare';
        if (n >= 2) return 'uncommon';
        return 'common';
    };

    /**
     * Calculate tier from recency of last activity.
     * <= 1h legendary, <= 6h epic, <= 24h rare, > 24h common
     * @param {string|null} iso - ISO date string of last activity
     * @returns {string} Tier name
     */
    const tierFromRecent = (iso) => {
        if (!iso) return 'common';
        const diff = nowMs.value - new Date(iso).getTime();
        if (diff <= 60 * 60 * 1000) return 'legendary';       // <= 1h
        if (diff <= 6 * 60 * 60 * 1000) return 'epic';        // <= 6h
        if (diff <= 24 * 60 * 60 * 1000) return 'rare';       // <= 24h
        return 'common';
    };

    // =====================
    // METRIC CONFIGURATION
    // =====================

    /**
     * Get metric configuration for a row based on current view.
     * @param {Object|null} row - Leaderboard row data
     * @returns {{ val: string|number, label: string, color: string, unit: string }}
     */
    const metricCfg = (row) => {
        if (!row) return { val: '-', label: '-', color: 'text-slate-400', unit: '' };

        if (currentView.value === 'current')
            return { val: row.streak_current ?? 0, label: 'STREAK', color: 'text-orange-400', unit: 'streak' };

        if (currentView.value === 'best')
            return { val: row.streak_best ?? 0, label: 'BEST', color: 'text-yellow-400', unit: 'streak' };

        if (currentView.value === 'active7')
            return {
                val: `${row.active_days_last_7d ?? 0}/7`,
                label: 'ACTIVE',
                color: 'text-purple-400',
                unit: 'days',
            };

        if (currentView.value === 'recent')
            return {
                val: formatAgo(row.last_active_at),
                label: 'SEEN',
                color: 'text-cyan-400',
                unit: 'time',
            };

        return { val: '-', label: '-', color: 'text-slate-300', unit: '' };
    };

    /**
     * Get the tier for a row based on current view.
     * @param {Object|null} row - Leaderboard row data
     * @returns {string} Tier name
     */
    const metricTier = (row) => {
        if (!row) return 'common';

        if (currentView.value === 'current') return tierFromStreak(row.streak_current ?? 0);
        if (currentView.value === 'best') return tierFromStreak(row.streak_best ?? 0);
        if (currentView.value === 'active7') return tierFromActive(row.active_days_last_7d ?? 0);
        if (currentView.value === 'recent') return tierFromRecent(row.last_active_at);

        return 'common';
    };

    /**
     * Get the CSS class for a rarity chip based on tier.
     * @param {string} tier - Tier name (common, uncommon, rare, epic, legendary)
     * @returns {string} Combined CSS class string
     */
    const rarityChipClass = (tier) => {
        const base =
            'inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1 font-mono font-black tracking-tight ' +
            'shadow-[0_10px_25px_rgba(0,0,0,0.25)] backdrop-blur-sm ' +
            'ring-1 ring-white/5';

        const tiers = {
            common: 'border-slate-700/60 bg-slate-950/40 text-slate-200',
            uncommon:
                'border-emerald-400/20 bg-gradient-to-r from-emerald-500/18 to-slate-950/35 text-emerald-100 shadow-[0_0_24px_rgba(16,185,129,0.10)]',
            rare: 'border-sky-400/20 bg-gradient-to-r from-sky-500/18 to-slate-950/35 text-sky-100 shadow-[0_0_24px_rgba(56,189,248,0.10)]',
            epic: 'border-purple-400/20 bg-gradient-to-r from-purple-500/18 to-slate-950/35 text-purple-100 shadow-[0_0_24px_rgba(168,85,247,0.12)]',
            legendary:
                'border-amber-400/25 bg-gradient-to-r from-amber-500/18 to-slate-950/35 text-amber-100 shadow-[0_0_28px_rgba(245,158,11,0.14)]',
        };

        return `${base} ${tiers[tier] || tiers.common}`;
    };

    // =====================
    // METER INFO
    // =====================

    /**
     * Get meter progress info for a row based on current view.
     * @param {Object|null} row - Leaderboard row data
     * @returns {{ pct: number, hint: string }}
     */
    const meterInfo = (row) => {
        if (!row) return { pct: 0, hint: '' };

        // Current / Best -> progress to next streak milestone
        if (currentView.value === 'current' || currentView.value === 'best') {
            const val = currentView.value === 'current' ? (row.streak_current ?? 0) : (row.streak_best ?? 0);

            const maxM = STREAK_MILESTONES[STREAK_MILESTONES.length - 1];
            if (val >= maxM) return { pct: 100, hint: 'MAX' };

            let prev = 0;
            let next = STREAK_MILESTONES[0];
            for (let i = 0; i < STREAK_MILESTONES.length; i++) {
                const m = STREAK_MILESTONES[i];
                if (val < m) {
                    next = m;
                    prev = i === 0 ? 0 : STREAK_MILESTONES[i - 1];
                    break;
                }
            }

            const span = Math.max(1, next - prev);
            const pct = Math.max(0, Math.min(100, ((val - prev) / span) * 100));
            return { pct, hint: `Next ${next}` };
        }

        // Active7 -> progress 0..7
        if (currentView.value === 'active7') {
            const d = Math.max(0, Math.min(7, row.active_days_last_7d ?? 0));
            return { pct: (d / 7) * 100, hint: `${d}/7` };
        }

        // Recent -> momentum decay (NOW full -> 24h empty)
        if (currentView.value === 'recent') {
            if (!row.last_active_at) {
                return { pct: 0, hint: 'Stopped' };
            }

            const diff = nowMs.value - new Date(row.last_active_at).getTime();
            const dayMs = 24 * 60 * 60 * 1000;

            const pct = Math.max(0, Math.min(100, (1 - diff / dayMs) * 100));

            let hint = 'Stopped';
            if (diff <= 30 * 60 * 1000) {
                hint = 'On fire';
            } else if (diff <= 3 * 60 * 60 * 1000) {
                hint = 'Strong momentum';
            } else if (diff <= 8 * 60 * 60 * 1000) {
                hint = 'Steady';
            } else if (diff <= 16 * 60 * 60 * 1000) {
                hint = 'Fading';
            } else if (diff <= dayMs) {
                hint = 'Lost momentum';
            }

            return { pct, hint };
        }

        return { pct: 0, hint: '' };
    };

    // =====================
    // SORTING & RANKING
    // =====================

    /**
     * Items sorted by the current view's primary metric (descending) with tiebreakers.
     */
    const sortedItems = computed(() => {
        const rawItems = items.value !== undefined ? items.value : items;
        const list = [...(rawItems || [])];

        const sortFn = (a, b) => {
            if (currentView.value === 'current') {
                const d1 = (b.streak_current ?? 0) - (a.streak_current ?? 0);
                if (d1 !== 0) return d1;

                const d2 = (b.streak_best ?? 0) - (a.streak_best ?? 0);
                if (d2 !== 0) return d2;

                const d3 = (b.active_days_last_7d ?? 0) - (a.active_days_last_7d ?? 0);
                if (d3 !== 0) return d3;

                return new Date(b.last_active_at || 0) - new Date(a.last_active_at || 0);
            }
            if (currentView.value === 'best') {
                const d = (b.streak_best ?? 0) - (a.streak_best ?? 0);
                return d !== 0 ? d : (b.streak_current ?? 0) - (a.streak_current ?? 0);
            }
            if (currentView.value === 'active7') {
                const d = (b.active_days_last_7d ?? 0) - (a.active_days_last_7d ?? 0);
                return d !== 0 ? d : (b.streak_current ?? 0) - (a.streak_current ?? 0);
            }
            if (currentView.value === 'recent') {
                const d = new Date(b.last_active_at || 0) - new Date(a.last_active_at || 0);
                return d !== 0 ? d : (b.streak_current ?? 0) - (a.streak_current ?? 0);
            }
            return 0;
        };

        return list.sort(sortFn);
    });

    /**
     * Sorted items with dynamic rank numbers added.
     */
    const rankedItems = computed(() =>
        sortedItems.value.map((r, idx) => ({ ...r, dynamicRank: idx + 1 }))
    );

    /**
     * The top-ranked item (champion), or null if no items.
     */
    const champion = computed(() => rankedItems.value[0] || null);

    /**
     * The current user's row from ranked items, or a fallback with rank '-'.
     */
    const meRow = computed(() => {
        const rawMe = me.value !== undefined ? me.value : me;
        const id = rawMe?.user?.id;
        if (!id) return null;
        return rankedItems.value.find((r) => r.user?.id === id) || { ...rawMe, dynamicRank: '-' };
    });

    /**
     * Check if a row belongs to the current user.
     * @param {Object} row - Leaderboard row data
     * @returns {boolean}
     */
    const isMe = (row) => {
        const rawMe = me.value !== undefined ? me.value : me;
        return row?.user?.id && row.user.id === rawMe?.user?.id;
    };

    return {
        currentView,
        viewOptions,
        sortedItems,
        rankedItems,
        champion,
        meRow,
        metricCfg,
        metricTier,
        tierFromStreak,
        tierFromActive,
        tierFromRecent,
        rarityChipClass,
        meterInfo,
        formatAgo,
        isMe,
        nowMs,
        startNowTicker,
        stopNowTicker,
    };
}
