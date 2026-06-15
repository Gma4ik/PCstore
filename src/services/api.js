import axios from 'axios';
import router from '../router';

const http = axios.create({
  baseURL: 'http://localhost/pc_store/backend/',
  headers: { 'Content-Type': 'application/json' }
});

// Attach JWT from localStorage on every request
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// On 401 from auth endpoints, clear token and redirect to login
http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && (error.config?.url ?? '').includes('auth')) {
      localStorage.removeItem('token');
      router.push('/login');
    }
    return Promise.reject(error);
  }
);

// ─── User-facing API ────────────────────────────────────────────────────────

export const api = {
  auth: {
    register: (name, email, password) =>
      http.post('api/auth/register', { name, email, password }),
    login: (email, password) =>
      http.post('api/auth/login', { email, password }),
    resetPassword: (email, new_password) =>
      http.post('api/auth/reset-password', { email, new_password })
  },

  // Public product browsing
  products: {
    getAll: (category) =>
      http.get('api/products', { params: category ? { category } : {} }),
    getById: (id) =>
      http.get(`api/products/${id}`)
  },

  // Public categories
  categories: {
    getAll: () => http.get('api/categories')
  },

  // User's own orders
  orders: {
    getAll: () => http.get('api/orders'),
    getById: (id) => http.get(`api/orders/${id}`),
    create: (orderData) => http.post('api/orders', orderData)
  },

  // User profile
  users: {
    updateMe: (data) => http.patch('api/users/me', data),
    changePassword: (current_password, new_password) =>
      http.patch('api/users/me', { current_password, new_password })
  },

  // Reviews
  reviews: {
    getAll: (productId) =>
      http.get('api/reviews', { params: { product_id: productId } }),
    create: (data) => http.post('api/reviews', data),
    update: (id, data) => http.put(`api/reviews/${id}`, data),
    delete: (id) => http.delete(`api/reviews/${id}`)
  },

  // Wishlist
  wishlist: {
    getAll: () => http.get('api/wishlist'),
    add: (productId) => http.post('api/wishlist', { product_id: productId }),
    remove: (productId) => http.delete(`api/wishlist/${productId}`)
  },

  // File upload
  upload: {
    image: (file) => {
      const fd = new FormData();
      fd.append('image', file);
      return http.post('api/upload.php', fd, { headers: { 'Content-Type': undefined } });
    }
  }
};

// ─── Admin API ───────────────────────────────────────────────────────────────

export const adminApi = {
  // All orders management
  orders: {
    getAll: () => http.get('api/admin/orders'),
    getById: (id) => http.get(`api/admin/orders/${id}`),
    updateStatus: (id, status) => http.patch(`api/admin/orders/${id}`, { status }),
    updatePaymentStatus: (id, payment_status) => http.patch(`api/admin/orders/${id}`, { payment_status })
  },

  // Users management
  users: {
    getAll: () => http.get('api/admin/users'),
    updateRole: (id, role) => http.patch(`api/admin/users/${id}`, { role })
  },

  // Products management (CRUD)
  products: {
    getAll: (category) =>
      http.get('api/products', { params: category ? { category } : {} }),
    getById: (id) => http.get(`api/products/${id}`),
    create: (data) => http.post('api/products', data),
    update: (id, data) => http.post(`api/products/${id}`, data),
    delete: (id) => http.delete(`api/products/${id}`)
  },

  // Categories management (CRUD)
  categories: {
    getAll: () => http.get('api/categories'),
    create: (data) => http.post('api/categories', data),
    update: (id, data) => http.put(`api/categories/${id}`, data),
    delete: (id) => http.delete(`api/categories/${id}`)
  },

  // Dashboard statistics
  stats: {
    getStats: () => http.get('api/admin/stats')
  },

  // File upload
  upload: {
    image: (file) => {
      const fd = new FormData();
      fd.append('image', file);
      return http.post('api/upload.php', fd, { headers: { 'Content-Type': undefined } });
    }
  }
};
