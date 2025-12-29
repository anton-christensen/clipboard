<script lang="ts">
export default {
  data() {
    return {
      clipboard: [],
      upload: { highlight: false, progress: 0 },
    };
  },
  methods: {
    isPreviewable: function () {
      return true;
    },
    humanFileSize: function (bytes, si = false, dp = 1) {
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
    uploadFiles: function (files) {
      const formData = new FormData();
      for (let i = 0; i < files.length; i++) {
        formData.append(files[i].name, files[i]);
      }

      // Uploading - for Firefox, Google Chrome and Safari
      const xhr = new XMLHttpRequest();
      // Update progress bar
      xhr.upload.addEventListener(
        'progress',
        (evt) => {
          // on progress
          this.upload.progress = (evt.loaded / evt.total) * 100;
        },
        false,
      );
      xhr.upload.addEventListener(
        'load',
        () => {
          // onFileUploadDone
          this.upload.highlight = true;
          setTimeout(() => {
            this.upload.highlight = false;
            setTimeout(() => {
              this.upload.progress = 0;
            }, 250);
          }, 250);
        },
        false,
      );
      xhr.upload.addEventListener(
        'loadstart',
        () => {
          // onFileUploadStarted
          this.upload.progress = 0;
          this.upload.highlight = false;
        },
        false,
      );
      xhr.addEventListener(
        'error',
        function (evt) {
          // onFileUploadServerResponse
          console.log('Error');
          console.log(evt);
        },
        false,
      );

      xhr.open('POST', '?', true);
      xhr.send(formData);
    },
    uploadFilesFromSelection: function (evt) {
      console.log(evt.target.files);
      this.uploadFiles(evt.target.files);
    },
    uploadClipboardContents: async function () {
      console.log('Copying from local clipboard. this may take a while');
      this.upload.progress = 100;
      this.upload.highlight = true;
      const newClipboard = [];

      const clipboardItems = await navigator.clipboard.read();
      for (const clipboardItem of clipboardItems) {
        for (const type of clipboardItem.types) {
          const blob = await clipboardItem.getType(type);
          if (blob) newClipboard.push(new File([blob], 'LABEL_' + encodeURIComponent(type)));
        }
      }

      // this.clipboard = newClipboard;
      this.uploadFiles(newClipboard);
    },
    getBlob: async function (label) {
      return new Promise(function (resolve) {
        const xhttp = new XMLHttpRequest();
        xhttp.responseType = 'blob';
        xhttp.onreadystatechange = function () {
          if (this.readyState === 4 && this.status === 200) {
            // Typical action to be performed when the document is ready:
            resolve(new Blob([xhttp.response], { type: label }));
          }
        };
        xhttp.open('GET', '?clip=' + label, true);
        xhttp.send();
      });
    },
    copyToLocalClipboard: async function () {
      console.log('Copying to local clipboard. this may take a while');
      const data = {};
      for (let i = 0; i < this.clipboard.length; i++) {
        const label = this.clipboard[i].label;
        let blob = await this.getBlob(label);
        if (blob.size === 0) blob = new Blob();
        data[label] = blob;
      }

      navigator.clipboard.write([new ClipboardItem(data)]).then(
        function (e) {
          /* success */
          console.log(e);
          console.log('WRITE SUCCESS');
        },
        function (e) {
          console.log(e);
          console.log('WRITE FAIL');
          /* failure */
        },
      );
    },
  },
  created() {
    const handleKeyboardShortcuts = () => {
      let ctrlDown = false;
      const ctrlKey = 17,
        cmdKey = 91,
        vKey = 86,
        cKey = 67;

      document.addEventListener('keyup', (e) => {
        if (e.keyCode === ctrlKey || e.keyCode === cmdKey) ctrlDown = false;
      });
      document.addEventListener('keydown', (e) => {
        if (e.keyCode === ctrlKey || e.keyCode === cmdKey) ctrlDown = true;

        if (ctrlDown && e.keyCode === cKey && window.getSelection().isCollapsed)
          this.copyToLocalClipboard();
        if (ctrlDown && e.keyCode === vKey) this.uploadClipboardContents();
      });
    };

    const watchForChanges = () => {
      function getInfo(callback) {
        const request = new XMLHttpRequest();
        request.onreadystatechange = function () {
          if (this.readyState === 4 && this.status === 200) {
            // Typical action to be performed when the document is ready:
            callback(request.responseText);
          }
        };
        request.open('GET', '?info', true);
        request.send();
      }

      let currentEditedTime = 0;
      const readRemote = () => {
        getInfo((info) => {
          const parsedInfo = JSON.parse(info);
          const newEditTime = parsedInfo.length === 0 ? 0 : parsedInfo[0].time;
          if (currentEditedTime !== newEditTime)
            this.clipboard = parsedInfo.map((x) => {
              return { ...x, expanded: false };
            });
          currentEditedTime = newEditTime;
        });
      };
      setInterval(readRemote, 1000);
      readRemote();
    };

    const handleDropFiles = () => {
      const prevDef = function (evt) {
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
          this.uploadFiles(evt.dataTransfer.files);
        },
        false,
      );
    };

    handleDropFiles();
    handleKeyboardShortcuts();
    watchForChanges();
  },
};
</script>

<template>
  <input
    type="file"
    multiple
    ref="files"
    @change="uploadFilesFromSelection"
    style="display: none"
  />
  <div class="progress">
    <div
      class="bar visible"
      v-bind:class="{ done: upload.highlight }"
      v-bind:style="{ width: upload.progress + '%' }"
    ></div>
  </div>
  <ul>
    <li>
      <button @click="copyToLocalClipboard">Ctrl+C</button>
      : To copy data to your local clipboard
    </li>
    <li>
      <button @click="uploadClipboardContents">Ctrl+V</button>
      : To upload your local clipboard to the site
    </li>
    <li>
      <button @click="$refs.files.click()">Upload</button>
      : files by dragging them onto the page
    </li>
    <li>
      <a href="?install">Instructions</a>: for getting OS integrations like
      <code>Ctrl+Win+C/V</code> to copy and paste to/from clipboard from anywhere directly
    </li>
  </ul>

  <p>Below is listed the currently uploaded representations of the online clipboard</p>
  <ul>
    <li v-for="item in clipboard" v-bind:key="item.label">
      <a v-bind:href="'?clip=' + item.label" target="_blank"
        ><b>{{ item.label }}: </b><span>{{ humanFileSize(item.size) }}</span></a
      >
      <button v-if="isPreviewable(item.label)" v-on:click="item.expanded = !item.expanded">
        Preview
      </button>
      <iframe
        v-if="item.expanded"
        v-bind:src="'?cachekill=' + item.hash + '&clip=' + item.label"
      ></iframe>
    </li>
  </ul>
</template>
