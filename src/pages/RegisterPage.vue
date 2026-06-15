<template>
  <div class="register-page page">
    <div class="container container-narrow">
      <div class="auth-card card">
        <div class="card-body">
          <h1 class="auth-title">Створити акаунт</h1>
          <p class="auth-subtitle">Приєднуйтесь до нашої спільноти</p>
          
          <form @submit.prevent="handleRegister" class="auth-form">
            <div class="form-group">
              <label for="name">Повне ім'я</label>
              <input 
                type="text" 
                id="name" 
                v-model="formData.name"
                @input="e => formData.name = e.target.value.replace(/\d/g, '')"
                required
                placeholder="Іван Іваненко"
              />
            </div>
            
            <div class="form-group">
              <label for="email">Email адреса</label>
              <input 
                type="email" 
                id="email" 
                v-model="formData.email" 
                required
                placeholder="your@email.com"
              />
            </div>
            
            <div class="form-group">
              <label for="password">Пароль</label>
              <input 
                type="password" 
                id="password" 
                v-model="formData.password" 
                required
                minlength="6"
                placeholder="Мінімум 6 символів"
              />
            </div>
            
            <div class="form-group">
              <label for="confirmPassword">Підтвердіть пароль</label>
              <input 
                type="password" 
                id="confirmPassword" 
                v-model="formData.confirmPassword" 
                required
                minlength="6"
                placeholder="Повторіть пароль"
              />
            </div>
            
            <div class="form-group mb-3">
              <label class="checkbox-label">
                <input type="checkbox" v-model="formData.acceptTerms" required />
                <span>Я погоджуюсь з <a href="#">Умовами використання</a> та <a href="#">Політикою конфіденційності</a></span>
              </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg btn-block">
              Зареєструватись
            </button>
          </form>
          
          <div class="auth-divider">
            <span>або</span>
          </div>
          
          <div class="auth-footer text-center">
            <p>Вже є акаунт? <router-link to="/login" class="auth-link">Увійти</router-link></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '../store';

export default {
  name: 'RegisterPage',
  setup() {
    const router = useRouter();
    const userStore = useUserStore();
    
    const formData = ref({
      name: '',
      email: '',
      password: '',
      confirmPassword: '',
      acceptTerms: false
    });
    
    const handleRegister = async () => {
      // Validate passwords match
      if (formData.value.password !== formData.value.confirmPassword) {
        alert('Паролі не співпадають! Спробуйте ще раз.');
        return;
      }
      
      if (formData.value.password.length < 6) {
        alert('Пароль має містити мінімум 6 символів.');
        return;
      }
      
      try {
        const result = await userStore.register(
          formData.value.name,
          formData.value.email,
          formData.value.password
        );
        
        if (result.success) {
          router.push('/profile');
        }
      } catch (error) {
        alert('Помилка реєстрації. Спробуйте ще раз.');
      }
    };
    
    return {
      formData,
      handleRegister
    };
  }
};
</script>

<style scoped>
.register-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 200px);
}

.auth-card {
  max-width: 450px;
  width: 100%;
  margin: 0 auto;
  box-shadow: var(--shadow-lg);
}

.auth-title {
  font-size: 2rem;
  font-weight: 700;
  text-align: center;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
}

.auth-subtitle {
  text-align: center;
  color: var(--text-secondary);
  margin-bottom: 2rem;
}

.auth-form {
  margin-bottom: 2rem;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 0.625rem;
  cursor: pointer;
  color: var(--text-secondary);
  font-size: 0.875rem;
  line-height: 1.5;
}

.checkbox-label input[type="checkbox"] {
  width: auto;
  cursor: pointer;
  margin-top: 0.2rem;
  flex-shrink: 0;
}

.checkbox-label a {
  color: var(--primary-color);
  text-decoration: underline;
}

.btn-block {
  width: 100%;
  display: block;
  text-align: center;
}

.auth-divider {
  position: relative;
  text-align: center;
  margin: 2rem 0;
}

.auth-divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background-color: var(--border-color);
}

.auth-divider span {
  position: relative;
  background-color: var(--bg-primary);
  padding: 0 1rem;
  color: var(--text-secondary);
  font-weight: 600;
}

.auth-footer p {
  text-align: center;
  color: var(--text-secondary);
  margin: 0;
}

.auth-link {
  color: var(--primary-color);
  font-weight: 600;
  text-decoration: none;
}

.auth-link:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .auth-card {
    margin: 1rem;
  }
}
</style>
