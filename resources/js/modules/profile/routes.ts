import type { RouteRecordRaw } from 'vue-router';
import { User } from '@lucide/vue';

export const profileRoutes: RouteRecordRaw[] = [
  {
    path: '/profile',
    name: 'profile',
    component: () => import('./pages/ProfilePage.vue'),
    meta: {
      title: 'Profil Saya',
      subtitle: 'Informasi data diri, log aktivitas, dan pengaturan akun.',
      icon: User,
      breadcrumbs: [{ label: 'Profil Saya', route: null }],
    },
  },
];
