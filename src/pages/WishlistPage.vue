<template>
  <div class="wishlist-page page">
    <div class="container">
      <div class="page-header text-center">
        <h1 class="page-title">Список бажань</h1>
        <p class="page-subtitle">Збережені товари, до яких ви хочете повернутися</p>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="empty-state">
        <div class="empty-state-icon">⏳</div>
        <p class="empty-state-text">Завантаження...</p>
      </div>

      <!-- Empty state -->
      <div v-else-if="wishlistProducts.length === 0" class="empty-state">
        <div class="empty-state-icon">🤍</div>
        <h3 class="empty-state-title">Ваш список бажань порожній</h3>
        <p class="empty-state-text">Додавайте товари, натискаючи ❤️ на картці товару</p>
        <router-link to="/products" class="btn btn-primary">Перейти до каталогу</router-link>
      </div>

      <!-- Products grid -->
      <div v-else class="products-grid grid grid-3">
        <div v-for="product in wishlistProducts" :key="product.id" class="wishlist-card card">
          <router-link :to="`/product/${product.id}`" class="product-link">
            <div class="product-image-wrapper">
              <img
                :src="product.image"
                :alt="product.name"
                class="product-image"
                @error="handleImageError($event, product.name)"
              />
              <span v-if="!product.in_stock" class="out-of-stock-badge">Немає в наявності</span>
            </div>
            <div class="product-body card-body">
              <h3 class="product-name">{{ product.name }}</h3>
              <div class="product-rating" v-if="product.rating">
                <span v-for="n in 5" :key="n" :class="getStarClass(n, product.rating)">★</span>
                <span class="rating-count">({{ product.reviews }})</span>
              </div>
              <div class="product-price">
                <span class="price">₴{{ Number(product.price).toFixed(2) }}</span>
              </div>
            </div>
          </router-link>

          <div class="product-footer card-footer">
            <button
              class="btn btn-primary btn-sm"
              :disabled="!product.in_stock"
              @click="addToCart(product)"
            >
              {{ product.in_stock ? 'Додати в кошик' : 'Немає в наявності' }}
            </button>
            <button class="btn btn-outline btn-sm remove-btn" @click="removeItem(product)">
              🗑 Видалити
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useWishlistStore, useCartStore } from '../store';
import { api } from '../services/api';

export default {
  name: 'WishlistPage',
  setup() {
    const wishlistStore = useWishlistStore();
    const cartStore = useCartStore();

    const loading = ref(false);
    const allProducts = ref([]);

    const wishlistProducts = computed(() =>
      allProducts.value.filter(p => wishlistStore.isInWishlist(p.id))
    );

    const loadProducts = async () => {
      loading.value = true;
      try {
        const response = await api.wishlist.getAll();
        allProducts.value = response.data.data ?? [];
        // Sync store items with fetched product ids
        wishlistStore.items = allProducts.value.map(p => Number(p.id));
      } finally {
        loading.value = false;
      }
    };

    onMounted(loadProducts);

    const addToCart = (product) => {
      if (product.in_stock) cartStore.addToCart(product, 1);
    };

    const removeItem = async (product) => {
      await wishlistStore.removeFromWishlist(product.id);
      allProducts.value = allProducts.value.filter(p => Number(p.id) !== Number(product.id));
    };

    const getStarClass = (n, rating) => {
      if (n <= Math.floor(rating)) return 'star-filled';
      if (n - 0.5 <= rating) return 'star-half';
      return 'star-empty';
    };

    const handleImageError = (e, name) => {
      const label = encodeURIComponent(name.split(' ').slice(0, 2).join('+'));
      e.target.src = `data:image/svg+xml;base64,${btoa(`<svg width="300" height="300" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#e2e8f0"/><text x="50%" y="50%" font-family="Arial, sans-serif" font-size="24" fill="#64748b" text-anchor="middle" dominant-baseline="middle">${label}</text></svg>`)}`;
    };

    return { loading, wishlistProducts, addToCart, removeItem, getStarClass, handleImageError };
  }
};
</script>

<style scoped>
.wishlist-card {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.product-link {
  text-decoration: none;
  color: inherit;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.product-image-wrapper {
  position: relative;
  overflow: hidden;
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  background-color: var(--bg-tertiary);
  aspect-ratio: 1;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.wishlist-card:hover .product-image {
  transform: scale(1.05);
}

.out-of-stock-badge {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: rgba(239, 68, 68, 0.9);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 0.875rem;
}

.product-body {
  flex: 1;
}

.product-name {
  font-size: 1.125rem;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-rating {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.5rem;
  color: var(--accent-color);
  font-size: 0.9rem;
}

.star-filled { color: var(--accent-color); }
.star-half   { color: var(--accent-color); opacity: 0.5; }
.star-empty  { color: var(--text-light); }

.rating-count {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.product-price { margin-top: auto; }

.price {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color);
}

.product-footer {
  display: flex;
  gap: 0.5rem;
  border-top: 1px solid var(--border-color);
  background-color: var(--bg-tertiary);
}

.product-footer .btn {
  flex: 1;
}

.remove-btn {
  color: var(--danger-color);
  border-color: var(--danger-color);
}

.remove-btn:hover {
  background: var(--danger-color);
  color: white;
}
</style>
