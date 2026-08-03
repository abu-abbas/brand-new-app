import {
  featuresIndex,
  rolesDestroy,
  rolesIndex,
  rolesRestore,
  rolesShow,
  rolesStore,
  rolesUpdate,
  type FeatureResource,
  type RoleResource,
  type RolesIndexSearchFieldsItem,
} from '@/api/generated/api';
import type { DateRangeValue } from '@/components/custom-ui/date-picker/DatePicker.vue';

export type RolesIndexSortBy =
  'code' | 'name' | 'need_region' | 'need_unit' | 'locked' | 'updated_at' | 'deleted_at';

export interface RoleFeatureItem {
  alias: string;
  name: string;
}

export interface RoleRow {
  id: string;
  code: string;
  name: string;
  level?: number;
  need_region: boolean;
  need_unit: boolean;
  active_periode?: DateRangeValue | null;
  locked: boolean;
  can_edit?: boolean;
  can_delete?: boolean;
  features: RoleFeatureItem[];
  feature_aliases: string[];
  updated_at?: string | null;
  deleted_at?: string | null;
}

export interface StoreRolePayload {
  code: string;
  name: string;
  level?: number;
  need_region?: boolean;
  need_unit?: boolean;
  active_periode?: DateRangeValue | null;
  features: string[];
}

export interface PermissionTreeNode {
  id: string;
  label: string;
  code?: string;
  children?: PermissionTreeNode[];
}

export interface RoleListParams {
  search?: string;
  search_fields?: string[];
  sort_by?: string;
  sort_direction?: 'asc' | 'desc';
  page?: number;
  per_page?: number;
  include_deleted?: boolean;
  updated_at_from?: string;
  updated_at_to?: string;
}

