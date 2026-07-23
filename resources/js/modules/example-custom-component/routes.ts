import type { RouteRecordRaw } from 'vue-router';

export const exampleCustomComponentRoutes: RouteRecordRaw[] = [
  {
    path: '/example-custom-component',
    component: () => import('./pages/ExampleCustomComponentPage.vue'),
    redirect: '/example-custom-component/data-table',
    children: [
      {
        path: 'data-table',
        name: 'example.data-table',
        component: () => import('@/components/custom-ui/data-table/DataTableExample.vue'),
      },
      {
        path: 'confirm-dialog',
        name: 'example.confirm-dialog',
        component: () => import('@/components/custom-ui/confirm-dialog/ConfirmDialogExample.vue'),
      },
      {
        path: 'combobox',
        name: 'example.combobox',
        component: () => import('@/components/custom-ui/combobox/ComboboxExample.vue'),
      },
      {
        path: 'dashboard',
        name: 'example.dashboard',
        component: () => import('./pages/DashboardOverview.vue'),
      },
    ],
  },
];
