import { createRouter, createWebHistory } from 'vue-router';
import { authRoutes } from '@/modules/auth/routes';
import { homeRoutes } from '@/modules/home/routes';
import { exampleRoutes } from '@/modules/example/routes';
import { announcementRoutes } from '@/modules/announcement/routes';
import { blankPageRoutes } from '@/modules/blank-page/routes';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    ...authRoutes,
    ...homeRoutes,
    ...exampleRoutes,
    ...announcementRoutes,
    ...blankPageRoutes,
  ],
});

export default router;
