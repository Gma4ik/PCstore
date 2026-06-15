<template>
  <div class="admin-orders page">
    <div class="container">
      <div class="admin-header mb-4">
        <h1 class="page-title">Управління замовленнями</h1>
        <p class="admin-subtitle">Перегляд та обробка замовлень клієнтів</p>
      </div>

      <!-- Filters -->
      <div class="filters-section card mb-4">
        <div class="card-body">
          <div class="flex-between">
            <div class="search-bar">
              <span class="search-icon">🔍</span>
              <input 
                type="text" 
                class="search-input" 
                placeholder="Пошук за ID або клієнтом..."
                v-model="searchQuery"
              />
            </div>
            
            <div class="filter-group">
              <label>Статус:</label>
              <select v-model="statusFilter">
                <option value="">Всі статуси</option>
                <option value="Processing">В обробці</option>
                <option value="Shipped">Відправлено</option>
                <option value="Delivered">Доставлено</option>
                <option value="Cancelled">Скасовано</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Orders Table -->
      <div class="orders-table card">
        <div class="card-body">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID замовлення</th>
                <th>Дата</th>
                <th>Клієнт</th>
                <th>Email</th>
                <th>Товари</th>
                <th>Сума</th>
                <th>Статус</th>
                <th>Оплата</th>
                <th>Дії</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in filteredOrders" :key="order.id">
                <td><strong>{{ order.id }}</strong></td>
                <td>{{ order.date || order.created_at }}</td>
                <td>{{ order.customer_info?.name || order.customerInfo?.name || 'Гість' }}</td>
                <td>{{ order.customer_info?.email || order.customerInfo?.email || 'Не вказано' }}</td>
                <td>{{ order.items?.length ?? 0 }} товарів</td>
                <td>₴{{ Number(order.total).toFixed(2) }}</td>
                <td>
                  <select 
                    :value="order.status" 
                    @change="updateOrderStatus(order.id, $event.target.value)"
                    :class="['status-select', getStatusClass(order.status)]"
                  >
                    <option value="Processing">В обробці</option>
                    <option value="Shipped">Відправлено</option>
                    <option value="Delivered">Доставлено</option>
                    <option value="Cancelled">Скасовано</option>
                  </select>
                </td>
                <!-- 10.1 Payment method column + 10.2 payment status badge -->
                <td class="payment-cell">
                  <span class="payment-method-label">
                    {{ order.payment_method === 'card' ? '💳 Картка' : '🚚 При отриманні' }}
                  </span>
                  <span :class="['payment-badge', getPaymentStatusClass(order.payment_status)]">
                    {{ order.payment_status === 'paid' ? 'Оплачено' : 'Очікує оплати' }}
                  </span>
                </td>
                <td>
                  <button @click="viewOrderDetails(order)" class="btn btn-sm btn-outline">
                    Деталі
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Order Details Modal -->
      <div v-if="selectedOrder" class="modal-overlay" @click="selectedOrder = null">
        <div class="modal-content modal-large" @click.stop>
          <div class="modal-header flex-between">
            <h2>Деталі замовлення - {{ selectedOrder.id }}</h2>
            <button @click="selectedOrder = null" class="btn-close">✕</button>
          </div>
          
          <div class="order-details">
            <div class="order-info-grid grid-2 mb-3">
              <div>
                <h4>Інформація про клієнта</h4>
                <p><strong>Ім'я:</strong> {{ (selectedOrder.customer_info || selectedOrder.customerInfo)?.name || 'Гість' }}</p>
                <p><strong>Email:</strong> {{ (selectedOrder.customer_info || selectedOrder.customerInfo)?.email || 'Не вказано' }}</p>
                <p><strong>Телефон:</strong> {{ (selectedOrder.customer_info || selectedOrder.customerInfo)?.phone || 'Не вказано' }}</p>
                <p><strong>Адреса:</strong> {{ (selectedOrder.customer_info || selectedOrder.customerInfo)?.address || 'Не вказано' }}</p>
              </div>
              
              <div>
                <h4>Інформація про замовлення</h4>
                <p><strong>Дата:</strong> {{ selectedOrder.date || selectedOrder.created_at }}</p>
                <p><strong>Статус:</strong> 
                  <span :class="['badge', getStatusClass(selectedOrder.status)]">
                    {{ selectedOrder.status }}
                  </span>
                </p>
                <!-- 10.3 Dynamic payment method + payment status (replaces hardcoded "Оплачено") -->
                <p>
                  <strong>Спосіб оплати:</strong>
                  {{ selectedOrder.payment_method === 'card' ? '💳 Картка' : '🚚 При отриманні' }}
                </p>
                <p class="payment-status-row">
                  <strong>Статус оплати:</strong>
                  <span :class="['payment-badge', getPaymentStatusClass(selectedOrder.payment_status)]">
                    {{ selectedOrder.payment_status === 'paid' ? 'Оплачено' : 'Очікує оплати' }}
                  </span>
                  <!-- Admin can change payment_status directly from modal -->
                  <select
                    :value="selectedOrder.payment_status"
                    @change="updatePaymentStatus(selectedOrder.id, $event.target.value)"
                    class="payment-status-select"
                  >
                    <option value="pending">Очікує оплати</option>
                    <option value="paid">Оплачено</option>
                  </select>
                </p>
              </div>
            </div>
            
            <h4>Товари в замовленні</h4>
            <table class="items-table">
              <thead>
                <tr>
                  <th>Товар</th>
                  <th>Кількість</th>
                  <th>Ціна</th>
                  <th>Разом</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in selectedOrder.items" :key="item.product_id || item.productId">
                  <td>{{ item.name }}</td>
                  <td>{{ item.quantity }}</td>
                  <td>₴{{ Number(item.price).toFixed(2) }}</td>
                  <td>₴{{ (Number(item.price) * item.quantity).toFixed(2) }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3"><strong>Разом</strong></td>
                  <td><strong>₴{{ Number(selectedOrder.total).toFixed(2) }}</strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
          
          <div class="modal-footer">
            <button @click="selectedOrder = null" class="btn btn-outline">Закрити</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { adminApi } from '../../services/api';

export default {
  name: 'AdminOrders',
  setup() {
    const orders = ref([]);
    const loading = ref(false);
    const searchQuery = ref('');
    const statusFilter = ref('');
    const selectedOrder = ref(null);

    const loadOrders = async () => {
      loading.value = true;
      try {
        const res = await adminApi.orders.getAll();
        orders.value = res.data.data ?? [];
      } catch (err) {
        console.error('Не вдалося завантажити замовлення', err);
      } finally {
        loading.value = false;
      }
    };

    onMounted(loadOrders);

    const filteredOrders = computed(() => {
      let result = [...orders.value];
      if (statusFilter.value) result = result.filter(o => o.status === statusFilter.value);
      if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(o =>
          String(o.id).toLowerCase().includes(q) ||
          (o.customer_info?.name ?? o.customerInfo?.name ?? '').toLowerCase().includes(q)
        );
      }
      return result;
    });

    const getStatusClass = (status) => {
      switch (status?.toLowerCase()) {
        case 'delivered':  return 'status-delivered';
        case 'shipped':    return 'status-shipped';
        case 'processing': return 'status-processing';
        case 'cancelled':  return 'status-cancelled';
        default: return '';
      }
    };

    const getPaymentStatusClass = (paymentStatus) => {
      return paymentStatus === 'paid' ? 'payment-paid' : 'payment-pending';
    };

    const updateOrderStatus = async (orderId, newStatus) => {
      try {
        await adminApi.orders.updateStatus(orderId, newStatus);
        const order = orders.value.find(o => o.id === orderId);
        if (order) order.status = newStatus;
      } catch (err) {
        alert(err.response?.data?.message || 'Не вдалося оновити статус замовлення');
      }
    };

    // 10.3 — update payment_status from modal
    const updatePaymentStatus = async (orderId, newPaymentStatus) => {
      try {
        await adminApi.orders.updatePaymentStatus(orderId, newPaymentStatus);
        const order = orders.value.find(o => o.id === orderId);
        if (order) order.payment_status = newPaymentStatus;
        if (selectedOrder.value?.id === orderId) {
          selectedOrder.value = { ...selectedOrder.value, payment_status: newPaymentStatus };
        }
      } catch (err) {
        alert(err.response?.data?.message || 'Не вдалося оновити статус оплати');
      }
    };

    const viewOrderDetails = (order) => { selectedOrder.value = order; };

    return {
      orders, loading, searchQuery, statusFilter, selectedOrder,
      filteredOrders, getStatusClass, getPaymentStatusClass,
      updateOrderStatus, updatePaymentStatus, viewOrderDetails
    };
  }
};
</script>

