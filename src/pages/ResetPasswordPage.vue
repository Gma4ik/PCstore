<template>
  <div class="reset-page page">
    <div class="container container-narrow">
      <div class="auth-card card">
        <div class="card-body">
          <template v-if="!token">
            <div class="state-box">
              <div class="state-icon">❌</div>
              <h2>Недійсне посилання</h2>
              <p>Посилання для скидання паролю відсутнє або пошкоджене.</p>
              <router-link to="/login" class="btn btn-primary mt-3">На сторінку входу</router-link>
            </div>
          </template>

          <template v-else-if="success">
            <div class="state-box">
              <div class="state-icon">✅</div>
              <h2>Пароль змінено</h2>
              <p>Тепер можете увійти з новим паролем.</p>
              <router-link to="/login" class="btn btn-primary mt-3">Увійти</router-link>
            </div>
          </template>

          <template v-else>
            <h1 class="auth-title">Новий пароль</h1>
            <p class="auth-subtitle">Введіть новий пароль для вашого акаунту</p>

            <form @submit.prevent="handleSubmit" class="auth-form">
              <div class="form-group">
                <label>Новий пароль</label>
                <input v-model="password" type="password" required minlength="6" placeholder="Мінімум 6 символів" />
              </div>
              <div class="form-group">
                <label>Підтвердіть пароль</label>
                <input v-model="confirm" type="password" required placeholder="Повторіть пароль" />
              </div>
              <span v-if="error" class="field-error">{{ error }}</span>
              <button type="submit" class="btn btn-primary btn-block mt-2" :disabled="loading">
                {{ loading ? 'Збереження...' : 'Зберегти пароль' }}
              </button>
            </form>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { api } from '../services/api';

export default {
  name: 'ResetPasswordPage',
  setup() {
    const route = useRoute();
    const token = ref('');
    const password = ref('');
    const confirm = ref('');
    const error = ref('');
    const loading = ref(false);
    const success = ref(false);

    onMounted(() => { token.value = route.query.token || ''; });

    const handleSubmit = async () => {
      error.value = '';
      if (password.value !== confirm.value) { error.value = 'Паролі не співпадають'; return; }
      loading.value = true;
      try {
        await api.auth.resetPassword(token.value, password.value);
        success.value = true;
      } catch (err) {
        error.value = err.response?.data?.message || 'Помилка. Спробуйте ще раз.';
      } finally {
        loading.value = false;
      }
    };

    return { token, password, confirm, error, loading, success, handleSubmit };
  }
};
</script>

<style scoped>
.reset-page {
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
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.btn-block { width: 100%; text-align: center; }

.field-error {
  color: var(--error-color, #e53e3e);
  font-size: 0.75rem;
}

.state-box {
  text-align: center;
  padding: 1rem 0;
}

.state-icon { font-size: 3rem; margin-bottom: 1rem; }

.state-box h2 {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
}

.state-box p { color: var(--text-secondary); }
</style>
