<template>
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <!-- Company Info -->
        <div class="footer-section">
          <h4>Про TechStore</h4>
          <p>Ваш магазин преміум комп'ютерного заліза та аксесуарів. Пропонуємо найкращі ціни та якісні товари.</p>
        </div>

        <div class="footer-section">
          <h4>Швидкі посилання</h4>
          <ul>
            <li><router-link to="/products">Всі товари</router-link></li>
            <li><router-link to="/products?category=processors">Процесори</router-link></li>
            <li><router-link to="/products?category=graphics-cards">Відеокарти</router-link></li>
            <li><router-link to="/products?category=memory">Пам'ять</router-link></li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Підтримка</h4>
          <ul>
            <li><a href="#">Зв'язатись з нами</a></li>
            <li><a href="#">Доставка</a></li>
            <li><a href="#">Повернення</a></li>
            <li><a href="#">FAQ</a></li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Контакти</h4>
          <ul>
            <li>📍 вул. Хрещатик, 1, Київ</li>
            <li>📞 +38 (044) 123-45-67</li>
            <li>✉️ support@techstore.com</li>
            <li>🕐 Пн-Пт: 9:00 - 18:00</li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2024 TechStore. Всі права захищені. | Розроблено на Vue.js</p>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'Footer'
};
</script>

<style scoped>
.footer {
  background-color: var(--secondary-color);
  color: var(--text-light);
  padding: 3rem 0 1.5rem;
  margin-top: auto;
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 2rem;
  margin-bottom: 2rem;
}

.footer-section h4 {
  color: white;
  margin-bottom: 1rem;
  font-size: 1.125rem;
}

.footer-section ul {
  list-style: none;
}

.footer-section li {
  margin-bottom: 0.5rem;
}

.footer-section a {
  color: var(--text-light);
  transition: var(--transition);
}

.footer-section a:hover {
  color: white;
}

.footer-section p {
  color: var(--text-light);
  line-height: 1.8;
}

.footer-bottom {
  text-align: center;
  padding-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--text-light);
}

@media (max-width: 768px) {
  .footer-content {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
