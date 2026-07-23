# DataTable Reusable — Kontrak dan Spesifikasi

Status: keputusan desain disepakati, implementasi belum dimulai.

Dokumen ini menjadi source of truth agar pembahasan dan implementasi DataTable dapat dilanjutkan pada
session/window lain tanpa mengulang discovery.

## 1. Tujuan

Menyediakan satu DataTable reusable untuk mayoritas halaman aplikasi dengan:

- mode data lokal dan server;
- konfigurasi kolom sederhana berbasis `fields`;
- custom header/cell melalui named slot;
- search, filter, sort, pagination, selection, action, loading, empty, dan error state;
- dukungan lanjutan untuk tree, grouped header, dan merged cell;
- visual mengikuti semantic tokens dan primitives shadcn-vue project.

Fitur yang belum mempunyai kebutuhan nyata tidak dimasukkan. Bulk-action toolbar, card/mobile renderer,
summary row, dan i18n ditunda.

## 2. Arsitektur dan dependency

### 2.1 Layering wajib

DataTable tidak boleh mengimpor Axios atau generated Orval client.

Alur server:

```text
DataTable
  -> fetcher prop
  -> API Facade module
  -> Orval generated client
  -> Axios
  -> Laravel API
```

DataTable mengelola TanStack Query, query state, retry, cache, loading, error, dan lifecycle event.
URL, HTTP method, autentikasi, CSRF, dan transform khusus endpoint tetap menjadi tanggung jawab API
Facade.

Mode server tidak memiliki prop `url` atau `method`.

### 2.2 Engine UI

- Body table menggunakan Element Plus Table sebagai engine untuk fixed column, resize, selection,
  expand, tree, lazy tree, dan span.
- Toolbar, Sheet filter, Button, Input, Tooltip, dan kontrol lain menggunakan shadcn-vue.
- Skin Element Plus dibuat scoped pada wrapper DataTable dan memetakan semantic tokens shadcn-vue.
- Screenshot referensi hanya mengatur struktur/perilaku. Source of truth visual adalah theme
  shadcn-vue project, termasuk dark mode dan theme color.
- Pixel-perfect diverifikasi pada viewport/browser baseline melalui screenshot test Playwright.

Dependency yang belum tersedia saat spec dibuat:

- Element Plus;
- TanStack Query;
- Axios/Orval dan infrastruktur API project yang direncanakan rules.

Dependency dipasang saat fase implementasi yang membutuhkannya.

## 3. Penentuan mode data

Tidak ada prop `mode`.

```vue
<DataTable :items="users" />

<DataTable query-key="users" :fetcher="fetchUsers" />
```

- `items` menentukan mode lokal.
- `fetcher` menentukan mode server.
- Jika `items` dan `fetcher` diberikan bersamaan, tampilkan development warning.
- Jika keduanya tidak diberikan, tabel berada pada empty state.

## 4. Kontrak mode server

### 4.1 Fetcher

```ts
type DataTableFetcher<T> = (context: {
  params: DataTableParams;
  signal: AbortSignal;
}) => Promise<LaravelDataTableResponse<T>>;
```

Contoh:

```vue
<DataTable query-key="users" :fetcher="({ params, signal }) => usersApi.list(params, signal)" />
```

`GET` atau `POST` ditentukan API Facade. DataTable hanya mengirim object params dan `AbortSignal`.
Request lama dibatalkan ketika params berubah agar response terlambat tidak menimpa data terbaru.

### 4.2 Query key

- `queryKey` opsional.
- Jika tidak diberikan, DataTable membuat key unik untuk instance aktif.
- Key otomatis tidak menjamin cache stabil setelah remount.
- Saat `remember` digunakan, `queryKey` eksplisit direkomendasikan dan warning ditampilkan jika absen.
- Params aktif dan `extraParams` selalu menjadi bagian query key final.

### 4.3 Params

Kontrak dasar:

