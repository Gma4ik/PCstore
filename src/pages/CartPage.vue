<template>
  <div class="cart-page page">
    <div class="container">
      <h1 class="page-title mb-4">Кошик</h1>
      
      <div v-if="cartItems.length > 0" class="cart-content">
        <div class="cart-items-section">
          <CartItem 
            v-for="item in cartItems" 
            :key="item.id"
            :item="item"
          />
          
          <div class="cart-actions mt-3">
            <router-link to="/products" class="btn btn-outline">Продовжити покупки</router-link>
            <button class="btn btn-danger" @click="clearCart">Очистити кошик</button>
          </div>
        </div>
        
        <div class="cart-summary-section">
          <div class="cart-summary">
            <h2 class="summary-title">Підсумок замовлення</h2>
            
            <div class="cart-summary-row">
              <span>Підсумок ({{ totalItems }} товарів)</span>
              <span>₴{{ totalPrice.toFixed(2) }}</span>
            </div>
            
            <div class="cart-summary-row">
              <span>Доставка</span>
              <span>{{ shippingCost === 0 ? 'БЕЗКОШТОВНО' : '₴' + shippingCost.toFixed(2) }}</span>
            </div>
            
            <div class="cart-summary-row total-row">
              <span>Разом</span>
              <span>₴{{ orderTotal.toFixed(2) }}</span>
            </div>
            
            <div class="summary-note" v-if="freeShippingThreshold > totalPrice">
              Додайте ще ₴{{ (freeShippingThreshold - totalPrice).toFixed(2) }} для БЕЗКОШТОВНОЇ доставки!
            </div>
            
            <router-link to="/checkout" class="btn btn-primary btn-lg btn-block">
              Оформити замовлення
            </router-link>
          </div>
        </div>
      </div>
      
      <div v-else class="empty-cart">
        <div class="empty-state">
          <div class="empty-state-icon">🛒</div>
          <h3 class="empty-state-title">Кошик порожній</h3>
          <p class="empty-state-text">Почніть покупки та додайте товари до кошика!</p>
          <router-link to="/products" class="btn btn-primary btn-lg">Переглянути товари</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';
import CartItem from '../components/CartItem.vue';
import { useCartStore } from '../store';

export default {
  name: 'CartPage',
  components: { CartItem },
  setup() {
    const cartStore = useCartStore();
    
    const cartItems = computed(() => cartStore.items);
    const totalItems = computed(() => cartStore.totalItems);
    const totalPrice = computed(() => cartStore.totalPrice);
    
    const freeShippingThreshold = 500;
    const shippingCost = computed(() => totalPrice.value >= freeShippingThreshold ? 0 : 29.99);
    const orderTotal = computed(() => totalPrice.value + shippingCost.value);
    
    const clearCart = () => {
      if (confirm('Ви впевнені, що хочете очистити кошик?')) {
        cartStore.clearCart();
      }
    };
    
    return { cartItems, totalItems, totalPrice, shippingCost, orderTotal, freeShippingThreshold, clearCart };
  }
};
</script>

<style scoped>
.cart-content {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 2rem;
}

.cart-items-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cart-actions {
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.cart-summary-section {
  position: sticky;
  top: 100px;
}

.summary-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: var(--text-primary);
}

.btn-block {
  width: 100%;
  display: block;
  text-align: center;
}

.summary-note {
  background-color: #fef3c7;
  color: #92400e;
  padding: 0.75rem;
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  text-align: center;
  margin-bottom: 1rem;
}

.empty-cart {
  max-width: 600px;
  margin: 0 auto;
}

@media (max-width: 1024px) {
  .cart-content {
    grid-template-columns: 1fr;
  }
  
  .cart-summary-section {
    position: static;
  }
}

@media (max-width: 768px) {
  .cart-actions {
    flex-direction: column;
  }
  
  .cart-actions .btn {
    width: 100%;
    text-align: center;
  }
}
</style>
