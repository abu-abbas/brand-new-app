import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import ExampleCustomComponentPage from '@/pages/ExampleCustomComponentPage.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomePage,
    },
    {
      path: '/example-custom-component',
      name: 'example-custom-component',
      component: ExampleCustomComponentPage,
    },
  ],
});

export default router;
