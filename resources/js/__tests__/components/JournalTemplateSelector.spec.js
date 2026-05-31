import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import JournalTemplateSelector from '@/Components/Journal/JournalTemplateSelector.vue';

const sampleTemplates = [
    {
        id: 'builtin:Daily Review',
        name: 'Daily Review',
        sections: [
            { title: 'Gratitude (3)' },
            { title: 'Top 3 Wins' },
            { title: 'Challenges / Friction' },
        ],
    },
    {
        id: 'builtin:Morning Plan',
        name: 'Morning Plan',
        sections: [
            { title: 'Intention' },
            { title: 'Must-Do (1–3)' },
        ],
    },
    {
        id: 'user:1',
        name: 'My Custom Template',
        sections: [
            { title: 'Section A' },
        ],
    },
];

describe('JournalTemplateSelector', () => {
    it('renders the available blueprints heading', () => {
        const wrapper = mount(JournalTemplateSelector, {
            props: { templates: sampleTemplates },
        });
        expect(wrapper.text()).toContain('Available Blueprints');
    });

    it('renders all templates in the grid', () => {
        const wrapper = mount(JournalTemplateSelector, {
            props: { templates: sampleTemplates },
        });
        const gridButtons = wrapper.findAll('.grid button');
        expect(gridButtons.length).toBe(sampleTemplates.length);
    });

    it('displays template name and section count', () => {
        const wrapper = mount(JournalTemplateSelector, {
            props: { templates: sampleTemplates },
        });
        expect(wrapper.text()).toContain('Daily Review');
        expect(wrapper.text()).toContain('3 sections');
        expect(wrapper.text()).toContain('Morning Plan');
        expect(wrapper.text()).toContain('2 sections');
        expect(wrapper.text()).toContain('My Custom Template');
        expect(wrapper.text()).toContain('1 sections');
    });

    it('emits select-template with template data when a template is clicked', async () => {
        const wrapper = mount(JournalTemplateSelector, {
            props: { templates: sampleTemplates },
        });
        const gridButtons = wrapper.findAll('.grid button');
        await gridButtons[0].trigger('click');

        expect(wrapper.emitted('select-template')).toBeTruthy();
        expect(wrapper.emitted('select-template')[0]).toEqual([sampleTemplates[0]]);
    });

    it('emits select-template with correct template for each button', async () => {
        const wrapper = mount(JournalTemplateSelector, {
            props: { templates: sampleTemplates },
        });
        const gridButtons = wrapper.findAll('.grid button');

        await gridButtons[1].trigger('click');
        expect(wrapper.emitted('select-template')[0]).toEqual([sampleTemplates[1]]);

        await gridButtons[2].trigger('click');
        expect(wrapper.emitted('select-template')[1]).toEqual([sampleTemplates[2]]);
    });

    it('renders empty grid when templates is empty', () => {
        const wrapper = mount(JournalTemplateSelector, {
            props: { templates: [] },
        });
        const gridButtons = wrapper.findAll('.grid button');
        expect(gridButtons.length).toBe(0);
    });

    it('renders with default empty array when templates prop is not provided', () => {
        const wrapper = mount(JournalTemplateSelector);
        const gridButtons = wrapper.findAll('.grid button');
        expect(gridButtons.length).toBe(0);
    });
});
