<template>
  <div class="placeholder-image" :style="containerStyle">
    <svg :width="width" :height="height" xmlns="http://www.w3.org/2000/svg">
      <rect width="100%" height="100%" fill="#e2e8f0"/>
      <text
        x="50%"
        y="50%"
        font-family="Arial, sans-serif"
        font-size="24"
        fill="#64748b"
        text-anchor="middle"
        dominant-baseline="middle"
      >
        {{ text }}
      </text>
    </svg>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  text: {
    type: String,
    default: 'Product Image'
  },
  width: {
    type: Number,
    default: 300
  },
  height: {
    type: Number,
    default: 300
  }
});

const containerStyle = computed(() => ({
  width: `${props.width}px`,
  height: `${props.height}px`,
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  backgroundColor: '#e2e8f0',
  borderRadius: '8px'
}));
</script>

<style scoped>
.placeholder-image svg {
  display: block;
}
</style>
