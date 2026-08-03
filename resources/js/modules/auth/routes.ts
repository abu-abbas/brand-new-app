import type { RouteRecordRaw } from 'vue-router';

export const authRoutes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'auth.login',
    component: () => import('./pages/LoginPage.vue'),
    meta: { public: true },
  },
  {
    path: '/select-group',
    name: 'auth.select-group',
    component: () => import('./pages/SelectGroupPage.vue'),
    meta: { title: 'Pilih Group' },
  },
];
