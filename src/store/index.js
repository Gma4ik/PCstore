import { defineStore } from 'pinia';
import { api } from '../services/api';

// Cart store - manages shopping cart state
export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [],
    savedForLater: []
  }),
  
  getters: {
    // Get total number of items in cart
    totalItems(state) {
      return state.items.reduce((total, item) => total + item.quantity, 0);
    },
    
    // Get total price of all items in cart
    totalPrice(state) {
      return state.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    },
    
    // Check if product is in cart
    isInCart: (state) => (productId) => {
      return state.items.some(item => item.id === productId);
    }
  },
  
  actions: {
    // Add product to cart
    addToCart(product, quantity = 1) {
      const stockQty = product.stock_quantity ?? Infinity;
      const inStock = product.in_stock !== false;

      // Treat as out of stock if in_stock is false regardless of stock_quantity (Req 1.5)
      if (!inStock || stockQty === 0) {
        return { error: 'В наявності є 0 шт. товару' };
      }

      const existingItem = this.items.find(item => item.id === product.id);
      const currentQty = existingItem ? existingItem.quantity : 0;
      const available = stockQty - currentQty;

      // Cannot add more than what's available (Req 1.2)
      if (available <= 0) {
        return { error: `В наявності є ${stockQty} шт. товару` };
      }

      const toAdd = Math.min(quantity, available);

      if (existingItem) {
        existingItem.quantity += toAdd;
      } else {
        this.items.push({ ...product, quantity: toAdd });
      }

      // Save to localStorage
      this.saveCart();

      return { added: toAdd, capped: toAdd < quantity };
    },
    
    // Remove product from cart
    removeFromCart(productId) {
      this.items = this.items.filter(item => item.id !== productId);
      this.saveCart();
    },
    
    // Update product quantity — clamp to stock_quantity (Req 1.3, 1.4)
    updateQuantity(productId, quantity) {
      const item = this.items.find(item => item.id === productId);

      if (item) {
        if (quantity <= 0) {
          this.removeFromCart(productId);
        } else {
          const max = item.stock_quantity ?? Infinity;
          item.quantity = Math.min(quantity, max);
          this.saveCart();
        }
      }
    },
    
    // Clear entire cart
    clearCart() {
      this.items = [];
      this.saveCart();
    },
    
    // Save cart to localStorage
    saveCart() {
      localStorage.setItem('cart', JSON.stringify(this.items));
    },
    
    // Load cart from localStorage
    loadCart() {
      const savedCart = localStorage.getItem('cart');
      if (savedCart) {
        this.items = JSON.parse(savedCart);
      }
    }
  }
});

// User store - manages authentication and user state
export const useUserStore = defineStore('user', {
  state: () => ({
    currentUser: null,
    isAuthenticated: false
  }),
  
  getters: {
    userName(state) {
      return state.currentUser?.name || 'Guest';
    },
    
    userEmail(state) {
      return state.currentUser?.email || '';
    }
  },
  
  actions: {
    // Decode JWT payload without a library
    _parseJwt(token) {
      try {
        const payload = token.split('.')[1];
        return JSON.parse(atob(payload));
      } catch {
        return null;
      }
    },

    // Login user via API
    async login(email, password) {
      const response = await api.auth.login(email, password);
      const { token, user } = response.data.data;

      localStorage.setItem('token', token);

      this.currentUser = user;
      this.isAuthenticated = true;

      return { success: true };
    },

    // Register new user via API
    async register(name, email, password) {
      const response = await api.auth.register(name, email, password);
      const { token, user } = response.data.data;

      localStorage.setItem('token', token);

      this.currentUser = user;
      this.isAuthenticated = true;

      return { success: true };
    },

    // Logout user
    logout() {
      this.currentUser = null;
      this.isAuthenticated = false;
      localStorage.removeItem('token');
    },

    // Load user from stored JWT payload
    loadUser() {
      const token = localStorage.getItem('token');
      if (!token) return;

      const payload = this._parseJwt(token);
      if (!payload || payload.exp * 1000 < Date.now()) {
        localStorage.removeItem('token');
        return;
      }

      this.currentUser = {
        id:      payload.sub,
        email:   payload.email,
        role:    payload.role,
        name:    payload.name ?? payload.email,
        phone:   payload.phone ?? null,
        address: payload.address ?? null,
      };
      this.isAuthenticated = true;
    },

    // Update profile (name, phone, address)
    async updateProfile(data) {
      const response = await api.users.updateMe(data);
      const updated = response.data.data;
      this.currentUser = { ...this.currentUser, ...updated };
      return { success: true };
    }
  }
});

// Orders store — збережено для сумісності, але компоненти тепер використовують api напряму
export const useOrderStore = defineStore('orders', {
  state: () => ({
    orders: [],
    loading: false,
    error: null
  }),
  actions: {
    async createOrder(orderData) {
      const response = await api.orders.create(orderData);
      return response.data.data;
    }
  }
});

// Reviews store - manages product reviews
export const useReviewStore = defineStore('reviews', {
  state: () => ({
    reviews: [],
    loading: false
  }),

  actions: {
    async fetchReviews(productId) {
      this.loading = true;
      try {
        const response = await api.reviews.getAll(productId);
        this.reviews = response.data.data ?? [];
      } finally {
        this.loading = false;
      }
    },

    async createReview(data) {
      const response = await api.reviews.create(data);
      const created = response.data.data;
      this.reviews.unshift(created);
      return created;
    },

    async updateReview(id, data) {
      const response = await api.reviews.update(id, data);
      const updated = response.data.data;
      const idx = this.reviews.findIndex(r => r.id === id);
      if (idx !== -1) this.reviews[idx] = updated;
      return updated;
    },

    async deleteReview(id) {
      await api.reviews.delete(id);
      this.reviews = this.reviews.filter(r => r.id !== id);
    }
  }
});

// Wishlist store - manages user's saved products
export const useWishlistStore = defineStore('wishlist', {
  state: () => ({
    items: [] // array of product_id numbers
  }),

  getters: {
    isInWishlist: (state) => (productId) => {
      return state.items.includes(Number(productId));
    }
  },

  actions: {
    async fetchWishlist() {
      try {
        const response = await api.wishlist.getAll();
        const products = response.data.data ?? [];
        this.items = products.map(p => Number(p.id));
      } catch {
        this.items = [];
      }
    },

    async toggleWishlist(product) {
      const id = Number(product.id);
      if (this.items.includes(id)) {
        await api.wishlist.remove(id);
        this.items = this.items.filter(i => i !== id);
      } else {
        await api.wishlist.add(id);
        this.items.push(id);
      }
    },

    async removeFromWishlist(productId) {
      const id = Number(productId);
      await api.wishlist.remove(id);
      this.items = this.items.filter(i => i !== id);
    },

    // Call on app init if user is authenticated
    init() {
      const token = localStorage.getItem('token');
      if (token) {
        this.fetchWishlist();
      }
    }
  }
});
