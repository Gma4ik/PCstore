<template>
  <div id="app">
    <Header v-if="!isAdminRoute" />
    
    <main class="main-content">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    
    <Footer v-if="!isAdminRoute" />
  </div>
</template>

<script>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import Header from './components/Header.vue';
import Footer from './components/Footer.vue';

export default {
  name: 'App',
  components: { Header, Footer },
  setup() {
    const route = useRoute();
    const isAdminRoute = computed(() => route.path.startsWith('/admin'));
    return { isAdminRoute };
  }
};
</script>

<style>
/* App-level styles */
#app {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-content {
  flex: 1;
  width: 100%;
}

/* Page transition animations */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}

/* Remove default button styles */
button {
  font-family: inherit;
}

/* Focus styles for accessibility */
*:focus-visible {
  outline: 2px solid var(--primary-color);
  outline-offset: 2px;
}

/* Selection color */
::selection {
  background-color: var(--primary-color);
  color: white;
}

::-moz-selection {
  background-color: var(--primary-color);
  color: white;
}

/* Scrollbar styling for Webkit browsers */
::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}

::-webkit-scrollbar-track {
  background: var(--bg-secondary);
}

::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: var(--radius-md);
}

::-webkit-scrollbar-thumb:hover {
  background: var(--text-light);
}
</style>
