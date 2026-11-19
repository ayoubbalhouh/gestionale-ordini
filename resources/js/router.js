import { createRouter, createWebHistory } from 'vue-router';
import { session } from './store';

const routes = [
    { path: '/login', name: 'login', component: () => import('./pages/Login.vue'), meta: { guest: true } },
    { path: '/', name: 'dashboard', component: () => import('./pages/Dashboard.vue') },
    { path: '/ordini', name: 'orders', component: () => import('./pages/Orders.vue') },
    { path: '/ordini/nuovo', name: 'order-new', component: () => import('./pages/OrderForm.vue') },
    { path: '/ordini/:id', name: 'order', component: () => import('./pages/OrderDetail.vue'), props: true },
    { path: '/ordini/:id/modifica', name: 'order-edit', component: () => import('./pages/OrderForm.vue'), props: true },
    { path: '/prodotti', name: 'products', component: () => import('./pages/Products.vue') },
    { path: '/clienti', name: 'customers', component: () => import('./pages/Customers.vue') },
    { path: '/magazzino', name: 'movements', component: () => import('./pages/Movements.vue') },
    { path: '/utenti', name: 'users', component: () => import('./pages/Users.vue'), meta: { admin: true } },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({ history: createWebHistory(), routes, scrollBehavior: () => ({ top: 0 }) });

router.beforeEach((to) => {
    if (!session.user && !to.meta.guest) return { name: 'login', query: to.fullPath !== '/' ? { redirect: to.fullPath } : {} };
    if (session.user && to.meta.guest) return { name: 'dashboard' };
    if (to.meta.admin && session.user?.role !== 'admin') return { name: 'dashboard' };
});

export default router;
