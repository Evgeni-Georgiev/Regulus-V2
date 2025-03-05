import { createRouter, createWebHistory } from 'vue-router';
import Coins from '@comps/pages/Coin/Coin.vue';
import Portfolio from '@comps/pages/Portfolio/Portfolio.vue';
import PortfolioView from "@comps/pages/Portfolio/PortfolioView.vue";
import TransactionView from "@comps/pages/Transaction/TransactionView.vue";

const routes = [
    {
        path: '/',
        name: 'Home',
        component: Coins,
    },
    {
        path: '/coins',
        name: 'Coins',
        component: Coins
    },
    {
        path: '/portfolios',
        name: 'Portfolio',
        component: Portfolio
    },
    {
        path: '/portfolios/:id',
        name: 'PortfolioView',
        component: PortfolioView,
        props: true,
    },
    {
        path: '/portfolio/:portfolioId/coin/:coinId/transactions',
        name: 'TransactionView',
        component: TransactionView,
        props: true
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