```ts
interface DataTableParams {
  page?: number;
  per_page?: number;
  search?: string;
  search_fields?: string[];
  sort_by?: string;
  sort_direction?: 'asc' | 'desc';
  sort?: Array<{
    key: string;
    direction: 'asc' | 'desc';
  }>;
  filters?: Record<string, unknown>;
  [extraParam: string]: unknown;
}
```

- Single-sort memakai `sort_by` dan `sort_direction`.
- Multi-sort memakai `sort` sesuai urutan prioritas dan tidak mengirim pasangan single-sort.
- `extraParams` digabung ke params dan bersifat reaktif.
- Param internal (`page`, `per_page`, `search`, `search_fields`, sort, dan `filters`) tidak boleh ditimpa
  `extraParams`.
- Kasus kontrak request khusus ditangani di API Facade/fetcher.
- Perubahan search, filter, sort, `per_page`, atau `extraParams` mengembalikan page ke `1`.
- `extraParams` yang berubah langsung memicu refetch.

Contoh single-sort:

```ts
{
  page: 1,
  per_page: 10,
  search: 'Afria',
  search_fields: ['username', 'name', 'unit_kerja'],
  sort_by: 'name',
  sort_direction: 'asc',
  filters: {
    role: 'staff',
    non_asn: false,
  },
}
```

### 4.4 Response sukses

`GET` dan `POST` harus mengembalikan bentuk konsisten:

```ts
interface LaravelDataTableResponse<T> {
  data: T[];
  meta?: {
    current_page: number;
    from: number | null;
    last_page: number;
    links: Array<{
      url: string | null;
      label: string;
      page?: number | null;
      active: boolean;
    }>;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
  message: string;
}
```

DataTable menghitung pagination dari `meta.current_page`, `meta.last_page`, `meta.per_page`, dan
`meta.total`. URL pada `meta.links` tidak diikuti.

### 4.5 Error

Application error mengikuti Error Definition Framework:

```json
{
  "message": "Pesan aman untuk pengguna.",
  "code": "MODULE-CONTEXT-001",
  "retryable": false
}
```

Validation error `422`:

```json
{
  "message": "Validasi gagal.",
  "errors": {
    "field": [
      {
        "code": "MODULE-VAL-001",
        "message": "Pesan aman untuk pengguna.",
        "retryable": false
      }
    ]
  }
}
```

Aturan:

- branching memakai `code`, status, dan `retryable`, bukan teks `message`;
- initial load gagal mempertahankan header/footer dan menampilkan error pada body;
- `message` contract digunakan sebagai pesan publik; network/unknown error memakai pesan generik;
- `retryable: true` menampilkan tombol **Coba lagi**;
- `422` menampilkan top-level message dan meneruskan structured `errors` melalui event;
- `401` mengikuti handler autentikasi global;
- `403` dan application error lain menampilkan pesan publik sesuai contract;
- ketika rows lama sudah ada, refetch gagal tidak mengosongkan tabel;
- request ID dari response header dapat diteruskan dalam payload event error.

Retry TanStack Query:

- tidak retry untuk `4xx` atau `retryable: false`;
- maksimal dua retry dengan backoff untuk network error, `5xx`, atau `retryable: true`;
- setelah habis, tampilkan error state/tombol retry.

### 4.6 Waktu initial request

- Default request berjalan setelah component mounted dan melewati satu render tick agar struktur tabel
  dan loading state sempat ter-render.
- Prop `enabled` dapat menunda query sampai dependency parent tersedia.

## 5. Mode lokal

- Menerima array object melalui `items`.
- Search, filter, sort, dan pagination dijalankan di browser.
- Dataset sekitar 11 ribu row masih memakai pagination biasa; virtual scrolling tidak diperlukan.
- Jika `paginated=false`, seluruh hasil dirender dan caller bertanggung jawab tidak memasukkan dataset
  yang terlalu besar.
