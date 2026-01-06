<script setup lang="ts">
import ClipboardItem, { type ClipboardObject } from '@/components/Clipboard/ClipboardItem.vue';

defineProps<{
  clipboard: ClipboardObject[];
}>();
</script>

<template>
  <p>Below is listed the currently uploaded representations of the online clipboard</p>
  <ul>
    <ClipboardItem v-for="(item, index) in clipboard" :key="item.info.hash" :item :index />
  </ul>
</template>
