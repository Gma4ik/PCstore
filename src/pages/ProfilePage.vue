<template>
  <div class="profile-page page">
    <div class="container">
      <h1 class="page-title mb-4">Мій акаунт</h1>
      
      <div class="profile-grid">
        <!-- User Info Section -->
        <div class="user-info-section">
          <div class="user-card card">
            <div class="card-body">
              <div class="user-header text-center mb-3">
                <div class="user-avatar-large">{{ userInitial }}</div>
                <h2 class="user-name">{{ userData.name }}</h2>
                <p class="user-email">{{ userData.email }}</p>
                <span class="badge badge-primary">Учасник з {{ userData.memberSince }}</span>
              </div>
              
              <div class="user-details">
                <div class="detail-row">
                  <span class="detail-label">📞 Телефон:</span>
                  <span v-if="!editing" class="detail-value">{{ userData.phone || '—' }}</span>
                  <div v-else>
                    <input v-model="editForm.phone" type="tel" placeholder="380XXXXXXXXX" @input="formatPhone" maxlength="12" />
                    <span v-if="phoneError" class="field-error">{{ phoneError }}</span>
                  </div>
                </div>
                <div class="detail-row">
                  <span class="detail-label">📍 Адреса:</span>
                  <span v-if="!editing" class="detail-value">{{ userData.address || '—' }}</span>
                  <input v-else v-model="editForm.address" type="text" placeholder="вул. Хрещатик, 1" />
                </div>
              </div>

              <div class="profile-actions mt-3">
                <template v-if="!editing">
                  <button @click="startEdit" class="btn btn-primary btn-block">Редагувати профіль</button>
                  <button @click="startPasswordChange" class="btn btn-outline btn-block mt-2">Змінити пароль</button>
                </template>
                <template v-else-if="changingPassword">
                  <div class="form-group">
                    <label class="detail-label">Поточний пароль</label>
                    <input v-model="passwordForm.current" type="password" placeholder="Поточний пароль" />
                  </div>
                  <div class="form-group mt-2">
                    <label class="detail-label">Новий пароль</label>
                    <input v-model="passwordForm.next" type="password" placeholder="Мінімум 6 символів" />
                  </div>
                  <div class="form-group mt-2">
                    <label class="detail-label">Підтвердіть пароль</label>
                    <input v-model="passwordForm.confirm" type="password" placeholder="Повторіть новий пароль" />
                  </div>
                  <span v-if="passwordError" class="field-error mb-2">{{ passwordError }}</span>
                  <button @click="savePassword" class="btn btn-primary btn-block mt-2" :disabled="saving">
                    {{ saving ? 'Збереження...' : 'Зберегти пароль' }}
                  </button>
                  <button @click="cancelEdit" class="btn btn-outline btn-block mt-2">Скасувати</button>
                </template>
                <template v-else>
                  <button @click="saveProfile" class="btn btn-primary btn-block" :disabled="saving">
                    {{ saving ? 'Збереження...' : 'Зберегти' }}
                  </button>
                  <button @click="cancelEdit" class="btn btn-outline btn-block mt-2">Скасувати</button>
                </template>
              </div>
              
              <button @click="handleLogout" class="btn btn-outline btn-block mt-3">
                Вийти
              </button>
            </div>
          </div>
        </div>
        
        <!-- Orders Section -->
        <div class="orders-section">
          <h2 class="section-title">Історія замовлень</h2>
          
          <div v-if="ordersLoading" class="loading-state">Завантаження замовлень...</div>

          <div v-else-if="ordersError" class="error-message">{{ ordersError }}</div>
          
          <div v-else-if="orders.length > 0" class="orders-list">
            <div 
              v-for="order in orders" 
              :key="order.id" 
              class="order-card card mb-3"
            >
              <div class="card-body">
                <div class="order-header flex-between mb-2">
                  <div>
                    <h3 class="order-id">{{ order.id }}</h3>
                    <p class="order-date">{{ order.date || order.created_at }}</p>
                  </div>
                  <span :class="['order-status', getStatusClass(order.status)]">
                    {{ translateStatus(order.status) }}
                  </span>
                </div>
                
                <div class="order-items">
                  <div 
                    v-for="item in order.items" 
                    :key="item.product_id || item.productId" 
                    class="order-item"
                  >
                    <span class="item-name">{{ item.name }}</span>
                    <span class="item-quantity">x{{ item.quantity }}</span>
                    <span class="item-price">₴{{ (item.price * item.quantity).toFixed(2) }}</span>
                  </div>
                </div>
                
                <div class="order-total flex-between mt-2">
                  <span class="total-label">Разом:</span>
                  <span class="total-amount">₴{{ Number(order.total).toFixed(2) }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3 class="empty-state-title">Замовлень ще немає </h3>
            <p class="empty-state-text">Почніть покупки, щоб побачити замовлення тут!</p>
            <router-link to="/products" class="btn btn-primary">Переглянути товари</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '../store';
import { api } from '../services/api';

export default {
  name: 'ProfilePage',
  setup() {
    const router = useRouter();
    const userStore = useUserStore();

    const editing = ref(false);
    const changingPassword = ref(false);
    const saving = ref(false);
    const phoneError = ref('');
    const passwordError = ref('');
    const editForm = ref({ phone: '', address: '' });
    const passwordForm = ref({ current: '', next: '', confirm: '' });

    // Orders — local state, no store, no caching
    const orders = ref([]);
    const ordersLoading = ref(false);
    const ordersError = ref('');

    const formatPhone = (e) => {
      let digits = e.target.value.replace(/\D/g, '');
      if (!digits.startsWith('380')) digits = '380' + digits.replace(/^380/, '');
      if (digits.length > 12) digits = digits.slice(0, 12);
      editForm.value.phone = digits;
      phoneError.value = digits.length < 12 ? 'Номер має містити 12 цифр (380XXXXXXXXX)' : '';
    };

    const userData = computed(() => userStore.currentUser || {});
    const userInitial = computed(() => (userData.value.name || 'U').charAt(0).toUpperCase());

    const loadOrders = async () => {
      ordersLoading.value = true;
      ordersError.value = '';
      try {
        const res = await api.orders.getAll();
        orders.value = res.data.data ?? [];
      } catch (err) {
        ordersError.value = err.response?.data?.message || 'Не вдалося завантажити замовлення';
      } finally {
        ordersLoading.value = false;
      }
    };

    onMounted(loadOrders);

    const startEdit = () => {
      const digits = (userData.value.phone || '').replace(/\D/g, '');
      editForm.value = { phone: digits || '380', address: userData.value.address || '' };
      phoneError.value = '';
      editing.value = true;
    };

    const cancelEdit = () => {
      editing.value = false;
      changingPassword.value = false;
      passwordError.value = '';
    };

    const startPasswordChange = () => {
      passwordForm.value = { current: '', next: '', confirm: '' };
      passwordError.value = '';
      changingPassword.value = true;
      editing.value = true;
    };

    const savePassword = async () => {
      passwordError.value = '';
      if (!passwordForm.value.current) { passwordError.value = 'Введіть поточний пароль'; return; }
      if (passwordForm.value.next.length < 6) { passwordError.value = 'Новий пароль має містити мінімум 6 символів'; return; }
      if (passwordForm.value.next !== passwordForm.value.confirm) { passwordError.value = 'Паролі не співпадають'; return; }
      saving.value = true;
      try {
        await api.users.changePassword(passwordForm.value.current, passwordForm.value.next);
        editing.value = false;
        changingPassword.value = false;
        alert('Пароль успішно змінено');
      } catch (err) {
        passwordError.value = err.response?.data?.message || 'Не вдалося змінити пароль';
      } finally {
        saving.value = false;
      }
    };

    const saveProfile = async () => {
      if (editForm.value.phone && editForm.value.phone.length < 12) {
        phoneError.value = 'Номер має містити 12 цифр (380XXXXXXXXX)';
        return;
      }
      saving.value = true;
      try {
        await userStore.updateProfile(editForm.value);
        editing.value = false;
      } catch (err) {
        alert(err.response?.data?.message || 'Не вдалося зберегти профіль');
      } finally {
        saving.value = false;
      }
    };

    const getStatusClass = (status) => {
      switch (status?.toLowerCase()) {
        case 'delivered':  return 'status-delivered';
        case 'shipped':    return 'status-shipped';
        case 'processing': return 'status-processing';
        case 'cancelled':  return 'status-cancelled';
        default: return '';
      }
    };

    const translateStatus = (status) => {
      switch (status?.toLowerCase()) {
        case 'delivered':  return 'Доставлено';
        case 'shipped':    return 'Відправлено';
        case 'processing': return 'В обробці';
        case 'cancelled':  return 'Скасовано';
        default: return status;
      }
    };

    const handleLogout = () => {
      if (confirm('Ви впевнені, що хочете вийти?')) {
        userStore.logout();
        router.push('/');
      }
    };

    return {
      userData, userInitial,
      orders, ordersLoading, ordersError,
      editing, changingPassword, saving,
      editForm, phoneError,
      passwordForm, passwordError,
      startEdit, startPasswordChange, cancelEdit,
      saveProfile, savePassword, formatPhone,
      getStatusClass, translateStatus, handleLogout,
    };
  }
};
</script>

<style scoped>
.profile-grid {
  display: grid;
  grid-template-columns: 350px 1fr;
  gap: 2rem;
}

.user-card {
  box-shadow: var(--shadow-md);
}

.user-avatar-large {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background-color: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0 auto 1rem;
}

.user-name {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0.5rem 0;
  color: var(--text-primary);
}

.user-email {
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

.user-details {
  padding-top: 1rem;
  border-top: 1px solid var(--border-color);
}

.detail-row {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border-color);
}

.detail-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.detail-label {
  font-weight: 600;
  color: var(--text-secondary);
  font-size: 0.875rem;
}

.detail-value {
  color: var(--text-primary);
  font-size: 1rem;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  color: var(--text-primary);
}

.order-card {
  box-shadow: var(--shadow-sm);
}

.order-header {
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border-color);
}

