import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import DatePicker from './DatePicker.vue';

describe('DatePicker', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
  });

  it('mounts properly with default placeholder', () => {
    const wrapper = mount(DatePicker, {
      props: {
        placeholder: 'Pilih Tanggal Transaksi',
      },
    });

    expect(wrapper.exists()).toBe(true);
    expect(wrapper.text()).toContain('Pilih Tanggal Transaksi');
  });

  it('formats single date value in Indonesian locale default', () => {
    const wrapper = mount(DatePicker, {
      props: {
        modelValue: '2026-07-28',
        locale: 'id-ID',
      },
    });

    expect(wrapper.text()).toMatch(/28/);
    expect(wrapper.text()).toMatch(/2026/);
  });

  it('supports month mode (mode="month") with YYYY-MM modelValue', () => {
    const wrapper = mount(DatePicker, {
      props: {
        mode: 'month',
        modelValue: '2026-07',
        locale: 'id-ID',
      },
    });

    // 2026 dan Juli
    expect(wrapper.text()).toMatch(/2026/);
    expect(wrapper.text()).toMatch(/Juli/i);
  });

  it('formats date using custom displayFormat string (DD/MM/YYYY)', () => {
    const wrapper = mount(DatePicker, {
      props: {
        modelValue: '2026-07-28',
        displayFormat: 'DD/MM/YYYY',
      },
    });

    expect(wrapper.text()).toContain('28/07/2026');
  });

  it('formats date using custom displayFormat function', () => {
    const wrapper = mount(DatePicker, {
      props: {
        modelValue: '2026-07-28',
        displayFormat: (iso) => `Tanggal: ${iso}`,
      },
    });

    expect(wrapper.text()).toContain('Tanggal: 2026-07-28');
  });

  it('formats date range value in Indonesian locale', () => {
    const wrapper = mount(DatePicker, {
      props: {
        mode: 'range',
        modelValue: { start: '2026-07-01', end: '2026-07-28' },
        locale: 'id-ID',
      },
    });

    expect(wrapper.text()).toMatch(/2026/);
    expect(wrapper.text()).toContain('-');
  });

  it('supports clear button when clearable is true and value is present', async () => {
    const wrapper = mount(DatePicker, {
      props: {
        modelValue: '2026-07-28',
        clearable: true,
      },
    });

    const clearBtn = wrapper.find('[data-slot="clear-button"]');
    expect(clearBtn.exists()).toBe(true);

    await clearBtn.trigger('click');

    expect(wrapper.emitted('update:modelValue')).toBeTruthy();
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([null]);
    expect(wrapper.emitted('clear')).toBeTruthy();
  });

  it('supports disabled state', () => {
    const wrapper = mount(DatePicker, {
      props: {
        disabled: true,
        placeholder: 'Pilih Tanggal',
      },
    });

    const triggerBtn = wrapper.find('button');
    expect(triggerBtn.attributes('disabled')).toBeDefined();
  });
});
