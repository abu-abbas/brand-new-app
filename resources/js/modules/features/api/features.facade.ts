import {
  featuresDestroy,
  featuresIndex,
  featuresOptions,
  featuresRestore,
  featuresStore,
  featuresUpdate,
  type FeaturesIndex200,
  type FeaturesIndexParams,
  type FeaturesOptions200,
  type FeaturesRestore200,
  type FeaturesStore201,
  type FeaturesUpdate200,
  type StoreFeatureRequest,
} from '@/api/generated/api';

export class FeaturesFacade {
  public static list(
    params?: FeaturesIndexParams,
    signal?: AbortSignal,
  ): Promise<FeaturesIndex200> {
    return featuresIndex(params, { signal });
  }

  public static options(signal?: AbortSignal): Promise<FeaturesOptions200> {
    return featuresOptions({ signal });
  }

  public static create(data: StoreFeatureRequest): Promise<FeaturesStore201> {
    return featuresStore(data);
  }

  public static update(alias: string, data: StoreFeatureRequest): Promise<FeaturesUpdate200> {
    return featuresUpdate(alias, data);
  }

  public static delete(alias: string): Promise<void> {
    return featuresDestroy(alias);
  }

  public static restore(alias: string): Promise<FeaturesRestore200> {
    return featuresRestore(alias);
  }
}
