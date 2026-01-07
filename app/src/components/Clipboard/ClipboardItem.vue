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

function isPreviewable(mime: string) {
  switch (mime) {
    case 'image/png':
    case 'image/jpeg':
    case 'image/gif':
    case 'image/webp':
    case 'video/mp4':
    case 'video/webm':
    case 'audio/mpeg':
    case 'audio/ogg':
    case 'text/plain':
    case 'text/html':
    case 'application/pdf':
      return true;
    default:
      return false;
  }
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
  <div>
    <div class="item-header">
      <button @click="toggleExpansion(index)">
        {{ isPreviewable(item.info.mime) ? 'Preview' : 'Download' }}
      </button>
      <a :href="`${BASE_URL}?clip=${item.info.label}`" target="_blank">
        <b>{{ item.info.label }}</b>
        <span>({{ humanFileSize(item.info.size) }})</span>
      </a>
    </div>

    <iframe
      v-if="item.expanded"
      :src="`${BASE_URL}?cachekill=${item.info.hash}&clip=${item.info.label}`"
    />
  </div>
</template>

<style scoped>
.item-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

iframe {
  width: 100%;
  height: 40vh;
  margin: 0.5rem 0;
  border: 0;

  resize: vertical;
  overflow-y: auto;
}
</style>
