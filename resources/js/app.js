import './bootstrap';
import '@css/app.css';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useAuthStore } from '@/stores/auth';

const initDarkMode = () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        document.documentElement.classList.remove('dark');
    } else {
        // Default to dark mode
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
};

initDarkMode();

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

// Initialize auth store and check authentication status
const authStore = useAuthStore();
authStore.initializeAuth();

app.mount('#app');

