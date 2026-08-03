import {
  referencesPerangkatDaerah,
  referencesWilayah,
  usersDestroy,
  usersIndex,
  usersShow,
  usersStore,
  usersTestError,
  usersToggleStatus,
  usersUpdate,
  type StoreUserRequest,
  type UpdateUserRequest,
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
  triggerTestError: (signal?: AbortSignal) => usersTestError(signal ? { signal } : undefined),

  getWilayahMock: () => referencesWilayah(),
  getPerangkatDaerahMock: () => referencesPerangkatDaerah(),
};

export const UserManagementFacade = userManagementFacade;