- Search berjalan setelah pengguna menekan `Enter`, bukan pada setiap ketikan.
- Search case-insensitive dan memakai satu frasa utuh dengan strategi `contains`.
- Nilai object/array ditelusuri secara rekursif, termasuk nested value.
- Jika `search_fields` dibatasi, traversal dimulai hanya dari field terpilih.
- Sorting tree lokal berjalan per-level, bukan dengan flatten global.

## 6. Fields dan column rendering

### 6.1 Bentuk dasar

```ts
type TableAlign = 'left' | 'center' | 'right';
type CellWrap = 'wrap' | 'nowrap' | 'ellipsis';

interface DataTableField<T> {
  key: string;
  label: string;
  hidden?: boolean;
  filterColumn?: boolean;
  sortable?: boolean;
  sortKey?: string;
  width?: string | number;
  minWidth?: string | number;
  align?: TableAlign;
  headerAlign?: TableAlign;
  wrap?: CellWrap;
  fixed?: 'left' | 'right';
  resizable?: boolean;
  formatter?: (value: unknown, row: T, column: DataTableField<T>) => string | number;
  children?: DataTableField<T>[];
}
```

Default:

- `hidden = false`;
- `filterColumn = true`;
- `sortable = true`;
- `wrap = 'wrap'`;
- `resizable = true`;
- `null`/`undefined` dirender sebagai `-`.

Aturan:

- `hidden: true` tidak merender kolom dan user tidak dapat melakukan unhide;
- field hidden tetap searchable selama `filterColumn !== false`;
- `filterColumn: false` mengeluarkan field dari daftar kolom global search;
- nested key seperti `unit.name` dibaca otomatis;
- `sortKey` dipakai jika key backend berbeda dari key tampilan;
- custom slot mengendalikan layout cell-nya sendiri;
- mode `ellipsis` memakai tooltip hanya ketika konten benar-benar terpotong;
- formatter hanya untuk output sederhana; markup kompleks memakai slot.

### 6.2 Field virtual `rownum`

`key: 'rownum'` dihitung oleh DataTable dan tidak dibaca dari response.

- Server: `(currentPage - 1) * perPage + rowIndex + 1`.
- Lokal: mengikuti hasil search/sort/pagination aktif.
- Tree: nomor hanya tampil pada root; child kosong.

### 6.3 Grouped header

Grouped/multi-level header memakai `children` secara rekursif:

```ts
{
  key: 'identity',
  label: 'Identitas',
  children: [
    { key: 'name', label: 'Nama' },
    { key: 'nip', label: 'NIP' },
    { key: 'email', label: 'Email' },
  ],
}
```

Parent group hanya membentuk header. Search, sort, filter, width, dan cell berlaku pada leaf column.

### 6.4 Resize, fixed, dan scroll

- Column resize aktif secara default dan dapat dimatikan per field.
- Resize menghormati `minWidth`.
- Lebar hasil resize hanya disimpan saat `remember` aktif.
- Selection dan action selalu sticky di kiri.
- Field lain dapat memakai `fixed: 'left' | 'right'`.
- Tabel tetap tabel pada mobile dan memakai horizontal scroll; tidak ada card renderer.
- Tinggi default mengikuti konten.
- `height` atau `maxHeight` mengaktifkan body scroll dan sticky header.
- Tanpa `height`/`maxHeight`, header tidak sticky terhadap scroll halaman.

### 6.5 Merge cell

Merge cell bersifat opt-in:

```ts
type SpanMethod<T> = (context: {
  row: T;
  column: DataTableField<T>;
  rowIndex: number;
  columnIndex: number;
}) => [rowspan: number, colspan: number] | { rowspan: number; colspan: number };
```

## 7. Slot

### 7.1 Title

```vue
<DataTable title="Daftar Pengguna" />
```

Default:

```text
Daftar Pengguna (66.601 baris)
```

Override:

```vue
<template #title="{ title, total, loading }">...</template>
```

