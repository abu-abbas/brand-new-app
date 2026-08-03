import type { RouteRecordRaw } from 'vue-router';
import { Megaphone } from '@lucide/vue';
import { userCan } from '@/shared/guards/user-can';
import { ANNOUNCEMENT_PERMISSIONS } from './permissions';

export const announcementRoutes: RouteRecordRaw[] = [
  {
    path: '/announcements',
    name: 'announcement-list',
    component: () => import('./pages/AnnouncementListPage.vue'),
    beforeEnter: userCan(ANNOUNCEMENT_PERMISSIONS.VIEW),
    meta: {
      title: 'Pengumuman',
      subtitle: 'Kelola informasi dan pengumuman aplikasi.',
      icon: Megaphone,
      breadcrumbs: [
        { label: 'Beranda', route: 'home' },
        { label: 'Pengumuman', route: null },
      ],
    },
  },
  {
    path: '/announcements/create',
    name: 'announcement-create',
    component: () => import('./pages/AnnouncementFormPage.vue'),
    beforeEnter: userCan(ANNOUNCEMENT_PERMISSIONS.VIEW),
    meta: {
      title: 'Tambah Pengumuman',
      subtitle: 'Buat pengumuman baru untuk aplikasi.',
      icon: Megaphone,
      breadcrumbs: [
        { label: 'Beranda', route: 'home' },
        { label: 'Pengumuman', route: 'announcement-list' },
        { label: 'Tambah', route: null },
      ],
    },
  },
  {
    path: '/announcements/:id/edit',
    name: 'announcement-edit',
    component: () => import('./pages/AnnouncementFormPage.vue'),
    beforeEnter: userCan(ANNOUNCEMENT_PERMISSIONS.VIEW),
    meta: {
      title: 'Ubah Pengumuman',
      subtitle: 'Perbarui pengumuman aplikasi.',
      icon: Megaphone,
      breadcrumbs: [
        { label: 'Beranda', route: 'home' },
        { label: 'Pengumuman', route: 'announcement-list' },
        { label: 'Ubah', route: null },
      ],
    },
  },
];
