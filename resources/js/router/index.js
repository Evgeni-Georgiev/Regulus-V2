import { createRouter, createWebHistory } from 'vue-router';
import Coins from '@comps/Coins/Index.vue'; // Your Coins component
import Portfolio from '@comps/Portfolio/Portfolio.vue'; // Your Coins component

const routes = [
    // { path: '/', name: 'Home', component: () => import('@/views/Home.vue') },
    { path: '/coins', name: 'Coins', component: Coins },
    { path: '/portfolios', name: 'Portfolio', component: Portfolio },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
