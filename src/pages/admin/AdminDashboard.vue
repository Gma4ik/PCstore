<template>
  <div class="admin-dashboard page">
    <div class="container">
      <div class="admin-header mb-4">
        <h1 class="page-title">Панель адміністратора</h1>
        <p class="admin-subtitle">Керуйте магазином та переглядайте статистику</p>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid mb-4">
        <div class="stat-card card">
          <div class="card-body">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
              <h3 class="stat-value">{{ totalProducts }}</h3>
              <p class="stat-label">Всього товарів</p>
            </div>
          </div>
        </div>
        <div class="stat-card card">
          <div class="card-body">
            <div class="stat-icon">🛒</div>
            <div class="stat-info">
              <h3 class="stat-value">{{ totalOrders }}</h3>
              <p class="stat-label">Всього замовлень</p>
            </div>
          </div>
        </div>
        <div class="stat-card card">
          <div class="card-body">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
              <h3 class="stat-value">{{ totalUsers }}</h3>
              <p class="stat-label">Всього користувачів</p>
            </div>
          </div>
        </div>
        <div class="stat-card card">
          <div class="card-body">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
              <h3 class="stat-value">₴{{ totalRevenue.toFixed(2) }}</h3>
              <p class="stat-label">Загальний дохід</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="charts-row mb-4">
        <!-- Sales by Month Bar Chart -->
        <div class="chart-card card">
          <div class="card-body chart-body">
            <h2 class="section-title">Продажі по місяцях</h2>
            <div v-if="statsLoading" class="chart-placeholder">Завантаження...</div>
            <Bar v-else-if="salesChartData" :data="salesChartData" :options="salesChartOptions" />
            <div v-else class="chart-placeholder">Немає даних</div>
          </div>
        </div>

        <!-- Orders by Status Doughnut Chart -->
        <div class="chart-card card">
          <div class="card-body chart-body">
            <h2 class="section-title">Замовлення по статусах</h2>
            <div v-if="statsLoading" class="chart-placeholder">Завантаження...</div>
            <div v-else-if="statusChartData" class="doughnut-wrapper">
              <Doughnut :data="statusChartData" :options="statusChartOptions" />
            </div>
            <div v-else class="chart-placeholder">Немає даних</div>
          </div>
        </div>
      </div>

      <!-- Top Products Table -->
      <div class="top-products-section mb-4">
        <h2 class="section-title">Топ-5 товарів за продажами</h2>
        <div class="card">
          <div class="card-body p-0">
            <div v-if="statsLoading" class="chart-placeholder">Завантаження...</div>
            <table v-else-if="topProducts.length" class="admin-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Назва товару</th>
                  <th>Продано (шт.)</th>
                  <th>Дохід</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(product, index) in topProducts" :key="product.product_id">
                  <td>{{ index + 1 }}</td>
                  <td>{{ product.name }}</td>
                  <td>{{ product.sold }}</td>
                  <td>₴{{ Number(product.revenue).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
            <div v-else class="chart-placeholder">Немає даних</div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions-section mb-4">
        <h2 class="section-title">Швидкі дії</h2>
        <div class="quick-actions-grid">
          <router-link to="/admin/products" class="action-card card">
            <div class="card-body text-center">
              <div class="action-icon">📝</div>
              <h3>Управління товарами</h3>
              <p>Додати, редагувати або видалити товари</p>
            </div>
          </router-link>
          <router-link to="/admin/orders" class="action-card card">
            <div class="card-body text-center">
              <div class="action-icon">📋</div>
              <h3>Управління замовленнями</h3>
              <p>Переглянути та обробити замовлення</p>
            </div>
          </router-link>
          <router-link to="/admin/users" class="action-card card">
            <div class="card-body text-center">
              <div class="action-icon">👥</div>
              <h3>Управління користувачами</h3>
              <p>Переглянути та керувати клієнтами</p>
            </div>
          </router-link>
          <router-link to="/" class="action-card card">
            <div class="card-body text-center">
              <div class="action-icon">🏪</div>
              <h3>Переглянути магазин</h3>
              <p>Перейти до вітрини магазину</p>
            </div>
          </router-link>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="recent-orders-section">
        <div class="section-header flex-between mb-3">
          <h2 class="section-title">Останні замовлення</h2>
          <router-link to="/admin/orders" class="btn btn-outline btn-sm">Переглянути всі</router-link>
        </div>
        <div class="orders-table card">
          <div class="card-body">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID замовлення</th>
                  <th>Дата</th>
                  <th>Клієнт</th>
                  <th>Сума</th>
                  <th>Статус</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in recentOrders" :key="order.id">
                  <td><strong>{{ order.id }}</strong></td>
                  <td>{{ order.date || order.created_at }}</td>
                  <td>{{ order.customer_info?.name || order.customerInfo?.name || 'Гість' }}</td>
                  <td>₴{{ Number(order.total).toFixed(2) }}</td>
                  <td>
                    <span :class="['badge', getStatusClass(order.status)]">{{ translateStatus(order.status) }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, ref } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';
import { adminApi } from '../../services/api';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

export default {
  name: 'AdminDashboard',
  components: { Bar, Doughnut },
  setup() {
    const allOrders    = ref([]);
    const totalProducts = ref(0);
    const totalUsers   = ref(0);

    const statsLoading   = ref(false);
    const topProducts    = ref([]);
    const salesChartData = ref(null);
    const statusChartData = ref(null);

    const salesChartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { callback: (v) => '₴' + v.toLocaleString() } }
      }
    };

    const statusChartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const item = ctx.dataset.rawData?.[ctx.dataIndex];
              const pct = item ? ` (${Number(item.percent).toFixed(1)}%)` : '';
              return ` ${ctx.label}: ${ctx.parsed}${pct}`;
            }
          }
        }
      }
    };

    const STATUS_COLORS = {
      Processing: '#fbbf24', Shipped: '#60a5fa',
      Delivered: '#34d399',  Cancelled: '#f87171'
    };

    async function loadStats() {
      statsLoading.value = true;
      try {
        const res  = await adminApi.stats.getStats();
        const data = res.data.data ?? res.data;

        // Use totals from stats endpoint if available
        if (data.totals) {
          totalProducts.value = data.totals.products ?? totalProducts.value;
          totalUsers.value    = data.totals.users    ?? totalUsers.value;
          allOrders.value     = { length: data.totals.orders };
          // For revenue we still need the array, so keep a separate ref
          totalRevenueOverride.value = data.totals.revenue ?? null;
        }

        const salesByMonth = data.sales_by_month ?? [];
        salesChartData.value = {
          labels: salesByMonth.map(m => m.month),
          datasets: [{ label: 'Продажі (₴)', data: salesByMonth.map(m => Number(m.total)), backgroundColor: '#6366f1', borderRadius: 4 }]
        };

        topProducts.value = data.top_products ?? [];

        const byStatus = data.orders_by_status ?? [];
        statusChartData.value = {
          labels: byStatus.map(s => s.status),
          datasets: [{
            data: byStatus.map(s => Number(s.count)),
            rawData: byStatus,
            backgroundColor: byStatus.map(s => STATUS_COLORS[s.status] ?? '#a78bfa'),
            borderWidth: 2
          }]
        };
      } catch {
        // silently skip if stats unavailable
      } finally {
        statsLoading.value = false;
      }
    }

    const totalRevenueOverride = ref(null);

    onMounted(async () => {
      try {
        const res = await adminApi.orders.getAll();
        allOrders.value = res.data.data ?? [];
      } catch {}
      await loadStats();
    });

    const totalOrders  = computed(() => Array.isArray(allOrders.value) ? allOrders.value.length : (allOrders.value?.length ?? 0));
    const totalRevenue = computed(() =>
      totalRevenueOverride.value !== null
        ? totalRevenueOverride.value
        : (Array.isArray(allOrders.value) ? allOrders.value.reduce((s, o) => s + Number(o.total), 0) : 0)
    );
    const recentOrders = computed(() => Array.isArray(allOrders.value) ? allOrders.value.slice(0, 5) : []);

    const getStatusClass = (status) => {
      switch (status?.toLowerCase()) {
        case 'delivered':  return 'badge-success';
        case 'shipped':    return 'badge-primary';
        case 'processing': return 'badge-warning';
        case 'cancelled':  return 'badge-danger';
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

    return {
      totalProducts, totalOrders, totalUsers, totalRevenue, recentOrders,
      getStatusClass, translateStatus,
      statsLoading, topProducts, salesChartData, statusChartData,
      salesChartOptions, statusChartOptions
    };
  }
};
</script>

