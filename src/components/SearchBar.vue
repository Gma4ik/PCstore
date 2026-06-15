<template>
  <div class="search-bar" :class="{ 'search-bar-large': large }">
    <span class="search-icon">🔍</span>
    <input 
      type="text" 
      class="search-input" 
      :placeholder="placeholder"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      @keyup.enter="handleSearch"
    />
    <button v-if="showClear && modelValue" class="clear-btn" @click="clearSearch">
      ✕
    </button>
  </div>
</template>

<script>
export default {
  name: 'SearchBar',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: 'Пошук товарів...'
    },
    large: {
      type: Boolean,
      default: false
    },
    showClear: {
      type: Boolean,
      default: true
    }
  },
  emits: ['update:modelValue', 'search'],
  setup(props, { emit }) {
    const handleSearch = () => {
      emit('search', props.modelValue);
    };
    
    const clearSearch = () => {
      emit('update:modelValue', '');
      emit('search', '');
    };
    
    return {
      handleSearch,
      clearSearch
    };
  }
};
</script>

<style scoped>
.search-bar {
  position: relative;
  max-width: 600px;
}

.search-bar-large {
  max-width: 800px;
}

.search-input {
  width: 100%;
  padding: 0.75rem 3rem 0.75rem 3rem;
  border: 2px solid var(--border-color);
  border-radius: var(--radius-lg);
  font-size: 1rem;
  transition: var(--transition);
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-light);
  pointer-events: none;
  font-size: 1.25rem;
}

.clear-btn {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--text-light);
  cursor: pointer;
  font-size: 1.25rem;
  padding: 0.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}

.clear-btn:hover {
  color: var(--danger-color);
}
</style>