`total` berasal dari `meta.total` untuk server dan jumlah hasil aktif untuk lokal.

### 7.2 Toolbar

Susunan:

```text
[ title ] [ slot toolbar fleksibel ] [ refresh | filter | search ]
```

Slot tengah:

```vue
<template #toolbar="{ params, refresh }">...</template>
```

Kontrol toolbar dapat disembunyikan satu per satu melalui `showRefresh`, `showFilter`, dan
`showSearch`, default `true`.

Tombol filter otomatis tidak tampil jika tidak ada advanced filter dan pilihan `search_fields` tidak
dapat diubah.

### 7.3 Header dan cell

```vue
<template #header(prioritas)="{ column }">...</template>

<template #cell(name)="{ row, value, column, rowIndex, search }">...</template>
```

Scope cell minimal:

- `row`;
- `value`;
- `column`;
- `rowIndex`;
- `search`;
- helper highlight untuk custom tree cell.

### 7.4 Expand

```vue
<template #expand="{ row, expanded }">...</template>
```

Jika slot tersedia, DataTable menambah kolom chevron. Expand detail memakai mode accordion: maksimal
satu row terbuka.

## 8. Toolbar, search, dan filter

### 8.1 Search

- Request/filter lokal hanya berjalan ketika pengguna menekan `Enter`.
- Input menampilkan hint `Enter`.
- Ketikan yang belum di-submit adalah draft dan tidak memicu `params-change`.
- Clear search mengembalikan page ke `1` dan menjalankan query/filter ulang.

### 8.2 Pemilihan kolom search

Drawer bagian **Filter Kolom** mengatur `search_fields`, bukan visibilitas tabel.

- Semua field dengan `filterColumn !== false` tersedia dan terpilih secara default.
- Field `hidden` tetap tersedia dan terpilih selama `filterColumn !== false`.
- Perubahan menjadi aktif setelah tombol **Terapkan**.

### 8.3 Advanced filter

Advanced filter dipisahkan dari `fields`:

```ts
interface DataTableFilter<TValue = unknown> {
  key: string;
  label: string;
  type: 'text' | 'select' | 'multi-select' | 'boolean' | 'date' | 'date-range' | 'custom';
  options?: Array<{ label: string; value: unknown }>;
  serialize?: (value: TValue) => unknown;
}
```

- Built-in date/date-range menghasilkan nilai tanggal lokal `YYYY-MM-DD`.
- Struktur payload domain tidak dipaksakan DataTable; `serialize` atau API Facade menentukan bentuk
  akhirnya.
- Filter khusus memakai slot `#filter(key)` dengan scope `{ value, setValue, filter }`.
- Drawer memakai draft state.
- **Terapkan** mengaktifkan draft, reset page ke `1`, dan menjalankan query.
- **Atur Ulang** menghapus draft dan filter aktif, reset page ke `1`, lalu langsung menjalankan query.
- Filter aktif dapat dikontrol melalui `v-model:filters`.
- Perubahan `v-model:filters` dari parent langsung reset page ke `1` dan refetch.

### 8.4 Refresh

Refresh hanya refetch dengan state aktif. Refresh tidak menghapus page, per-page, search, filter, sort,
atau selection.

## 9. Sorting

- Semua leaf field sortable secara default.
- `sortable: false` menonaktifkan sort.
- Field virtual/control seperti `rownum`, selection, expand, dan action tidak sortable.
- Klik biasa menjadikan kolom tersebut satu-satunya sort.
- `Shift + klik` menambah/mengubah multi-sort.
- Siklus: `asc -> desc -> tidak di-sort`.
- Saat multi-sort, header menampilkan arah dan nomor prioritas.
- Sort tree lokal mengurutkan sibling pada setiap level.

## 10. Pagination

Default:

```ts
perPage = 10;
perPageOptions = [5, 10, 15, 25, 50, 100];
```

