<template>
  <div class="pagination" v-if="totalPages > 1">
    <button 
      class="pagination-btn" 
      @click="$emit('update:modelValue', 1)"
      :disabled="modelValue === 1"
    >
      Перша
    </button>
    
    <button 
      class="pagination-btn" 
      @click="$emit('update:modelValue', modelValue - 1)"
      :disabled="modelValue === 1"
    >
      Назад
    </button>
    
    <button
      v-for="page in visiblePages"
      :key="page"
      :class="['pagination-btn', { active: modelValue === page }]"
      @click="$emit('update:modelValue', page)"
    >
      {{ page }}
    </button>
    
    <button 
      class="pagination-btn" 
      @click="$emit('update:modelValue', modelValue + 1)"
      :disabled="modelValue === totalPages"
    >
      Вперед
    </button>
    
    <button 
      class="pagination-btn" 
      @click="$emit('update:modelValue', totalPages)"
      :disabled="modelValue === totalPages"
    >
      Остання
    </button>
    
    <span class="pagination-info">
      Сторінка {{ modelValue }} з {{ totalPages }}
    </span>
  </div>
</template>

<script>
import { computed } from 'vue';

export default {
  name: 'Pagination',
  props: {
    modelValue: {
      type: Number,
      required: true,
      default: 1
    },
    totalItems: {
      type: Number,
      required: true
    },
    itemsPerPage: {
      type: Number,
      default: 9
    }
  },
  emits: ['update:modelValue'],
  setup(props) {
    const totalPages = computed(() => Math.ceil(props.totalItems / props.itemsPerPage));
    
    const visiblePages = computed(() => {
      const pages = [];
      const current = props.modelValue;
      const total = totalPages.value;
      
      // Show first page
      if (current > 3) {
        pages.push(1);
        if (current > 4) pages.push('...');
      }
      
      // Show pages around current page
      for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
        pages.push(i);
      }
      
      // Show last page
      if (current < total - 2) {
        if (current < total - 3) pages.push('...');
        pages.push(total);
      }
      
      // If only one page or current is near start/end
      if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
      }
      
      return pages.filter((p, index, arr) => p === '...' || arr.indexOf(p) === index);
    });
    
    return {
      totalPages,
      visiblePages
    };
  }
};
</script>

<style scoped>
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
  flex-wrap: wrap;
}

.pagination-btn {
  min-width: 40px;
  height: 40px;
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-color);
  background-color: var(--bg-primary);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: var(--transition);
  font-weight: 500;
  color: var(--text-primary);
}

.pagination-btn:hover:not(:disabled) {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.pagination-btn.active {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background-color: var(--bg-tertiary);
}

.pagination-info {
  margin-left: 1rem;
  color: var(--text-secondary);
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .pagination-btn {
    min-width: 36px;
    height: 36px;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
  }
  
  .pagination-info {
    width: 100%;
    text-align: center;
    margin-left: 0;
    margin-top: 0.5rem;
  }
}
</style>
