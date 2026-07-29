// Jadikan file ini module agar augmentasi bekerja (bukan ambient override)
export {};

declare module 'vue-router' {
  interface RouteMeta {
    title?: string;
    subtitle?: string;
    icon?: import('vue').Component;
    backUrl?: string;
    public?: boolean;
    breadcrumbs?: Array<{ label: string; route: string | null }>;
  }
}
