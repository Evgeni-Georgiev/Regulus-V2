import './bootstrap';
import '@css/app.css';

import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

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

createApp(App)
    .use(router)
    .mount('#app');

