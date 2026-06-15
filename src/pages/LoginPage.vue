<template>
  <div class="login-page page">
    <div class="container container-narrow">
      <div class="auth-card card">
        <div class="card-body">
          <h1 class="auth-title">З поверненням</h1>
          <p class="auth-subtitle">Увійдіть у свій акаунт</p>
          
          <form @submit.prevent="handleLogin" class="auth-form">
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
                placeholder="Введіть пароль"
              />
            </div>
            
            <div class="form-options flex-between mb-3">
              <label class="checkbox-label">
                <input type="checkbox" v-model="formData.remember" />
                Запам'ятати мене
              </label>
              <a href="#" class="forgot-link" @click.prevent="showForgot = true">Забули пароль?</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg btn-block">
              Увійти
            </button>
          </form>
          
          <div class="auth-divider">
            <span>або</span>
          </div>
          
          <div class="auth-footer text-center">
            <p>Ще немає акаунту? <router-link to="/register" class="auth-link">Створити акаунт</router-link></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Forgot Password Modal -->
    <div v-if="showForgot" class="modal-overlay" @click.self="closeForgot">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Відновлення паролю</h2>
          <button @click="closeForgot" class="btn-close">✕</button>
        </div>
        <template v-if="!forgotSent">
          <p class="modal-desc">Введіть ваш email та новий пароль.</p>
          <form @submit.prevent="handleResetPassword" class="forgot-form">
            <div class="form-group">
              <label>Email адреса</label>
              <input v-model="forgotForm.email" type="email" required placeholder="your@email.com" />
            </div>
            <div class="form-group">
              <label>Новий пароль</label>
              <input v-model="forgotForm.password" type="password" required minlength="6" placeholder="Мінімум 6 символів" />
            </div>
            <div class="form-group">
              <label>Підтвердіть пароль</label>
              <input v-model="forgotForm.confirm" type="password" required placeholder="Повторіть пароль" />
            </div>
            <span v-if="forgotError" class="field-error">{{ forgotError }}</span>
            <div class="modal-footer">
              <button type="button" @click="closeForgot" class="btn btn-outline">Скасувати</button>
              <button type="submit" class="btn btn-primary" :disabled="forgotLoading">
                {{ forgotLoading ? 'Збереження...' : 'Змінити пароль' }}
              </button>
            </div>
          </form>
        </template>
        <template v-else>
          <div class="forgot-success">
            <div class="success-icon">✅</div>
            <p>Пароль успішно змінено.</p>
            <button @click="closeForgot" class="btn btn-primary btn-block mt-2">Увійти</button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '../store';
import { api } from '../services/api';

export default {
  name: 'LoginPage',
  setup() {
    const router = useRouter();
    const userStore = useUserStore();
    
    const formData = ref({ email: '', password: '', remember: false });

    const showForgot = ref(false);
    const forgotLoading = ref(false);
    const forgotSent = ref(false);
    const forgotError = ref('');
    const forgotForm = ref({ email: '', password: '', confirm: '' });

    const handleLogin = async () => {
      try {
        const result = await userStore.login(formData.value.email, formData.value.password);
        if (result.success) router.push('/profile');
      } catch (error) {
        alert('Помилка входу. Перевірте дані та спробуйте ще раз.');
      }
    };

    const closeForgot = () => {
      showForgot.value = false;
      forgotSent.value = false;
      forgotError.value = '';
      forgotForm.value = { email: '', password: '', confirm: '' };
    };

    const handleResetPassword = async () => {
      forgotError.value = '';
      if (forgotForm.value.password !== forgotForm.value.confirm) {
        forgotError.value = 'Паролі не співпадають';
        return;
      }
      forgotLoading.value = true;
      try {
        await api.auth.resetPassword(forgotForm.value.email, forgotForm.value.password);
        forgotSent.value = true;
      } catch (err) {
        forgotError.value = err.response?.data?.message || 'Помилка. Спробуйте ще раз.';
      } finally {
        forgotLoading.value = false;
      }
    };

    return { formData, handleLogin, showForgot, forgotForm, forgotError, forgotLoading, forgotSent, closeForgot, handleResetPassword };
  }
};
</script>

<style scoped>
.login-page {
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

.form-options {
  font-size: 0.875rem;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  cursor: pointer;
  color: var(--text-secondary);
}

.checkbox-label input[type="checkbox"] {
  width: auto;
  cursor: pointer;
  margin-top: 0.15rem;
  flex-shrink: 0;
}

.forgot-link {
  color: var(--primary-color);
  font-size: 0.875rem;
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

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  padding: 2rem;
  max-width: 420px;
  width: 90%;
  box-shadow: var(--shadow-lg);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.modal-header h2 {
  font-size: 1.25rem;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: var(--text-secondary);
}

.modal-desc {
  color: var(--text-secondary);
  font-size: 0.875rem;
  margin-bottom: 1.25rem;
}

.forgot-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-footer {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

.field-error {
  color: var(--error-color, #e53e3e);
  font-size: 0.75rem;
  display: block;
}

.forgot-success {
  text-align: center;
  padding: 1rem 0;
}

.success-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.forgot-success p {
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}
</style>
