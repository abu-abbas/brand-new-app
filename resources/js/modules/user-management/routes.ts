import type { RouteRecordRaw } from 'vue-router';
import { Users } from '@lucide/vue';
import { userCan } from '@/shared/guards/user-can';
import { USER_PERMISSIONS } from './permissions';

export const userRoutes: RouteRecordRaw[] = [
  {
    path: '/settings/users',
    name: 'users.home',
    component: () => import('./pages/UserListPage.vue'),
    beforeEnter: userCan(USER_PERMISSIONS.VIEW),
    meta: {
      title: 'Manajemen Pengguna',
      subtitle: 'Kelola data pengguna, status keaktifan, dan penugasan peran aplikasi.',
      icon: Users,
      breadcrumbs: [
        { label: 'Beranda', route: 'home' },
        { label: 'Manajemen Pengguna', route: null },
      ],
    },
  },
];
