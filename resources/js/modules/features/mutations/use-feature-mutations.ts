import { useMutation, useQueryClient } from '@tanstack/vue-query';
import type { StoreFeatureRequest } from '@/api/generated/api';
import { FeaturesFacade } from '../api/features.facade';

export function useCreateFeatureMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: StoreFeatureRequest) => FeaturesFacade.create(data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['features'] }),
  });
}

export function useUpdateFeatureMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: StoreFeatureRequest }) =>
      FeaturesFacade.update(id, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['features'] }),
  });
}

export function useDeleteFeatureMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => FeaturesFacade.delete(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['features'] }),
  });
}

export function useRestoreFeatureMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => FeaturesFacade.restore(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['features'] }),
  });
}
