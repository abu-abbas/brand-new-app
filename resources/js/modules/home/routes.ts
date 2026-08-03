import type { RouteRecordRaw } from 'vue-router';
import { Home } from '@lucide/vue';
import { userCan } from '@/shared/guards/user-can';
import { HOME_PERMISSIONS } from './permissions';

export const homeRoutes: RouteRecordRaw[] = [
  {
    path: '/',
    alias: '/home',
    name: 'home',
    component: () => import('./pages/HomePage.vue'),
    beforeEnter: userCan(HOME_PERMISSIONS.VIEW),
    meta: {
      title: 'Beranda',
      subtitle: 'Ringkasan aktivitas dan informasi umum portal.',
      icon: Home,
      breadcrumbs: [{ label: 'Beranda', route: null }],
    },
  },
];
