import { createRouter, createWebHistory } from 'vue-router';
import { authRoutes } from '@/modules/auth/routes';
import { homeRoutes } from '@/modules/home/routes';
import { exampleRoutes } from '@/modules/example/routes';
import { announcementRoutes } from '@/modules/announcement/routes';
import { blankPageRoutes } from '@/modules/blank-page/routes';
import { featureRoutes } from '@/modules/features/routes';
import { useAuthStore } from '@/stores/auth';

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

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  await auth.restore();

  if (to.meta.public) {
    return auth.isAuthenticated ? { name: 'home' } : true;
  }

  if (!auth.isAuthenticated) {
    return { name: 'auth.login', query: { redirect: to.fullPath } };
  }

  return true;
});

router.afterEach((to) => {
  const appName = window.__APP_CONFIG__?.name || import.meta.env.VITE_APP_NAME;
  const title = to.meta.title;

  document.title = title ? `${title} | ${appName}` : appName;
});

export default router;
