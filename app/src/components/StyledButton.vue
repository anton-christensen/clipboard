<script setup lang="ts">
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
    class="group relative px-6 py-3 font-bold text-white"
    @click="$emit('click')"
    :class="{ 'cursor-pointer': !disabled }"
    :disabled="disabled"
  >
    <span v-if="disabled">
      <span class="absolute inset-0 -translate-x-1 -translate-y-1 bg-gray-700 opacity-80"></span>
      <span
        class="absolute inset-0 translate-x-1 translate-y-1 bg-gray-600 opacity-80 mix-blend-screen"
      ></span>
    </span>

    <span v-else>
      <span
        class="ease absolute inset-0 -translate-x-1 -translate-y-1 transform bg-purple-800 opacity-80 transition duration-300 group-hover:translate-x-0 group-hover:translate-y-0"
      ></span>
      <span
        class="ease absolute inset-0 translate-x-1 translate-y-1 transform bg-pink-800 opacity-80 mix-blend-screen transition duration-300 group-hover:translate-x-0 group-hover:translate-y-0"
      ></span>
    </span>

    <span class="relative">
      <span v-if="icon != null" class="material-symbols-outlined animate-test align-middle">
        {{ icon }}
      </span>
      {{ text }}
    </span>
  </button>
</template>

<style scoped></style>
