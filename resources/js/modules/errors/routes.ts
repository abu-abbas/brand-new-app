import type { RouteRecordRaw } from 'vue-router';

export const errorRoutes: RouteRecordRaw[] = [
  {
    path: '/403',
    name: 'forbidden',
    component: () => import('./pages/ForbiddenPage.vue'),
    meta: { allowAnonymous: true, title: 'Akses Ditolak' },
  },
  {
    path: '/404',
    name: 'not-found',
    component: () => import('./pages/NotFoundPage.vue'),
    meta: { allowAnonymous: true, title: 'Halaman Tidak Ditemukan' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found.fallback',
    component: () => import('./pages/NotFoundPage.vue'),
    meta: { allowAnonymous: true, title: 'Halaman Tidak Ditemukan' },
  },
];
