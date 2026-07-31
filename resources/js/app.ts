import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { VueQueryPlugin } from '@tanstack/vue-query';
import router from './router';

// custom style
import 'element-plus/dist/index.css';
import './../css/form.css';

import App from './App.vue';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia).use(router).use(VueQueryPlugin).mount('#app');
