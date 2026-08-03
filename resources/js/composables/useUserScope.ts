import { useAuthStore } from '@/stores/auth';
import { computed } from 'vue';

export interface PerangkatDaerahOption {
  code: string;
  name: string;
  spmu?: string;
  sipkd_code?: string;
  [key: string]: unknown;
}

export interface WilayahOption {
  code: string;
  name: string;
  order?: number;
  [key: string]: unknown;
}

export function useUserScope() {
  const authStore = useAuthStore();

  const isRootUser = computed(() => Boolean(authStore.user?.is_root));

  // Roles of current logged in user that have need_unit
  const activeUnitRoles = computed(() => {
    if (isRootUser.value) return [];
    return (authStore.user?.user_roles ?? []).filter((ur) => ur.need_unit);
  });

  // Roles of current logged in user that have need_region
  const activeRegionRoles = computed(() => {
    if (isRootUser.value) return [];
    return (authStore.user?.user_roles ?? []).filter((ur) => ur.need_region);
  });

  const isNeedUnit = computed(() => activeUnitRoles.value.length > 0);
  const isNeedRegion = computed(() => activeRegionRoles.value.length > 0);

  // Logged-in user's assigned unit code
  const currentUserUnitCode = computed(() => {
    const roleUnit = activeUnitRoles.value.find((ur) => ur.unit)?.unit;
    return roleUnit || authStore.user?.unit?.code || undefined;
  });

  // Logged-in user's assigned wilayah codes
  const currentUserWilayahList = computed<string[]>(() => {
    const result: string[] = [];
    activeRegionRoles.value.forEach((ur) => {
      if (ur.wilayah) {
        const split = ur.wilayah
          .split(',')
          .map((w) => w.trim())
          .filter(Boolean);
        result.push(...split);
      }
    });
    return Array.from(new Set(result));
  });

  /**
   * Filter Perangkat Daerah options list based on active user's role scope:
   * - If need_unit -> filter to user's unit code only
   * - If need_region -> filter to PDs matching user's assigned wilayah code prefix
   */
  const filterPdList = <T extends { code: string; name?: string; [key: string]: unknown }>(
    allPd: T[] = [],
  ): T[] => {
    if (isRootUser.value) return allPd;

    if (isNeedUnit.value && currentUserUnitCode.value) {
      return allPd.filter((pd) => pd.code === currentUserUnitCode.value);
    }

    if (isNeedRegion.value && currentUserWilayahList.value.length > 0) {
      const wilList = currentUserWilayahList.value;
      return allPd.filter((pd) => pd.code && wilList.some((wCode) => pd.code.startsWith(wCode)));
    }

    return allPd;
  };

  /**
   * Filter Wilayah options list based on active user's role scope
   */
  const filterWilayahList = <T extends { code: string; name?: string; [key: string]: unknown }>(
    allWilayah: T[] = [],
  ): T[] => {
    if (isRootUser.value) return allWilayah;

    if (isNeedRegion.value && currentUserWilayahList.value.length > 0) {
      const wilList = currentUserWilayahList.value;
      return allWilayah.filter((w) => w.code && wilList.includes(w.code));
    }

    return allWilayah;
  };

  return {
    isRootUser,
    isNeedUnit,
    isNeedRegion,
    currentUserUnitCode,
    currentUserWilayahList,
    filterPdList,
    filterWilayahList,
  };
}