- `perPage` dan `perPageOptions` dapat dioverride.
- Perubahan per-page langsung reset page ke `1`.
- Maksimal lima item pager, termasuk ellipsis.
- Tombol previous/next terpisah, sehingga maksimal tujuh elemen.
- Untuk jumlah halaman kecil, pager menampilkan minimal tiga item bila tersedia; dengan previous/next
  menjadi lima elemen.
- `showPagination` dan `showPerPage` hanya mengatur visibilitas kontrol.
- `paginated=false` tidak mengirim `page`/`per_page`, tidak memotong data lokal, dan otomatis
  menyembunyikan kedua kontrol.
- Tree tanpa pagination umumnya memakai `paginated=false`.
- Jika tree lokal tetap paginated, pagination menghitung root node saja.

## 11. Selection

API:

```vue
<DataTable selection v-model:selected="selectedUser" />

<DataTable selection="multiple" v-model:selected="selectedUsers" />
```

- Tanpa `selection`: tidak ada kolom selection.
- `selection` boolean berarti single selection dengan radio.
- `selection="multiple"` berarti multiple selection dengan checkbox.
- Kolom selection otomatis paling kiri.
- Action berada setelah selection.
- Single model: `T | null`.
- Multiple model: `T[]`.
- Nilai awal parent menjadi preselected.
- Row terpilih memperoleh visual state `selected`.
- `rowSelectable(row)` menentukan apakah row dapat dipilih.
- Row yang tidak selectable menampilkan control disabled dan tidak dapat dipilih lewat interaksi lain.
- Klik row tidak otomatis mengubah selection; selection hanya melalui radio/checkbox.
- Button, link, input, dan cell action tidak memengaruhi selection.

Persistence:

- pindah halaman: selection tetap;
- sort/refresh: selection tetap;
- search/filter berubah: selection dibersihkan;
- select-all: hanya page aktif;
- mode server + multiple memerlukan `rowKey`;
- `rowKey` default mencoba `id`, tetapi dapat berupa key lain atau callback;
- snapshot row terpilih diperbarui ketika row dengan key sama muncul lagi.

Tree + selection tidak melakukan cascade parent/child; setiap node independen.

Bulk-action toolbar belum dibuat.

## 12. Action

Action bersifat opt-in:

```vue
<DataTable actions @edit="handleEdit" @delete="handleDelete" />
```

- Tanpa `actions`: tidak ada kolom action.
- `actions` menambah kolom sticky di kiri, setelah selection.
- Default renderer menampilkan **Edit** dan **Hapus**.
- Default renderer emit `edit(row)` dan `delete(row)`.
- `canEdit(row)` dan `canDelete(row)` menyembunyikan action terkait ketika menghasilkan `false`.
- DataTable tidak menjalankan mutation, navigasi, permission check backend, atau konfirmasi bisnis.
- Parent memakai API Facade dan `useConfirmDialog()` untuk aksi terkait.

Override total:

```vue
<template #cell(action)="{ row }">
  <Button @click="handleView(row)">Lihat</Button>
</template>
```

## 13. Loading, empty, refetch, dan error state

- Header tabel selalu tetap terlihat.
- Initial loading tanpa rows: body full-width berisi spinner dan teks **Memuat data…**.
- Empty biasa: **Belum ada data.**
- Empty setelah search/filter: **Data tidak ditemukan.**
- Initial error: body full-width berisi ikon, pesan aman, dan tombol retry jika retryable.
- Refetch dengan rows lama: rows tetap terlihat; spinner kecil tampil pada tombol refresh.
- Kembali dari detail: cached rows langsung tampil lalu refetch di background.
- Refetch gagal: cached rows tetap terlihat dan kegagalan pembaruan diinformasikan tanpa mengganti body.

## 14. Remember state

`remember` selalu memakai key eksplisit:

```vue
<DataTable remember="settings-users-table" />
```

