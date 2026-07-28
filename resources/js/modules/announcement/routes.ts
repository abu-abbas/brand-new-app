import type { RouteRecordRaw } from 'vue-router';
import AnnouncementListPage from './pages/AnnouncementListPage.vue';
import AnnouncementFormPage from './pages/AnnouncementFormPage.vue';

export const announcementRoutes: RouteRecordRaw[] = [
  {
    path: '/announcements',
    name: 'announcement-list',
    component: AnnouncementListPage,
  },
  {
    path: '/announcements/create',
    name: 'announcement-create',
    component: AnnouncementFormPage,
  },
  {
    path: '/announcements/:id/edit',
    name: 'announcement-edit',
    component: AnnouncementFormPage,
  },
];
