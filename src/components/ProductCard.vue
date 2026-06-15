<template>
  <div class="product-card card">
    <router-link :to="`/product/${product.id}`" class="product-link">
      <div class="product-image-wrapper">
        <img 
          :src="product.image" 
          :alt="product.name" 
          class="product-image" 
          @error="handleImageError"
        />
        <span v-if="!product.in_stock" class="out-of-stock-badge">Немає в наявності</span>
        <div class="wishlist-overlay">
          <WishlistButton :product="product" />
        </div>
      </div>
      
      <div class="product-body card-body">
        <h3 class="product-name">{{ product.name }}</h3>
        
        <div class="product-rating" v-if="product.rating">
          <div class="star-rating">
            <span v-for="n in 5" :key="n" :class="getStarClass(n)">★</span>
          </div>
          <span class="rating-count">({{ product.reviews }})</span>
        </div>
        
        <div class="product-price">
          <span class="price">₴{{ product.price.toFixed(2) }}</span>
        </div>
      </div>
    </router-link>
    
    <div class="product-footer card-footer">
      <button 
        class="btn btn-primary btn-sm" 
        @click.stop="addToCart"
        :disabled="!product.in_stock"
      >
        {{ product.in_stock ? 'Додати в кошик' : 'Немає в наявності' }}
      </button>
    </div>
  </div>
</template>

<script>
import { useCartStore } from '../store';
import WishlistButton from './WishlistButton.vue';

export default {
  name: 'ProductCard',
  components: { WishlistButton },
  props: {
    product: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const cartStore = useCartStore();
    
    const addToCart = () => {
      if (props.product.in_stock) {
        const result = cartStore.addToCart(props.product, 1);
        if (result?.error) {
          alert(result.error);
        }
      }
    };
    
    const getStarClass = (starIndex) => {
      const rating = props.product.rating;
      if (starIndex <= Math.floor(rating)) {
        return 'star-filled';
      } else if (starIndex - 0.5 <= rating) {
        return 'star-half';
      } else {
        return 'star-empty';
      }
    };
    
    const handleImageError = (e) => {
      // Create a placeholder SVG on error
      const productName = encodeURIComponent(props.product.name.split(' ').slice(0, 2).join('+'));
      e.target.src = `data:image/svg+xml;base64,${btoa(`<svg width="300" height="300" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#e2e8f0"/><text x="50%" y="50%" font-family="Arial, sans-serif" font-size="24" fill="#64748b" text-anchor="middle" dominant-baseline="middle">${productName}</text></svg>`)}`;
    };
    
    return {
      addToCart,
      getStarClass,
      handleImageError
    };
  }
};
</script>

<style scoped>
.product-card {
  height: 100%;
  display: flex;
  flex-direction: column;
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

.product-card:hover .product-image {
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

.wishlist-overlay {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  z-index: 2;
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
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.star-rating {
  color: var(--accent-color);
}

.star-filled {
  color: var(--accent-color);
}

.star-half {
  color: var(--accent-color);
  opacity: 0.5;
}

.star-empty {
  color: var(--text-light);
}

.rating-count {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.product-price {
  margin-top: auto;
}

.price {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color);
}

.product-footer {
  border-top: 1px solid var(--border-color);
  background-color: var(--bg-tertiary);
}

.product-footer button {
  width: 100%;
}

@media (max-width: 768px) {
  .price {
    font-size: 1.25rem;
  }
}
</style>
