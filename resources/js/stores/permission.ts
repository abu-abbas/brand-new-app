import { defineStore } from 'pinia';
import { computed } from 'vue';
import { useAuthStore } from './auth';

export const usePermissionStore = defineStore('permission', () => {
  const auth = useAuthStore();

  const permissions = computed<string[]>(() => {
    return (auth.user?.permissions as string[] | undefined) ?? [];
  });

  function can(permission: string): boolean {
    if (!permission) return true;
    if (auth.user?.is_root) return true;
    return permissions.value.includes(permission);
  }

  function canAny(perms: string[]): boolean {
    if (!perms || perms.length === 0) return true;
    if (auth.user?.is_root) return true;
    return perms.some((p) => permissions.value.includes(p));
  }

  return {
    permissions,
    can,
    canAny,
  };
});
