import type { RouteRecordRaw } from 'vue-router';
import { userCan } from '@/shared/guards/user-can';
import { ANNOUNCEMENT_PERMISSIONS } from './permissions';

export const announcementRoutes: RouteRecordRaw[] = [
  {
    path: '/announcements',
    name: 'announcement-list',
    component: () => import('./pages/AnnouncementListPage.vue'),
    beforeEnter: userCan(ANNOUNCEMENT_PERMISSIONS.VIEW),
  },
  {
    path: '/announcements/create',
    name: 'announcement-create',
    component: () => import('./pages/AnnouncementFormPage.vue'),
    beforeEnter: userCan(ANNOUNCEMENT_PERMISSIONS.VIEW),
  },
  {
    path: '/announcements/:id/edit',
    name: 'announcement-edit',
    component: () => import('./pages/AnnouncementFormPage.vue'),
    beforeEnter: userCan(ANNOUNCEMENT_PERMISSIONS.VIEW),
  },
];
