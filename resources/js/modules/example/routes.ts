import type { RouteRecordRaw } from 'vue-router';
import { userCan } from '@/shared/guards/user-can';
import { EXAMPLE_PERMISSIONS } from './permissions';

export const exampleRoutes: RouteRecordRaw[] = [
  {
    path: '/example-custom-component',
    name: 'example-custom-component',
    component: () => import('./pages/ExamplePage.vue'),
    beforeEnter: userCan(EXAMPLE_PERMISSIONS.VIEW),
  },
];
