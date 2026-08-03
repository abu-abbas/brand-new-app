import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { userManagementFacade } from '../api/user-management.facade';

export function useToggleUserStatusMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => userManagementFacade.toggleUserStatus(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
  });
}