<style scoped>
.admin-orders {
  min-height: 100vh;
  padding: 2rem 0;
}

.admin-header {
  text-align: left;
}

.admin-subtitle {
  color: var(--text-secondary);
  font-size: 1.125rem;
}

.filters-section {
  box-shadow: var(--shadow-md);
}

.search-bar {
  max-width: 400px;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.filter-group label {
  margin: 0;
  font-weight: 600;
}

.filter-group select {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
}

.status-select {
  padding: 0.375rem 0.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
}

.status-delivered  { background-color: #d1fae5; color: #065f46; }
.status-shipped    { background-color: #dbeafe; color: #1e40af; }
.status-processing { background-color: #fef3c7; color: #92400e; }
.status-cancelled  { background-color: #fee2e2; color: #991b1b; }

/* Payment column */
.payment-cell {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 130px;
}

.payment-method-label {
  font-size: 0.85rem;
  color: var(--text-primary);
  white-space: nowrap;
}

.payment-badge {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  border-radius: var(--radius-sm);
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
}

.payment-paid    { background-color: #d1fae5; color: #065f46; }
.payment-pending { background-color: #fef3c7; color: #92400e; }

/* Modal payment status row */
.payment-status-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 0.5rem;
}

.payment-status-select {
  padding: 0.25rem 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  cursor: pointer;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: var(--bg-primary);
  border-radius: var(--radius-lg);
  padding: 2rem;
  max-width: 800px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-large { max-width: 900px; }

.modal-header {
  margin-bottom: 1.5rem;
}

.modal-header h2 {
  font-size: 1.5rem;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--text-secondary);
}

.order-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.order-info-grid h4 {
  font-size: 1.125rem;
  margin-bottom: 1rem;
  color: var(--text-primary);
}

.order-info-grid p {
  margin-bottom: 0.5rem;
  color: var(--text-secondary);
}

.items-table {
  width: 100%;
  margin-top: 1rem;
}

.items-table th,
.items-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--border-color);
}

.items-table th { background-color: var(--bg-tertiary); font-weight: 600; }
.items-table tfoot { background-color: var(--bg-tertiary); font-weight: 700; }

.modal-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 2rem;
}

@media (max-width: 768px) {
  .admin-table { overflow-x: auto; }
  .order-info-grid { grid-template-columns: 1fr; }
}
</style>
