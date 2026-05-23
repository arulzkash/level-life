import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import JournalMoodPicker from '@/Components/Journal/JournalMoodPicker.vue';

// Stub the v-twemoji directive
const directives = {
    twemoji: (el, binding) => {
        el.textContent = binding.value;
    },
};

describe('JournalMoodPicker', () => {
    const mountPicker = (props = {}) =>
        mount(JournalMoodPicker, {
            props,
            global: { directives },
        });

    it('renders all 10 mood emoji buttons', () => {
        const wrapper = mountPicker();
        const buttons = wrapper.findAll('button');
        expect(buttons.length).toBe(10);
    });

    it('renders the "Mood" label', () => {
        const wrapper = mountPicker();
        const label = wrapper.find('span.text-\\[10px\\]');
        expect(label.exists()).toBe(true);
        expect(label.text()).toBe('Mood');
    });

    it('highlights the selected emoji when modelValue matches', () => {
        const wrapper = mountPicker({ modelValue: '🔥' });
        const buttons = wrapper.findAll('button');
        const fireButton = buttons.find((btn) => btn.text() === '🔥');
        expect(fireButton.classes()).toContain('border-sky-500');
        expect(fireButton.classes()).toContain('ring-2');
        expect(fireButton.classes()).toContain('ring-sky-500');
    });

    it('does not highlight non-selected emojis', () => {
        const wrapper = mountPicker({ modelValue: '🔥' });
        const buttons = wrapper.findAll('button');
        const sadButton = buttons.find((btn) => btn.text() === '😢');
        expect(sadButton.classes()).not.toContain('border-sky-500');
        expect(sadButton.classes()).not.toContain('ring-2');
    });

    it('emits update:modelValue when an emoji is clicked', async () => {
        const wrapper = mountPicker({ modelValue: '' });
        const buttons = wrapper.findAll('button');
        await buttons[0].trigger('click');
        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')[0]).toEqual(['😴']);
    });

    it('emits the correct emoji value for each button click', async () => {
        const wrapper = mountPicker({ modelValue: '' });
        const buttons = wrapper.findAll('button');
        await buttons[5].trigger('click');
        expect(wrapper.emitted('update:modelValue')[0]).toEqual(['🔥']);
    });

    it('works with v-model pattern (modelValue prop updates selection)', async () => {
        const wrapper = mountPicker({ modelValue: '😐' });
        const buttons = wrapper.findAll('button');
        const selectedButton = buttons.find((btn) => btn.text() === '😐');
        expect(selectedButton.classes()).toContain('ring-2');

        await wrapper.setProps({ modelValue: '🤩' });
        const newSelectedButton = wrapper.findAll('button').find((btn) => btn.text() === '🤩');
        expect(newSelectedButton.classes()).toContain('ring-2');
    });

    it('defaults modelValue to empty string when not provided', () => {
        const wrapper = mountPicker();
        const buttons = wrapper.findAll('button');
        // No button should have the selected class
        buttons.forEach((btn) => {
            expect(btn.classes()).not.toContain('ring-2');
        });
    });
});
