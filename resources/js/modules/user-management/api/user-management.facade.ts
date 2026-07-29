import {
  usersIndex,
  usersTestError,
  type UsersIndex200,
  type UsersIndexParams,
} from '@/api/generated/api';

export class UserManagementFacade {
  public static async getUsers(
    params?: UsersIndexParams,
    signal?: AbortSignal,
  ): Promise<UsersIndex200> {
    return usersIndex(params, { signal });
  }

  public static async triggerTestError(signal?: AbortSignal): Promise<unknown> {
    return usersTestError({ signal });
  }
}
