import { usersIndex, usersTestError } from '@/api/generated/users';
import type { UsersIndex200, UsersIndexParams } from '@/api/generated/models';

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
