import { useQuery } from '@tanstack/vue-query';
import { userManagementFacade } from '../api/user-management.facade';

export function usePerangkatDaerahListQuery() {
  return useQuery({
    queryKey: ['references', 'perangkat-daerah'],
    queryFn: () => userManagementFacade.getPerangkatDaerahMock(),
    staleTime: Infinity,
  });
}
