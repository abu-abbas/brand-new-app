import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { VueQueryPlugin } from '@tanstack/vue-query';
import router from './router';
import 'element-plus/dist/index.css';
import App from './App.vue';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia).use(router).use(VueQueryPlugin).mount('#app');
