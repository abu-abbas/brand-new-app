import { mount } from '@vue/test-utils';
import { describe, expect, it, beforeEach } from 'vitest';
import Combobox from './Combobox.vue';

const sampleOptions = [
  { id: 1, name: 'Jakarta' },
  { id: 2, name: 'Bandung' },
  { id: 3, name: 'Surabaya' },
];

describe('Combobox', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('mounts properly with options prop', () => {
    const wrapper = mount(Combobox, {
      props: {
        options: sampleOptions,
        valueKey: 'id',
        labelKey: 'name',
        placeholder: 'Pilih Kota',
      },
    });

    expect(wrapper.exists()).toBe(true);
    expect(wrapper.text()).toContain('Pilih Kota');
  });

  it('renders selected option label when selectedOptions is passed', () => {
    const wrapper = mount(Combobox, {
      props: {
        options: sampleOptions,
        selectedOptions: [{ id: 2, name: 'Bandung' }],
        modelValue: 2,
        valueKey: 'id',
        labelKey: 'name',
      },
    });

    expect(wrapper.text()).toContain('Bandung');
  });

  it('supports disabled state', () => {
    const wrapper = mount(Combobox, {
      props: {
        options: sampleOptions,
        disabled: true,
        placeholder: 'Pilih Kota',
      },
    });

    const triggerBtn = wrapper.find('button');
    expect(triggerBtn.attributes('disabled')).toBeDefined();
  });
});
