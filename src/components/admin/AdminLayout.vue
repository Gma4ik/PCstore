<template>
  <div class="admin-layout">
    <AdminSidebar />
    <main class="admin-content">
      <RouterView />
    </main>
  </div>
</template>

<script>
import AdminSidebar from './AdminSidebar.vue';
import { RouterView } from 'vue-router';

export default {
  name: 'AdminLayout',
  components: {
    AdminSidebar,
    RouterView
  }
};
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
}

.admin-content {
  margin-left: 260px;
  flex: 1;
  background-color: var(--bg-secondary);
  min-height: 100vh;
}

@media (max-width: 768px) {
  .admin-content {
    margin-left: 0;
  }
}
</style>
