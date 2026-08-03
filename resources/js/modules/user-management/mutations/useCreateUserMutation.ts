import { useMutation, useQueryClient } from '@tanstack/vue-query';
import type { StoreUserRequest } from '../../../api/generated/api';
import { userManagementFacade } from '../api/user-management.facade';

export function useCreateUserMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: StoreUserRequest) => userManagementFacade.createUser(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
  });
}
