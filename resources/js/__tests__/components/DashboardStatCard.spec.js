import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DashboardStatCard from '@/Components/Dashboard/DashboardStatCard.vue';

// Mock Inertia Link component
const LinkStub = {
  name: 'Link',
  template: '<a :href="href" class="inertia-link"><slot /></a>',
  props: ['href']
};

describe('DashboardStatCard', () => {
  it('renders as a div when href is not provided', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 100 },
      global: { stubs: { Link: LinkStub } }
    });
    expect(wrapper.element.tagName).toBe('DIV');
  });

  it('renders as a div when href is null', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 100, href: null },
      global: { stubs: { Link: LinkStub } }
    });
    expect(wrapper.element.tagName).toBe('DIV');
  });

  it('renders as Inertia Link when href is provided', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 100, href: '/treasury' },
      global: { stubs: { Link: LinkStub } }
    });
    expect(wrapper.element.tagName).toBe('A');
    expect(wrapper.attributes('href')).toBe('/treasury');
  });

  it('applies the correct base styling classes', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 42 },
      global: { stubs: { Link: LinkStub } }
    });
    const expectedClasses = [
      'rounded-2xl', 'border', 'border-slate-700', 'bg-slate-800/50',
      'p-3', 'md:p-5', 'text-center', 'shadow-sm',
      'transition-all', 'duration-300', 'hover:scale-105',
      'hover:bg-slate-800', 'hover:shadow-lg'
    ];
    expectedClasses.forEach(cls => {
      expect(wrapper.classes()).toContain(cls);
    });
  });

  it('renders label when provided', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 500, label: 'Treasury' },
      global: { stubs: { Link: LinkStub } }
    });
    const labelEl = wrapper.find('.text-xs.text-slate-400');
    expect(labelEl.exists()).toBe(true);
    expect(labelEl.text()).toBe('Treasury');
  });

  it('does not render label when label is empty string', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 500, label: '' },
      global: { stubs: { Link: LinkStub } }
    });
    const labelEl = wrapper.find('.text-xs.text-slate-400');
    expect(labelEl.exists()).toBe(false);
  });

  it('does not render label when label is not provided', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 500 },
      global: { stubs: { Link: LinkStub } }
    });
    const labelEl = wrapper.find('.text-xs.text-slate-400');
    expect(labelEl.exists()).toBe(false);
  });

  it('renders value with default colorClass text-white', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 42 },
      global: { stubs: { Link: LinkStub } }
    });
    const valueEl = wrapper.find('.text-2xl.font-bold');
    expect(valueEl.exists()).toBe(true);
    expect(valueEl.classes()).toContain('text-white');
    expect(valueEl.text()).toContain('42');
  });

  it('renders value with custom colorClass', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 99, colorClass: 'text-yellow-400' },
      global: { stubs: { Link: LinkStub } }
    });
    const valueEl = wrapper.find('.text-2xl.font-bold');
    expect(valueEl.classes()).toContain('text-yellow-400');
  });

  it('renders icon when provided', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 10, icon: '🔥' },
      global: { stubs: { Link: LinkStub } }
    });
    const valueEl = wrapper.find('.text-2xl.font-bold');
    expect(valueEl.text()).toContain('🔥');
    expect(valueEl.text()).toContain('10');
  });

  it('does not render icon span when icon is not provided', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 10 },
      global: { stubs: { Link: LinkStub } }
    });
    const valueEl = wrapper.find('.text-2xl.font-bold');
    expect(valueEl.find('span').exists()).toBe(false);
  });

  it('renders slot content instead of default value display', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 10 },
      slots: { default: '<div class="custom-content">Custom!</div>' },
      global: { stubs: { Link: LinkStub } }
    });
    expect(wrapper.find('.custom-content').exists()).toBe(true);
    expect(wrapper.find('.text-2xl.font-bold').exists()).toBe(false);
  });

  it('accepts string value', () => {
    const wrapper = mount(DashboardStatCard, {
      props: { value: 'N/A' },
      global: { stubs: { Link: LinkStub } }
    });
    const valueEl = wrapper.find('.text-2xl.font-bold');
    expect(valueEl.text()).toContain('N/A');
  });
});
