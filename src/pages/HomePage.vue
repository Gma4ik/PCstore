<template>
  <div class="home-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">
            Преміум комп'ютерне залізо<br />для професіоналів та геймерів
          </h1>
          <p class="hero-subtitle">
            Відкрийте для себе найновіші процесори, відеокарти та аксесуари за найкращими цінами.
          </p>
          <div class="hero-actions">
            <router-link to="/products" class="btn btn-primary btn-lg">Купити зараз</router-link>
            <router-link to="/products?category=processors" class="btn btn-outline btn-lg">Переглянути акції</router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section py-4">
      <div class="container">
        <h2 class="section-title">Категорії товарів</h2>
        <div class="categories-grid grid grid-4">
          <router-link 
            v-for="category in displayedCategories" 
            :key="category.id"
            :to="`/products?category=${category.id}`"
            class="category-card card"
          >
            <div class="category-card-body card-body text-center">
              <div class="category-icon">{{ category.icon }}</div>
              <h3 class="category-name">{{ category.name }}</h3>
            </div>
          </router-link>
        </div>
      </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-section py-4">
      <div class="container">
        <div class="section-header flex-between">
          <h2 class="section-title">Популярні товари</h2>
          <router-link to="/products" class="btn btn-outline">Всі товари</router-link>
        </div>
        <div class="products-grid grid grid-4">
          <ProductCard 
            v-for="product in featuredProducts" 
            :key="product.id"
            :product="product"
          />
        </div>
      </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="features-section py-4">
      <div class="container">
        <div class="features-grid grid grid-4">
          <div class="feature-card text-center">
            <div class="feature-icon">🚚</div>
            <h3>Безкоштовна доставка</h3>
            <p>При замовленні від ₴500</p>
          </div>
          <div class="feature-card text-center">
            <div class="feature-icon">🔒</div>
            <h3>Безпечна оплата</h3>
            <p>100% захищені транзакції</p>
          </div>
          <div class="feature-card text-center">
            <div class="feature-icon">↩️</div>
            <h3>Легке повернення</h3>
            <p>Повернення протягом 30 днів</p>
          </div>
          <div class="feature-card text-center">
            <div class="feature-icon">💬</div>
            <h3>Підтримка 24/7</h3>
            <p>Завжди готові допомогти</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import ProductCard from '../components/ProductCard.vue';
import { api } from '../services/api';

export default {
  name: 'HomePage',
  components: { ProductCard },
  setup() {
    const featuredProducts = ref([]);
    const displayedCategories = ref([]);

    onMounted(async () => {
      const [prodRes, catRes] = await Promise.all([
        api.products.getAll(),
        api.categories.getAll()
      ]);
      featuredProducts.value = (prodRes.data.data ?? []).slice(0, 8);
      displayedCategories.value = (catRes.data.data ?? []).slice(0, 8);
    });

    return { featuredProducts, displayedCategories };
  }
};
</script>

<style scoped>
.hero-section {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
  color: white;
  padding: 5rem 0;
  margin-bottom: 3rem;
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.hero-title {
  font-size: 3rem;
  font-weight: 800;
  margin-bottom: 1rem;
  line-height: 1.2;
  color: white;
}

.hero-subtitle {
  font-size: 1.25rem;
  margin-bottom: 2rem;
  color: rgba(255, 255, 255, 0.9);
}

.hero-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.hero-actions .btn-outline {
  border-color: white;
  color: white;
}

.hero-actions .btn-outline:hover {
  background-color: white;
  color: var(--primary-color);
}

.section-title {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 2rem;
  text-align: center;
}

.section-header {
  margin-bottom: 2rem;
}

.categories-section,
.featured-section,
.features-section {
  padding-top: 3rem;
  padding-bottom: 3rem;
}

.category-card {
  transition: var(--transition);
}

.category-card:hover {
  transform: translateY(-8px);
}

.category-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.category-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.feature-card {
  padding: 2rem 1rem;
}

.feature-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.feature-card h3 {
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
}

.feature-card p {
  color: var(--text-secondary);
  margin: 0;
}

@media (max-width: 1024px) {
  .hero-title {
    font-size: 2.5rem;
  }
  
  .grid-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .hero-section {
    padding: 3rem 0;
  }
  
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-subtitle {
    font-size: 1rem;
  }
  
  .hero-actions {
    flex-direction: column;
  }
  
  .grid-4 {
    grid-template-columns: 1fr;
  }
  
  .section-title {
    font-size: 1.5rem;
  }
}
</style>
