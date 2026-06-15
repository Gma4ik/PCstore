<template>
  <div class="category-filter">
    <h3 class="filter-title">{{ title }}</h3>
    <div class="category-pills">
      <button
        v-for="category in categories"
        :key="category.id"
        :class="['category-pill', { active: modelValue === category.id }]"
        @click="$emit('update:modelValue', category.id)"
      >
        <span class="category-icon">{{ category.icon }}</span>
        <span class="category-name">{{ category.name }}</span>
      </button>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { api } from '../services/api';

export default {
  name: 'CategoryFilter',
  props: {
    modelValue: { type: String, default: '' },
    title: { type: String, default: 'Категорії' }
  },
  emits: ['update:modelValue'],
  setup() {
    const categories = ref([]);
    onMounted(async () => {
      const res = await api.categories.getAll();
      categories.value = res.data.data ?? [];
    });
    return { categories };
  }
};
</script>

<style scoped>
.category-filter {
  background-color: var(--bg-primary);
  padding: 1.5rem;
  border-radius: var(--radius-lg);
  margin-bottom: 2rem;
}

.filter-title {
  font-weight: 600;
  margin-bottom: 1rem;
  color: var(--text-primary);
}

.category-pills {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.category-pill {
  padding: 0.5rem 1rem;
  border: 2px solid var(--border-color);
  background-color: var(--bg-primary);
  border-radius: var(--radius-full, 9999px);
  cursor: pointer;
  transition: var(--transition);
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-primary);
}

.category-pill:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.category-pill.active {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.category-icon {
  font-size: 1.125rem;
}

@media (max-width: 768px) {
  .category-pills {
    gap: 0.5rem;
  }
  
  .category-pill {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
  }
}
</style>
