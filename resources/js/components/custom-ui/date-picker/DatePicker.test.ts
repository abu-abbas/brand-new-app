import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { createPinia, setActivePinia, type Pinia } from 'pinia';
import { useAppBootstrapStore } from '@/stores/app-bootstrap';
import DatePicker from './DatePicker.vue';

describe('DatePicker', () => {
  let pinia: Pinia;

  beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
    document.body.innerHTML = '';
  });

  const mountDatePicker = (props: Record<string, unknown> = {}) => {
    return mount(DatePicker, {
      props,
      global: {
        plugins: [pinia],
      },
    });
  };

  it('uses appBootstrap.config.current_date as reference for presets', () => {
    const store = useAppBootstrapStore();
    store.config.current_date = '2026-07-28';

    const wrapper = mountDatePicker({
      presets: true,
    });

    expect(wrapper.exists()).toBe(true);
  });

  it('mounts properly with default placeholder', () => {
    const wrapper = mountDatePicker({
      placeholder: 'Pilih Tanggal Transaksi',
    });

    expect(wrapper.exists()).toBe(true);
    expect(wrapper.text()).toContain('Pilih Tanggal Transaksi');
  });

  it('formats single date value in Indonesian locale default', () => {
    const wrapper = mountDatePicker({
      modelValue: '2026-07-28',
      locale: 'id-ID',
    });

    expect(wrapper.text()).toMatch(/28/);
    expect(wrapper.text()).toMatch(/2026/);
  });

  it('supports month mode (mode="month") with YYYY-MM modelValue', () => {
    const wrapper = mountDatePicker({
      mode: 'month',
      modelValue: '2026-07',
      locale: 'id-ID',
    });

    // 2026 dan Juli
    expect(wrapper.text()).toMatch(/2026/);
    expect(wrapper.text()).toMatch(/Juli/i);
  });

  it('formats date using custom displayFormat string (DD/MM/YYYY)', () => {
    const wrapper = mountDatePicker({
      modelValue: '2026-07-28',
      displayFormat: 'DD/MM/YYYY',
    });

    expect(wrapper.text()).toContain('28/07/2026');
  });

  it('formats date using custom displayFormat function', () => {
    const wrapper = mountDatePicker({
      modelValue: '2026-07-28',
      displayFormat: (iso: string) => `Tanggal: ${iso}`,
    });

    expect(wrapper.text()).toContain('Tanggal: 2026-07-28');
  });

  it('formats date range value in Indonesian locale', () => {
    const wrapper = mountDatePicker({
      mode: 'range',
      modelValue: { start: '2026-07-01', end: '2026-07-28' },
      locale: 'id-ID',
    });

    expect(wrapper.text()).toMatch(/2026/);
    expect(wrapper.text()).toContain('-');
  });

  it('supports clear button when clearable is true and value is present', async () => {
    const wrapper = mountDatePicker({
      modelValue: '2026-07-28',
      clearable: true,
    });

    const clearBtn = wrapper.find('[data-slot="clear-button"]');
    expect(clearBtn.exists()).toBe(true);

    await clearBtn.trigger('click');

    expect(wrapper.emitted('update:modelValue')).toBeTruthy();
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([null]);
    expect(wrapper.emitted('clear')).toBeTruthy();
  });

  it('supports disabled state', () => {
    const wrapper = mountDatePicker({
      disabled: true,
      placeholder: 'Pilih Tanggal',
    });

    const triggerBtn = wrapper.find('button');
    expect(triggerBtn.attributes('disabled')).toBeDefined();
  });

  it('keeps the calendar popover inside short viewports', () => {
    const wrapper = mount(DatePicker, {
      props: { mode: 'range', popoverSide: 'left', popoverAlign: 'end' },
      global: {
        plugins: [pinia],
        stubs: {
          PopoverContent: {
            props: ['align', 'collisionPadding', 'side'],
            template:
              '<div data-testid="popover-content" :data-align="align" :data-collision-padding="collisionPadding" :data-side="side"><slot /></div>',
          },
        },
      },
    });

    const content = wrapper.get('[data-testid="popover-content"]');
    expect(content.attributes('data-collision-padding')).toBe('8');
    expect(content.attributes('data-side')).toBe('left');
    expect(content.attributes('data-align')).toBe('end');
    expect(content.classes()).toContain('max-h-[var(--reka-popper-available-height)]');
    expect(content.classes()).toContain('overflow-y-auto');
  });
});
