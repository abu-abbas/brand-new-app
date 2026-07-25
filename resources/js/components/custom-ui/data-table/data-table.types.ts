export type SortDirection = 'asc' | 'desc';
export type TableAlign = 'left' | 'center' | 'right';
export type CellWrap = 'wrap' | 'nowrap' | 'ellipsis';

export interface DataTableSort {
  key: string;
  direction: SortDirection;
}

export interface DataTableParams {
  page?: number;
  per_page?: number;
  search?: string;
  search_fields?: string[];
  sort_by?: string;
  sort_direction?: SortDirection;
  sort?: DataTableSort[];
  filters?: Record<string, unknown>;
  [key: string]: unknown;
}

export interface DataTableMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  links?: Array<{
    url: string | null;
    label: string;
    page?: number | null;
    active: boolean;
  }>;
  path?: string;
  per_page: number;
  to: number | null;
  total: number;
}

export interface LaravelDataTableResponse<T> {
  data: T[];
  meta?: DataTableMeta;
  message: string;
}

export type DataTableFetcher<T> = (context: {
  params: DataTableParams;
  signal: AbortSignal;
}) => Promise<LaravelDataTableResponse<T>>;

export interface DataTableField<T = Record<string, unknown>> {
  key: string;
  label: string;
  hidden?: boolean;
  filterColumn?: boolean;
  sortable?: boolean;
  sortKey?: string;
  width?: string | number;
  minWidth?: string | number;
  align?: TableAlign;
  headerAlign?: TableAlign;
  wrap?: CellWrap;
  fixed?: 'left' | 'right';
  resizable?: boolean;
  formatter?: (value: unknown, row: T, column: DataTableField<T>) => string | number;
  children?: DataTableField<T>[];
}

export type DataTableSpanMethod<T> = (context: {
  row: T;
  column: DataTableField<T>;
  rowIndex: number;
  columnIndex: number;
}) => [rowspan: number, colspan: number] | { rowspan: number; colspan: number };

export interface DataTableFilter {
  key: string;
  label: string;
  type: 'text' | 'select' | 'multi-select' | 'boolean' | 'date' | 'date-range' | 'custom';
  options?: Array<{ label: string; value: unknown }>;
  serialize?: (value: unknown) => unknown;
}

export interface DataTableError {
  message: string;
  code?: string;
  retryable: boolean;
  validationErrors?: Record<string, unknown>;
  requestId?: string;
  supportId?: string;
  whatsappUrl?: string;
}

export interface DataTableInstance {
  refresh: () => Promise<void>;
  resetFilters: () => Promise<void>;
  clearSelection: () => void;
  scrollToTop: () => void;
  expandAll: () => void;
  collapseAll: () => void;
}
