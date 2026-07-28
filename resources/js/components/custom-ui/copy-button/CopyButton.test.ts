import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import CopyButton from './CopyButton.vue';

const copyMock = vi.fn().mockResolvedValue(undefined);
const isCopied = ref(false);

vi.mock('@vueuse/core', async () => {
  const actual = await vi.importActual<typeof import('@vueuse/core')>('@vueuse/core');
  return {
    ...actual,
    useClipboard: () => ({
      copy: copyMock,
      copied: isCopied,
    }),
  };
});

describe('CopyButton', () => {
  beforeEach(() => {
    copyMock.mockClear();
    isCopied.value = false;
  });

  it('merender tombol salin dengan label default', () => {
    const wrapper = mount(CopyButton, {
      props: { text: 'Teks rahasia' },
    });

    expect(wrapper.text()).toContain('Salin');
  });

  it('memanggil clipboard writeText dan memancarkan event copy saat diklik', async () => {
    const wrapper = mount(CopyButton, {
      props: { text: 'Teks rahasia' },
    });

    await wrapper.find('button').trigger('click');

    expect(copyMock).toHaveBeenCalledWith('Teks rahasia');
    expect(wrapper.emitted('copy')).toBeTruthy();
    expect(wrapper.emitted('copy')?.[0]).toEqual([{ text: 'Teks rahasia', success: true }]);
  });
});
