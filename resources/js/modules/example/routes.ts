import type { RouteRecordRaw } from 'vue-router';
import ExamplePage from './pages/ExamplePage.vue';

export const exampleRoutes: RouteRecordRaw[] = [
  {
    path: '/example-custom-component',
    name: 'example-custom-component',
    component: ExamplePage,
  },
];
