import type { RouteRecordRaw } from 'vue-router';
import { ShieldCheck } from '@lucide/vue';
import { userCan } from '@/shared/guards/user-can';
import { ROLE_PERMISSIONS } from './permissions';

export const roleRoutes: RouteRecordRaw[] = [
  {
    path: '/settings/roles',
    name: 'roles.home',
    component: () => import('./pages/RoleListPage.vue'),
    beforeEnter: userCan(ROLE_PERMISSIONS.VIEW),
    meta: {
      title: 'Manajemen Group',
      subtitle: 'Kelola group pengguna, peran, dan hak akses fitur aplikasi.',
      icon: ShieldCheck,
      breadcrumbs: [
        { label: 'Beranda', route: 'home' },
        { label: 'Manajemen Group', route: null },
      ],
    },
  },
];
