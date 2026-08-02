import type { RouteRecordRaw } from 'vue-router';
import { userCan } from '@/shared/guards/user-can';
import { HOME_PERMISSIONS } from './permissions';

export const homeRoutes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('./pages/HomePage.vue'),
    beforeEnter: userCan(HOME_PERMISSIONS.VIEW),
  },
];
