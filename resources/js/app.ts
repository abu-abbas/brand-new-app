import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { VueQueryPlugin } from '@tanstack/vue-query';
import router from './router';
import App from './App.vue';

// custom style
import 'element-plus/dist/index.css';
import './../css/form.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia).use(router).use(VueQueryPlugin).mount('#app');
