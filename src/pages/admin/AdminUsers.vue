<template>
  <div class="admin-users page">
    <div class="container">
      <div class="admin-header mb-4">
        <h1 class="page-title">Управління користувачами</h1>
        <p class="admin-subtitle">Перегляд та управління акаунтами клієнтів</p>
      </div>

      <div class="filters-section card mb-4">
        <div class="card-body">
          <div class="search-bar">
            <span class="search-icon">🔍</span>
            <input type="text" class="search-input"
              placeholder="Пошук за ім'ям або email..."
              v-model="searchQuery" />
          </div>
        </div>
      </div>

      <div class="users-table card">
        <div class="card-body">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Користувач</th>
                <th>Email</th>
                <th>Дата реєстрації</th>
                <th>Роль</th>
                <th>Дії</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredUsers" :key="user.id">
                <td>
                  <div class="user-cell">
                    <div class="user-avatar-small">{{ getInitials(user.name) }}</div>
                    <strong>{{ user.name }}</strong>
                  </div>
                </td>
                <td>{{ user.email }}</td>
                <td>{{ user.created_at?.split('T')[0] || '-' }}</td>
                <td>
                  <span :class="['badge', getRoleBadge(user.role)]">{{ translateRole(user.role) }}</span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="viewUserDetails(user)" class="btn-icon" title="Деталі">👁️</button>
                    <template v-if="isSuperAdmin && user.role !== 'superadmin'">
                      <button v-if="user.role === 'user'" @click="changeRole(user, 'admin')"
                        class="btn btn-sm btn-primary">⬆ Адмін</button>
                      <button v-else-if="user.role === 'admin'" @click="changeRole(user, 'user')"
                        class="btn btn-sm btn-danger">⬇ Зняти</button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- User Details Modal -->
      <div v-if="selectedUser" class="modal-overlay" @click="selectedUser = null">
        <div class="modal-content" @click.stop>
          <div class="modal-header flex-between">
            <h2>Деталі користувача</h2>
            <button @click="selectedUser = null" class="btn-close">✕</button>
          </div>
          <div class="user-details">
            <div class="user-header-detail text-center mb-3">
              <div class="user-avatar-large">{{ getInitials(selectedUser.name) }}</div>
              <h3>{{ selectedUser.name }}</h3>
              <p class="user-email-detail">{{ selectedUser.email }}</p>
            </div>
            <div class="user-info-section mb-3">
              <h4>Інформація про акаунт</h4>
              <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ selectedUser.email }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Роль:</span>
                <span class="info-value">{{ translateRole(selectedUser.role) }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Дата реєстрації:</span>
                <span class="info-value">{{ selectedUser.created_at?.split('T')[0] || '-' }}</span>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="selectedUser = null" class="btn btn-outline">Закрити</button>
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
  name: 'AdminUsers',
  setup() {
    const searchQuery = ref('');
    const selectedUser = ref(null);
    const usersList = ref([]);

    const isSuperAdmin = computed(() => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return false;
        const payload = JSON.parse(atob(token.split('.')[1]));
        return payload.role === 'superadmin';
      } catch {
        return false;
      }
    });

    onMounted(async () => {
      try {
        const res = await adminApi.users.getAll();
        usersList.value = res.data.data ?? [];
      } catch {}
    });

    const filteredUsers = computed(() => {
      if (!searchQuery.value) return usersList.value;
      const q = searchQuery.value.toLowerCase();
      return usersList.value.filter(
        (u) => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
      );
    });

    const getInitials = (name) =>
      (name ?? 'U').split(' ').map((n) => n[0]).join('').toUpperCase();

    const translateRole = (role) => {
      switch (role) {
        case 'superadmin': return 'Супер адмін';
        case 'admin':      return 'Адмін';
        default:           return 'Користувач';
      }
    };

    const getRoleBadge = (role) => {
      switch (role) {
        case 'superadmin': return 'badge-danger';
        case 'admin':      return 'badge-primary';
        default:           return 'badge-success';
      }
    };

    const changeRole = async (user, newRole) => {
      const label = newRole === 'admin' ? 'призначити адміном' : 'зняти права адміна';
      if (!confirm(`Ви впевнені, що хочете ${label} для ${user.name}?`)) return;
      try {
        const res = await adminApi.users.updateRole(user.id, newRole);
        const updated = res.data.data;
        const idx = usersList.value.findIndex((u) => u.id === user.id);
        if (idx !== -1) usersList.value[idx] = { ...usersList.value[idx], ...updated };
      } catch (err) {
        alert(err.response?.data?.message || 'Не вдалося змінити роль');
      }
    };

    const viewUserDetails = (user) => { selectedUser.value = user; };

    return {
      searchQuery, selectedUser, filteredUsers, isSuperAdmin,
      getInitials, translateRole, getRoleBadge, changeRole, viewUserDetails,
    };
  },
};
</script>

<style scoped>
.admin-users { min-height: 100vh; padding: 2rem 0; }
.admin-subtitle { color: var(--text-secondary); font-size: 1.125rem; }
.user-cell { display: flex; align-items: center; gap: 0.75rem; }
.user-avatar-small {
  width: 40px; height: 40px; border-radius: 50%;
  background-color: var(--primary-color); color: white;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 0.875rem;
}
.action-buttons { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
.btn-icon { background: none; border: none; cursor: pointer; font-size: 1.25rem; padding: 0.25rem; transition: var(--transition); }
.btn-icon:hover { transform: scale(1.2); }
.btn-danger { background-color: #ef4444; color: white; border: none; }
.btn-danger:hover { opacity: 0.85; }
.badge { padding: 0.25rem 0.6rem; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 600; }
.badge-success { background-color: #d1fae5; color: #065f46; }
.badge-primary { background-color: #dbeafe; color: #1e40af; }
.badge-danger  { background-color: #fee2e2; color: #991b1b; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background-color: var(--bg-primary); border-radius: var(--radius-lg); padding: 2rem; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
.modal-header { margin-bottom: 1.5rem; }
.modal-header h2 { font-size: 1.5rem; margin: 0; }
.btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
.user-header-detail { padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); }
.user-avatar-large { width: 100px; height: 100px; border-radius: 50%; background-color: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; margin: 0 auto 1rem; }
.user-header-detail h3 { font-size: 1.5rem; margin: 0.5rem 0; color: var(--text-primary); }
.user-email-detail { color: var(--text-secondary); margin: 0; }
.user-info-section h4 { font-size: 1.125rem; margin-bottom: 1rem; color: var(--text-primary); }
.info-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color); }
.info-row:last-child { border-bottom: none; }
.info-label { color: var(--text-secondary); font-weight: 600; }
.info-value { color: var(--text-primary); }
.modal-footer { display: flex; justify-content: flex-end; margin-top: 1.5rem; }
@media (max-width: 768px) { .admin-table { overflow-x: auto; } }
</style>
