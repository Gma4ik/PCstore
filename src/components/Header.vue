<template>
  <header class="header">
    <div class="container">
      <div class="header-content">
        <!-- Logo -->
        <router-link to="/" class="logo">
          <div class="logo-icon-wrap">🖥️</div>
          <span class="logo-text">Tech<span class="logo-accent">Store</span></span>
        </router-link>

        <!-- Search Bar - Desktop -->
        <div class="search-wrap hidden-mobile">
          <span class="search-icon">🔍</span>
          <input
            type="text"
            class="search-input"
            placeholder="Пошук товарів..."
            v-model="searchQuery"
            @keyup.enter="handleSearch"
          />
          <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''">✕</button>
        </div>

        <!-- Navigation -->
        <nav class="nav-menu hidden-mobile">
          <router-link to="/products" class="nav-link">Каталог</router-link>
          <router-link v-if="isAuthenticated" to="/wishlist" class="nav-link">
            <span>🤍</span>
            <span>Бажання</span>
          </router-link>
          <router-link to="/cart" class="nav-link cart-link">
            <span class="cart-icon">🛒</span>
            <span>Кошик</span>
            <span v-if="cartCount > 0" class="cart-badge">{{ cartCount > 99 ? '99+' : cartCount }}</span>
          </router-link>
        </nav>

        <!-- User Menu -->
        <div class="user-menu hidden-mobile">
          <template v-if="isAuthenticated">
            <router-link to="/profile" class="user-avatar-btn" :title="userName">
              <div class="user-avatar">{{ userInitial }}</div>
            </router-link>
            <button @click="handleLogout" class="btn btn-outline btn-sm">Вийти</button>
          </template>
          <template v-else>
            <router-link to="/login" class="btn btn-outline btn-sm">Увійти</router-link>
            <router-link to="/register" class="btn btn-primary btn-sm">Реєстрація</router-link>
          </template>
        </div>

        <!-- Mobile right side -->
        <div class="mobile-right visible-mobile">
          <router-link to="/cart" class="mobile-cart-btn">
            <span>🛒</span>
            <span v-if="cartCount > 0" class="cart-badge">{{ cartCount }}</span>
          </router-link>
          <button class="mobile-menu-toggle" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen">
            <span class="burger" :class="{ open: mobileOpen }">
              <span></span><span></span><span></span>
            </span>
          </button>
        </div>
      </div>

      <!-- Mobile Search -->
      <div class="mobile-search visible-mobile">
        <span class="search-icon">🔍</span>
        <input
          type="text"
          class="search-input"
          placeholder="Пошук товарів..."
          v-model="searchQuery"
          @keyup.enter="handleSearch"
        />
      </div>

      <!-- Mobile Menu Dropdown -->
      <div class="mobile-menu" :class="{ open: mobileOpen }">
        <router-link to="/products" class="mobile-nav-link" @click="mobileOpen = false">📦 Каталог</router-link>
        <router-link to="/cart" class="mobile-nav-link" @click="mobileOpen = false">🛒 Кошик <span v-if="cartCount > 0" class="cart-badge">{{ cartCount }}</span></router-link>
        <template v-if="isAuthenticated">
          <router-link to="/wishlist" class="mobile-nav-link" @click="mobileOpen = false">🤍 Бажання</router-link>
          <router-link to="/profile" class="mobile-nav-link" @click="mobileOpen = false">👤 Профіль</router-link>
          <button class="mobile-nav-link mobile-logout" @click="handleLogout">🚪 Вийти</button>
        </template>
        <template v-else>
          <router-link to="/login" class="mobile-nav-link" @click="mobileOpen = false">🔑 Увійти</router-link>
          <router-link to="/register" class="mobile-nav-link" @click="mobileOpen = false">✨ Реєстрація</router-link>
        </template>
      </div>
    </div>
  </header>
</template>

<script>
import { computed, ref } from "vue";
import { useRouter } from "vue-router";
import { useCartStore, useUserStore } from "../store";

