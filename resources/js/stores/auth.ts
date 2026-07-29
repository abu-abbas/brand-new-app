import type { LoginRequest, UserResource } from '@/api/generated/api';
import { clearDataTableMemory } from '@/components/custom-ui/data-table';
import { AuthFacade } from '@/modules/auth/api/auth.facade';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<UserResource | null>(null);
  const restored = ref(false);
  const isAuthenticated = computed(() => user.value !== null);

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

  async function logout(): Promise<void> {
    await AuthFacade.logout();
    user.value = null;
    clearDataTableMemory();
  }

  return { user, isAuthenticated, restore, login, logout };
});
