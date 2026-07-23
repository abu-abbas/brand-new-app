import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
  await page.goto('/');
});

test('datatable populated light dan dark', async ({ page }) => {
  const table = page.locator('.datatable').first();
  await expect(table).toContainText('Afria');
  await expect(table).toHaveScreenshot('datatable-populated-light.png');

  await page.evaluate(() => document.documentElement.classList.add('dark'));
  await expect(table).toHaveScreenshot('datatable-populated-dark.png');
});

test('datatable empty setelah pencarian', async ({ page }) => {
  const table = page.locator('.datatable').first();
  const search = table.getByLabel('Cari data');
  await search.fill('tidak-ada-hasil');
  await expect(search).toHaveValue('tidak-ada-hasil');
  await search.press('Enter');

  await expect(table).toContainText('Data tidak ditemukan.');
  await expect(table).toHaveScreenshot('datatable-empty.png');
});

test('datatable filter sheet dan selection', async ({ page }) => {
  const table = page.locator('.datatable').first();
  await table.getByLabel('Buka filter').click();
  const sheet = page.getByRole('dialog', { name: 'Filter data' });
  await expect(sheet).toBeVisible();
  await expect(sheet).toHaveScreenshot('datatable-filter-sheet.png');
  await page.keyboard.press('Escape');

  await table.getByLabel('Pilih baris 1', { exact: true }).click();
  await expect(table).toHaveScreenshot('datatable-selection.png');
});

test('datatable menampilkan initial loading', async ({ page }) => {
  await page.addInitScript(() => {
    const setTimeout = window.setTimeout;
    window.setTimeout = (handler, timeout, ...args) =>
      setTimeout(handler, timeout === 1200 ? 10_000 : timeout, ...args);
  });
  await page.goto('/');
  const table = page.locator('.datatable').nth(1);
  await expect(table).toContainText('Memuat data…');
  await expect(table).toHaveScreenshot('datatable-loading.png');
});
