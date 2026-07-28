import { mount } from '@vue/test-utils';
import { describe, expect, it, beforeEach } from 'vitest';
import IconPicker from './IconPicker.vue';

describe('IconPicker Component', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('renders placeholder and right filter icon when modelValue is empty', () => {
    const wrapper = mount(IconPicker, {
      props: {
        modelValue: '',
        placeholder: 'Pilih Icon...',
      },
    });

    expect(wrapper.text()).toContain('Pilih Icon...');
    // Clear button (X) should NOT exist when modelValue is empty
    expect(wrapper.find('button[title="Hapus pilihan"]').exists()).toBe(false);
  });

  it('renders selected icon name and hides right filter icon when modelValue is provided', () => {
    const wrapper = mount(IconPicker, {
      props: {
        modelValue: 'Sparkles',
        clearable: true,
      },
    });

    expect(wrapper.text()).toContain('Sparkles');
    // Clear button (X) should exist when modelValue is selected
    const clearButton = wrapper.find('button[title="Hapus pilihan"]');
    expect(clearButton.exists()).toBe(true);
  });

  it('emits update:modelValue and change with empty string when clear button is clicked', async () => {
    const wrapper = mount(IconPicker, {
      props: {
        modelValue: 'Sparkles',
        clearable: true,
      },
    });

    const clearButton = wrapper.find('button[title="Hapus pilihan"]');
    await clearButton.trigger('click');

    expect(wrapper.emitted('update:modelValue')).toEqual([['']]);
    expect(wrapper.emitted('change')).toEqual([['']]);
  });

  it('supports disabled state', () => {
    const wrapper = mount(IconPicker, {
      props: {
        modelValue: 'Sparkles',
        disabled: true,
      },
    });

    const triggerBtn = wrapper.find('button');
    expect(triggerBtn.attributes('disabled')).toBeDefined();
    // Clear button should not show when disabled
    expect(wrapper.find('button[title="Hapus pilihan"]').exists()).toBe(false);
  });
});
