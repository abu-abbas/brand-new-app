import {
  referencesPerangkatDaerah,
  referencesWilayah,
  rolesOptions,
  type RoleOptionResource,
  usersDestroy,
  usersIndex,
  usersShow,
  usersSendPasswordLink,
  usersStore,
  usersToggleStatus,
  usersUpdate,
  type StoreUserRequest,
  type UpdateUserRequest,
  type UserRoleResource,
  type UsersIndexParams,
} from '../../../api/generated/api';

export const userManagementFacade = {
  getUsers: (params?: UsersIndexParams, signal?: AbortSignal) =>
    usersIndex(params, signal ? { signal } : undefined),
  getUserDetail: (id: string, signal?: AbortSignal) =>
    usersShow(id as unknown as number, signal ? { signal } : undefined),
  createUser: (data: StoreUserRequest) => usersStore(data),
  updateUser: (id: string, data: UpdateUserRequest) => usersUpdate(id as unknown as number, data),
  deleteUser: (id: string) => usersDestroy(id as unknown as number),
  toggleUserStatus: (id: string) => usersToggleStatus(id as unknown as number),
  sendPasswordLink: (id: string) => usersSendPasswordLink(id as unknown as number),
  getRoleOptions: () => rolesOptions(),
  getWilayahOptions: () => referencesWilayah(),
  getPerangkatDaerahOptions: () => referencesPerangkatDaerah(),
};

export const UserManagementFacade = userManagementFacade;

export type { RoleOptionResource, UserRoleResource };
