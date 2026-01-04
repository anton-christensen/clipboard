<script lang="ts">
import { progressTrackingUpload } from '@/helpers.ts';

interface ClipboardItem {
  label: string;
  mime: string;
  size: string;
  hash: string;
  time: number;
  expanded: boolean;
}

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
    humanFileSize: function (bytes: number, si = false, dp = 1) {
      // function stolen from stack overflow
      const thresh = si ? 1000 : 1024;
      if (Math.abs(bytes) < thresh) {
        return bytes + ' B';
      }
      const units = si
        ? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
        : ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
      let u = -1;
      const r = 10 ** dp;
      do {
        bytes /= thresh;
        ++u;
      } while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1);
      return bytes.toFixed(dp) + ' ' + units[u];
    },
    uploadFiles: function (files: FileList | File[]) {
      const body = new FormData();
      for (const item of files) {
        body.append(item.name, item);
      }

      progressTrackingUpload('https://clipboard.achri.dk/?', {
        method: 'POST',
        body,
        onProgress: (progress) => (this.upload.progress = progress.progressPercent),
      }).then(() => {
        this.upload.done = true;
        setTimeout(() => {
          this.upload.progress = 0;
          setTimeout(() => {
            this.upload.done = false;
          }, 1000);
        }, 1000);
      });
    },
    uploadButtonClicked: function () {
      const fileInput = this.$refs.files as HTMLInputElement;
      if (fileInput != null) {
        fileInput.click();
      }
    },
    uploadFilesFromSelection: function (evt: Event) {
      if (!(evt.target instanceof HTMLInputElement)) {
        return;
      }

      console.log('Selected files', evt.target.files);
      this.uploadFiles(evt.target.files ?? []);
    },
    uploadClipboardContents: async function () {
      console.log('Copying from local clipboard. this may take a while');

      const newClipboard = [];
      const clipboardItems = await navigator.clipboard.read();
      for (const clipboardItem of clipboardItems) {
        for (const type of clipboardItem.types) {
          const blob = await clipboardItem.getType(type);
          if (blob) newClipboard.push(new File([blob], 'LABEL_' + encodeURIComponent(type)));
        }
      }

      this.uploadFiles(newClipboard);
    },
    fetchBlob: (label: string) =>
      fetch(`https://clipboard.achri.dk/?clip=${label}`).then((response) => response.blob()),
    copyToLocalClipboard: async function () {
      if (this.clipboard.length === 0) {
        return;
      }

      console.log('Copying to local clipboard. this may take a while');
      const data: Record<string, Blob> = {};
      for (const item of this.clipboard) {
        const label = item.label;
        data[label] = await this.fetchBlob(label);
      }

      const clipBoardItems = [new ClipboardItem(data)];
      navigator.clipboard
        .write(clipBoardItems)
        .then((e) => {
          console.log(e);
          console.log('WRITE SUCCESS');
        })
        .catch((e) => {
          console.log(e);
          console.log('WRITE FAIL');
        });
    },
  },
  mounted() {
    document.addEventListener('keydown', (event) => {
      if (event.ctrlKey || event.metaKey) {
        if (event.key === 'c' && window.getSelection()?.isCollapsed) {
          this.copyToLocalClipboard();
        } else if (event.key === 'v') {
          this.uploadClipboardContents();
        }
      }
    });

    const pollChanges = () => {
      let currentEditedTime = 0;
      const readRemote = () => {
        fetch('https://clipboard.achri.dk/?info')
          .then((response) => response.json())
          .then((parsedInfo) => {
            const newEditTime = parsedInfo.length === 0 ? 0 : parsedInfo[0].time;
            if (currentEditedTime !== newEditTime) {
              this.clipboard = parsedInfo.map((x: object) => ({ ...x, expanded: false }));
              currentEditedTime = newEditTime;
            }
          });
      };

      setInterval(readRemote, 1000);
      readRemote();
    };

    const handleDropFiles = () => {
      const prevDef = function (evt: Event) {
        evt.preventDefault();
        evt.stopPropagation();
      };
      window.addEventListener('dragenter', prevDef, false);
      window.addEventListener('dragover', prevDef, false);
      window.addEventListener('dragleave', prevDef, false);

      window.addEventListener(
        'drop',
        (evt) => {
          prevDef(evt);
          this.uploadFiles(evt.dataTransfer?.files ?? []);
        },
        false,
      );
    };

    handleDropFiles();
    pollChanges();
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
      <button @click="copyToLocalClipboard" v-bind:disabled="clipboard.length === 0">Ctrl+C</button>
      : To copy data to your local clipboard
    </li>
    <li>
      <button @click="uploadClipboardContents">Ctrl+V</button>
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
    <li v-for="item in clipboard" v-bind:key="item.label">
      <a v-bind:href="'https://clipboard.achri.dk/?clip=' + item.label" target="_blank"
        ><b>{{ item.label }}: </b><span>{{ humanFileSize(Number(item.size)) }}</span></a
      >
      <button v-if="isPreviewable()" v-on:click="item.expanded = !item.expanded">Preview</button>
      <iframe
        v-if="item.expanded"
        v-bind:src="'https://clipboard.achri.dk/?cachekill=' + item.hash + '&clip=' + item.label"
      ></iframe>
    </li>
  </ul>
</template>
