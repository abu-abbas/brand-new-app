import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import DataTableErrorAlert from './DataTableErrorAlert.vue';

describe('DataTableErrorAlert Component', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    // jsdom tidak mengimplementasikan Permissions API, jadi useClipboard() selalu
    // jatuh ke jalur legacy (document.execCommand) di lingkungan test ini.
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

    expect(wrapper.emitted('retry')).toBeTruthy();
  });

  it('renders each validation error field with its message and code', () => {
    const wrapper = mount(DataTableErrorAlert, {
      props: {
        error: {
          message: 'Validasi data gagal.',
          retryable: false,
          validationErrors: {
            per_page: [{ code: 'UM-VAL-005', message: 'Jumlah per halaman maksimal 100.' }],
          },
        },
      },
    });

    expect(wrapper.text()).toContain('per_page');
    expect(wrapper.text()).toContain('Jumlah per halaman maksimal 100.');
    expect(wrapper.text()).toContain('UM-VAL-005');
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

    expect(document.execCommand).toHaveBeenCalledWith('copy');

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

    expect(document.execCommand).toHaveBeenCalledWith('copy');

    wrapper.unmount();
  });
});
