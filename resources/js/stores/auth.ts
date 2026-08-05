import type { LoginRequest, UserResource } from '@/api/generated/api';
import { clearDataTableMemory } from '@/components/custom-ui/data-table';
import { AuthFacade } from '@/modules/auth/api/auth.facade';
import { ImpersonateFacade } from '@/modules/user-management/api/impersonate.facade';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<UserResource | null>(null);
  const restored = ref(false);
  const isAuthenticated = computed(() => user.value !== null);

  const activeGroup = computed(() => user.value?.active_group_id ?? null);

  const isImpersonating = computed(() => Boolean(user.value?.is_impersonating));
  const impersonator = computed(() => user.value?.impersonator ?? null);
  const impersonatedActiveGroup = computed(() => user.value?.impersonated_active_group ?? null);
  const impersonateExpiresAt = computed(() => user.value?.impersonate_expires_at ?? null);

  const requiresGroupSelection = computed(() => {
    if (!user.value) return false;
    const hasMultiple =
      user.value.has_multiple_groups ?? (user.value.roles && user.value.roles.length > 1);
    return Boolean(hasMultiple && !user.value.active_group_id);
  });

  function can(permission: string): boolean {
    return user.value?.permissions?.includes(permission) ?? false;
  }

  async function restore(): Promise<void> {
    if (restored.value) return;

    try {
      user.value = (await AuthFacade.me()).data;
    } catch {
      user.value = null;
    } finally {
      restored.value = true;
    }
  }

  async function login(credentials: LoginRequest): Promise<void> {
    user.value = (await AuthFacade.login(credentials)).data;
    restored.value = true;
  }

  async function setActiveGroup(groupId: string, remember = false): Promise<void> {
    const res = await AuthFacade.setActiveGroup({ group_id: groupId, remember });
    user.value = res.data;

    if (remember && user.value) {
      localStorage.setItem(`app_default_group_id_${user.value.userid}`, groupId);
    }
  }

  async function resetDefaultGroup(): Promise<void> {
    const res = await AuthFacade.resetDefaultGroup();
    user.value = res.data;

    if (user.value) {
      localStorage.removeItem(`app_default_group_id_${user.value.userid}`);
    }
  }

  async function startImpersonate(
    targetUserId: string,
    targetGroupId?: string,
  ): Promise<UserResource> {
    const res = await ImpersonateFacade.startImpersonate(targetUserId, targetGroupId);
    user.value = res.data;
    clearDataTableMemory();
    return res.data;
  }

  async function leaveImpersonate(): Promise<UserResource> {
    const res = await ImpersonateFacade.leaveImpersonate();
    user.value = res.data;
    clearDataTableMemory();
    return res.data;
  }

  async function logout(): Promise<void> {
    await AuthFacade.logout();
    clearLocalSession();
  }

  function clearLocalSession(): void {
    user.value = null;
    clearDataTableMemory();
  }

  return {
    user,
    isAuthenticated,
    activeGroup,
    isImpersonating,
    impersonator,
    impersonatedActiveGroup,
    impersonateExpiresAt,
    requiresGroupSelection,
    can,
    restore,
    login,
    setActiveGroup,
    resetDefaultGroup,
    startImpersonate,
    leaveImpersonate,
    logout,
    clearLocalSession,
  };
});
