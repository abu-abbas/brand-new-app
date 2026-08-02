import { defineStore } from 'pinia';
import { computed } from 'vue';
import { useAuthStore } from './auth';

export const usePermissionStore = defineStore('permission', () => {
  const auth = useAuthStore();

  const permissions = computed<string[]>(() => {
    return (auth.user?.permissions as string[] | undefined) ?? [];
  });

  const isRoot = computed<boolean>(() => {
    return Boolean(auth.user?.is_root);
  });

  function can(permission: string): boolean {
    if (!permission) return true;
    if (isRoot.value) return true;
    return permissions.value.includes(permission);
  }

  function canAny(perms: string[]): boolean {
    if (!perms || perms.length === 0) return true;
    if (isRoot.value) return true;
    return perms.some((p) => permissions.value.includes(p));
  }

  return {
    permissions,
    isRoot,
    can,
    canAny,
  };
});
