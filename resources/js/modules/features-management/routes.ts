import type { RouteRecordRaw } from 'vue-router';
import { ListTree } from '@lucide/vue';

export const featureRoutes: RouteRecordRaw[] = [
  {
    path: '/settings/features',
    name: 'features.home',
    component: () => import('./pages/FeatureListPage.vue'),
    meta: {
      title: 'Manajemen Fitur',
      subtitle: 'Kelola daftar fitur dan struktur menu aplikasi.',
      icon: ListTree,
      breadcrumbs: [
        { label: 'Beranda', route: 'home' },
        { label: 'Manajemen Fitur', route: null },
      ],
    },
  },
];
