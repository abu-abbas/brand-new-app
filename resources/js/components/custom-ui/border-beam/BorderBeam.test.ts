import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import BorderBeam from './BorderBeam.vue';

describe('BorderBeam', () => {
  it('renders correctly with default props', () => {
    const wrapper = mount(BorderBeam);

    expect(wrapper.find('.border-beam-container').exists()).toBe(true);
    expect(wrapper.find('.border-beam-spinner').exists()).toBe(true);

    const style = wrapper.find('.border-beam-container').attributes('style');
    expect(style).toContain('--size: 300px');
    expect(style).toContain('--duration: 8s');
    expect(style).toContain('--border-width: 2px');
    expect(style).toContain('--color-from: var(--primary)');
    expect(style).toContain('--color-to: var(--chart-2)');
    expect(style).toContain('--delay: -0s');
  });

  it('applies custom props correctly', () => {
    const wrapper = mount(BorderBeam, {
      props: {
        size: 200,
        duration: 5,
        borderWidth: 3,
        colorFrom: 'var(--color-primary-100)',
        colorTo: 'var(--color-primary-900)',
        delay: 2,
      },
    });

    const style = wrapper.find('.border-beam-container').attributes('style');
    expect(style).toContain('--size: 200px');
    expect(style).toContain('--duration: 5s');
    expect(style).toContain('--border-width: 3px');
    expect(style).toContain('--color-from: var(--color-primary-100)');
    expect(style).toContain('--color-to: var(--color-primary-900)');
    expect(style).toContain('--delay: -2s');
  });

  it('merges custom class prop correctly', () => {
    const wrapper = mount(BorderBeam, {
      props: {
        class: 'custom-beam-class',
      },
    });

    expect(wrapper.find('.border-beam-container').classes()).toContain('custom-beam-class');
  });
});
