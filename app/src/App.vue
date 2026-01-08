<script setup lang="ts">
import { onMounted, onUnmounted, provide, ref, useTemplateRef } from 'vue';
import {
  BASE_URL,
  fetchClipboardBlob,
  fetchClipboardInfo,
  fileFromBlob,
  uploadToClipboard,
} from '@/api.ts';
import ClipboardItems from '@/components/Clipboard/ClipboardItems.vue';
import type { ClipboardObject } from '@/components/Clipboard/ClipboardItem.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StyledButton from '@/components/StyledButton.vue';

const fileInputRef = useTemplateRef('file-input');
function uploadButtonClicked() {
  fileInputRef.value?.click();
}

const clipboard = ref([] as ClipboardObject[]);
function fetchAndWrite() {
  if (clipboard.value.length === 0) {
    return;
  }

  fetchClipboardBlobs()
    .then((blobRecord) => new ClipboardItem(blobRecord))
    .then((clipBoardItem) => navigator.clipboard.write([clipBoardItem]))
    .then(() => console.info('Successfully wrote to local clipboard'))
    .catch((err) => console.warn('Failed to write to local clipboard', err));
}

function fetchClipboardBlobs(): Promise<Record<string, Blob>> {
  return Promise.all(
    clipboard.value.map(({ info: { label } }) =>
      fetchClipboardBlob(label).then((blob) => ({ label, blob })),
    ),
  ).then((labelledBlobs) =>
    labelledBlobs.reduce(
      (acc, { blob, label }) => {
        acc[label] = blob;
        return acc;
      },
      {} as Record<string, Blob>,
    ),
  );
}

function readAndUpload() {
  navigator.clipboard
    .read()
    .then((clipboardItems) =>
      Promise.all(
        clipboardItems.flatMap((clipboardItem) =>
          clipboardItem.types.map((type) =>
            clipboardItem.getType(type).then((blob) => fileFromBlob(blob, type)),
          ),
        ),
      ),
    )
    .then((files) => uploadFiles(files));
}

function uploadFilesFromSelection(evt: Event) {
  if (!(evt.target instanceof HTMLInputElement)) {
    return;
  }

  uploadFiles(evt.target.files ?? []);
}

const uploadProgress = ref(0);
function uploadFiles(files: FileList | File[]) {
  if (uploadProgress.value !== 0 || files.length === 0) {
    return;
  }

  const body = new FormData();
  for (const item of files) {
    body.append(item.name, item);
  }

  uploadToClipboard(body, ({ progressPercent }) => (uploadProgress.value = progressPercent))
    .then(() => console.info('Successfully uploaded to external clipboard'))
    .catch((err) => console.warn('Failed to upload to external clipboard', err))
    .then(() => fetchAndUpdateClipboardInfo());
}

function fetchAndUpdateClipboardInfo() {
  fetchClipboardInfo().then((clipboardItemInfos) => {
    if (
      clipboard.value.length !== clipboardItemInfos.length ||
      clipboard.value.some((value, index) => value.info.hash !== clipboardItemInfos[index]?.hash)
    ) {
      clipboard.value = clipboardItemInfos.map((info) => ({ info, expanded: false }));
    }
  });
}

function toggleExpansion(index: number) {
  if (clipboard.value[index] != null) {
    clipboard.value[index].expanded = !clipboard.value[index].expanded;
  }
}
provide('toggleExpansion', toggleExpansion);

onMounted(() => {
  document.addEventListener('keydown', (event) => {
    if (event.ctrlKey || event.metaKey) {
      if (event.key === 'c' && window.getSelection()?.isCollapsed) {
        fetchAndWrite();
      } else if (event.key === 'v') {
        readAndUpload();
      }
    }
  });
});

onMounted(() => {
  window.addEventListener('dragover', (e) => {
    e.preventDefault();
    e.dataTransfer!.dropEffect = 'copy';
  });

  window.addEventListener('drop', (e) => {
    e.preventDefault();

    Promise.all(
      [...(e.dataTransfer?.items ?? [])].map((item) => {
        if (item.kind == 'file') {
          return Promise.resolve(item.getAsFile()!);
        }

        return new Promise<File>((resolve) => {
          const type = item.type;
          item.getAsString((data) => resolve(fileFromBlob(new Blob([data]), type)));
        });
      }),
    ).then((files) => uploadFiles(files));
  });
});

onMounted(() => {
  const infoPollInterval = setInterval(() => fetchAndUpdateClipboardInfo(), 1000);
  fetchAndUpdateClipboardInfo();

  onUnmounted(() => {
    clearInterval(infoPollInterval);
  });
});
</script>

<template>
  <ProgressBar v-model="uploadProgress" />

  <div class="flex flex-col gap-5">
    <h1 class="select-none text-6xl font-heading text-center text-sky-600 group">
      The
      <span
        class="group-hover:animate-spin-slow before:block before:absolute before:-inset-1 before:-skew-y-3 before:bg-pink-500 relative inline-block"
      >
        <span class="relative text-amber-300">online</span>
      </span>
      Clipboard
    </h1>

    <div class="flex flex-col gap-4">
      <div class="flex gap-6">
        <StyledButton
          text="Copy"
          icon="content_copy"
          @click="fetchAndWrite"
          :disabled="clipboard.length === 0"
        />

        <StyledButton text="Paste" @click="readAndUpload" :disabled="uploadProgress !== 0" />

        <StyledButton
          text="Upload Files"
          @click="uploadButtonClicked"
          :disabled="uploadProgress !== 0"
        >
        </StyledButton>
        <input
          type="file"
          multiple
          class="hidden"
          ref="file-input"
          @change="uploadFilesFromSelection"
          :disabled="uploadProgress !== 0"
        />
      </div>

      <ClipboardItems :clipboard />
    </div>
  </div>
</template>
