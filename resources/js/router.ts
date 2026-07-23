import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '@/pages/HomePage.vue';
import ExampleCustomComponentPage from '@/pages/ExampleCustomComponentPage.vue';
import DataTableExample from '@/components/custom-ui/data-table/DataTableExample.vue';
import ConfirmDialogExample from '@/components/custom-ui/confirm-dialog/ConfirmDialogExample.vue';
import ComboboxExample from '@/components/custom-ui/combobox/ComboboxExample.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomePage,
    },
    {
      path: '/example-custom-component',
      component: ExampleCustomComponentPage,
      redirect: '/example-custom-component/data-table',
      children: [
        {
          path: 'data-table',
          name: 'example.data-table',
          component: DataTableExample,
        },
        {
          path: 'confirm-dialog',
          name: 'example.confirm-dialog',
          component: ConfirmDialogExample,
        },
        {
          path: 'combobox',
          name: 'example.combobox',
          component: ComboboxExample,
        },
      ],
    },
  ],
});

export default router;
