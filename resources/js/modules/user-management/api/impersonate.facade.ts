import {
  impersonateLeave,
  usersImpersonate,
  type ImpersonateRequest,
} from '../../../api/generated/api';

export const impersonateFacade = {
  startImpersonate: (userId: string, targetGroupId?: string) =>
    usersImpersonate(userId as unknown as number, {
      target_group_id: targetGroupId,
    }),
  leaveImpersonate: () => impersonateLeave(),
};

export const ImpersonateFacade = impersonateFacade;

export type { ImpersonateRequest };
