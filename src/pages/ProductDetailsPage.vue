<template>
  <div v-if="loading" class="page">
    <div class="container flex-center" style="min-height: 400px">
      <div class="spinner"></div>
    </div>
  </div>

  <div v-else-if="product" class="product-details-page page">
    <div class="container">
      <!-- Breadcrumb -->
      <nav class="breadcrumb">
        <router-link to="/" class="breadcrumb-item">Головна</router-link>
        <span class="breadcrumb-separator">/</span>
        <router-link to="/products" class="breadcrumb-item">Товари</router-link>
        <span class="breadcrumb-separator">/</span>
        <span class="breadcrumb-item">{{
          getCategoryName(product.category)
        }}</span>
        <span class="breadcrumb-separator">/</span>
        <span class="breadcrumb-item active">{{ product.name }}</span>
      </nav>

      <!-- Product Details -->
      <div class="product-detail-grid">
        <!-- Product Image -->
        <div class="product-image-section">
          <div class="main-image">
            <img
              :src="product.image"
              :alt="product.name"
              @error="handleImageError"
            />
          </div>
          <div v-if="!product.in_stock" class="out-of-stock-overlay">
            Немає в наявності
          </div>
        </div>

        <!-- Product Info -->
        <div class="product-info-section">
          <div class="product-meta">
            <span class="product-category-badge">{{
              getCategoryName(product.category)
            }}</span>
            <span v-if="product.in_stock" class="stock-badge in-stock"
              >✓ В наявності</span
            >
            <span v-else class="stock-badge out-of-stock"
              >✗ Немає в наявності</span
            >
          </div>

          <h1 class="product-title">{{ product.name }}</h1>

          <div class="product-rating mb-2">
            <div class="star-rating">
              <span v-for="n in 5" :key="n" :class="getStarClass(n)">★</span>
            </div>
            <span class="rating-text"
              >{{ product.rating }} ({{ product.reviews }} відгуків)</span
            >
          </div>

          <div class="product-price-section mb-3">
            <span class="price">₴{{ Number(product.price).toFixed(2) }}</span>
          </div>

          <p class="product-description">{{ product.description }}</p>

          <div class="divider"></div>

          <!-- Quantity Selector -->
          <div class="quantity-section mb-3">
            <label>Кількість:</label>
            <div class="quantity-selector">
              <button
                class="quantity-btn"
                @click="decreaseQuantity"
                :disabled="quantity <= 1"
              >
                −
              </button>
              <input
                type="number"
                class="quantity-input"
                v-model.number="quantity"
                min="1"
                :max="product.stock_quantity"
              />
              <button
                class="quantity-btn"
                @click="increaseQuantity"
                :disabled="quantity >= product.stock_quantity"
              >
                +
              </button>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="action-buttons">
            <button
              class="btn btn-primary btn-lg"
              @click="addToCart"
              :disabled="!product.in_stock"
            >
              <span>🛒</span>
              {{ product.in_stock ? "Додати в кошик" : "Немає в наявності" }}
            </button>
            <router-link to="/products" class="btn btn-outline btn-lg"
              >← Назад до товарів</router-link
            >
          </div>

          <!-- Added to cart notification -->
          <transition name="fade">
            <div v-if="addedToCart" class="alert alert-success mt-2">
              ✓ Товар додано до кошика
            </div>
          </transition>

          <!-- Stock error notification -->
          <transition name="fade">
            <div v-if="stockError" class="alert alert-error mt-2">
              ⚠ {{ stockError }}
            </div>
          </transition>
        </div>
      </div>

      <!-- Specifications Section -->
      <div v-if="specEntries.length > 0" class="specifications-section mt-4">
        <h2 class="section-title">Технічні характеристики</h2>
        <div class="specs-card card">
          <div class="card-body">
            <table class="specs-table">
              <tr v-for="[key, value] in specEntries" :key="key">
                <td class="specs-label">{{ formatSpecLabel(key) }}</td>
                <td class="specs-value">{{ value }}</td>
              </tr>
            </table>
          </div>
        </div>
      </div>

      <!-- Reviews Section -->
      <ReviewsSection
        v-if="product"
        :product-id="product.id"
        @rating-updated="fetchProduct(route.params.id)"
      />

      <!-- Related Products -->
      <div
        class="related-products-section mt-4"
        v-if="relatedProducts.length > 0"
      >
        <h2 class="section-title">Схожі товари</h2>
        <div class="products-grid grid grid-4">
          <ProductCard v-for="p in relatedProducts" :key="p.id" :product="p" />
        </div>
      </div>
    </div>
  </div>

  <div v-else class="page">
    <div class="container">
      <div class="empty-state">
        <div class="empty-state-icon">😕</div>
        <h3 class="empty-state-title">Товар не знайдено</h3>
        <p class="empty-state-text">
          Вибачте, цей товар не існує або був видалений.
        </p>
        <router-link to="/products" class="btn btn-primary"
          >Переглянути товари</router-link
        >
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from "vue";
import { useRoute } from "vue-router";
import ProductCard from "../components/ProductCard.vue";
import ReviewsSection from "../components/ReviewsSection.vue";
import { useCartStore } from "../store";
import { api } from "../services/api";

