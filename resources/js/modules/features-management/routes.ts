import type { RouteRecordRaw } from 'vue-router';
import { ListTree } from '@lucide/vue';
import { userCan } from '@/shared/guards/user-can';
import { FEATURE_PERMISSIONS } from './permissions';

export const featureRoutes: RouteRecordRaw[] = [
  {
    path: '/settings/features',
    name: 'features.home',
    component: () => import('./pages/FeatureListPage.vue'),
    beforeEnter: userCan(FEATURE_PERMISSIONS.VIEW),
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
