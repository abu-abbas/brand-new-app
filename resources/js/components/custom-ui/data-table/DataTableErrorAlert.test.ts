import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import DataTableErrorAlert from './DataTableErrorAlert.vue';

describe('DataTableErrorAlert Component', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    const writeTextMock = vi.fn().mockResolvedValue(undefined);
    Object.assign(navigator, {
      clipboard: {
        writeText: writeTextMock,
      },
    });
    document.execCommand = vi.fn().mockReturnValue(true);
  });

  it('renders the error message and code badge', () => {
    const wrapper = mount(DataTableErrorAlert, {
      props: {
        error: {
          message: 'Pengguna dalam status terkunci dan tidak dapat diubah.',
          code: 'UM-BUS-001',
          retryable: false,
        },
      },
    });

    expect(wrapper.text()).toContain('Pengguna dalam status terkunci dan tidak dapat diubah.');
    expect(wrapper.text()).toContain('UM-BUS-001');
  });

  it('does not render the retry button when retryable is false', () => {
    const wrapper = mount(DataTableErrorAlert, {
      props: {
        error: { message: 'Gagal.', retryable: false },
      },
    });

    expect(wrapper.text()).not.toContain('Coba lagi');
  });

  it('emits retry when the retry button is clicked', async () => {
    const wrapper = mount(DataTableErrorAlert, {
      props: {
        error: { message: 'Gagal memuat data.', retryable: true },
      },
    });

    const retryButton = wrapper
      .findAll('button')
      .find((button) => button.text().includes('Coba lagi'));

    expect(retryButton).toBeDefined();

    await retryButton?.trigger('click');

    expect(wrapper.emitted('retry')).toHaveLength(1);
  });

  it('renders validation errors correctly', () => {
    const wrapper = mount(DataTableErrorAlert, {
      props: {
        error: {
          message: 'Validasi gagal.',
          retryable: false,
          validationErrors: {
            username: [{ message: 'Username sudah digunakan.', code: 'UM-VAL-001' }],
            per_page: ['Jumlah per halaman maksimal 100.'],
          },
        },
      },
    });

    expect(wrapper.text()).toContain('username');
    expect(wrapper.text()).toContain('Username sudah digunakan.');
    expect(wrapper.text()).toContain('UM-VAL-001');

    expect(wrapper.text()).toContain('per_page');
    expect(wrapper.text()).toContain('Jumlah per halaman maksimal 100.');
  });

  it('copies the request ID to the clipboard when the copy button is clicked', async () => {
    const wrapper = mount(DataTableErrorAlert, {
      props: {
        error: {
          message: 'Gagal.',
          retryable: false,
          requestId: '0443b4df-31a6-444a-a30e-dfa306c222f2',
        },
      },
      attachTo: document.body,
    });

    await nextTick();

    expect(wrapper.text()).toContain('0443b4df-31a6-444a-a30e-dfa306c222f2');

    const copyButton = document.body.querySelector('button');
    expect(copyButton).not.toBeNull();

    copyButton?.click();
    await nextTick();

    expect(navigator.clipboard.writeText).toHaveBeenCalledWith(
      '0443b4df-31a6-444a-a30e-dfa306c222f2',
    );

    wrapper.unmount();
  });

  it('copies the support ID to the clipboard when the copy button is clicked', async () => {
    const wrapper = mount(DataTableErrorAlert, {
      props: {
        error: {
          message: 'Ditolak firewall.',
          retryable: false,
          supportId: '4499979717396997446',
        },
      },
      attachTo: document.body,
    });

    await nextTick();

    expect(wrapper.text()).toContain('Support ID: 4499979717396997446');

    const copyButton = document.body.querySelector('button');
    expect(copyButton).not.toBeNull();

    copyButton?.click();
    await nextTick();

    expect(navigator.clipboard.writeText).toHaveBeenCalledWith('4499979717396997446');

    wrapper.unmount();
  });
});
