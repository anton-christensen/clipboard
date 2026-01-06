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
    .then(() => console.log('Successfully wrote to write to local clipboard'))
    .catch((err) => console.warn('Failed to write to local clipboard', err));
}

function fetchClipboardBlobs(): Promise<Record<string, Blob>> {
  const promises = clipboard.value.map(({ info: { label } }) =>
    fetchClipboardBlob(label).then((blob) => ({ label, blob })),
  );

  return Promise.all(promises).then((result) =>
    result.reduce(
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

const upload = ref({ progress: 0, done: false });
function uploadFiles(files: FileList | File[]) {
  const body = new FormData();
  for (const item of files) {
    body.append(item.name, item);
  }

  uploadToClipboard(body, (progress) => (upload.value.progress = progress.progressPercent)).then(
    () => {
      fetchAndUpdateClipboardInfo();
      upload.value.done = true;
      setTimeout(() => {
        upload.value.progress = 0;
        setTimeout(() => {
          upload.value.done = false;
        }, 1000);
      }, 1000);
    },
  );
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
  <div class="progress">
    <div class="bar" :class="{ done: upload.done }" :style="{ width: upload.progress + '%' }"></div>
  </div>
  <ul>
    <li>
      <button @click="fetchAndWrite" :disabled="clipboard.length === 0">Ctrl+C</button>
      : To copy data to your local clipboard
    </li>
    <li>
      <button @click="readAndUpload">Ctrl+V</button>
      : To upload your local clipboard to the site
    </li>
    <li>
      <button @click="uploadButtonClicked">Upload</button>
      : files by dragging them onto the page
      <input
        type="file"
        multiple
        ref="file-input"
        @change="uploadFilesFromSelection"
        style="display: none"
      />
    </li>
    <li>
      <a :href="`${BASE_URL}?install`">Instructions</a>: for getting OS integrations like
      <code>Ctrl+Win+C/V</code> to copy and paste to/from clipboard from anywhere directly
    </li>
  </ul>

  <ClipboardItems :clipboard />
</template>
