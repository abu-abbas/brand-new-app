export const ROLE_PERMISSIONS = {
  VIEW: 'roles.view',
  CREATE: 'roles.create',
  EDIT: 'roles.edit',
  DELETE: 'roles.delete',
} as const;

export type RolePermissionKey = (typeof ROLE_PERMISSIONS)[keyof typeof ROLE_PERMISSIONS];
