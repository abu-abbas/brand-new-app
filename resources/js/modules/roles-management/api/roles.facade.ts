import { type DateRangeValue } from '@/components/custom-ui/date-picker/DatePicker.vue';

export interface RoleRow {
  [key: string]: unknown;
  id?: string;
  code: string;
  name: string;
  region?: boolean | string;
  regional_device?: boolean | string;
  permissions: string[];
  active_date_range?: DateRangeValue;
  user_count: number;
  created_at: string;
  updated_at: string;
}

export interface StoreRoleRequest {
  code: string;
  name: string;
  region?: boolean;
  regional_device?: boolean;
  permissions: string[];
  active_date_range?: DateRangeValue;
}

export interface PermissionTreeNode {
  id: string;
  label: string;
  code?: string;
  children?: PermissionTreeNode[];
}

// Mock initial roles data
const mockRoles: RoleRow[] = [
  {
    id: 'role-1',
    code: 'ADMIN_UTAMA',
    name: 'Administrator Utama',
    region: '3201',
    regional_device: 'BAPRENDA',
    permissions: [
      'home',
      'user.view',
      'user.create',
      'user.edit',
      'user.delete',
      'roles.view',
      'roles.create',
    ],
    user_count: 5,
    created_at: '2026-07-01 08:00:00',
    updated_at: '2026-07-28 10:30:00',
  },
  {
    id: 'role-2',
    code: 'OPERATOR_WILAYAH',
    name: 'Operator Wilayah',
    region: '3201',
    permissions: ['home', 'input-kinerja'],
    user_count: 12,
    created_at: '2026-07-10 09:15:00',
    updated_at: '2026-07-29 14:20:00',
  },
];

export class RolesFacade {
  public static async list(): Promise<{ data: RoleRow[]; total: number }> {
    return Promise.resolve({
      data: [...mockRoles],
      total: mockRoles.length,
    });
  }

  public static async find(code: string): Promise<RoleRow | null> {
    const role = mockRoles.find((r) => r.code === code);
    return Promise.resolve(role ? { ...role } : null);
  }

  public static async create(data: StoreRoleRequest): Promise<RoleRow> {
    const newRole: RoleRow = {
      id: `role-${Date.now()}`,
      ...data,
      permissions: data.permissions || [],
      user_count: 0,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };
    mockRoles.unshift(newRole);
    return Promise.resolve(newRole);
  }

  public static async update(code: string, data: StoreRoleRequest): Promise<RoleRow> {
    const index = mockRoles.findIndex((r) => r.code === code);
    if (index !== -1) {
      mockRoles[index] = {
        ...mockRoles[index],
        ...data,
        updated_at: new Date().toISOString(),
      };
      return Promise.resolve(mockRoles[index]);
    }
    throw new Error('Group / Role tidak ditemukan');
  }

  public static async delete(code: string): Promise<void> {
    const index = mockRoles.findIndex((r) => r.code === code);
    if (index !== -1) {
      mockRoles.splice(index, 1);
    }
    return Promise.resolve();
  }
}