<style scoped>
.admin-dashboard {
  min-height: 100vh;
  padding: 2rem 0;
}

.admin-header {
  text-align: center;
}

.admin-subtitle {
  color: var(--text-secondary);
  font-size: 1.125rem;
}

/* Stats cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
}

.stat-card { transition: var(--transition); }
.stat-card:hover { transform: translateY(-4px); }

.card-body {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  font-size: 3rem;
  background-color: var(--bg-tertiary);
  padding: 1rem;
  border-radius: var(--radius-lg);
}

.stat-info { flex: 1; }

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary-color);
  margin: 0;
}

.stat-label {
  color: var(--text-secondary);
  margin: 0;
  font-size: 0.875rem;
}

/* Charts */
.charts-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
}

.chart-card .card-body {
  display: block;
}

.chart-body {
  padding: 1.5rem;
}

.chart-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 260px;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

/* Bar chart needs a fixed height container */
.chart-body canvas {
  max-height: 260px;
}

.doughnut-wrapper {
  height: 260px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Top products */
.top-products-section .card-body {
  display: block;
  padding: 0;
}

.p-0 { padding: 0 !important; }

/* Section title */
.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: var(--text-primary);
}

/* Quick actions */
.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
}

.action-card {
  text-decoration: none;
  color: inherit;
  transition: var(--transition);
}

.action-card:hover { transform: translateY(-8px); }

.action-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

.action-card h3 {
  font-size: 1.125rem;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
}

.action-card p {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin: 0;
}

/* Table */
.admin-table { width: 100%; }

.admin-table th,
.admin-table td {
  padding: 1rem;
  text-align: left;
}

.admin-table th {
  background-color: var(--bg-tertiary);
  font-weight: 600;
  color: var(--text-primary);
}

.admin-table tbody tr { border-bottom: 1px solid var(--border-color); }
.admin-table tbody tr:hover { background-color: var(--bg-secondary); }

/* Badges */
.badge {
  padding: 0.375rem 0.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-success  { background-color: #d1fae5; color: #065f46; }
.badge-primary  { background-color: #dbeafe; color: #1e40af; }
.badge-warning  { background-color: #fef3c7; color: #92400e; }
.badge-danger   { background-color: #fee2e2; color: #991b1b; }

@media (max-width: 1024px) {
  .stats-grid,
  .quick-actions-grid { grid-template-columns: repeat(2, 1fr); }
  .charts-row { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .stats-grid,
  .quick-actions-grid { grid-template-columns: 1fr; }
}
</style>
