import { describe, it, expect, beforeEach } from 'vitest';
import { ref } from 'vue';
import { useLeaderboardMetrics } from '@/Composables/useLeaderboardMetrics';

describe('useLeaderboardMetrics', () => {
    const mockItems = [
        {
            user: { id: 1, name: 'Alice', username: 'alice' },
            streak_current: 10,
            streak_best: 15,
            active_days_last_7d: 5,
            last_active_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(), // 2h ago
        },
        {
            user: { id: 2, name: 'Bob', username: 'bob' },
            streak_current: 25,
            streak_best: 30,
            active_days_last_7d: 7,
            last_active_at: new Date(Date.now() - 30 * 60 * 1000).toISOString(), // 30m ago
        },
        {
            user: { id: 3, name: 'Charlie', username: 'charlie' },
            streak_current: 5,
            streak_best: 8,
            active_days_last_7d: 2,
            last_active_at: new Date(Date.now() - 48 * 60 * 60 * 1000).toISOString(), // 2d ago
        },
    ];

    const mockMe = { user: { id: 1, name: 'Alice', username: 'alice' } };

    let metrics;

    beforeEach(() => {
        metrics = useLeaderboardMetrics(ref(mockItems), ref(mockMe));
    });

    describe('viewOptions', () => {
        it('should have 4 view options', () => {
            expect(metrics.viewOptions).toHaveLength(4);
        });

        it('should have correct keys', () => {
            const keys = metrics.viewOptions.map(v => v.key);
            expect(keys).toEqual(['current', 'best', 'active7', 'recent']);
        });
    });

    describe('currentView', () => {
        it('should default to "current"', () => {
            expect(metrics.currentView.value).toBe('current');
        });
    });

    describe('sortedItems', () => {
        it('should sort by current streak descending in "current" view', () => {
            metrics.currentView.value = 'current';
            const sorted = metrics.sortedItems.value;
            expect(sorted[0].user.name).toBe('Bob');    // 25
            expect(sorted[1].user.name).toBe('Alice');  // 10
            expect(sorted[2].user.name).toBe('Charlie'); // 5
        });

        it('should sort by best streak descending in "best" view', () => {
            metrics.currentView.value = 'best';
            const sorted = metrics.sortedItems.value;
            expect(sorted[0].user.name).toBe('Bob');    // 30
            expect(sorted[1].user.name).toBe('Alice');  // 15
            expect(sorted[2].user.name).toBe('Charlie'); // 8
        });

        it('should sort by active days descending in "active7" view', () => {
            metrics.currentView.value = 'active7';
            const sorted = metrics.sortedItems.value;
            expect(sorted[0].user.name).toBe('Bob');    // 7
            expect(sorted[1].user.name).toBe('Alice');  // 5
            expect(sorted[2].user.name).toBe('Charlie'); // 2
        });

        it('should sort by most recent first in "recent" view', () => {
            metrics.currentView.value = 'recent';
            const sorted = metrics.sortedItems.value;
            expect(sorted[0].user.name).toBe('Bob');    // 30m ago
            expect(sorted[1].user.name).toBe('Alice');  // 2h ago
            expect(sorted[2].user.name).toBe('Charlie'); // 2d ago
        });
    });

    describe('rankedItems', () => {
        it('should add dynamicRank to each item', () => {
            const ranked = metrics.rankedItems.value;
            expect(ranked[0].dynamicRank).toBe(1);
            expect(ranked[1].dynamicRank).toBe(2);
            expect(ranked[2].dynamicRank).toBe(3);
        });
    });

    describe('champion', () => {
        it('should return the top-ranked item', () => {
            expect(metrics.champion.value.user.name).toBe('Bob');
        });

        it('should return null for empty items', () => {
            const emptyMetrics = useLeaderboardMetrics(ref([]), ref(mockMe));
            expect(emptyMetrics.champion.value).toBeNull();
        });
    });

    describe('meRow', () => {
        it('should find the current user in ranked items', () => {
            expect(metrics.meRow.value.user.name).toBe('Alice');
            expect(metrics.meRow.value.dynamicRank).toBe(2);
        });
    });

    describe('isMe', () => {
        it('should return true for current user row', () => {
            expect(metrics.isMe(mockItems[0])).toBe(true);
        });

        it('should return false for other user row', () => {
            expect(metrics.isMe(mockItems[1])).toBe(false);
        });
    });

    describe('tierFromStreak', () => {
        it('should return common for 0-2', () => {
            expect(metrics.tierFromStreak(0)).toBe('common');
            expect(metrics.tierFromStreak(1)).toBe('common');
            expect(metrics.tierFromStreak(2)).toBe('common');
        });

        it('should return uncommon for 3-6', () => {
            expect(metrics.tierFromStreak(3)).toBe('uncommon');
            expect(metrics.tierFromStreak(6)).toBe('uncommon');
        });

        it('should return rare for 7-13', () => {
            expect(metrics.tierFromStreak(7)).toBe('rare');
            expect(metrics.tierFromStreak(13)).toBe('rare');
        });

        it('should return epic for 14-29', () => {
            expect(metrics.tierFromStreak(14)).toBe('epic');
            expect(metrics.tierFromStreak(29)).toBe('epic');
        });

        it('should return legendary for 30+', () => {
            expect(metrics.tierFromStreak(30)).toBe('legendary');
            expect(metrics.tierFromStreak(100)).toBe('legendary');
        });
    });

    describe('tierFromActive', () => {
        it('should return common for 0-1', () => {
            expect(metrics.tierFromActive(0)).toBe('common');
            expect(metrics.tierFromActive(1)).toBe('common');
        });

        it('should return uncommon for 2-3', () => {
            expect(metrics.tierFromActive(2)).toBe('uncommon');
            expect(metrics.tierFromActive(3)).toBe('uncommon');
        });

        it('should return rare for 4-5', () => {
            expect(metrics.tierFromActive(4)).toBe('rare');
            expect(metrics.tierFromActive(5)).toBe('rare');
        });

        it('should return epic for 6', () => {
            expect(metrics.tierFromActive(6)).toBe('epic');
        });

        it('should return legendary for 7', () => {
            expect(metrics.tierFromActive(7)).toBe('legendary');
        });
    });

    describe('tierFromRecent', () => {
        it('should return common for null', () => {
            expect(metrics.tierFromRecent(null)).toBe('common');
        });

        it('should return legendary for <= 1h ago', () => {
            const iso = new Date(metrics.nowMs.value - 30 * 60 * 1000).toISOString();
            expect(metrics.tierFromRecent(iso)).toBe('legendary');
        });

        it('should return epic for <= 6h ago', () => {
            const iso = new Date(metrics.nowMs.value - 3 * 60 * 60 * 1000).toISOString();
            expect(metrics.tierFromRecent(iso)).toBe('epic');
        });

        it('should return rare for <= 24h ago', () => {
            const iso = new Date(metrics.nowMs.value - 12 * 60 * 60 * 1000).toISOString();
            expect(metrics.tierFromRecent(iso)).toBe('rare');
        });

        it('should return common for > 24h ago', () => {
            const iso = new Date(metrics.nowMs.value - 48 * 60 * 60 * 1000).toISOString();
            expect(metrics.tierFromRecent(iso)).toBe('common');
        });
    });

    describe('metricCfg', () => {
        it('should return fallback for null row', () => {
            const cfg = metrics.metricCfg(null);
            expect(cfg.val).toBe('-');
            expect(cfg.label).toBe('-');
        });

        it('should return streak config in "current" view', () => {
            metrics.currentView.value = 'current';
            const cfg = metrics.metricCfg(mockItems[0]);
            expect(cfg.val).toBe(10);
            expect(cfg.label).toBe('STREAK');
            expect(cfg.color).toBe('text-orange-400');
            expect(cfg.unit).toBe('streak');
        });

        it('should return active days config in "active7" view', () => {
            metrics.currentView.value = 'active7';
            const cfg = metrics.metricCfg(mockItems[0]);
            expect(cfg.val).toBe('5/7');
            expect(cfg.label).toBe('ACTIVE');
        });
    });

    describe('metricTier', () => {
        it('should return common for null row', () => {
            expect(metrics.metricTier(null)).toBe('common');
        });

        it('should use tierFromStreak for "current" view', () => {
            metrics.currentView.value = 'current';
            expect(metrics.metricTier(mockItems[0])).toBe('rare'); // streak 10
        });

        it('should use tierFromActive for "active7" view', () => {
            metrics.currentView.value = 'active7';
            expect(metrics.metricTier(mockItems[0])).toBe('rare'); // 5 active days
        });
    });

    describe('rarityChipClass', () => {
        it('should return a string containing base classes', () => {
            const cls = metrics.rarityChipClass('common');
            expect(cls).toContain('inline-flex');
            expect(cls).toContain('rounded-xl');
        });

        it('should return tier-specific classes', () => {
            expect(metrics.rarityChipClass('legendary')).toContain('border-rarity-legendary');
            expect(metrics.rarityChipClass('epic')).toContain('border-rarity-epic');
            expect(metrics.rarityChipClass('rare')).toContain('border-rarity-rare');
            expect(metrics.rarityChipClass('uncommon')).toContain('border-rarity-uncommon');
            expect(metrics.rarityChipClass('common')).toContain('border-slate');
        });

        it('should fallback to common for unknown tier', () => {
            expect(metrics.rarityChipClass('unknown')).toContain('border-slate');
        });
    });

    describe('meterInfo', () => {
        it('should return 0 pct for null row', () => {
            expect(metrics.meterInfo(null)).toEqual({ pct: 0, hint: '' });
        });

        it('should calculate streak progress in "current" view', () => {
            metrics.currentView.value = 'current';
            const info = metrics.meterInfo(mockItems[0]); // streak 10, between 7 and 14
            expect(info.pct).toBeGreaterThan(0);
            expect(info.pct).toBeLessThan(100);
            expect(info.hint).toBe('Next 14');
        });

        it('should calculate active7 progress', () => {
            metrics.currentView.value = 'active7';
            const info = metrics.meterInfo(mockItems[0]); // 5 active days
            expect(info.pct).toBeCloseTo((5 / 7) * 100);
            expect(info.hint).toBe('5/7');
        });

        it('should return MAX for streak at max milestone', () => {
            metrics.currentView.value = 'current';
            const maxRow = { streak_current: 500 };
            expect(metrics.meterInfo(maxRow)).toEqual({ pct: 100, hint: 'MAX' });
        });
    });

    describe('formatAgo', () => {
        it('should return "—" for null', () => {
            expect(metrics.formatAgo(null)).toBe('—');
        });

        it('should return "NOW" for < 60s ago', () => {
            const iso = new Date(metrics.nowMs.value - 30_000).toISOString();
            expect(metrics.formatAgo(iso)).toBe('NOW');
        });

        it('should return minutes for < 60m ago', () => {
            const iso = new Date(metrics.nowMs.value - 5 * 60_000).toISOString();
            expect(metrics.formatAgo(iso)).toBe('5m');
        });

        it('should return hours for < 24h ago', () => {
            const iso = new Date(metrics.nowMs.value - 3 * 60 * 60_000).toISOString();
            expect(metrics.formatAgo(iso)).toBe('3h');
        });

        it('should return days for >= 24h ago', () => {
            const iso = new Date(metrics.nowMs.value - 48 * 60 * 60_000).toISOString();
            expect(metrics.formatAgo(iso)).toBe('2d');
        });
    });
});
