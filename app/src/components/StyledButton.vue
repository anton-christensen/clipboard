<script setup lang="ts">
import ic = CSS.ic;

defineProps<{
  text: string;
  icon?: string;
  disabled?: boolean;
}>();

defineEmits<{
  (e: 'click'): void;
}>();
</script>

<template>
  <button
    class="relative px-6 py-3 font-bold text-white group"
    @click="$emit('click')"
    :class="{ 'cursor-pointer': !disabled }"
    :disabled="disabled"
  >
    <span v-if="disabled">
      <span class="absolute bg-gray-700 inset-0 -translate-x-1 -translate-y-1 opacity-80"></span>
      <span
        class="absolute bg-gray-600 inset-0 translate-x-1 translate-y-1 opacity-80 mix-blend-screen"
      ></span>
    </span>

    <span v-else>
      <span
        class="absolute bg-purple-800 inset-0 transition duration-300 transform -translate-x-1 -translate-y-1 ease opacity-80 group-hover:translate-x-0 group-hover:translate-y-0"
      ></span>
      <span
        class="absolute bg-pink-800 inset-0 transition duration-300 transform translate-x-1 translate-y-1 ease opacity-80 group-hover:translate-x-0 group-hover:translate-y-0 mix-blend-screen"
      ></span>
    </span>

    <span class="relative">
      <span v-if="icon != null" class="material-symbols-outlined align-middle animate-test">
        {{ icon }}
      </span>
      {{ text }}
    </span>
  </button>
</template>

<style scoped></style>
