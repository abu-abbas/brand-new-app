import type { RouteRecordRaw } from 'vue-router';
import { Boxes } from '@lucide/vue';
import { userCan } from '@/shared/guards/user-can';
import { EXAMPLE_PERMISSIONS } from './permissions';

export const exampleRoutes: RouteRecordRaw[] = [
  {
    path: '/example-custom-component',
    name: 'example-custom-component',
    component: () => import('./pages/ExamplePage.vue'),
    beforeEnter: userCan(EXAMPLE_PERMISSIONS.VIEW),
    meta: {
      title: 'Contoh Komponen UI',
      subtitle: 'Panduan dan contoh penggunaan komponen custom UI.',
      icon: Boxes,
      breadcrumbs: [
        { label: 'Beranda', route: 'home' },
        { label: 'Contoh Komponen UI', route: null },
      ],
    },
  },
];
