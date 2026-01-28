<script setup lang="ts">
import { onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import {
  type ClipboardItemInfo,
  fetchClipboardBlob,
  fetchClipboardInfo,
  fileFromBlob,
  uploadToClipboard,
} from '@/api.ts';
import ClipboardItems from '@/components/ClipboardItems.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StyledButton from '@/components/StyledButton.vue';
import HeaderTitle from '@/components/HeaderTitle.vue';
import ThemeSelector from '@/components/ThemeSelector.vue';

const fileInputRef = useTemplateRef('file-input');
function uploadButtonClicked() {
  fileInputRef.value?.click();
}

const clipboard = ref([] as ClipboardItemInfo[]);
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
    clipboard.value.map(({ label }) => fetchClipboardBlob(label).then((blob) => ({ label, blob }))),
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
      clipboard.value.some((value, index) => value.hash !== clipboardItemInfos[index]?.hash)
    ) {
      clipboard.value = clipboardItemInfos;
    }
  });
}

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

  <ThemeSelector />

  <div
    class="animate-sunrise fixed z-[-1] h-full w-full bg-radial-[100vh_circle_at_var(--rise-x)_var(--rise-y)] from-amber-300 from-10% via-pink-300 via-35% to-sky-300 dark:bg-radial-[100vh_circle_at_var(--rise-x)_calc(var(--rise-y)-30%)] dark:from-blue-100 dark:from-5% dark:via-gray-800 dark:via-10% dark:to-gray-950 dark:to-20%"
  ></div>

  <div class="flex flex-col gap-12 p-8">
    <HeaderTitle />

    <div class="flex gap-6 self-center">
      <StyledButton
        text="Copy"
        icon="content_copy"
        @click="fetchAndWrite"
        :disabled="clipboard.length === 0"
      />

      <StyledButton
        text="Paste"
        icon="content_paste"
        @click="readAndUpload"
        :disabled="uploadProgress !== 0"
      />

      <StyledButton
        text="Upload files"
        icon="upload_file"
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
</template>
