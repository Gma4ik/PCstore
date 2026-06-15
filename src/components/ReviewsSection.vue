<template>
  <div class="reviews-section">
    <h2 class="section-title">Відгуки покупців</h2>

    <!-- Loading -->
    <div v-if="reviewStore.loading" class="flex-center" style="padding: 2rem;">
      <div class="spinner"></div>
    </div>

    <template v-else>
      <!-- Review list -->
      <div v-if="reviewStore.reviews.length > 0" class="reviews-list">
        <div v-for="review in reviewStore.reviews" :key="review.id" class="review-card card">
          <div class="review-header">
            <div class="review-author-info">
              <span class="review-author">{{ review.author_name }}</span>
              <span class="review-date">{{ formatDate(review.created_at) }}</span>
            </div>
            <div class="review-stars">
              <span v-for="n in 5" :key="n" :class="n <= review.rating ? 'star-filled' : 'star-empty'">★</span>
            </div>
          </div>
          <p v-if="review.comment" class="review-comment">{{ review.comment }}</p>

          <!-- Owner / admin actions -->
          <div v-if="canManage(review)" class="review-actions">
            <button class="btn btn-sm btn-outline" @click="startEdit(review)">Редагувати</button>
            <button class="btn btn-sm btn-danger" @click="handleDelete(review.id)">Видалити</button>
          </div>
        </div>
      </div>
      <p v-else class="no-reviews">Відгуків ще немає. Будьте першим!</p>

      <!-- Edit form -->
      <div v-if="editingReview" class="review-form card mt-3">
        <h3 class="form-title">Редагувати відгук</h3>
        <StarPicker v-model="form.rating" />
        <textarea
          v-model="form.comment"
          class="form-control mt-2"
          rows="3"
          placeholder="Ваш коментар..."
        ></textarea>
        <div class="form-actions mt-2">
          <button class="btn btn-primary" :disabled="submitting" @click="handleUpdate">Зберегти</button>
          <button class="btn btn-outline" @click="cancelEdit">Скасувати</button>
        </div>
        <p v-if="formError" class="form-error">{{ formError }}</p>
      </div>

      <!-- New review form (auth only) -->
      <div v-else-if="userStore.isAuthenticated && !userHasReview" class="review-form card mt-3">
        <h3 class="form-title">Залишити відгук</h3>
        <StarPicker v-model="form.rating" />
        <textarea
          v-model="form.comment"
          class="form-control mt-2"
          rows="3"
          placeholder="Ваш коментар..."
        ></textarea>
        <div class="form-actions mt-2">
          <button class="btn btn-primary" :disabled="submitting || form.rating === 0" @click="handleCreate">
            Надіслати відгук
          </button>
        </div>
        <p v-if="formError" class="form-error">{{ formError }}</p>
      </div>

      <!-- Not authenticated -->
      <div v-else-if="!userStore.isAuthenticated" class="auth-prompt card mt-3">
        <router-link to="/login">Увійдіть</router-link>, щоб залишити відгук.
      </div>
    </template>
  </div>
</template>

<script>
import { ref, computed, watch, h } from 'vue';
import { useReviewStore } from '../store';
import { useUserStore } from '../store';

// Inline star picker sub-component
const StarPicker = {
  name: 'StarPicker',
  props: { modelValue: { type: Number, default: 0 } },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const hovered = ref(0);
    const starClass = (n) =>
      n <= (hovered.value || props.modelValue) ? 'star-filled star-pick' : 'star-empty star-pick';
    return () =>
      h('div', { class: 'star-picker' },
        [1, 2, 3, 4, 5].map((n) =>
          h('span', {
            key: n,
            class: starClass(n),
            onMouseenter: () => { hovered.value = n; },
            onMouseleave: () => { hovered.value = 0; },
            onClick: () => emit('update:modelValue', n),
          }, '★')
        )
      );
  },
};

