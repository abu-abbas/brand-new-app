import { createApp } from 'vue';
import { VueQueryPlugin } from '@tanstack/vue-query';
import 'element-plus/dist/index.css';
import App from './App.vue';

createApp(App).use(VueQueryPlugin).mount('#app');