- Tanpa `remember`, state tidak dipersist.
- Penyimpanan memakai `sessionStorage`.
- Scope route otomatis berasal dari parent route record pada `route.matched`.
- State bertahan di sibling/child route dalam parent yang sama.
- State dihapus ketika navigasi keluar dari parent route.
- Override scope dapat disediakan untuk struktur route khusus.
- Logout flow wajib memanggil `clearDataTableMemory()` karena `sessionStorage` tidak otomatis bersih saat
  logout.

State yang disimpan:

- page dan per-page;
- submitted search dan `search_fields`;
- filter aktif;
- sort;
- column width hasil resize;
- selection yang masih valid;
- tree `expandedKeys`;
- current server page rows/meta untuk stale-while-revalidate; items lokal tidak diduplikasi ke storage.

Detail expand accordion biasa tidak disimpan.

## 15. Row/cell interaction dan styling

Events:

```ts
row - click(row, column, event);
row - dblclick(row, column, event);
row - contextmenu(row, column, event);
```

- DataTable hanya emit; parent menangani navigasi/detail/menu.
- Browser context menu hanya dicegah jika listener context menu dipasang.

Conditional class:

```ts
rowClass?: (row, rowIndex) => string
cellClass?: (row, column, rowIndex) => string
```

Class callback digabung dengan class internal untuk selected, disabled, hover, sticky, dan state lain.

## 16. Tree dan expand

Tree adalah fitur advanced dan opt-in.

```vue
<DataTable tree />

<DataTable :tree="{ children: 'subordinates', defaultExpandAll: true }" />

<DataTable :tree="{ lazy: true, hasChildren: 'has_children' }" :load-children="loadChildren" />
```

Aturan:

- default children key adalah `children`;
- children key dapat dioverride;
- `rowKey` wajib unik untuk seluruh node;
- eager nested tree dan lazy tree didukung;
- `defaultExpandAll` membuka seluruh eager tree secara default;
- `v-model:expandedKeys` mengontrol node terbuka;
- tidak ada tombol bawaan expand/collapse;
- exposes `expandAll()` dan `collapseAll()`;
- lazy `expandAll()` hanya membuka node yang sudah dimuat;
- expanded keys disimpan ketika `remember` aktif;
- response server/lazy menjadi source of truth; DataTable tidak merekonstruksi hierarchy server.

Tree search lokal:

- child match mempertahankan seluruh ancestor;
- sibling yang tidak cocok disembunyikan;
- jalur hasil otomatis di-expand;
- frasa match di-highlight pada default cell;
- custom cell menerima search/helper highlight;
- highlight search hanya aktif pada tree;
- server/lazy search ditampilkan sesuai hierarchy yang dikembalikan server.

Expand detail biasa berbeda dari tree expand:

- diaktifkan oleh slot `#expand`;
- menggunakan accordion;
- ditutup ketika page, search, filter, atau data berubah;
- tidak dipersist.

## 17. Props ringkas

Nama final dapat disesuaikan saat typing implementation, tetapi kapabilitas berikut sudah disepakati:

```ts
interface DataTableProps<T> {
  items?: T[];
  fetcher?: DataTableFetcher<T>;
  queryKey?: string | readonly unknown[];
  enabled?: boolean;
  extraParams?: Record<string, unknown>;

  fields: DataTableField<T>[];
  filters?: DataTableFilter[];
  title?: string;
  rowKey?: keyof T | ((row: T) => string | number);

  paginated?: boolean;
  perPage?: number;
  perPageOptions?: number[];
  showPagination?: boolean;
  showPerPage?: boolean;

  showSearch?: boolean;
  showFilter?: boolean;
  showRefresh?: boolean;

  selection?: boolean | 'multiple';
  rowSelectable?: (row: T) => boolean;

  actions?: boolean;
  canEdit?: (row: T) => boolean;
  canDelete?: (row: T) => boolean;

  remember?: string;
  rememberScope?: string;

  height?: string | number;
  maxHeight?: string | number;
  rowClass?: (row: T, rowIndex: number) => string;
  cellClass?: (row: T, column: DataTableField<T>, rowIndex: number) => string;
  spanMethod?: SpanMethod<T>;

  tree?:
    | boolean
    | {
        children?: string;
        hasChildren?: string;
        lazy?: boolean;
        defaultExpandAll?: boolean;
      };
  loadChildren?: (row: T, signal: AbortSignal) => Promise<T[]>;
}
```

