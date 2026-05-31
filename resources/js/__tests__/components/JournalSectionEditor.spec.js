import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import JournalSectionEditor from '@/Components/Journal/JournalSectionEditor.vue';

const makeSections = (count = 3) =>
    Array.from({ length: count }, (_, i) => ({
        id: `sec_${i}`,
        title: `Section ${i + 1}`,
        content: `Content ${i + 1}`,
    }));

describe('JournalSectionEditor', () => {
    it('renders all sections passed via props', () => {
        const sections = makeSections(3);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        const sectionEls = wrapper.findAll('[id^="sec-"]');
        expect(sectionEls).toHaveLength(3);
    });

    it('renders empty state when sections is empty', () => {
        const wrapper = mount(JournalSectionEditor, { props: { sections: [] } });
        const sectionEls = wrapper.findAll('[id^="sec-"]');
        expect(sectionEls).toHaveLength(0);
    });

    it('renders section titles in inputs', () => {
        const sections = makeSections(2);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        const inputs = wrapper.findAll('input[placeholder="SECTION TITLE"]');
        expect(inputs[0].element.value).toBe('Section 1');
        expect(inputs[1].element.value).toBe('Section 2');
    });

    it('renders section content in textareas', () => {
        const sections = makeSections(2);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        const textareas = wrapper.findAll('textarea');
        expect(textareas[0].element.value).toBe('Content 1');
        expect(textareas[1].element.value).toBe('Content 2');
    });

    it('emits "add" when add button is clicked', async () => {
        const wrapper = mount(JournalSectionEditor, { props: { sections: [] } });
        const addBtn = wrapper.find('button');
        await addBtn.trigger('click');
        expect(wrapper.emitted('add')).toHaveLength(1);
    });

    it('emits "remove" with index when delete button is clicked', async () => {
        const sections = makeSections(2);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        const deleteButtons = wrapper.findAll('button').filter(b => b.text().includes('🗑️'));
        await deleteButtons[1].trigger('click');
        expect(wrapper.emitted('remove')).toHaveLength(1);
        expect(wrapper.emitted('remove')[0]).toEqual([1]);
    });

    it('emits "reorder" with (from, to) when move up is clicked', async () => {
        const sections = makeSections(3);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        // Find all ▲ buttons (move up)
        const upButtons = wrapper.findAll('button').filter(b => b.text() === '▲');
        // Click move up on second section (idx=1)
        await upButtons[1].trigger('click');
        expect(wrapper.emitted('reorder')).toHaveLength(1);
        expect(wrapper.emitted('reorder')[0]).toEqual([1, 0]);
    });

    it('emits "reorder" with (from, to) when move down is clicked', async () => {
        const sections = makeSections(3);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        // Find all ▼ buttons (move down)
        const downButtons = wrapper.findAll('button').filter(b => b.text() === '▼');
        // Click move down on first section (idx=0)
        await downButtons[0].trigger('click');
        expect(wrapper.emitted('reorder')).toHaveLength(1);
        expect(wrapper.emitted('reorder')[0]).toEqual([0, 1]);
    });

    it('does not emit "reorder" when move up is clicked on first section', async () => {
        const sections = makeSections(3);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        const upButtons = wrapper.findAll('button').filter(b => b.text() === '▲');
        await upButtons[0].trigger('click');
        expect(wrapper.emitted('reorder')).toBeUndefined();
    });

    it('does not emit "reorder" when move down is clicked on last section', async () => {
        const sections = makeSections(3);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        const downButtons = wrapper.findAll('button').filter(b => b.text() === '▼');
        await downButtons[2].trigger('click');
        expect(wrapper.emitted('reorder')).toBeUndefined();
    });

    it('emits "update" with (idx, "title", value) when title input changes', async () => {
        const sections = makeSections(2);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        const inputs = wrapper.findAll('input[placeholder="SECTION TITLE"]');
        await inputs[0].setValue('New Title');
        expect(wrapper.emitted('update')).toHaveLength(1);
        expect(wrapper.emitted('update')[0]).toEqual([0, 'title', 'New Title']);
    });

    it('emits "update" with (idx, "content", value) when textarea changes', async () => {
        const sections = makeSections(2);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        const textareas = wrapper.findAll('textarea');
        await textareas[1].setValue('Updated content');
        expect(wrapper.emitted('update')).toHaveLength(1);
        expect(wrapper.emitted('update')[0]).toEqual([1, 'content', 'Updated content']);
    });

    it('renders the add button with correct text', () => {
        const wrapper = mount(JournalSectionEditor, { props: { sections: [] } });
        const addBtn = wrapper.find('button');
        expect(addBtn.text()).toContain('+ Add New Section');
    });

    it('applies correct styling classes to section cards', () => {
        const sections = makeSections(1);
        const wrapper = mount(JournalSectionEditor, { props: { sections } });
        const sectionEl = wrapper.find('[id^="sec-"]');
        expect(sectionEl.classes()).toContain('rounded-xl');
        expect(sectionEl.classes()).toContain('border');
        expect(sectionEl.classes()).toContain('border-slate-700');
    });
});
