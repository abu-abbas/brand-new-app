import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import { exampleCustomComponentRoutes } from '@/modules/example-custom-component/routes';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomePage,
    },
    ...exampleCustomComponentRoutes,
  ],
});

export default router;