`filters`, `selected`, dan `expandedKeys` mendukung `v-model` sesuai bagian masing-masing.

## 18. Events

```ts
loading(isLoading: boolean)
loaded({ rows, meta, message })
error({ error, code, retryable, validationErrors, requestId })
params-change({ page, perPage, search, searchFields, sort, filters, extraParams })

edit(row)
delete(row)

row-click(row, column, event)
row-dblclick(row, column, event)
row-contextmenu(row, column, event)
```

`params-change` hanya dipancarkan setelah state final/aktif berubah, bukan pada search/filter draft.

## 19. Exposed methods

```ts
refresh(): Promise<void>
resetFilters(): Promise<void>
clearSelection(): void
scrollToTop(): void
expandAll(): void
collapseAll(): void
```

Global:

```ts
clearDataTableMemory(): void
```

## 20. Accessibility

Tidak boleh disederhanakan:

- radio/checkbox memiliki accessible label;
- sorting dapat dioperasikan keyboard dan memakai `aria-sort`;
- tooltip tidak menjadi satu-satunya sumber informasi;
- Sheet filter memiliki title;
- focus kembali ke trigger setelah Sheet ditutup;
- loading/error/empty state diumumkan secara wajar tanpa spam saat background refetch;
- sticky/control columns mempertahankan urutan keyboard yang logis;
- click-only behavior mempunyai keyboard equivalent jika merupakan aksi.

## 21. Fase implementasi

### Fase 1 — Core

- mode lokal/server;
- TanStack Query + fetcher/API Facade boundary;
- fields, nested path, formatter, header/cell/title/toolbar slots;
- search dan pemilihan `search_fields`;
- advanced filter + draft/apply/reset;
- single/multi-sort;
- pagination/per-page;
- single/multiple selection;
- default/custom action;
- loading/empty/error/refetch;
- remember/session storage;
- fixed/resizable columns, horizontal scroll, height/maxHeight;
- row/cell events dan class callbacks;
- responsive shadcn-vue skin.

### Fase 2 — Advanced

- eager/lazy tree;
- local tree search + ancestor preservation + highlight;
- tree expand state;
- detail expand accordion;
- recursive grouped header;
- `spanMethod`.

## 22. Demo dan acceptance

Demo menggunakan data pengguna seperti referensi karena mencakup:

- nested object/array;
- hidden searchable fields;
- custom header dan cell;
- action;
- selection;
- local pagination;
- server-like Laravel response.

Sediakan dua demo:

1. mode lokal dari fixture array;
2. mode server dari mock API Facade/fetcher yang mengembalikan `data`, `meta`, dan `message`.

Minimum automated checks:

- test util local search nested, single phrase, pagination, rownum, sort, dan tree filtering;
- test query params/reset page serta selection persistence/clearing;
- test response/error normalization sesuai Error Definition Framework;
- component test untuk slot/events/state;
- Playwright screenshot baseline untuk light/dark, loading, populated, empty, error, filter Sheet, sticky,
  dan selection;
- satu baseline viewport desktop menjadi syarat klaim pixel-perfect; viewport/browser lain ditambah jika
  menjadi target resmi.

## 23. Fitur yang sengaja ditunda

- card renderer/mobile-specific layout;
- virtual scrolling;
- bulk-action toolbar;
- summary/footer aggregation;
- user-controlled column visibility/reordering;
- click-to-expand ellipsis cell;
- i18n;
- tree selection cascade;
- select-all seluruh hasil server lintas halaman.

Tambahkan hanya ketika ada use case nyata.
