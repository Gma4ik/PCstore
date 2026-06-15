<template>
  <div class="wishlist-btn-wrap">
    <button
      class="wishlist-btn"
      :class="{ active: inWishlist }"
      :title="tooltip"
      @click.prevent.stop="handleClick"
      :aria-label="inWishlist ? 'Видалити зі списку бажань' : 'Додати до списку бажань'"
    >
      {{ inWishlist ? '❤️' : '🤍' }}
    </button>
    <span v-if="showTooltip" class="wishlist-tooltip">Увійдіть для збереження</span>
  </div>
</template>

<script>
import { computed, ref } from 'vue';
import { useWishlistStore, useUserStore } from '../store';

export default {
  name: 'WishlistButton',
  props: {
    product: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const wishlistStore = useWishlistStore();
    const userStore = useUserStore();
    const showTooltip = ref(false);

    const inWishlist = computed(() => wishlistStore.isInWishlist(props.product.id));
    const tooltip = computed(() => inWishlist.value ? 'Видалити зі списку бажань' : 'Додати до списку бажань');

    let tooltipTimer = null;

    const handleClick = () => {
      if (!userStore.isAuthenticated) {
        showTooltip.value = true;
        clearTimeout(tooltipTimer);
        tooltipTimer = setTimeout(() => { showTooltip.value = false; }, 2000);
        return;
      }
      wishlistStore.toggleWishlist(props.product);
    };

    return { inWishlist, tooltip, showTooltip, handleClick };
  }
};
</script>

<style scoped>
.wishlist-btn-wrap {
  position: relative;
  display: inline-flex;
}

.wishlist-btn {
  background: rgba(255, 255, 255, 0.85);
  border: none;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  font-size: 1.1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 1px 4px rgba(0,0,0,0.15);
  transition: transform 0.15s, box-shadow 0.15s;
  line-height: 1;
}

.wishlist-btn:hover {
  transform: scale(1.12);
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.wishlist-btn.active {
  background: rgba(255, 255, 255, 0.95);
}

.wishlist-tooltip {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  background: rgba(30, 30, 30, 0.88);
  color: #fff;
  font-size: 0.75rem;
  white-space: nowrap;
  padding: 0.3rem 0.6rem;
  border-radius: 6px;
  pointer-events: none;
  z-index: 10;
}
</style>
