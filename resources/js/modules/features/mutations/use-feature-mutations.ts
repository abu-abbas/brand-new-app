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
    mutationFn: ({ alias, data }: { alias: string; data: StoreFeatureRequest }) =>
      FeaturesFacade.update(alias, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['features'] }),
  });
}

export function useDeleteFeatureMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (alias: string) => FeaturesFacade.delete(alias),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['features'] }),
  });
}

export function useRestoreFeatureMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (alias: string) => FeaturesFacade.restore(alias),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['features'] }),
  });
}
