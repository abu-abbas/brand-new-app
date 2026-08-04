import type { RouteRecordRaw } from 'vue-router';

export const authRoutes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'auth.login',
    component: () => import('./pages/LoginPage.vue'),
    meta: { public: true, title: 'Login' },
  },
  {
    path: '/forgot-password',
    name: 'auth.forgot-password',
    component: () => import('./pages/ForgotPasswordPage.vue'),
    meta: { public: true, title: 'Lupa Password' },
  },
  {
    path: '/reset-password',
    name: 'auth.reset-password',
    component: () => import('./pages/ResetPasswordPage.vue'),
    meta: { allowAnonymous: true, title: 'Reset Password' },
  },
  {
    path: '/change-password',
    name: 'auth.change-password',
    component: () => import('./pages/ChangePasswordPage.vue'),
    meta: { title: 'Ubah Password' },
  },
  {
    path: '/select-group',
    name: 'auth.select-group',
    component: () => import('./pages/SelectGroupPage.vue'),
    meta: { title: 'Pilih Group' },
  },
];
