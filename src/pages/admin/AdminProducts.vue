<template>
  <div class="admin-products page">
    <div class="container">
      <div class="admin-header mb-4 flex-between">
        <div>
          <h1 class="page-title">Управління товарами</h1>
          <p class="admin-subtitle">Додавання, редагування та видалення товарів</p>
        </div>
        <button @click="openAddModal" class="btn btn-primary">
          ➕ Додати товар
        </button>
      </div>

      <!-- Products Table -->
      <div class="products-table card">
        <div class="card-body">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Фото</th>
                <th>Назва</th>
                <th>Категорія</th>
                <th>Ціна</th>
                <th>Залишок</th>
                <th>Наявність</th>
                <th>Рейтинг</th>
                <th>Дії</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in paginatedProducts" :key="product.id">
                <td>
                  <img :src="product.image" :alt="product.name" class="product-thumb" />
                </td>
                <td><strong>{{ product.name }}</strong></td>
                <td>{{ getCategoryName(product.category) }}</td>
                <td>₴{{ product.price.toFixed(2) }}</td>
                <td>{{ product.stock_quantity ?? 0 }}</td>
                <td>
                  <span :class="['badge', product.in_stock ? 'badge-success' : 'badge-danger']">
                    {{ product.in_stock ? 'В наявності' : 'Немає' }}
                  </span>
                </td>
                <td>
                  <div class="star-rating" v-if="product.rating">
                    ★ {{ product.rating }}
                  </div>
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="editProduct(product)" class="btn-icon btn-edit" title="Редагувати">
                      ✏️
                    </button>
                    <button @click="deleteProduct(product.id)" class="btn-icon btn-delete" title="Видалити">
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <Pagination 
        v-model="currentPage" 
        :total-items="filteredProducts.length"
        :items-per-page="itemsPerPage"
      />

      <!-- Add/Edit Modal -->
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header flex-between">
            <h2>{{ isEditing ? 'Редагувати товар' : 'Додати товар' }}</h2>
            <button @click="closeModal" class="btn-close">✕</button>
          </div>
          
          <form @submit.prevent="saveProduct" class="product-form">
            <div class="form-group">
              <label>Назва товару *</label>
              <input
                v-model="formData.name"
                type="text"
                :class="{ 'input-error': formErrors.name }"
                @input="clearError('name')"
                placeholder="Наприклад: Intel Core i9-14900K"
              />
              <span v-if="formErrors.name" class="field-error">{{ formErrors.name }}</span>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Категорія *</label>
                <select
                  v-model="formData.category"
                  :class="{ 'input-error': formErrors.category }"
                  @change="clearError('category')"
                >
                  <option value="" disabled>Оберіть категорію</option>
                  <option v-for="cat in categoriesList" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
                <span v-if="formErrors.category" class="field-error">{{ formErrors.category }}</span>
              </div>

              <div class="form-group">
                <label>Ціна (₴) *</label>
                <input
                  v-model.number="formData.price"
                  type="number"
                  step="0.01"
                  min="0"
                  :class="{ 'input-error': formErrors.price }"
                  @input="clearError('price')"
                  placeholder="0.00"
                />
                <span v-if="formErrors.price" class="field-error">{{ formErrors.price }}</span>
              </div>
            </div>

            <div class="form-group">
              <label>Опис *</label>
              <textarea
                v-model="formData.description"
                rows="4"
                :class="{ 'input-error': formErrors.description }"
                @input="clearError('description')"
                placeholder="Короткий опис товару..."
              ></textarea>
              <div class="field-hint">{{ formData.description?.length ?? 0 }} / мін. 20 символів</div>
              <span v-if="formErrors.description" class="field-error">{{ formErrors.description }}</span>
            </div>

            <div class="form-group">
              <label>Зображення</label>
              <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" @change="onImageChange" class="file-input" />
              <div v-if="formErrors.image" class="field-error">{{ formErrors.image }}</div>
              <div v-if="imagePreview || formData.image" class="image-preview">
                <img :src="imagePreview || formData.image" alt="preview" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>В наявності?</label>
                <select v-model="formData.in_stock">
                  <option :value="true">Так</option>
                  <option :value="false">Ні</option>
                </select>
              </div>

              <div class="form-group">
                <label>Кількість на складі</label>
                <input
                  v-model.number="formData.stock_quantity"
                  type="number"
                  min="0"
                  :class="{ 'input-error': formErrors.stock_quantity }"
                  @input="clearError('stock_quantity')"
                  placeholder="0"
                />
                <span v-if="formErrors.stock_quantity" class="field-error">{{ formErrors.stock_quantity }}</span>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Рейтинг</label>
                <input
                  v-model.number="formData.rating"
                  type="number"
                  step="0.1"
                  min="0"
                  max="5"
                  :class="{ 'input-error': formErrors.rating }"
                  @input="clearError('rating')"
                  placeholder="0.0"
                />
                <span v-if="formErrors.rating" class="field-error">{{ formErrors.rating }}</span>
              </div>
            </div>
            
            <div class="modal-footer">
              <div v-if="formErrors._server" class="field-error server-error">{{ formErrors._server }}</div>
              <button type="button" @click="closeModal" class="btn btn-outline">Скасувати</button>
              <button type="submit" class="btn btn-primary">
                {{ isEditing ? 'Оновити' : 'Створити' }} товар
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import Pagination from '../../components/Pagination.vue';
import { adminApi } from '../../services/api';

