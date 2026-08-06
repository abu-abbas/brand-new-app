import { createRouter, createWebHistory } from 'vue-router';
import { authRoutes } from '@/modules/auth/routes';
import { homeRoutes } from '@/modules/home/routes';
import { exampleRoutes } from '@/modules/example/routes';
import { announcementRoutes } from '@/modules/announcement/routes';
import { blankPageRoutes } from '@/modules/blank-page/routes';
import { featureRoutes } from '@/modules/features-management/routes';
import { roleRoutes } from '@/modules/roles-management/routes';
import { userRoutes } from '@/modules/user-management/routes';
import { profileRoutes } from '@/modules/profile/routes';
import { errorRoutes } from '@/modules/errors/routes';
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
    ...roleRoutes,
    ...userRoutes,
    ...profileRoutes,
    ...errorRoutes,
  ],
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  await auth.restore();

  if (to.meta.allowAnonymous) {
    return true;
  }

  if (to.meta.public) {
    if (!auth.isAuthenticated) {
      return true;
    }
    if (auth.user?.must_change_password) {
      return { name: 'auth.change-password' };
    }
    if (auth.requiresGroupSelection) {
      return { name: 'auth.select-group' };
    }
    return { name: 'home' };
  }

  if (!auth.isAuthenticated) {
    return { name: 'auth.login', query: { redirect: to.fullPath } };
  }

  // Pengguna terautentikasi:
  // 0. Cek kedaluwarsa password
  if (auth.user?.must_change_password) {
    if (to.name === 'auth.change-password') {
      return true;
    }
    return { name: 'auth.change-password' };
  }

  // 1. Cek pemilihan group
  if (auth.requiresGroupSelection) {
    // KUNCI ANTI-LOOP: Jika halaman tujuan adalah 'auth.select-group', biarkan lewat!
    if (to.name === 'auth.select-group') {
      return true;
    }
    return {
      name: 'auth.select-group',
      query: to.fullPath !== '/home' && to.fullPath !== '/' ? { redirect: to.fullPath } : undefined,
    };
  }

  // 2. Jika pengguna SUDAH memiliki active_group_id (requiresGroupSelection === false)
  // dan mencoba membuka halaman /select-group secara langsung (bukan mode switch)
  if (
    to.name === 'auth.select-group' &&
    !auth.requiresGroupSelection &&
    to.query.switch !== 'true'
  ) {
    return { name: 'home' };
  }

  return true;
});

router.afterEach((to) => {
  const appName = window.__APP_CONFIG__?.name || import.meta.env.VITE_APP_NAME;
  const title = to.meta.title;

  document.title = title ? `${title} | ${appName}` : appName;
});

export default router;
