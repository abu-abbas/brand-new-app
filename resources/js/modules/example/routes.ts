import type { RouteRecordRaw } from 'vue-router';

export const exampleRoutes: RouteRecordRaw[] = [
  {
    path: '/example-custom-component',
    name: 'example-custom-component',
    component: () => import('./pages/ExamplePage.vue'),
  },
];
