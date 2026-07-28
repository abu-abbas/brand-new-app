import type { RouteRecordRaw } from 'vue-router';
import HomePage from './pages/HomePage.vue';

export const homeRoutes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: HomePage,
  },
];
