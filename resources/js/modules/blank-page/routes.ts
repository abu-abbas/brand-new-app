import type { RouteRecordRaw } from 'vue-router';
import { FileCode2 } from '@lucide/vue';

export const blankPageRoutes: RouteRecordRaw[] = [
  {
    path: '/blank-page',
    name: 'blank-page',
    component: () => import('./pages/BlankPage.vue'),
    meta: {
      title: 'Halaman Kosong',
      subtitle:
        'Gunakan template halaman ini sebagai titik awal untuk membangun fitur atau modul baru.',
      parentTitle: 'Pages',
      icon: FileCode2,
    },
  },
  {
    path: '/blank-page/detail',
    name: 'blank-page.detail',
    component: () => import('./pages/BlankDetailPage.vue'),
    meta: {
      title: 'Detail Halaman Kosong',
      subtitle: 'Halaman ini menggunakan rute meta backUrl dengan named route scope "blank-page".',
      parentTitle: 'Pages',
      backUrl: 'blank-page',
    },
  },
];
