import {
  profileActivityLogs,
  type ProfileActivityLogsParams,
  type ProfileActivityLogs200,
} from '@/api/generated/api';

export class ProfileFacade {
  static async getActivityLogs(
    params?: ProfileActivityLogsParams,
    signal?: AbortSignal,
  ): Promise<ProfileActivityLogs200> {
    return profileActivityLogs(params, { signal });
  }
}
