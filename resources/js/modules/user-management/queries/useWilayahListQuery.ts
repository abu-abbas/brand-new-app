import { useQuery } from '@tanstack/vue-query';
import { userManagementFacade } from '../api/user-management.facade';

export function useWilayahListQuery() {
  return useQuery({
    queryKey: ['references', 'wilayah'],
    queryFn: () => userManagementFacade.getWilayahOptions(),
    staleTime: Infinity,
  });
}
