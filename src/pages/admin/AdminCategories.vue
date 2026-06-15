<template>
  <div class="admin-categories page">
    <div class="container">
      <div class="admin-header mb-4 flex-between">
        <div>
          <h1 class="page-title">Управління категоріями</h1>
          <p class="admin-subtitle">Додавання, редагування та видалення категорій товарів</p>
        </div>
        <button @click="openAddModal" class="btn btn-primary">➕ Додати категорію</button>
      </div>

      <div class="card">
        <div class="card-body">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Іконка</th>
                <th>ID</th>
                <th>Назва</th>
                <th>Дії</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories" :key="cat.id">
                <td style="font-size:1.5rem">{{ cat.icon }}</td>
                <td><code>{{ cat.id }}</code></td>
                <td>{{ cat.name }}</td>
                <td>
                  <div class="action-buttons">
                    <button @click="editCategory(cat)" class="btn-icon" title="Редагувати">✏️</button>
                    <button @click="deleteCategory(cat.id)" class="btn-icon" title="Видалити">🗑️</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal -->
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header flex-between">
            <h2>{{ isEditing ? 'Редагувати категорію' : 'Додати категорію' }}</h2>
            <button @click="closeModal" class="btn-close">✕</button>
          </div>
          <form @submit.prevent="saveCategory" class="product-form">
            <div class="form-group">
              <label>ID (slug) *</label>
              <input v-model="formData.id" type="text" required :disabled="isEditing" placeholder="напр. graphics-cards" />
              <span v-if="formErrors.id" class="field-error">{{ formErrors.id }}</span>
            </div>
            <div class="form-group">
              <label>Назва *</label>
              <input v-model="formData.name" type="text" required placeholder="напр. Відеокарти" />
              <span v-if="formErrors.name" class="field-error">{{ formErrors.name }}</span>
            </div>
            <div class="form-group">
              <label>Іконка (emoji)</label>
              <input v-model="formData.icon" type="text" placeholder="🎮" maxlength="4" />
            </div>
            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn btn-outline">Скасувати</button>
              <button type="submit" class="btn btn-primary">{{ isEditing ? 'Оновити' : 'Створити' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { adminApi } from '../../services/api';

export default {
  name: 'AdminCategories',
  setup() {
    const categories = ref([]);
    const showModal = ref(false);
    const isEditing = ref(false);
    const formData = ref({ id: '', name: '', icon: '📦' });
    const formErrors = ref({});

    onMounted(async () => {
      const res = await adminApi.categories.getAll();
      categories.value = res.data.data ?? [];
    });

    const openAddModal = () => {
      isEditing.value = false;
      formData.value = { id: '', name: '', icon: '📦' };
      formErrors.value = {};
      showModal.value = true;
    };

    const editCategory = (cat) => {
      isEditing.value = true;
      formData.value = { ...cat };
      formErrors.value = {};
      showModal.value = true;
    };

    const deleteCategory = async (id) => {
      if (!confirm('Видалити цю категорію?')) return;
      try {
        await adminApi.categories.delete(id);
        categories.value = categories.value.filter(c => c.id !== id);
      } catch {
        alert('Не вдалося видалити категорію');
      }
    };

    const saveCategory = async () => {
      formErrors.value = {};
      if (!isEditing.value && !formData.value.id?.trim()) formErrors.value.id = "ID обов'язковий";
      if (!formData.value.name?.trim()) formErrors.value.name = "Назва обов'язкова";
      if (Object.keys(formErrors.value).length) return;
      try {
        if (isEditing.value) {
          await adminApi.categories.update(formData.value.id, formData.value);
          const idx = categories.value.findIndex(c => c.id === formData.value.id);
          if (idx !== -1) categories.value[idx] = { ...formData.value };
        } else {
          const res = await adminApi.categories.create(formData.value);
          categories.value.push(res.data.data);
        }
        closeModal();
      } catch {
        alert('Не вдалося зберегти категорію');
      }
    };

    const closeModal = () => {
      showModal.value = false;
      formErrors.value = {};
    };

    return { categories, showModal, isEditing, formData, formErrors, openAddModal, editCategory, deleteCategory, saveCategory, closeModal };
  }
};
</script>

<style scoped>
.admin-categories { min-height: 100vh; padding: 2rem 0; }
.admin-subtitle { color: var(--text-secondary); }
.action-buttons { display: flex; gap: 0.5rem; }
.btn-icon { background: none; border: none; cursor: pointer; font-size: 1.25rem; padding: 0.25rem; transition: var(--transition); }
.btn-icon:hover { transform: scale(1.2); }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: var(--bg-primary); border-radius: var(--radius-lg); padding: 2rem; max-width: 480px; width: 90%; }
.modal-header { margin-bottom: 1.5rem; }
.modal-header h2 { font-size: 1.5rem; margin: 0; }
.btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary); }
.product-form { display: flex; flex-direction: column; gap: 1rem; }
.modal-footer { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; }
code { background: var(--bg-tertiary); padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 0.875rem; }
.field-error { color: var(--error-color, #e53e3e); font-size: 0.75rem; margin-top: 0.25rem; display: block; }
</style>
