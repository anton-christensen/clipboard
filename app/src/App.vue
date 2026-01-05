<script lang="ts">
import { progressTrackingUpload } from '@/helpers.ts';

interface ClipboardItemInfo {
  label: string;
  mime: string;
  size: string;
  hash: string;
  time: number;
}

interface ClipboardItem {
  info: ClipboardItemInfo;
  expanded: boolean;
}

let infoPollInterval: number;

export default {
  data() {
    return {
      clipboard: [] as ClipboardItem[],
      upload: { progress: 0, done: false },
    };
  },
  methods: {
    isPreviewable: function () {
      return true;
    },
    humanFileSize: function (bytes: number, dp = 1) {
      const threshold = 1000;
      const units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

      if (Math.abs(bytes) < threshold) {
        return `${bytes} B`;
      }

      let u = -1;
      const r = 10 ** dp;
      do {
        bytes /= threshold;
        ++u;
      } while (Math.round(Math.abs(bytes) * r) / r >= threshold && u < units.length - 1);

      return `${bytes.toFixed(dp)} ${units[u]}`;
    },
    uploadButtonClicked: function () {
      const fileInput = this.$refs.files as HTMLInputElement;
      if (fileInput != null) {
        fileInput.click();
      }
    },

    fetchAndWrite: function () {
      if (this.clipboard.length === 0) {
        return;
      }

      this.fetchClipboardBlobs()
        .then((blobRecord) => new ClipboardItem(blobRecord))
        .then((clipBoardItem) => navigator.clipboard.write([clipBoardItem]))
        .then(() => console.log('Successfully wrote to write to local clipboard'))
        .catch((err) => console.warn('Failed to write to local clipboard', err));
    },
    fetchClipboardBlobs: function (): Promise<Record<string, Blob>> {
      const promises = this.clipboard.map(({ info: { label } }) =>
        this.fetchClipboardBlob(label).then((blob) => ({ label, blob })),
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
    },
    fetchClipboardBlob: function (label: string): Promise<Blob> {
      return fetch(`?clip=${label}`).then((response) => response.blob());
    },
    fetchClipboardInfo: function (): Promise<ClipboardItemInfo[]> {
      return fetch('?info').then((response) => response.json());
    },

    readAndUpload: async function () {
      navigator.clipboard
        .read()
        .then((clipboardItems) =>
          Promise.all(
            clipboardItems.flatMap((clipboardItem) =>
              clipboardItem.types.map((type) =>
                clipboardItem
                  .getType(type)
                  .then((blob) => new File([blob], `LABEL_${encodeURIComponent(type)}`)),
              ),
            ),
          ),
        )
        .then((files) => this.uploadFiles(files));
    },
    uploadFilesFromSelection: function (evt: Event) {
      if (!(evt.target instanceof HTMLInputElement)) {
        return;
      }

      this.uploadFiles(evt.target.files ?? []);
    },
    uploadFiles: function (files: FileList | File[]) {
      const body = new FormData();
      for (const item of files) {
        body.append(item.name, item);
      }

      progressTrackingUpload(
        '?',
        {
          method: 'POST',
          body,
        },
        (progress) => (this.upload.progress = progress.progressPercent),
      ).then(() => {
        this.fetchAndUpdateClipboardInfo();
        this.upload.done = true;
        setTimeout(() => {
          this.upload.progress = 0;
          setTimeout(() => {
            this.upload.done = false;
          }, 1000);
        }, 1000);
      });
    },

    fetchAndUpdateClipboardInfo: function () {
      this.fetchClipboardInfo().then((clipboardItemInfos) => {
        if (this.clipboard[0]?.info.time !== clipboardItemInfos[0]?.time) {
          this.clipboard = clipboardItemInfos.map((info) => ({ info, expanded: false }));
        }
      });
    },
  },
  mounted() {
    // Keyboard shortcuts
    document.addEventListener('keydown', (event) => {
      if (event.ctrlKey || event.metaKey) {
        if (event.key === 'c' && window.getSelection()?.isCollapsed) {
          this.fetchAndWrite();
        } else if (event.key === 'v') {
          this.readAndUpload();
        }
      }
    });

    // Dragged files
    window.addEventListener('dragover', (e) => {
      e.dataTransfer!.dropEffect = 'copy';
      e.preventDefault();
    });
    window.addEventListener('drop', (e) => {
      if (e.dataTransfer?.items == null) {
        return;
      }

      if ([...e.dataTransfer.items].some((item) => item.kind === 'file')) {
        e.preventDefault();
      }
    });

    // Polling for updates
    infoPollInterval = setInterval(() => this.fetchAndUpdateClipboardInfo(), 1000);
    this.fetchAndUpdateClipboardInfo();
  },
  unmounted() {
    clearInterval(infoPollInterval);
  },
};
</script>

<template>
  <div class="progress">
    <div
      class="bar"
      v-bind:class="{ done: upload.done }"
      v-bind:style="{ width: upload.progress + '%' }"
    ></div>
  </div>
  <ul>
    <li>
      <button @click="fetchAndWrite" v-bind:disabled="clipboard.length === 0">Ctrl+C</button>
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
        ref="files"
        @change="uploadFilesFromSelection"
        style="display: none"
      />
    </li>
    <li>
      <a href="?install">Instructions</a>: for getting OS integrations like
      <code>Ctrl+Win+C/V</code> to copy and paste to/from clipboard from anywhere directly
    </li>
  </ul>

  <p>Below is listed the currently uploaded representations of the online clipboard</p>
  <ul>
    <li v-for="item in clipboard" v-bind:key="item.info.label">
      <a v-bind:href="'?clip=' + item.info.label" target="_blank"
        ><b>{{ item.info.label }}: </b><span>{{ humanFileSize(Number(item.info.size)) }}</span></a
      >
      <button v-if="isPreviewable()" v-on:click="item.expanded = !item.expanded">Preview</button>
      <iframe
        v-if="item.expanded"
        v-bind:src="'?cachekill=' + item.info.hash + '&clip=' + item.info.label"
      ></iframe>
    </li>
  </ul>
</template>
