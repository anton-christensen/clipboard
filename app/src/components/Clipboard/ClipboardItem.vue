<script setup lang="ts">
import { BASE_URL, type ClipboardItemInfo } from '@/api.ts';
import { inject } from 'vue';

export interface ClipboardObject {
  info: ClipboardItemInfo;
  expanded: boolean;
}

defineProps<{
  item: ClipboardObject;
  index: number;
}>();

const toggleExpansion = inject('toggleExpansion') as (index: number) => void;

function isPreviewable() {
  return true;
}

function humanFileSize(bytes: number, fractionDigits = 1) {
  const threshold = 1000;
  const units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

  if (Math.abs(bytes) < threshold) {
    return `${bytes} B`;
  }

  let u = -1;
  const r = 10 ** fractionDigits;
  do {
    bytes /= threshold;
    ++u;
  } while (Math.round(Math.abs(bytes) * r) / r >= threshold && u < units.length - 1);

  return `${bytes.toFixed(fractionDigits)} ${units[u]}`;
}
</script>

<template>
  <li>
    <a :href="`${BASE_URL}?clip=${item.info.label}`" target="_blank"
      ><b>{{ item.info.label }}: </b><span>{{ humanFileSize(item.info.size) }}</span></a
    >
    <button v-if="isPreviewable" @click="toggleExpansion(index)">Preview</button>
    <iframe
      v-if="item.expanded"
      :src="`${BASE_URL}?cachekill=${item.info.hash}&clip=${item.info.label}`"
    />
  </li>
</template>

<style scoped>
iframe {
  width: 100%;
  height: 40vh;
  box-sizing: border-box;
  border: 0;
  border-top: 1px solid;
  border-bottom: 1px solid;
  margin: 1rem 0;
  resize: vertical;
  overflow-y: auto;
}
</style>
