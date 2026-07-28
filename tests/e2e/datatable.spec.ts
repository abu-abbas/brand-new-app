import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }, testInfo) => {
  if (testInfo.title.includes('initial loading')) {
    await page.route('**/api/users*', async (route) => {
      await new Promise((resolve) => setTimeout(resolve, 3000));
      await route.continue();
    });
  }
  await page.goto('/example-custom-component#data-tables');
  await page.locator('.datatable').first().waitFor();
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

  const status = sheet.getByRole('combobox', { name: 'Status' });
  await status.click();
  await page.getByRole('option', { name: 'Ya', exact: true }).click();
  await expect(status).toContainText('Ya');
  await page.keyboard.press('Escape');

  await table.getByLabel('Pilih baris 1', { exact: true }).click();
  await expect(table).toHaveScreenshot('datatable-selection.png');
});

test('datatable menampilkan initial loading', async ({ page }) => {
  const table = page.locator('.datatable').nth(1);
  await expect(table).toContainText('Memuat data…');
  await expect(table).toHaveScreenshot('datatable-loading.png');
});

test('datatable sticky selection dan action column', async ({ page }) => {
  const table = page.locator('.datatable').first();
  const fixedColumn = table.locator('.el-table-fixed-column--left, .el-table-column--fixed-left, .el-table__fixed-left, .el-table__fixed, th.el-table__cell[class*="fixed"]').first();
  await expect(fixedColumn).toBeVisible();
  await expect(table).toHaveScreenshot('datatable-sticky.png');
});