export default {
  name: 'AdminProducts',
  components: { Pagination },
  setup() {
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
    const showModal = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);
    const productsList = ref([]);
    const categoriesList = ref([]);
    const imagePreview = ref(null);
    const imageFile = ref(null);

    const formData = ref({
      name: '', category: '', price: 0, description: '', image: '', in_stock: true, rating: 0, stock_quantity: 0
    });
    const formErrors = ref({});

    onMounted(async () => {
      const [prodRes, catRes] = await Promise.all([
        adminApi.products.getAll(),
        adminApi.categories.getAll()
      ]);
      productsList.value = prodRes.data.data ?? [];
      categoriesList.value = catRes.data.data ?? [];
    });

    const filteredProducts = computed(() => productsList.value);
    const paginatedProducts = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value;
      return filteredProducts.value.slice(start, start + itemsPerPage.value);
    });

    const getCategoryName = (categoryId) => {
      const cat = categoriesList.value.find(c => c.id === categoryId);
      return cat ? cat.name : categoryId;
    };

    const openAddModal = () => {
      isEditing.value = false;
      imagePreview.value = null;
      imageFile.value = null;
      formData.value = { name: '', category: 'processors', price: 0, description: '', image: '', in_stock: true, rating: 0, stock_quantity: 0 };
      showModal.value = true;
    };

    const editProduct = (product) => {
      isEditing.value = true;
      editingId.value = product.id;
      imagePreview.value = null;
      imageFile.value = null;
      formData.value = { ...product };
      showModal.value = true;
    };

    const onImageChange = (e) => {
      const file = e.target.files[0];
      if (!file) return;
      const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
      if (!allowed.includes(file.type)) {
        formErrors.value.image = 'Дозволені формати: JPG, PNG, WEBP, GIF';
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        formErrors.value.image = 'Розмір файлу не може перевищувати 5 МБ';
        return;
      }
      formErrors.value.image = '';
      imageFile.value = file;
      imagePreview.value = URL.createObjectURL(file);
    };

    const deleteProduct = async (id) => {
      if (!confirm('Ви впевнені, що хочете видалити цей товар?')) return;
      try {
        await adminApi.products.delete(id);
        productsList.value = productsList.value.filter(p => p.id !== id);
      } catch {
        alert('Не вдалося видалити товар');
      }
    };

    const clearError = (field) => {
      if (formErrors.value[field]) formErrors.value[field] = '';
    };

    const validate = () => {
      const errors = {};
      const name = formData.value.name?.trim() ?? '';
      const desc = formData.value.description?.trim() ?? '';
      const price = formData.value.price;
      const rating = formData.value.rating;
      const qty = formData.value.stock_quantity;

      if (!name) errors.name = "Назва обов'язкова";
      else if (name.length < 3) errors.name = 'Назва має містити щонайменше 3 символи';
      else if (name.length > 200) errors.name = 'Назва не може перевищувати 200 символів';

      if (!formData.value.category) errors.category = "Категорія обов'язкова";

      if (price === null || price === '' || price === undefined) errors.price = "Ціна обов'язкова";
      else if (isNaN(price) || price <= 0) errors.price = 'Ціна має бути більше 0';
      else if (price > 100000) errors.price = 'Ціна не може перевищувати ₴100,000';

      if (!desc) errors.description = "Опис обов'язковий";
      else if (desc.length < 20) errors.description = 'Опис має містити щонайменше 20 символів';
      else if (desc.length > 2000) errors.description = 'Опис не може перевищувати 2000 символів';

      if (qty !== '' && qty !== null && qty !== undefined) {
        if (isNaN(qty) || qty < 0) errors.stock_quantity = 'Кількість не може бути від\'ємною';
        else if (!Number.isInteger(qty)) errors.stock_quantity = 'Кількість має бути цілим числом';
      }

      if (rating !== '' && rating !== null && rating !== undefined) {
        if (isNaN(rating) || rating < 0 || rating > 5) errors.rating = 'Рейтинг має бути від 0 до 5';
      }

      return errors;
    };

    const saveProduct = async () => {
      formErrors.value = validate();
      if (Object.keys(formErrors.value).length) return;
      try {
        let imagePath = formData.value.image ?? '';
        if (imageFile.value) {
          const uploadRes = await adminApi.upload.image(imageFile.value);
          imagePath = uploadRes.data.data.path;
        }

        const payload = { ...formData.value, image: imagePath };
        delete payload.created_at;

        if (isEditing.value) {
          const res = await adminApi.products.update(editingId.value, payload);
          const idx = productsList.value.findIndex(p => p.id === editingId.value);
          if (idx !== -1) productsList.value[idx] = res.data.data;
        } else {
          const res = await adminApi.products.create(payload);
          productsList.value.unshift(res.data.data);
        }
        closeModal();
      } catch (err) {
        const serverMsg = err.response?.data?.message;
        if (serverMsg) formErrors.value._server = serverMsg;
      }
    };

    const closeModal = () => {
      showModal.value = false;
      isEditing.value = false;
      editingId.value = null;
      imagePreview.value = null;
      imageFile.value = null;
      formErrors.value = {};
    };

    return {
      categoriesList, currentPage, itemsPerPage, paginatedProducts, filteredProducts,
      showModal, isEditing, formData, formErrors, imagePreview, getCategoryName,
      openAddModal, editProduct, deleteProduct, saveProduct, closeModal, onImageChange, clearError
    };
  }
};
</script>

