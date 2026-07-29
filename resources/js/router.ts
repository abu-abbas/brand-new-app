import { createRouter, createWebHistory } from 'vue-router';
import { authRoutes } from '@/modules/auth/routes';
import { homeRoutes } from '@/modules/home/routes';
import { exampleRoutes } from '@/modules/example/routes';
import { announcementRoutes } from '@/modules/announcement/routes';
import { blankPageRoutes } from '@/modules/blank-page/routes';
import { featureRoutes } from '@/modules/features/routes';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    ...authRoutes,
    ...homeRoutes,
    ...exampleRoutes,
    ...announcementRoutes,
    ...blankPageRoutes,
    ...featureRoutes,
  ],
});

export default router;
