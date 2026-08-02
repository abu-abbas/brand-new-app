import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { usePermissionStore } from '@/stores/permission';

/**
 * Navigation guard helper untuk memproteksi akses route berdasarkan permission key.
 *
 * @param permission Key permission yang dipersyaratkan (misal: FEATURE_PERMISSIONS.VIEW)
 * @returns NavigationGuard function untuk Vue Router `beforeEnter`
 *
 * @example
 * beforeEnter: userCan(FEATURE_PERMISSIONS.VIEW)
 */
export function userCan(permission: string) {
  return async (
    to: RouteLocationNormalized,
    _from: RouteLocationNormalized,
    next: NavigationGuardNext,
  ) => {
    const auth = useAuthStore();
    const permissionStore = usePermissionStore();
    await auth.restore();

    if (!auth.isAuthenticated) {
      next({ name: 'auth.login', query: { redirect: to.fullPath } });
      return;
    }

    if (permissionStore.can(permission)) {
      next();
    } else {
      next({ name: 'forbidden' });
    }
  };
}
