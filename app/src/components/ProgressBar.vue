<script setup lang="ts">
import { watchEffect } from 'vue';

const progress = defineModel<number>({ required: true });

watchEffect(() => {
  if (progress.value >= 100) {
    setTimeout(() => (progress.value = 0), 750);
  }
});
</script>

<template>
  <div class="progress">
    <div
      class="bar"
      :style="{ width: `${progress}%`, 'transition-property': progress === 0 ? 'none' : 'width' }"
      :class="{ completed: progress >= 100 }"
    ></div>
  </div>
</template>

<style scoped>
.progress {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;

  .bar {
    position: absolute;
    left: 0;
    top: 0;
    width: 0;
    height: 0.5rem;

    background-color: hsl(230, 100%, 75%);
    transition: width 250ms;

    &.completed {
      background-color: hsl(230, 100%, 50%);
      animation: 500ms fade-in forwards;
    }
  }
}

@keyframes fade-in {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}
</style>
