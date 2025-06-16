import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Public pages
import Coins from '@comps/pages/Coin/Coin.vue';

// Protected pages
import Dashboard from '@/components/pages/Dashboard.vue';
import Portfolio from '@comps/pages/Portfolio/Portfolio.vue';
import PortfolioView from "@comps/pages/Portfolio/PortfolioView.vue";
import TransactionView from "@comps/pages/Transaction/TransactionView.vue";

// Authentication pages
import LoginForm from '@/components/auth/LoginForm.vue';
import RegisterForm from '@/components/auth/RegisterForm.vue';
import ForgotPasswordForm from '@/components/auth/ForgotPasswordForm.vue';
import EmailVerificationNotice from '@/components/auth/EmailVerificationNotice.vue';

// Layouts
import AuthLayout from '@/components/layout/AuthLayout.vue';
import AppLayout from '@/components/layout/AppLayout.vue';

const routes = [
    // Public routes (no authentication required)
    {
        path: '/',
        name: 'Home',
        component: AppLayout,
        children: [
            {
                path: '',
                name: 'Coins',
                component: Coins,
                meta: { requiresAuth: false, showAuthButtons: true }
            }
        ]
    },
    {
        path: '/coins',
        name: 'CoinsPage',
        component: AppLayout,
        children: [
            {
                path: '',
                component: Coins,
                meta: { requiresAuth: false, showAuthButtons: true }
            }
        ]
    },

    // Authentication routes
    {
        path: '/auth',
        component: AuthLayout,
        children: [
            {
                path: 'login',
                name: 'Login',
                component: LoginForm,
                meta: { requiresGuest: true }
            },
            {
                path: 'register',
                name: 'Register',
                component: RegisterForm,
                meta: { requiresGuest: true }
            },
            {
                path: 'forgot-password',
                name: 'ForgotPassword',
                component: ForgotPasswordForm,
                meta: { requiresGuest: true }
            },
            {
                path: 'verify-email',
                name: 'VerifyEmail',
                component: EmailVerificationNotice,
                meta: { requiresAuth: true }
            }
        ]
    },

    // Protected routes (authentication required)
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                component: Dashboard
            }
        ]
    },
    {
        path: '/portfolios',
        name: 'Portfolio',
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                component: Portfolio
            },
            {
                path: ':id',
                name: 'PortfolioView',
                component: PortfolioView,
                props: true
            }
        ]
    },
    {
        path: '/portfolio/:portfolioId/coin/:coinId/transactions',
        name: 'TransactionView',
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                component: TransactionView,
                props: true
            }
        ]
    },

    // Catch all route - redirect to login
    {
        path: '/:pathMatch(.*)*',
        redirect: '/auth/login'
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // Wait for auth initialization if needed
    if (!authStore.user && authStore.token) {
        await authStore.fetchUser();
    }

    const isAuthenticated = authStore.isAuthenticated;
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
    const requiresGuest = to.matched.some(record => record.meta.requiresGuest);

    if (requiresAuth && !isAuthenticated) {
        // Redirect to login if authentication is required but user is not authenticated
        next({
            name: 'Login',
            query: { redirect: to.fullPath }
        });
    } else if (requiresGuest && isAuthenticated) {
        // Redirect to dashboard if guest route but user is authenticated
        next({ name: 'Dashboard' });
    } else {
        // Allow navigation
        next();
    }
});

export default router;
