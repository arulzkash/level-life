import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import LeaderboardChampion from '@/Components/Leaderboard/LeaderboardChampion.vue';

// Mock Inertia Link component
const LinkStub = {
    name: 'Link',
    props: ['href'],
    template: '<a :href="href"><slot /></a>',
};

const defaultChampion = {
    user: { id: 1, name: 'TestChampion', username: 'testchamp' },
    streak_current: 15,
    streak_best: 30,
    active_days_last_7d: 5,
    last_active_at: new Date().toISOString(),
    status: 'On Fire',
    badge_top: { key: 'streak_14', name: 'Warrior', description: 'A fierce warrior' },
};

const defaultMetricCfg = { val: 15, label: 'STREAK', color: 'text-orange-400', unit: 'streak' };
const defaultTierClass = 'inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1 font-mono font-black tracking-tight';
const defaultMeterInfo = { pct: 50, hint: 'Next 30' };

function createWrapper(props = {}) {
    return mount(LeaderboardChampion, {
        props: {
            champion: defaultChampion,
            currentView: 'current',
            metricCfg: defaultMetricCfg,
            tierClass: defaultTierClass,
            meterInfo: defaultMeterInfo,
            metricChipText: '15',
            formatAgo: (iso) => (iso ? '5m' : '—'),
            ...props,
        },
        global: {
            stubs: {
                Link: LinkStub,
            },
        },
    });
}

describe('LeaderboardChampion', () => {
    it('renders nothing when champion is null', () => {
        const wrapper = createWrapper({ champion: null });
        expect(wrapper.html()).toBe('<!--v-if-->');
    });

    it('renders champion name', () => {
        const wrapper = createWrapper();
        expect(wrapper.text()).toContain('TestChampion');
    });

    it('renders champion profile link when username exists', () => {
        const wrapper = createWrapper();
        const links = wrapper.findAllComponents(LinkStub);
        const profileLinks = links.filter((l) => l.props('href') === '/u/testchamp');
        expect(profileLinks.length).toBeGreaterThan(0);
    });

    it('renders champion name as span when username is missing', () => {
        const wrapper = createWrapper({
            champion: { ...defaultChampion, user: { id: 1, name: 'NoUsername' } },
        });
        expect(wrapper.text()).toContain('NoUsername');
        const links = wrapper.findAllComponents(LinkStub);
        const profileLinks = links.filter((l) => l.props('href')?.startsWith('/u/'));
        expect(profileLinks.length).toBe(0);
    });

    it('renders status badge', () => {
        const wrapper = createWrapper();
        expect(wrapper.text()).toContain('BLAZING');
    });

    it('renders badge_top when present', () => {
        const wrapper = createWrapper();
        expect(wrapper.text()).toContain('Warrior');
    });

    it('does not render badge button when badge_top is missing', () => {
        const wrapper = createWrapper({
            champion: { ...defaultChampion, badge_top: null },
        });
        const buttons = wrapper.findAll('[data-lore-trigger="1"]');
        expect(buttons.length).toBe(0);
    });

    it('renders metric label and chip text', () => {
        const wrapper = createWrapper();
        expect(wrapper.text()).toContain('STREAK');
        expect(wrapper.text()).toContain('15');
    });

    it('renders meter info hint', () => {
        const wrapper = createWrapper();
        expect(wrapper.text()).toContain('Next 30');
    });

    it('shows detail time in recent view', () => {
        const wrapper = createWrapper({ currentView: 'recent' });
        // Should show the formatDetailTime output for last_active_at
        // The desktop section shows "Last seen" text
        expect(wrapper.text()).toContain('Last seen');
    });

    it('shows active days in active7 view', () => {
        const wrapper = createWrapper({ currentView: 'active7' });
        expect(wrapper.text()).toContain('Active: 5/7');
    });

    it('handles missing optional properties with fallback values', () => {
        const championWithMissing = {
            user: { id: 2, name: 'Minimal' },
            status: 'Unknown',
            // Missing: badge_top, last_active_at, streak_current
        };
        const wrapper = createWrapper({
            champion: championWithMissing,
            metricCfg: { val: 0, label: 'STREAK', color: 'text-orange-400', unit: 'streak' },
            metricChipText: '0',
        });
        expect(wrapper.text()).toContain('Minimal');
        expect(wrapper.text()).toContain('HIDDEN');
        expect(wrapper.text()).toContain('0');
    });

    it('renders "Unknown" when user name is missing', () => {
        const wrapper = createWrapper({
            champion: { ...defaultChampion, user: { id: 1 } },
        });
        expect(wrapper.text()).toContain('Unknown');
    });

    it('emits open-lore event when badge button is clicked', async () => {
        const wrapper = createWrapper();
        const badgeButton = wrapper.find('[data-lore-trigger="1"]');
        await badgeButton.trigger('click');
        expect(wrapper.emitted('open-lore')).toBeTruthy();
    });

    it('renders crown icon and #1 rank badge', () => {
        const wrapper = createWrapper();
        expect(wrapper.text()).toContain('👑');
        expect(wrapper.text()).toContain('#1');
    });

    it('renders "Keep the crown" motivational text', () => {
        const wrapper = createWrapper();
        expect(wrapper.text()).toContain('Keep the crown.');
        expect(wrapper.text()).toContain("Don't break the chain.");
    });
});
