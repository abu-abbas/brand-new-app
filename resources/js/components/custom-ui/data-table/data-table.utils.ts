import type {
  DataTableError,
  DataTableField,
  DataTableParams,
  DataTableSort,
} from './data-table.types';

export function getPath(value: unknown, path: string): unknown {
  return path.split('.').reduce<unknown>((current, key) => {
    if (current === null || typeof current !== 'object') return undefined;
    return (current as Record<string, unknown>)[key];
  }, value);
}

function searchable(value: unknown): string {
  if (value == null) return '';
  if (Array.isArray(value)) return value.map(searchable).join(' ');
  if (typeof value === 'object') return Object.values(value).map(searchable).join(' ');
  return String(value);
}

export function searchRows<T>(
  rows: T[],
  search: string,
  fields: DataTableField<T>[],
  searchFields?: string[],
): T[] {
  const phrase = search.trim().toLocaleLowerCase();
  if (!phrase) return rows;
  const keys = searchFields?.length
    ? searchFields
    : fields.filter((field) => field.filterColumn !== false).map((field) => field.key);
  return rows.filter((row) =>
    keys.some((key) => searchable(getPath(row, key)).toLocaleLowerCase().includes(phrase)),
  );
}

export function filterRows<T>(
  rows: T[],
  filters: Record<string, unknown>,
  definitions: Array<{ key: string; serialize?: (value: unknown) => unknown }>,
): T[] {
  const active = Object.entries(filters).filter(([, value]) =>
    Array.isArray(value) ? value.length : value !== '' && value != null,
  );
  if (!active.length) return rows;
  return rows.filter((row) =>
    active.every(([key, expected]) => {
      const definition = definitions.find((filter) => filter.key === key);
      const actual = definition?.serialize
        ? definition.serialize(getPath(row, key))
        : getPath(row, key);
      if (Array.isArray(expected)) return expected.includes(actual);
      if (typeof expected === 'string' && typeof actual === 'string')
        return actual.toLocaleLowerCase().includes(expected.toLocaleLowerCase());
      return actual === expected;
    }),
  );
}

export function sortRows<T>(rows: T[], sorts: DataTableSort[]): T[] {
  if (!sorts.length) return rows;
  return [...rows].sort((left, right) => {
    for (const sort of sorts) {
      const a = getPath(left, sort.key);
      const b = getPath(right, sort.key);
      const comparison =
        a == null && b == null
          ? 0
          : a == null
            ? -1
            : b == null
              ? 1
              : String(a).localeCompare(String(b), undefined, {
                  numeric: true,
                  sensitivity: 'base',
                });
      if (comparison) return sort.direction === 'asc' ? comparison : -comparison;
    }
    return 0;
  });
}

export function buildParams(
  state: {
    page: number;
    perPage: number;
    paginated: boolean;
    search: string;
    searchFields: string[];
    sorts: DataTableSort[];
    filters: Record<string, unknown>;
  },
  extraParams: Record<string, unknown> = {},
): DataTableParams {
  const protectedKeys = new Set([
    'page',
    'per_page',
    'search',
    'search_fields',
    'sort_by',
    'sort_direction',
    'sort',
    'filters',
  ]);
  const params: DataTableParams = Object.fromEntries(
    Object.entries(extraParams).filter(([key]) => !protectedKeys.has(key)),
  );
  if (state.paginated) Object.assign(params, { page: state.page, per_page: state.perPage });
  if (state.search)
    Object.assign(params, { search: state.search, search_fields: state.searchFields });
  if (Object.keys(state.filters).length) params.filters = state.filters;
  if (state.sorts.length === 1)
    Object.assign(params, {
      sort_by: state.sorts[0].key,
      sort_direction: state.sorts[0].direction,
    });
  else if (state.sorts.length > 1) params.sort = state.sorts;
  return params;
}

export function normalizeError(error: unknown): DataTableError {
  const candidate = error as {
    message?: string;
    code?: string;
    retryable?: boolean;
    errors?: Record<string, unknown>;
    requestId?: string;
    response?: {
      status?: number;
      data?: {
        message?: string;
        code?: string;
        retryable?: boolean;
        errors?: Record<string, unknown>;
      };
      headers?: Record<string, string>;
    };
  };
  const data = candidate?.response?.data ?? candidate;
  return {
    message: data?.message || 'Terjadi kesalahan. Silakan coba lagi.',
    code: data?.code,
    retryable:
      data?.retryable ?? (!candidate?.response?.status || candidate.response.status >= 500),
    validationErrors: data?.errors,
    requestId: candidate?.requestId ?? candidate?.response?.headers?.['x-request-id'],
  };
}

export function clearDataTableMemory(): void {
  if (typeof sessionStorage === 'undefined') return;
  Object.keys(sessionStorage)
    .filter((key) => key.startsWith('datatable:'))
    .forEach((key) => sessionStorage.removeItem(key));
}
