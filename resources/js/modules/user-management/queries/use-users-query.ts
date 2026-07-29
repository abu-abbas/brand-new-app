import { useQuery } from '@tanstack/vue-query';
import { computed, type Ref } from 'vue';
import type { UsersIndexParams } from '@/api/generated/api';
import { UserManagementFacade } from '../api/user-management.facade';

export function useUsersQuery(paramsRef: Ref<UsersIndexParams>) {
  const queryKey = computed(() => ['users', paramsRef.value]);

  return useQuery({
    queryKey,
    queryFn: ({ signal }) => UserManagementFacade.getUsers(paramsRef.value, signal),
    placeholderData: (previousData) => previousData,
    staleTime: 5000,
  });
}
