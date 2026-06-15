<template>
  <aside class="admin-sidebar">
    <div class="sidebar-header">
      <router-link to="/admin" class="admin-logo">
        <span class="logo-icon">⚙️</span>
        <span class="logo-text">Адмін панель</span>
      </router-link>
    </div>
    
    <nav class="sidebar-nav">
      <router-link to="/admin" class="nav-item" exact-active-class="nav-item-active">
        <span class="nav-icon">📊</span>
        <span class="nav-text">Дашборд</span>
      </router-link>
      
      <router-link to="/admin/products" class="nav-item" active-class="nav-item-active">
        <span class="nav-icon">📦</span>
        <span class="nav-text">Товари</span>
      </router-link>
      
      <router-link to="/admin/orders" class="nav-item" active-class="nav-item-active">
        <span class="nav-icon">🛒</span>
        <span class="nav-text">Замовлення</span>
      </router-link>
      
      <router-link to="/admin/users" class="nav-item" active-class="nav-item-active">
        <span class="nav-icon">👥</span>
        <span class="nav-text">Користувачі</span>
      </router-link>

      <router-link to="/admin/categories" class="nav-item" active-class="nav-item-active">
        <span class="nav-icon">🏷️</span>
        <span class="nav-text">Категорії</span>
      </router-link>
      
      <div class="nav-divider"></div>
      
      <router-link to="/" class="nav-item">
        <span class="nav-icon">🏪</span>
        <span class="nav-text">До магазину</span>
      </router-link>
      
      <button @click="handleLogout" class="nav-item nav-button">
        <span class="nav-icon">🚪</span>
        <span class="nav-text">Вийти</span>
      </button>
    </nav>
    
    <div class="sidebar-footer">
      <div class="admin-user-info">
        <div class="user-avatar-small">{{ userInitial }}</div>
        <div class="user-info-text">
          <p class="user-name">{{ userName }}</p>
          <p class="user-role">Адміністратор</p>
        </div>
      </div>
    </div>
  </aside>
</template>

<script>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '../../store';

export default {
  name: 'AdminSidebar',
  setup() {
    const router = useRouter();
    const userStore = useUserStore();
    
    const userName = computed(() => userStore.userName || 'Admin');
    const userInitial = computed(() => {
      const name = userName.value;
      return name.charAt(0).toUpperCase();
    });
    
    const handleLogout = () => {
      userStore.logout();
      router.push('/login');
    };
    
    return {
      userName,
      userInitial,
      handleLogout
    };
  }
};
</script>

<style scoped>
.admin-sidebar {
  width: 260px;
  background-color: var(--secondary-color);
  color: white;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  z-index: 100;
}

.sidebar-header {
  padding: 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.25rem;
  font-weight: 700;
  color: white;
  text-decoration: none;
}

.logo-icon {
  font-size: 1.75rem;
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.5rem;
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  transition: var(--transition);
  border: none;
  background: none;
  cursor: pointer;
  font-size: 0.9375rem;
  width: 100%;
  text-align: left;
}

.nav-item:hover {
  background-color: rgba(255, 255, 255, 0.1);
  color: white;
}

.nav-item.nav-item-active {
  background-color: var(--primary-color);
  color: white;
}

.nav-button {
  margin-top: auto;
}

.nav-divider {
  height: 1px;
  background-color: rgba(255, 255, 255, 0.1);
  margin: 1rem 0;
}

.sidebar-footer {
  padding: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar-small {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.125rem;
}

.user-info-text {
  flex: 1;
}

.user-name {
  font-weight: 600;
  font-size: 0.875rem;
  margin: 0;
  color: white;
}

.user-role {
  font-size: 0.75rem;
  margin: 0;
  color: rgba(255, 255, 255, 0.5);
}

@media (max-width: 768px) {
  .admin-sidebar {
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }
  
  .admin-sidebar.mobile-open {
    transform: translateX(0);
  }
}
</style>