<style scoped>
.admin-products {
  min-height: 100vh;
  padding: 2rem 0;
}

.admin-header {
  text-align: left;
}

.admin-subtitle {
  color: var(--text-secondary);
  font-size: 1.125rem;
}

.product-thumb {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: var(--radius-md);
  background-color: var(--bg-tertiary);
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.25rem;
  padding: 0.25rem;
  transition: var(--transition);
}

.btn-icon:hover {
  transform: scale(1.2);
}

.btn-edit:hover {
  opacity: 0.8;
}

.btn-delete:hover {
  opacity: 0.8;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: var(--bg-primary);
  border-radius: var(--radius-lg);
  padding: 2rem;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  margin-bottom: 1.5rem;
}

.modal-header h2 {
  font-size: 1.5rem;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--text-secondary);
}

.product-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.modal-footer {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1rem;
}

.file-input {
  padding: 0.5rem 0;
}

.image-preview {
  margin-top: 0.5rem;
}

.image-preview img {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
}

.field-error {
  color: var(--danger-color, #e53e3e);
  font-size: 0.75rem;
  margin-top: 0.25rem;
  display: block;
}

.field-hint {
  font-size: 0.75rem;
  color: var(--text-light);
  margin-top: 0.25rem;
}

.input-error {
  border-color: var(--danger-color, #e53e3e) !important;
  background-color: #fff5f5;
}

.input-error:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
}

.server-error {
  flex: 1;
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .admin-table {
    overflow-x: auto;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
