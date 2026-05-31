import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import LeaderboardViewTabs from '@/Components/Leaderboard/LeaderboardViewTabs.vue';

const viewOptions = [
    { key: 'current', label: 'Streak', icon: '🔥', mobileLabel: 'Streak' },
    { key: 'best', label: 'Best Streak', icon: '🏆', mobileLabel: 'Best' },
    { key: 'active7', label: 'This Week', icon: '⚡', mobileLabel: 'Week' },
    { key: 'recent', label: 'Last Seen', icon: '🕒', mobileLabel: 'Seen' },
];

describe('LeaderboardViewTabs', () => {
    it('renders a button for each view option', () => {
        const wrapper = mount(LeaderboardViewTabs, {
            props: { viewOptions, currentView: 'current' },
        });
        const buttons = wrapper.findAll('button');
        expect(buttons.length).toBe(4);
    });

    it('applies active styling to the current view tab', () => {
        const wrapper = mount(LeaderboardViewTabs, {
            props: { viewOptions, currentView: 'best' },
        });
        const buttons = wrapper.findAll('button');
        // 'best' is the second button (index 1)
        expect(buttons[1].classes()).toContain('border-indigo-400/50');
        expect(buttons[1].classes()).toContain('bg-indigo-500/20');
        expect(buttons[1].classes()).toContain('text-indigo-100');
    });

    it('applies inactive styling to non-current view tabs', () => {
        const wrapper = mount(LeaderboardViewTabs, {
            props: { viewOptions, currentView: 'current' },
        });
        const buttons = wrapper.findAll('button');
        // 'best' is the second button (index 1) - should be inactive
        expect(buttons[1].classes()).toContain('border-slate-700/50');
        expect(buttons[1].classes()).toContain('bg-slate-900/60');
        expect(buttons[1].classes()).toContain('text-slate-400');
    });

    it('emits update:currentView with the key when a tab is clicked', async () => {
        const wrapper = mount(LeaderboardViewTabs, {
            props: { viewOptions, currentView: 'current' },
        });
        const buttons = wrapper.findAll('button');
        await buttons[2].trigger('click'); // click 'active7'
        expect(wrapper.emitted('update:currentView')).toBeTruthy();
        expect(wrapper.emitted('update:currentView')[0]).toEqual(['active7']);
    });

    it('renders icon for each tab', () => {
        const wrapper = mount(LeaderboardViewTabs, {
            props: { viewOptions, currentView: 'current' },
        });
        const buttons = wrapper.findAll('button');
        expect(buttons[0].text()).toContain('🔥');
        expect(buttons[1].text()).toContain('🏆');
        expect(buttons[2].text()).toContain('⚡');
        expect(buttons[3].text()).toContain('🕒');
    });

    it('renders full label and mobile label for each tab', () => {
        const wrapper = mount(LeaderboardViewTabs, {
            props: { viewOptions, currentView: 'current' },
        });
        const buttons = wrapper.findAll('button');
        // Full label (hidden on mobile via sm:inline)
        expect(buttons[0].find('.hidden.sm\\:inline').text()).toBe('Streak');
        expect(buttons[1].find('.hidden.sm\\:inline').text()).toBe('Best Streak');
        // Mobile label (hidden on sm via sm:hidden)
        expect(buttons[0].find('.sm\\:hidden').text()).toBe('Streak');
        expect(buttons[1].find('.sm\\:hidden').text()).toBe('Best');
    });
});
