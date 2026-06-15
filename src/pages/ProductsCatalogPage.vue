<template>
  <div class="products-catalog-page page">
    <div class="container">
      <!-- Page Header -->
      <div class="page-header text-center">
        <h1 class="page-title">Всі товари</h1>
        <p class="page-subtitle">Перегляньте наш повний асортимент комп'ютерного заліза</p>
      </div>

      <!-- Search and Filters -->
      <div class="filters-section mb-4">
        <SearchBar 
          v-model="searchQuery" 
          @search="handleSearch"
          placeholder="Пошук товарів за назвою..."
        />
        
        <CategoryFilter 
          v-model="selectedCategory" 
          title="Фільтр за категорією"
        />
        
        <div class="sort-controls flex-between">
          <div class="results-count">
            Показано {{ filteredProducts.length }} з {{ products.length }} товарів
          </div>
          
          <div class="sort-dropdown">
            <label for="sort">Сортування:</label>
            <select id="sort" v-model="sortBy" @change="sortProducts">
              <option value="default">За замовчуванням</option>
              <option value="price-asc">Ціна: від низької до високої</option>
              <option value="price-desc">Ціна: від високої до низької</option>
              <option value="name-asc">Назва: А до Я</option>
              <option value="rating-desc">Рейтинг: від високого</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="empty-state">
        <div class="empty-state-icon">⏳</div>
        <p class="empty-state-text">Завантаження товарів...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="empty-state">
        <div class="empty-state-icon">⚠️</div>
        <h3 class="empty-state-title">Щось пішло не так</h3>
        <p class="empty-state-text">{{ error }}</p>
        <button class="btn btn-primary" @click="fetchProducts(selectedCategory)">Спробувати знову</button>
      </div>

      <!-- Products Grid -->
      <div v-else-if="paginatedProducts.length > 0" class="products-grid grid grid-3">
        <ProductCard 
          v-for="product in paginatedProducts" 
          :key="product.id"
          :product="product"
        />
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading && !error" class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <h3 class="empty-state-title">Товарів не знайдено</h3>
        <p class="empty-state-text">Спробуйте змінити пошук або фільтри</p>
        <button class="btn btn-primary" @click="clearFilters">Скинути фільтри</button>
      </div>

      <!-- Pagination -->
      <Pagination 
        v-model="currentPage" 
        :total-items="filteredProducts.length"
        :items-per-page="itemsPerPage"
      />
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ProductCard from '../components/ProductCard.vue';
import SearchBar from '../components/SearchBar.vue';
import CategoryFilter from '../components/CategoryFilter.vue';
import Pagination from '../components/Pagination.vue';
import { api } from '../services/api';

export default {
  name: 'ProductsCatalogPage',
  components: {
    ProductCard,
    SearchBar,
    CategoryFilter,
    Pagination
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    
    // State
    const products = ref([]);
    const loading = ref(false);
    const error = ref('');
    const searchQuery = ref(route.query.search || '');
    const selectedCategory = ref(route.query.category || '');
    const sortBy = ref('default');
    const currentPage = ref(1);
    const itemsPerPage = ref(9);

    const fetchProducts = async (category) => {
      loading.value = true;
      error.value = '';
      try {
        const res = await api.products.getAll(category || '');
        products.value = res.data.data ?? res.data;
      } catch (err) {
        error.value = err.response?.data?.message || 'Не вдалося завантажити товари. Спробуйте ще раз.';
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => fetchProducts(selectedCategory.value));

    // Re-fetch when category filter changes
    watch(selectedCategory, (cat) => {
      currentPage.value = 1;
      fetchProducts(cat);
    });
    
    // Filter products based on search (category filtering is done server-side)
    const filteredProducts = computed(() => {
      let result = [...products.value];
      
      // Filter by search query (client-side)
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(p => 
          p.name.toLowerCase().includes(query) ||
          (p.description || '').toLowerCase().includes(query)
        );
      }
      
      // Sort products
      result = sortProductsByCriteria(result, sortBy.value);
      
      return result;
    });
    
    // Paginate products
    const paginatedProducts = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value;
      const end = start + itemsPerPage.value;
      return filteredProducts.value.slice(start, end);
    });
    
    // Sort function
    function sortProductsByCriteria(productsList, criteria) {
      switch(criteria) {
        case 'price-asc':
          return [...productsList].sort((a, b) => a.price - b.price);
        case 'price-desc':
          return [...productsList].sort((a, b) => b.price - a.price);
        case 'name-asc':
          return [...productsList].sort((a, b) => a.name.localeCompare(b.name));
        case 'rating-desc':
          return [...productsList].sort((a, b) => (b.rating || 0) - (a.rating || 0));
        default:
          return productsList;
      }
    }
    
    // Methods
    const handleSearch = () => {
      updateRoute();
    };
    
    const sortProducts = () => {
      currentPage.value = 1; // Reset to first page when sorting changes
    };
    
    const clearFilters = () => {
      searchQuery.value = '';
      selectedCategory.value = '';
      sortBy.value = 'default';
      currentPage.value = 1;
      updateRoute();
    };
    
    const updateRoute = () => {
      const query = {};
      
      if (searchQuery.value) {
        query.search = searchQuery.value;
      }
      
      if (selectedCategory.value) {
        query.category = selectedCategory.value;
      }
      
      router.push({ query });
    };
    
    // Watch for route changes
    watch(() => route.query, (newQuery) => {
      if (newQuery.search !== undefined) {
        searchQuery.value = newQuery.search || '';
      }
      if (newQuery.category !== undefined) {
        selectedCategory.value = newQuery.category || '';
      }
    }, { immediate: true });
    
    // Reset page when search changes
    watch(searchQuery, () => {
      currentPage.value = 1;
    });
    
    return {
      products,
      loading,
      error,
      fetchProducts,
      searchQuery,
      selectedCategory,
      sortBy,
      currentPage,
      itemsPerPage,
      filteredProducts,
      paginatedProducts,
      handleSearch,
      sortProducts,
      clearFilters
    };
  }
};
</script>

<style scoped>
.products-catalog-page {
  min-height: 100vh;
}

.page-header {
  margin-bottom: 2rem;
}

.filters-section {
  background-color: var(--bg-primary);
  padding: 2rem;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.sort-controls {
  align-items: center;
}

.results-count {
  color: var(--text-secondary);
  font-weight: 500;
}

.sort-dropdown {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.sort-dropdown label {
  margin: 0;
  font-weight: 600;
  color: var(--text-primary);
}

.sort-dropdown select {
  max-width: 200px;
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background-color: var(--bg-primary);
  cursor: pointer;
}

.products-grid {
  margin-top: 2rem;
}

@media (max-width: 768px) {
  .filters-section {
    padding: 1.5rem;
  }
  
  .sort-controls {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .sort-dropdown {
    flex-direction: column;
    align-items: stretch;
  }
  
  .sort-dropdown select {
    max-width: 100%;
  }
}
</style>
