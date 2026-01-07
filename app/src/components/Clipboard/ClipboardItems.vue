<script setup lang="ts">
import ClipboardItem, { type ClipboardObject } from '@/components/Clipboard/ClipboardItem.vue';

defineProps<{
  clipboard: ClipboardObject[];
}>();
</script>

<template>
  <h2>Clipboard Contents</h2>
  <div v-if="clipboard.length === 0">
    <p>This clipboard is currently empty</p>
  </div>
  <div v-else>
    <p>Listed below are the current contents of the online clipboard</p>
    <div class="items">
      <ClipboardItem v-for="(item, index) in clipboard" :key="item.info.hash" :item :index />
    </div>
  </div>
</template>

<style scoped>
.items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
</style>
