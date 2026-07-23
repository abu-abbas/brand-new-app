import { mount, type MountingOptions } from '@vue/test-utils';
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query';
import ElementPlus from 'element-plus';
import { nextTick, type Component } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import DataTable from './DataTable.vue';

const rows = [
  { id: 1, name: 'Budi', unit: { name: 'Keuangan' } },
  { id: 2, name: 'Ani', unit: { name: 'Teknologi' } },
];
const fields = [
  { key: 'name', label: 'Nama' },
  { key: 'unit.name', label: 'Unit' },
];

function mountTable(options: MountingOptions<Record<string, unknown>> = {}) {
  return mount(DataTable as unknown as Component, {
    props: { items: rows, fields, title: 'Pengguna', rowKey: 'id' },
    global: {
      plugins: [ElementPlus, [VueQueryPlugin, { queryClient: new QueryClient() }]],
      stubs: { teleport: true },
    },
    ...options,
  });
}

beforeEach(() => {
  vi.stubGlobal(
    'ResizeObserver',
    class ResizeObserver {
      observe() {}
      unobserve() {}
      disconnect() {}
    },
  );
  vi.stubGlobal('matchMedia', () => ({
    matches: false,
    addEventListener() {},
    removeEventListener() {},
  }));
});

afterEach(() => vi.unstubAllGlobals());

