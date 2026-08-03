import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { userManagementFacade } from '../api/user-management.facade';

export function useDeleteUserMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => userManagementFacade.deleteUser(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
  });
}
