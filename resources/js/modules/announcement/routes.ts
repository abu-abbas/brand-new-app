import type { RouteRecordRaw } from 'vue-router';

export const announcementRoutes: RouteRecordRaw[] = [
  {
    path: '/announcements',
    name: 'announcement-list',
    component: () => import('./pages/AnnouncementListPage.vue'),
  },
  {
    path: '/announcements/create',
    name: 'announcement-create',
    component: () => import('./pages/AnnouncementFormPage.vue'),
  },
  {
    path: '/announcements/:id/edit',
    name: 'announcement-edit',
    component: () => import('./pages/AnnouncementFormPage.vue'),
  },
];