describe('DataTable', () => {
  it('merender title, nested cell, dan custom cell slot', async () => {
    const wrapper = mountTable({
      slots: {
        'cell(name)': ({ value }: { value: string }) => `Nama: ${value}`,
      },
    });

    await nextTick();
    await nextTick();
    expect(wrapper.text()).toContain('Pengguna (2 baris)');
    expect(wrapper.text()).toContain('Nama: Budi');
    expect(wrapper.text()).toContain('Keuangan');
  });

  it('submit search saat Enter dan memancarkan params-change', async () => {
    const wrapper = mountTable();
    await nextTick();
    await nextTick();
    const input = wrapper.get('input[aria-label="Cari data"]');

    await input.setValue('Ani');
    await input.trigger('keydown.enter');
    await input.trigger('search');

    expect(wrapper.text()).toContain('Ani');
    expect(wrapper.text()).not.toContain('Budi');
    expect(wrapper.emitted('params-change')?.at(-1)?.[0]).toMatchObject({
      page: 1,
      search: 'Ani',
    });

    await input.setValue('tidak-ada-hasil');
    await input.trigger('keydown.enter');
    expect(wrapper.text()).toContain('Data tidak ditemukan.');
  });

  it('memancarkan action dan row event', async () => {
    const wrapper = mountTable({
      props: { items: rows, fields, rowKey: 'id', actions: true },
    });

    await nextTick();
    await nextTick();
    await wrapper.findAll('button[aria-label="Edit"]')[1].trigger('click');
    expect(wrapper.emitted('edit')?.[0]?.[0]).toEqual(rows[0]);

    await wrapper.find('.el-table__row').trigger('dblclick');
    expect(wrapper.emitted('row-dblclick')?.[0]?.[0]).toEqual(rows[0]);
  });

  it('memilih halaman aktif, memperbarui snapshot, dan menyimpan selection', async () => {
    const wrapper = mountTable({
      props: {
        items: rows,
        fields,
        rowKey: 'id',
        selection: 'multiple',
        remember: 'selection-test',
      },
    });
    await nextTick();
    await nextTick();

    await wrapper.get('[aria-label="Pilih semua baris di halaman ini"]').trigger('click');
    expect(wrapper.emitted('update:selected')?.at(-1)?.[0]).toEqual(rows);

    const updatedRows = [{ ...rows[0], name: 'Budi Baru' }, rows[1]];
    await wrapper.setProps({ items: updatedRows });
    await nextTick();

    expect(wrapper.emitted('update:selected')?.at(-1)?.[0]).toEqual(updatedRows);
    expect(
      JSON.parse(sessionStorage.getItem('datatable:default:selection-test') ?? '{}').selected,
    ).toEqual(updatedRows);
  });

  it('memperingatkan rowKey yang hilang pada multiple selection mode server', async () => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => undefined);
    mountTable({
      props: {
        fields,
        selection: 'multiple',
        fetcher: vi.fn().mockResolvedValue({ data: rows, message: 'OK' }),
      },
    });
    await nextTick();

    expect(warn).toHaveBeenCalledWith(
      '[DataTable] rowKey wajib untuk multiple selection mode server.',
    );
  });

  it('mengekspos reset, selection, scroll, dan tree expansion', () => {
    const wrapper = mountTable();

    expect(wrapper.vm).toMatchObject({
      refresh: expect.any(Function),
      resetFilters: expect.any(Function),
      clearSelection: expect.any(Function),
      scrollToTop: expect.any(Function),
      expandAll: expect.any(Function),
      collapseAll: expect.any(Function),
    });
  });

  it('menampilkan pesan ramah pengguna ketika terjadi network/technical error', async () => {
    const error = Object.assign(new Error('Network Error'), { retryable: false });
    const fetcher = vi.fn().mockRejectedValue(error);
    const wrapper = mountTable({
      props: {
        fields,
        fetcher,
      },
    });

    await nextTick();
    await nextTick();
    await new Promise((resolve) => setTimeout(resolve, 100));
    await nextTick();

    expect(fetcher).toHaveBeenCalled();
    expect(wrapper.text()).toContain('Terjadi kesalahan saat memuat data.');
  });

  it('menyimpan dan memulihkan columnWidths saat remember aktif', async () => {
    const wrapper = mountTable({
      props: {
        items: rows,
        fields,
        remember: 'column-width-test',
        rememberScope: 'custom-scope',
      },
    });

    await nextTick();
    await nextTick();

    // trigger header-dragend event
    await wrapper
      .findComponent({ name: 'ElTable' })
      .vm.$emit('header-dragend', 250, 150, { property: 'name' });
    await nextTick();

    const stored = JSON.parse(
      sessionStorage.getItem('datatable:custom-scope:column-width-test') ?? '{}',
    );
    expect(stored.columnWidths).toEqual({ name: 250 });
  });

  it('membatalkan request lama (AbortSignal.aborted) saat search param berubah sebelum fetcher terdahulu selesai', async () => {
    const signals: AbortSignal[] = [];
    const fetcher = vi.fn().mockImplementation(({ signal }: { signal: AbortSignal }) => {
      signals.push(signal);
      return new Promise((resolve) => {
        setTimeout(() => {
          resolve({ data: rows, message: 'OK' });
        }, 200);
      });
    });

    const wrapper = mountTable({
      props: {
        fields,
        fetcher,
      },
    });

    await nextTick();
    await nextTick();

    const input = wrapper.get('input[aria-label="Cari data"]');
    await input.setValue('Ani');
    await input.trigger('keydown.enter');
    await nextTick();
    await nextTick();

    expect(signals.length).toBeGreaterThanOrEqual(2);
    expect(signals[0].aborted).toBe(true);
  });

  it('membersihkan selection dan reset page ke 1 saat pencarian baru di-submit', async () => {
    const wrapper = mountTable({
      props: {
        items: rows,
        fields,
        rowKey: 'id',
        selection: 'multiple',
      },
    });
    await nextTick();
    await nextTick();

    await wrapper.get('[aria-label="Pilih baris 1"]').click();
    expect(wrapper.emitted('update:selected')?.at(-1)?.[0]).toEqual([rows[0]]);

    const input = wrapper.get('input[aria-label="Cari data"]');
    await input.setValue('Ani');
    await input.trigger('keydown.enter');
    await nextTick();

    expect(wrapper.emitted('update:selected')?.at(-1)?.[0]).toEqual([]);
    expect(wrapper.emitted('params-change')?.at(-1)?.[0]).toMatchObject({
      page: 1,
      search: 'Ani',
    });
  });

  it('expandAll membuka tree node statis dan node anak hasil lazy loading', async () => {
    const treeRows = [
      {
        id: 'parent-1',
        name: 'Parent Node',
        hasChildren: true,
      },
    ];
    const wrapper = mountTable({
      props: {
        items: treeRows,
        fields: [{ key: 'name', label: 'Nama' }],
        rowKey: 'id',
        tree: { lazy: true, hasChildren: 'hasChildren' },
      },
    });
    await nextTick();
    await nextTick();

    // Mock loaded lazy children in Element Plus store
    const elTable = wrapper.findComponent({ name: 'ElTable' });
    (elTable.vm as unknown as { store: { states: { lazyTreeNodeMap: { value: Record<string, unknown[]> } } } }).store = {
      states: {
        lazyTreeNodeMap: {
          value: {
            'parent-1': [{ id: 'child-10', name: 'Child Node' }],
          },
        },
      },
    };

    (wrapper.vm as unknown as { expandAll: () => void }).expandAll();
    await nextTick();

    expect(wrapper.emitted('update:expandedKeys')?.at(-1)?.[0]).toEqual(['parent-1']);
  });
});
