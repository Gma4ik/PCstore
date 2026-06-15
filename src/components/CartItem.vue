<template>
  <div class="cart-item">
    <div class="cart-item-image">
      <img :src="item.image" :alt="item.name" />
    </div>
    
    <div class="cart-item-details">
      <h3 class="cart-item-name">{{ item.name }}</h3>
      <p class="cart-item-category">{{ getCategoryName(item.category) }}</p>
      <div class="cart-item-price">₴{{ item.price.toFixed(2) }}</div>
    </div>
    
    <div class="cart-item-quantity">
      <label>Кількість:</label>
      <div class="quantity-selector">
        <button class="quantity-btn" @click="decreaseQuantity">-</button>
        <input 
          type="number" 
          class="quantity-input" 
          :value="item.quantity"
          @change="updateQuantity($event.target.value)"
          min="1"
          :max="item.stock_quantity ?? undefined"
        />
        <button
          class="quantity-btn"
          @click="increaseQuantity"
          :disabled="isAtStockLimit"
        >+</button>
      </div>
      <p v-if="isAtStockLimit" class="stock-warning">
        Максимум {{ item.stock_quantity }} шт. в наявності
      </p>
    </div>
    
    <div class="cart-item-total">
      <div class="total-label">Разом:</div>
      <div class="total-price">₴{{ (item.price * item.quantity).toFixed(2) }}</div>
    </div>
    
    <button class="cart-item-remove" @click="removeItem">
      🗑️
    </button>
  </div>
</template>

<script>
import { computed } from 'vue';
import { useCartStore } from '../store';

export default {
  name: 'CartItem',
  props: {
    item: { type: Object, required: true }
  },
  setup(props) {
    const cartStore = useCartStore();

    // category name may already be on the item, otherwise just show the id
    const getCategoryName = (categoryId) => categoryId ?? '';

    const stockMax = () => props.item.stock_quantity ?? Infinity;
    const isAtStockLimit = computed(() => props.item.quantity >= stockMax());

    const increaseQuantity = () => {
      if (props.item.quantity < stockMax()) {
        cartStore.updateQuantity(props.item.id, props.item.quantity + 1);
      }
    };
    const decreaseQuantity = () => {
      if (props.item.quantity > 1) cartStore.updateQuantity(props.item.id, props.item.quantity - 1);
    };
    const updateQuantity = (value) => {
      const q = parseInt(value);
      if (!isNaN(q) && q >= 1) {
        // store.updateQuantity already clamps to stock_quantity (Req 1.3, 1.4)
        cartStore.updateQuantity(props.item.id, q);
      }
    };
    const removeItem = () => cartStore.removeFromCart(props.item.id);

    return { getCategoryName, isAtStockLimit, increaseQuantity, decreaseQuantity, updateQuantity, removeItem };
  }
};
</script>

<style scoped>
.cart-item {
  display: grid;
  grid-template-columns: 100px 1fr auto auto auto;
  gap: 1.5rem;
  align-items: center;
  padding: 1.5rem;
  background-color: var(--bg-primary);
  border-radius: var(--radius-lg);
  margin-bottom: 1rem;
  box-shadow: var(--shadow-sm);
}

.cart-item-image {
  width: 100px;
  height: 100px;
  border-radius: var(--radius-md);
  overflow: hidden;
  background-color: var(--bg-tertiary);
}

.cart-item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cart-item-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.cart-item-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.cart-item-category {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin: 0;
}

.cart-item-price {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-color);
  margin-top: 0.5rem;
}

.cart-item-quantity {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.cart-item-quantity label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-secondary);
}

.stock-warning {
  font-size: 0.75rem;
  color: #e67e22;
  margin: 0.25rem 0 0;
  text-align: center;
}

.cart-item-total {
  text-align: right;
}

.total-label {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-bottom: 0.25rem;
}

.total-price {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color);
}

.cart-item-remove {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.5rem;
  opacity: 0.6;
  transition: var(--transition);
  padding: 0.5rem;
}

.cart-item-remove:hover {
  opacity: 1;
  transform: scale(1.1);
}

@media (max-width: 1024px) {
  .cart-item {
    grid-template-columns: 80px 1fr auto;
    gap: 1rem;
  }
  
  .cart-item-quantity {
    grid-row: 2;
  }
  
  .cart-item-total {
    grid-row: 2;
    text-align: left;
  }
  
  .cart-item-remove {
    grid-row: 1;
  }
}

@media (max-width: 768px) {
  .cart-item {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .cart-item-image {
    width: 100%;
    max-width: 200px;
    height: 200px;
    margin: 0 auto;
  }
  
  .cart-item-total {
    text-align: center;
  }
  
  .cart-item-remove {
    justify-self: center;
  }
}
</style>
