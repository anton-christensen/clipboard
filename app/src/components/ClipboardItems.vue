<script setup lang="ts">
import ClipboardItem from '@/components/ClipboardItem.vue';
import { computed, ref, watch } from 'vue';
import { BASE_URL, type ClipboardItemInfo } from '@/api.ts';

const props = defineProps<{
  clipboard: ClipboardItemInfo[];
}>();

const selectedItemIndex = ref<null | number>(null);

watch(
  () => props.clipboard,
  () => (selectedItemIndex.value = null),
);

const selectedItem = computed(() => {
  if (selectedItemIndex.value == null) {
    return null;
  }

  return props.clipboard[selectedItemIndex.value] as ClipboardItemInfo;
});

function toggleSelectedItem(index: number) {
  if (selectedItemIndex.value === index) {
    selectedItemIndex.value = null;
  } else {
    selectedItemIndex.value = index;
  }
}
</script>

<template>
  <div v-if="clipboard.length === 0">
    <p class="dark:text-gray-400">This clipboard is currently empty</p>
  </div>

  <div v-else class="flex flex-wrap gap-4">
    <div class="flex flex-wrap gap-2">
      <ClipboardItem
        v-for="(item, index) in clipboard"
        :key="item.hash"
        :item
        :selected="selectedItemIndex === index"
        @selected="toggleSelectedItem(index)"
      />
    </div>

    <iframe
      class="grow"
      v-if="selectedItem != null"
      :src="`${BASE_URL}?cachekill=${selectedItem.hash}&clip=${selectedItem.label}`"
    />
  </div>
</template>
