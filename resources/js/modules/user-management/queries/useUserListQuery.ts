import { useQuery } from '@tanstack/vue-query';
import { computed, type Ref } from 'vue';
import type { UsersIndexParams } from '../../../api/generated/api';
import { userManagementFacade } from '../api/user-management.facade';

export function useUserListQuery(params: Ref<UsersIndexParams>) {
  return useQuery({
    queryKey: computed(() => ['users', params.value]),
    queryFn: () => userManagementFacade.getUsers(params.value),
  });
}