export default {
  name: "ProductDetailsPage",
  components: { ProductCard, ReviewsSection },
  setup() {
    const route = useRoute();
    const cartStore = useCartStore();

    const quantity = ref(1);
    const product = ref(null);
    const relatedProducts = ref([]);
    const loading = ref(false);
    const error = ref("");
    const addedToCart = ref(false);
    const stockError = ref("");

    // Normalize specifications: always return array of [key, value] pairs
    const specEntries = computed(() => {
      const specs = product.value?.specifications;
      if (!specs || typeof specs !== "object") return [];
      // If it's an array of objects like [{key, value}]
      if (Array.isArray(specs)) {
        return specs
          .filter((s) => s && typeof s === "object" && "key" in s)
          .map((s) => [s.key, s.value]);
      }
      // Plain object
      return Object.entries(specs).filter(([k]) => k !== "" && k !== null);
    });

    const fetchProduct = async (id) => {
      loading.value = true;
      error.value = "";
      product.value = null;
      relatedProducts.value = [];
      try {
        const res = await api.products.getById(id);
        product.value = res.data.data ?? res.data;
        const allRes = await api.products.getAll(product.value.category);
        const all = allRes.data.data ?? allRes.data;
        relatedProducts.value = Array.isArray(all)
          ? all.filter((p) => p.id !== product.value.id).slice(0, 4)
          : [];
      } catch (err) {
        if (err.response?.status !== 404) {
          error.value =
            err.response?.data?.message || "Не вдалося завантажити товар.";
        }
      } finally {
        loading.value = false;
      }
    };

    watch(
      () => route.params.id,
      (id) => {
        if (id) fetchProduct(id);
      },
      { immediate: true }
    );

    const getCategoryName = (categoryId) => {
      if (!categoryId) return "";
      return (
        String(categoryId).charAt(0).toUpperCase() + String(categoryId).slice(1)
      );
    };

    const getStarClass = (starIndex) => {
      const rating = product.value?.rating || 0;
      if (starIndex <= Math.floor(rating)) return "star-filled";
      if (starIndex - 0.5 <= rating) return "star-half";
      return "star-empty";
    };

    const formatSpecLabel = (key) => {
      return String(key)
        .replace(/_/g, " ")
        .replace(/([A-Z])/g, " $1")
        .replace(/^./, (str) => str.toUpperCase())
        .trim();
    };

    const increaseQuantity = () => {
      const max = product.value?.stock_quantity ?? Infinity;
      if (quantity.value < max) quantity.value++;
    };
    const decreaseQuantity = () => {
      if (quantity.value > 1) quantity.value--;
    };

    const addToCart = () => {
      const inStock = product.value?.in_stock ?? product.value?.inStock;
      if (product.value && inStock) {
        const result = cartStore.addToCart(product.value, quantity.value);
        if (result?.error) {
          stockError.value = result.error;
          setTimeout(() => { stockError.value = ""; }, 3000);
          return;
        }
        quantity.value = 1;
        addedToCart.value = true;
        setTimeout(() => {
          addedToCart.value = false;
        }, 2500);
      }
    };

    const handleImageError = (e) => {
      const name =
        product.value?.name?.split(" ").slice(0, 2).join(" ") || "Product";
      e.target.src = `data:image/svg+xml;base64,${btoa(
        `<svg width="600" height="600" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#e2e8f0"/><text x="50%" y="50%" font-family="Arial,sans-serif" font-size="40" fill="#64748b" text-anchor="middle" dominant-baseline="middle">${name}</text></svg>`
      )}`;
    };

    return {
      product,
      quantity,
      relatedProducts,
      loading,
      error,
      addedToCart,
      stockError,
      specEntries,
      getCategoryName,
      getStarClass,
      formatSpecLabel,
      increaseQuantity,
      decreaseQuantity,
      addToCart,
      handleImageError,
    };
  },
};
</script>

<style scoped>
.product-details-page {
  padding: 2rem 0 4rem;
}

.product-detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  align-items: start;
}

/* Image */
.product-image-section {
  position: relative;
  border-radius: var(--radius-xl);
  overflow: hidden;
  background: var(--bg-primary);
  box-shadow: var(--shadow-lg);
}

.main-image img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: contain;
  padding: 1.5rem;
  background: var(--bg-primary);
}

.out-of-stock-overlay {
  position: absolute;
  top: 1rem;
  left: 1rem;
  background: rgba(239, 68, 68, 0.9);
  color: white;
  padding: 0.4rem 0.9rem;
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.875rem;
}

/* Info section */
.product-info-section {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.product-meta {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.product-category-badge {
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  padding: 0.25rem 0.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stock-badge {
  padding: 0.25rem 0.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  font-weight: 600;
}

.stock-badge.in-stock {
  background: #d1fae5;
  color: #065f46;
}

.stock-badge.out-of-stock {
  background: #fee2e2;
  color: #991b1b;
}

.product-title {
  font-size: 1.875rem;
  font-weight: 700;
  line-height: 1.3;
  margin-bottom: 0.5rem;
}

.product-rating {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.rating-text {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.product-price-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.price {
  font-size: 2rem;
  font-weight: 800;
  color: var(--primary-color);
}

.product-description {
  color: var(--text-secondary);
  line-height: 1.7;
  margin-bottom: 0;
}

.divider {
  height: 1px;
  background: var(--border-color);
  margin: 0.75rem 0;
}

.quantity-section label {
  font-weight: 600;
  margin-bottom: 0.5rem;
  display: block;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.action-buttons .btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Specs */
.section-title {
  font-size: 1.375rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: var(--text-primary);
}

.specs-card {
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.specs-card:hover {
  transform: none;
}

.specs-table {
  width: 100%;
  border-collapse: collapse;
}

.specs-table tr:nth-child(even) {
  background-color: var(--bg-secondary);
}

.specs-table td {
  padding: 0.875rem 1.25rem;
  border-bottom: 1px solid var(--border-color);
}

.specs-table tr:last-child td {
  border-bottom: none;
}

.specs-label {
  font-weight: 600;
  color: var(--text-secondary);
  width: 40%;
}

.specs-value {
  color: var(--text-primary);
}

/* Fade transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .product-detail-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .product-title {
    font-size: 1.5rem;
  }

  .price {
    font-size: 1.5rem;
  }

  .action-buttons {
    flex-direction: column;
  }

  .action-buttons .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
