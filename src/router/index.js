import { createRouter, createWebHistory } from 'vue-router';

// Import page components
import HomePage from '../pages/HomePage.vue';
import ProductsCatalogPage from '../pages/ProductsCatalogPage.vue';
import ProductDetailsPage from '../pages/ProductDetailsPage.vue';
import CartPage from '../pages/CartPage.vue';
import CheckoutPage from '../pages/CheckoutPage.vue';
import LoginPage from '../pages/LoginPage.vue';
import RegisterPage from '../pages/RegisterPage.vue';
import ProfilePage from '../pages/ProfilePage.vue';
import ResetPasswordPage from '../pages/ResetPasswordPage.vue';
import WishlistPage from '../pages/WishlistPage.vue';

// Import admin page components
import AdminLayout from '../components/admin/AdminLayout.vue';
import AdminDashboard from '../pages/admin/AdminDashboard.vue';
import AdminProducts from '../pages/admin/AdminProducts.vue';
import AdminOrders from '../pages/admin/AdminOrders.vue';
import AdminUsers from '../pages/admin/AdminUsers.vue';
import AdminCategories from '../pages/admin/AdminCategories.vue';

const routes = [
  {
    path: '/',
    name: 'Home',
    component: HomePage
  },
  {
    path: '/products',
    name: 'ProductsCatalog',
    component: ProductsCatalogPage
  },
  {
    path: '/product/:id',
    name: 'ProductDetails',
    component: ProductDetailsPage
  },
  {
    path: '/cart',
    name: 'Cart',
    component: CartPage
  },
  {
    path: '/checkout',
    name: 'Checkout',
    component: CheckoutPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginPage
  },
  {
    path: '/register',
    name: 'Register',
    component: RegisterPage
  },
  {
    path: '/reset-password',
    name: 'ResetPassword',
    component: ResetPasswordPage
  },
  {
    path: '/profile',
    name: 'Profile',
    component: ProfilePage,
    meta: { requiresAuth: true }
  },
  {
    path: '/wishlist',
    name: 'Wishlist',
    component: WishlistPage,
    meta: { requiresAuth: true }
  },

  // Admin Routes
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAdmin: true },
    children: [
      {
        path: '',
        name: 'AdminDashboard',
        component: AdminDashboard
      },
      {
        path: 'products',
        name: 'AdminProducts',
        component: AdminProducts
      },
      {
        path: 'orders',
        name: 'AdminOrders',
        component: AdminOrders
      },
      {
        path: 'users',
        name: 'AdminUsers',
        component: AdminUsers
      },
      {
        path: 'categories',
        name: 'AdminCategories',
        component: AdminCategories
      }
    ]
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    // Always scroll to top when navigating
    if (savedPosition) {
      return savedPosition;
    } else {
      return { top: 0 };
    }
  }
});

// Navigation guard for protected routes - using return syntax (no next parameter)
router.beforeEach((to) => {
  const token = localStorage.getItem('token');

  if (to.meta.requiresAdmin) {
    if (!token) return '/login';
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      if (payload.role !== 'admin' && payload.role !== 'superadmin') return '/';
    } catch {
      return '/login';
    }
  }

  if (to.meta.requiresAuth && !token) {
    return '/login';
  }

  return true;
});

export default router;
