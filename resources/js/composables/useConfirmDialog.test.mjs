import assert from 'node:assert/strict';
import test from 'node:test';
import {
  acceptConfirmDialog,
  confirmDialogState,
  dismissConfirmDialog,
  useConfirmDialog,
} from './useConfirmDialog.ts';

test('confirm dialog handles dismiss, async success, and async error', async () => {
  const confirmDialog = useConfirmDialog();

  const dismissed = confirmDialog({ title: 'Batal?' });
  dismissConfirmDialog();
  assert.equal(await dismissed, false);

  let completed = false;
  const confirmed = confirmDialog({
    title: 'Lanjut?',
    onConfirm: async () => {
      await Promise.resolve();
      completed = true;
    },
  });
  const accepting = acceptConfirmDialog();

  assert.equal(confirmDialogState.loading, true);
  await accepting;
  assert.equal(completed, true);
  assert.equal(confirmDialogState.loading, false);
  assert.equal(confirmDialogState.phase, 'success');
  assert.equal(confirmDialogState.countdown, 3);
  dismissConfirmDialog();
  assert.equal(await confirmed, true);

  const autoClosed = confirmDialog({
    title: 'Auto close?',
    onConfirm: () => Promise.resolve(),
    successAutoCloseSeconds: 1,
  });
  await acceptConfirmDialog();
  assert.equal(confirmDialogState.countdown, 1);
  assert.equal(await autoClosed, true);
  assert.equal(confirmDialogState.open, false);

  const failed = confirmDialog({
    title: 'Gagal?',
    onConfirm: () => Promise.reject(new Error('Request gagal.')),
  });
  await acceptConfirmDialog();
  assert.equal(confirmDialogState.phase, 'error');

  const rejection = assert.rejects(failed, /Request gagal/);
  dismissConfirmDialog();
  await rejection;
});