export default {
  name: 'ReviewsSection',
  components: { StarPicker },
  props: {
    productId: { type: [Number, String], required: true }
  },
  emits: ['rating-updated'],
  setup(props, { emit }) {
    const reviewStore = useReviewStore();
    const userStore = useUserStore();

    const form = ref({ rating: 0, comment: '' });
    const submitting = ref(false);
    const formError = ref('');
    const editingReview = ref(null);

    const userHasReview = computed(() =>
      reviewStore.reviews.some(r => Number(r.user_id) === Number(userStore.currentUser?.id))
    );

    const canManage = (review) => {
      if (!userStore.isAuthenticated) return false;
      return Number(review.user_id) === Number(userStore.currentUser?.id) || userStore.currentUser?.role === 'admin';
    };

    const formatDate = (dateStr) => {
      if (!dateStr) return '';
      return new Date(dateStr).toLocaleDateString('uk-UA', { year: 'numeric', month: 'long', day: 'numeric' });
    };

    const resetForm = () => {
      form.value = { rating: 0, comment: '' };
      formError.value = '';
      editingReview.value = null;
    };

    const handleCreate = async () => {
      if (form.value.rating === 0 || submitting.value) return;
      submitting.value = true;
      formError.value = '';
      try {
        await reviewStore.createReview({ product_id: props.productId, ...form.value });
        emit('rating-updated');
        resetForm();
      } catch (err) {
        formError.value = err.response?.data?.message || 'Не вдалося надіслати відгук.';
      } finally {
        submitting.value = false;
      }
    };

    const startEdit = (review) => {
      editingReview.value = review;
      form.value = { rating: review.rating, comment: review.comment || '' };
      formError.value = '';
    };

    const cancelEdit = () => resetForm();

    const handleUpdate = async () => {
      submitting.value = true;
      formError.value = '';
      try {
        await reviewStore.updateReview(editingReview.value.id, form.value);
        emit('rating-updated');
        resetForm();
      } catch (err) {
        formError.value = err.response?.data?.message || 'Не вдалося оновити відгук.';
      } finally {
        submitting.value = false;
      }
    };

    const handleDelete = async (id) => {
      if (!confirm('Видалити цей відгук?')) return;
      try {
        await reviewStore.deleteReview(id);
        emit('rating-updated');
      } catch (err) {
        alert(err.response?.data?.message || 'Не вдалося видалити відгук.');
      }
    };

    watch(() => props.productId, (id) => {
      if (id) reviewStore.fetchReviews(id);
    }, { immediate: true });

    return {
      reviewStore, userStore,
      form, submitting, formError, editingReview,
      userHasReview, canManage, formatDate,
      handleCreate, startEdit, cancelEdit, handleUpdate, handleDelete
    };
  }
};
</script>

<style scoped>
.reviews-section {
  margin-top: 2.5rem;
}

.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.review-card {
  padding: 1.25rem;
  border-radius: var(--radius-lg);
}

.review-card:hover {
  transform: none;
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.review-author {
  font-weight: 600;
  color: var(--text-primary);
}

.review-date {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-left: 0.5rem;
}

.review-comment {
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0.5rem 0 0;
}

.review-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.btn-danger {
  background: #ef4444;
  color: #fff;
  border: none;
}

.btn-danger:hover {
  background: #dc2626;
}

.btn-sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.8rem;
}

.no-reviews {
  color: var(--text-secondary);
  font-style: italic;
  padding: 1rem 0;
}

.review-form {
  padding: 1.5rem;
  border-radius: var(--radius-lg);
}

.review-form:hover {
  transform: none;
}

.form-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
}

.form-error {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.auth-prompt {
  padding: 1rem 1.5rem;
  color: var(--text-secondary);
  border-radius: var(--radius-lg);
}

.auth-prompt:hover {
  transform: none;
}

.star-picker {
  display: flex;
  gap: 0.25rem;
}

.star-pick {
  font-size: 1.75rem;
  cursor: pointer;
  transition: transform 0.1s;
  line-height: 1;
}

.star-pick:hover {
  transform: scale(1.2);
}
</style>
