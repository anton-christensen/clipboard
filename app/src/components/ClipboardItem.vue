<script setup lang="ts">
import { type ClipboardItemInfo, downloadClipboardItem } from '@/api.ts';
import { computed } from 'vue';

const props = defineProps<{
  item: ClipboardItemInfo;
  selected: boolean;
}>();

const emits = defineEmits<{
  (e: 'selected'): void;
}>();

function handleClick() {
  if (isPreviewable.value) {
    emits('selected');
  } else {
    downloadClipboardItem(props.item);
  }
}

const isPreviewable = computed(() => {
  switch (props.item.mime) {
    case 'application/pdf':
    case 'audio/mpeg':
    case 'audio/ogg':
    case 'image/gif':
    case 'image/jpeg':
    case 'image/png':
    case 'image/webp':
    case 'text/html':
    case 'text/plain':
    case 'text/uri-list':
    case 'video/mp4':
    case 'video/webm':
      return true;
    default:
      return false;
  }
});

const label = computed(
  () => `${props.item.label.slice(0, 20)}${props.item.label.length > 20 ? '...' : ''}`,
);

function humanReadableFileSize(bytes: number) {
  const threshold = 1000;
  const units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

  if (Math.abs(bytes) < threshold) {
    return `${bytes} B`;
  }

  let u = -1;
  do {
    bytes /= threshold;
    ++u;
  } while (Math.round(Math.abs(bytes)) >= threshold && u < units.length - 1);

  return `${bytes.toFixed()} ${units[u]}`;
}
</script>

<template>
  <div
    @click="handleClick()"
    @dblclick="downloadClipboardItem(item)"
    :class="{ 'clipboard-item-active!': selected }"
    class="hover:clipboard-item-active hover:animate-wiggle flex w-36 cursor-pointer flex-col items-center gap-2 rounded-md border p-2 shadow-sm shadow-black/15 backdrop-blur-sm transition-all select-none dark:text-gray-400"
  >
    <span class="material-symbols-outlined icon-thin text-8xl!">
      {{ isPreviewable ? 'draft' : 'file_save' }}
    </span>

    <div class="flex flex-col items-center text-sm">
      <span>
        {{ humanReadableFileSize(item.size) }}
      </span>
      <span class="w-24 text-center wrap-break-word">{{ label }}</span>
    </div>
  </div>
</template>