.order-id {
  font-size: 1.125rem;
  font-weight: 700;
  margin: 0;
  color: var(--text-primary);
}

.order-date {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin: 0.25rem 0 0;
}

.order-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.order-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background-color: var(--bg-secondary);
  border-radius: var(--radius-md);
}

.item-name {
  flex: 1;
  font-size: 0.875rem;
  color: var(--text-primary);
}

.item-quantity {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin: 0 1rem;
}

.item-price {
  font-weight: 600;
  color: var(--primary-color);
  font-size: 0.875rem;
}

.order-total {
  padding-top: 1rem;
  border-top: 1px solid var(--border-color);
}

.total-label {
  font-weight: 600;
  color: var(--text-secondary);
}

.total-amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-color);
}

@media (max-width: 1024px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
  
  .user-info-section {
    max-width: 500px;
    margin: 0 auto;
  }
}

.profile-actions {
  display: flex;
  flex-direction: column;
}

.btn-block {
  width: 100%;
  text-align: center;
}

.detail-row input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  font-size: 0.9375rem;
  color: var(--text-primary);
  background-color: var(--bg-secondary);
}

.detail-row input:focus {
  outline: none;
  border-color: var(--primary-color);
}

.field-error {
  color: var(--error-color, #e53e3e);
  font-size: 0.75rem;
  margin-top: 0.25rem;
  display: block;
}

.loading-state {
  color: var(--text-secondary);
  padding: 2rem 0;
  text-align: center;
}

.error-message {
  color: var(--error-color, #e53e3e);
  background-color: var(--error-bg, #fff5f5);
  border: 1px solid var(--error-color, #e53e3e);
  border-radius: var(--radius-md);
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
}
</style>
