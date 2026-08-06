import { useInfiniteQuery } from '@tanstack/vue-query';
import { ProfileFacade } from '../api/profile.facade';

export function useProfileActivityLogsQuery(perPage = 15) {
  return useInfiniteQuery({
    queryKey: ['profile', 'activity-logs', { perPage }],
    queryFn: async ({ pageParam = 1, signal }) => {
      return ProfileFacade.getActivityLogs({ page: pageParam, per_page: perPage }, signal);
    },
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      const currentPage = lastPage.meta?.current_page ?? 1;
      const lastPageNum = lastPage.meta?.last_page ?? 1;
      return currentPage < lastPageNum ? currentPage + 1 : undefined;
    },
    staleTime: 1000 * 30, // 30 seconds
  });
}
