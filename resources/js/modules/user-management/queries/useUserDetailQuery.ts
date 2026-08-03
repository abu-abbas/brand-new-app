import { useQuery } from '@tanstack/vue-query';
import { computed, type Ref } from 'vue';
import { userManagementFacade } from '../api/user-management.facade';

export function useUserDetailQuery(id: Ref<string | null>) {
  return useQuery({
    queryKey: computed(() => ['users', id.value]),
    queryFn: () => (id.value ? userManagementFacade.getUserDetail(id.value) : null),
    enabled: computed(() => !!id.value),
  });
}
