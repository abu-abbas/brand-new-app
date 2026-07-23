import { createRouter, createWebHistory } from 'vue-router';
import { exampleCustomComponentRoutes } from '@/modules/example-custom-component/routes';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: '/example-custom-component/data-table',
    },
    ...exampleCustomComponentRoutes,
  ],
});

export default router;
