<script setup lang="ts">
import ClipboardItem, { type ClipboardObject } from '@/components/Clipboard/ClipboardItem.vue';

defineProps<{
  clipboard: ClipboardObject[];
}>();
</script>

<template>
  <div v-if="clipboard.length === 0">
    <p>This clipboard is currently empty</p>
  </div>

  <div v-else>
    <p>Listed below are the current contents of the online clipboard</p>
    <div class="flex flex-col gap-2">
      <ClipboardItem v-for="(item, index) in clipboard" :key="item.info.hash" :item :index />
    </div>
  </div>
</template>