export default {
  name: "Header",
  setup() {
    const router = useRouter();
    const cartStore = useCartStore();
    const userStore = useUserStore();

    const searchQuery = ref("");
    const mobileOpen = ref(false);

    const cartCount = computed(() => cartStore.totalItems);
    const isAuthenticated = computed(() => userStore.isAuthenticated);
    const userName = computed(() => userStore.userName || "");
    const userInitial = computed(() => (userStore.userName || "U").charAt(0).toUpperCase());

    const handleSearch = () => {
      if (searchQuery.value.trim()) {
        router.push({ path: "/products", query: { search: searchQuery.value } });
        searchQuery.value = "";
        mobileOpen.value = false;
      }
    };

    const handleLogout = () => {
      userStore.logout();
      mobileOpen.value = false;
      router.push("/");
    };

    return {
      searchQuery, mobileOpen,
      cartCount, isAuthenticated, userName, userInitial,
      handleSearch, handleLogout,
    };
  },
};
</script>

<style scoped>
.header {
  background-color: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border-color);
  padding: 0.75rem 0;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 1px 12px rgba(0, 0, 0, 0.06);
}

.header-content {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

/* Logo */
.logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  flex-shrink: 0;
}

.logo-icon-wrap {
  font-size: 1.75rem;
  line-height: 1;
}

.logo-text {
  font-size: 1.375rem;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.02em;
}

.logo-accent {
  color: var(--primary-color);
}

/* Search */
.search-wrap {
  flex: 1;
  max-width: 420px;
  position: relative;
  display: flex;
  align-items: center;
}

.search-wrap .search-icon {
  position: absolute;
  left: 0.875rem;
  font-size: 0.9rem;
  pointer-events: none;
  z-index: 1;
}

.search-input {
  width: 100%;
  padding: 0.55rem 2.5rem 0.55rem 2.5rem;
  font-size: 0.9rem;
  border: 1.5px solid var(--border-color);
  border-radius: 999px;
  background: var(--bg-secondary);
  color: var(--text-primary);
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  background: var(--bg-primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-clear {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-secondary);
  font-size: 0.8rem;
  padding: 0.2rem;
  line-height: 1;
}

/* Nav */
.nav-menu {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.5rem 0.875rem;
  border-radius: var(--radius-md);
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--text-secondary);
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
  position: relative;
}

.nav-link:hover {
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.nav-link.router-link-active {
  color: var(--primary-color);
  background: rgba(37, 99, 235, 0.07);
}

.cart-link {
  position: relative;
}

.cart-badge {
  background: var(--danger-color);
  color: white;
  font-size: 0.7rem;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
}

/* User menu */
.user-menu {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-shrink: 0;
  margin-left: auto;
}

.user-avatar-btn {
  text-decoration: none;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  transition: transform 0.15s, box-shadow 0.15s;
}

.user-avatar:hover {
  transform: scale(1.05);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}

/* Mobile */
.mobile-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: auto;
}

.mobile-cart-btn {
  position: relative;
  font-size: 1.4rem;
  text-decoration: none;
  padding: 0.25rem;
  line-height: 1;
}

.mobile-cart-btn .cart-badge {
  position: absolute;
  top: -4px;
  right: -6px;
}

.mobile-menu-toggle {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.4rem;
  display: flex;
  align-items: center;
}

.burger {
  display: flex;
  flex-direction: column;
  gap: 5px;
  width: 22px;
}

.burger span {
  display: block;
  height: 2px;
  background: var(--text-primary);
  border-radius: 2px;
  transition: transform 0.2s, opacity 0.2s;
  transform-origin: center;
}

.burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.burger.open span:nth-child(2) { opacity: 0; }
.burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

.mobile-search {
  margin-top: 0.625rem;
  position: relative;
}

.mobile-search .search-icon {
  position: absolute;
  left: 0.875rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

.mobile-search .search-input {
  padding-left: 2.5rem;
  border-radius: 999px;
  background: var(--bg-secondary);
}

.mobile-menu {
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.25s ease;
}

.mobile-menu.open {
  max-height: 400px;
}

.mobile-nav-link {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 0.5rem;
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--text-primary);
  text-decoration: none;
  border-bottom: 1px solid var(--border-color);
  transition: color 0.15s;
}

.mobile-nav-link:last-child {
  border-bottom: none;
}

.mobile-nav-link:hover {
  color: var(--primary-color);
}

.mobile-logout {
  background: none;
  border: none;
  cursor: pointer;
  width: 100%;
  text-align: left;
  color: var(--danger-color);
}
</style>
