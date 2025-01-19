import './bootstrap';
import '@css/app.css';

import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

createApp(App)
    .use(router)
    .mount('#app');

// import { createRouter, createWebHistory } from 'vue-router';
// import App from './App.vue';
// import Coins from '@comps/Coins/Index.vue';
// import axios from 'axios';
// import echo from 'echo';

// Create router
// const router = createRouter({
//     history: createWebHistory(),
//     routes: [
//         {
//             path: '/coins',
//             name: 'coins.index',
//             component: Coins
//         },
//         // Add other routes as needed
//     ]
// });
//
// // Axios defaults
// axios.defaults.baseURL = '/api';
// axios.defaults.withCredentials = true;
//
// const app = createApp(App);
// app.use(router);
// app.mount('#app');
//
// // axios.interceptors.request.use(config => {
// //     const token = localStorage.getItem('token');
// //     if (token) {
// //         config.headers.Authorization = `Bearer ${token}`;
// //     }
// //     return config;
// // });
// //
// // axios.interceptors.response.use(
// //     response => response,
// //     error => {
// //         if (error.response.status === 401) {
// //             localStorage.removeItem('token');
// //             router.push({ name: 'Login' });
// //         }
// //         return Promise.reject(error);
// //     }
// // );