export class RolesFacade {
  public static async list(params?: RoleListParams): Promise<{
    data: RoleRow[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
  }> {
    const response = await rolesIndex({
      search: params?.search,
      'search_fields[]': params?.search_fields as RolesIndexSearchFieldsItem[] | undefined,
      sort_by: params?.sort_by as RolesIndexSortBy | undefined,
      sort_direction: params?.sort_direction,
      page: params?.page,
      per_page: params?.per_page,
      include_deleted: params?.include_deleted ? 'true' : 'false',
      updated_at_from: params?.updated_at_from,
      updated_at_to: params?.updated_at_to,
    });

    const items = response.data || [];
    const meta = (response as unknown as { meta?: Record<string, number> }).meta || {};

    const mappedData: RoleRow[] = items.map((item: RoleResource) =>
      RolesFacade.mapResourceToRow(item),
    );

    return {
      data: mappedData,
      total: meta.total ?? mappedData.length,
      current_page: meta.current_page ?? 1,
      last_page: meta.last_page ?? 1,
      per_page: meta.per_page ?? mappedData.length,
    };
  }

  public static async find(id: string): Promise<RoleRow | null> {
    try {
      const response = await rolesShow(id as unknown as number);
      return response.data ? RolesFacade.mapResourceToRow(response.data) : null;
    } catch {
      return null;
    }
  }

  public static async create(payload: StoreRolePayload): Promise<RoleRow> {
    const response = await rolesStore({
      code: payload.code,
      name: payload.name,
      level: payload.level,
      need_region: payload.need_region ?? false,
      need_unit: payload.need_unit ?? false,
      active_periode: RolesFacade.serializeActivePeriode(payload.active_periode),
      features: payload.features,
    });

    return RolesFacade.mapResourceToRow(response.data);
  }

  public static async update(id: string, payload: StoreRolePayload): Promise<RoleRow> {
    const response = await rolesUpdate(id as unknown as number, {
      code: payload.code,
      name: payload.name,
      level: payload.level,
      need_region: payload.need_region ?? false,
      need_unit: payload.need_unit ?? false,
      active_periode: RolesFacade.serializeActivePeriode(payload.active_periode),
      features: payload.features,
    });

    return RolesFacade.mapResourceToRow(response.data);
  }

  public static async delete(id: string): Promise<void> {
    await rolesDestroy(id as unknown as number);
  }

  public static async restore(id: string): Promise<RoleRow> {
    const response = await rolesRestore(id as unknown as number);
    return RolesFacade.mapResourceToRow(response.data);
  }

  /**
   * Mengambil daftar fitur dari backend dan menyusunnya menjadi tree hirarkis permission
   */
  public static async getPermissionTree(): Promise<PermissionTreeNode[]> {
    try {
      const response = await featuresIndex({ per_page: 100 });
      const features: FeatureResource[] = response.data || [];

      return RolesFacade.buildPermissionTree(features);
    } catch {
      return [];
    }
  }

  /**
   * Mengambil mapping alias fitur ke nama fitur (human readable)
   */
  public static async getFeatureMap(): Promise<Record<string, string>> {
    try {
      const response = await featuresIndex({ per_page: 100 });
      const features: FeatureResource[] = response.data || [];
      const map: Record<string, string> = {};
      for (const feat of features) {
        if (feat.alias && feat.name) {
          map[feat.alias] = feat.name;
        }
      }
      return map;
    } catch {
      return {};
    }
  }

  private static mapResourceToRow(item: RoleResource): RoleRow {
    const rawFeatures = (item.features || []) as unknown as Array<
      { alias?: string; name?: string } | string
    >;
    const featureItems: RoleFeatureItem[] = rawFeatures.map((f) => {
      if (typeof f === 'object' && f !== null) {
        return { alias: f.alias || '', name: f.name || f.alias || '' };
      }
      return { alias: String(f), name: String(f) };
    });

    return {
      id: item.id,
      code: item.code,
      name: item.name,
      level: item.level,
      need_region: item.need_region,
      need_unit: item.need_unit,
      active_periode: RolesFacade.parseActivePeriode(item.active_periode),
      locked: item.locked,
      can_edit: (item as unknown as { can_edit?: boolean }).can_edit ?? true,
      can_delete: (item as unknown as { can_delete?: boolean }).can_delete ?? false,
      features: featureItems,
      feature_aliases: featureItems.map((f) => f.alias),
      updated_at: item.updated_at,
      deleted_at: (item as unknown as { deleted_at?: string | null }).deleted_at ?? null,
    };
  }

  private static parseActivePeriode(raw: unknown): DateRangeValue | null {
    if (!raw) return null;
    if (typeof raw === 'object' && raw !== null && 'start' in raw && 'end' in raw) {
      const obj = raw as { start?: unknown; end?: unknown };
      return {
        start: RolesFacade.formatDateString(obj.start),
        end: RolesFacade.formatDateString(obj.end),
      };
    }
    if (Array.isArray(raw) && raw.length >= 2) {
      return {
        start: RolesFacade.formatDateString(raw[0]),
        end: RolesFacade.formatDateString(raw[1]),
      };
    }
    return null;
  }

  private static formatDateString(val: unknown): string | null {
    if (!val) return null;
    if (typeof val === 'string') return val;
    if (typeof val === 'object' && val !== null && 'toISOString' in val) {
      return (val as Date).toISOString().split('T')[0];
    }
    return String(val);
  }

  private static serializeActivePeriode(range?: DateRangeValue | null): string[] | null {
    if (!range || (!range.start && !range.end)) return null;
    const startStr = RolesFacade.formatDateString(range.start) ?? '';
    const endStr = RolesFacade.formatDateString(range.end) ?? '';
    return [startStr, endStr];
  }

  private static buildPermissionTree(features: FeatureResource[]): PermissionTreeNode[] {
    const nodeMap = new Map<string, PermissionTreeNode>();
    const rootNodes: PermissionTreeNode[] = [];

    // First pass: buat node awal
    for (const feat of features) {
      const node: PermissionTreeNode = {
        id: feat.alias,
        label: feat.name,
        code: feat.alias,
        children: [],
      };
      nodeMap.set(feat.alias, node);
    }

    // Second pass: hubungkan parent dan children
    for (const feat of features) {
      const node = nodeMap.get(feat.alias);
      if (!node) continue;

      if (feat.parent && nodeMap.has(feat.parent)) {
        const parentNode = nodeMap.get(feat.parent)!;
        parentNode.children = parentNode.children || [];
        parentNode.children.push(node);
      } else {
        rootNodes.push(node);
      }
    }

    // Cleaning: hapus array children yang kosong
    function cleanChildren(nodes: PermissionTreeNode[]): PermissionTreeNode[] {
      return nodes.map((n) => {
        if (n.children && n.children.length > 0) {
          return { ...n, children: cleanChildren(n.children) };
        }
        return {
          id: n.id,
          label: n.label,
          code: n.code,
        };
      });
    }

    return cleanChildren(rootNodes);
  }
}
