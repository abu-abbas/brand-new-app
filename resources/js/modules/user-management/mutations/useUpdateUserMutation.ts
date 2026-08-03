import { useMutation, useQueryClient } from '@tanstack/vue-query';
import type { UpdateUserRequest } from '../../../api/generated/api';
import { userManagementFacade } from '../api/user-management.facade';

export function useUpdateUserMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: UpdateUserRequest }) =>
      userManagementFacade.updateUser(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
  });
}
