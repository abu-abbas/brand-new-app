import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CopyButton from './CopyButton.vue';

describe('CopyButton', () => {
  it('merender tombol salin dengan label default', () => {
    const wrapper = mount(CopyButton, {
      props: { text: 'Teks rahasia' },
    });

    expect(wrapper.text()).toContain('Salin');
  });

  it('memanggil clipboard writeText dan memancarkan event copy saat diklik', async () => {
    const writeTextMock = vi.fn().mockResolvedValue(undefined);
    Object.assign(navigator, {
      clipboard: {
        writeText: writeTextMock,
      },
    });

    const wrapper = mount(CopyButton, {
      props: { text: 'Teks rahasia' },
    });

    await wrapper.find('button').trigger('click');

    expect(writeTextMock).toHaveBeenCalledWith('Teks rahasia');
    expect(wrapper.emitted('copy')).toBeTruthy();
    expect(wrapper.emitted('copy')?.[0]).toEqual([{ text: 'Teks rahasia', success: true }]);
    expect(wrapper.text()).toContain('Tersalin');
  });
});
