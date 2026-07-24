import { mount } from '@vue/test-utils';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { nextTick } from 'vue';
import ConfirmDialog from './ConfirmDialog.vue';
import {
  useConfirmDialog,
  confirmDialogState,
  dismissConfirmDialog,
  acceptConfirmDialog,
} from '@/composables/useConfirmDialog';

describe('ConfirmDialog', () => {
  beforeEach(() => {
    confirmDialogState.open = false;
    confirmDialogState.loading = false;
    confirmDialogState.phase = 'confirm';
    confirmDialogState.countdown = 0;
    confirmDialogState.error = undefined;
    document.body.innerHTML = '';
  });

  it('mounts without crashing when dialog is closed', () => {
    const wrapper = mount(ConfirmDialog, {
      attachTo: document.body,
    });

    expect(wrapper.exists()).toBe(true);
    wrapper.unmount();
  });

  it('renders dialog content when open via useConfirmDialog', async () => {
    const wrapper = mount(ConfirmDialog, {
      attachTo: document.body,
    });

    const confirm = useConfirmDialog();
    const promise = confirm({
      title: 'Hapus Data',
      description: 'Apakah Anda yakin ingin menghapus data ini?',
      confirmLabel: 'Ya, Hapus',
      cancelLabel: 'Batal',
    });

    await nextTick();

    expect(confirmDialogState.open).toBe(true);
    expect(document.body.textContent).toContain('Hapus Data');
    expect(document.body.textContent).toContain('Apakah Anda yakin ingin menghapus data ini?');

    dismissConfirmDialog();
    await expect(promise).resolves.toBe(false);
    wrapper.unmount();
  });

  it('executes onConfirm action and transitions to success phase', async () => {
    const wrapper = mount(ConfirmDialog, {
      attachTo: document.body,
    });

    const onConfirmMock = vi.fn().mockResolvedValue(undefined);
    const confirm = useConfirmDialog();
    const promise = confirm({
      title: 'Konfirmasi Aksi',
      description: 'Jalankan proses penting',
      onConfirm: onConfirmMock,
      successAutoCloseSeconds: false,
    });

    await nextTick();

    await acceptConfirmDialog();
    await nextTick();

    expect(onConfirmMock).toHaveBeenCalledTimes(1);
    expect(confirmDialogState.phase).toBe('success');
    expect(document.body.textContent).toContain('Berhasil');

    dismissConfirmDialog();
    await expect(promise).resolves.toBe(true);
    wrapper.unmount();
  });

  it('handles error phase when onConfirm fails', async () => {
    const wrapper = mount(ConfirmDialog, {
      attachTo: document.body,
    });

    const errorMock = new Error('Gagal memproses request');
    const onConfirmMock = vi.fn().mockRejectedValue(errorMock);

    const confirm = useConfirmDialog();
    const promise = confirm({
      title: 'Proses Data',
      onConfirm: onConfirmMock,
      errorDescription: (err) => (err as Error).message,
    });

    await nextTick();

    await acceptConfirmDialog();
    await nextTick();
    await nextTick();

    expect(confirmDialogState.phase).toBe('error');
    expect(document.body.textContent).toContain('Proses gagal');
    expect(document.body.textContent).toContain('Gagal memproses request');

    dismissConfirmDialog();
    await expect(promise).rejects.toThrow('Gagal memproses request');
    wrapper.unmount();
  });
});
