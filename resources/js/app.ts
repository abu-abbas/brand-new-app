import { createApp } from 'vue';
import { VueQueryPlugin } from '@tanstack/vue-query';
import router from './app/router';
import 'element-plus/dist/index.css';
import App from './App.vue';

createApp(App).use(router).use(VueQueryPlugin).mount('#app');
