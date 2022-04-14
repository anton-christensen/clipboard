
<!DOCTYPE html>
<html>
<head>
  <title>Clipboard.AntonChristensen.net</title>
  <link rel="stylesheet" type="text/css" href="style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <? if(getenv('DEVMODE') == '1'): ?> 
  <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
  <? else: ?>
  <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
  <? endif; ?>
</head>
<body>
  <div id="app">
    <div class="progress">
      <div class="bar visible" v-bind:class="{ done: upload.highlight }" v-bind:style="{ width: upload.progress + '%' }"></div>
    </div>
    <p>
    To copy data to your local clipboard press <button v-on:click="copyToLocalClipboard">Ctrl+C</button><br>
    To upload your local clipboard to the site press <button v-on:click="uploadClipboardContents">Ctrl+V</button> or drag and drop a file onto the page<br>
    <br>
    Below is listed the currently uploaded representations of the online clipboard
    </p>
    <ul>
      <li v-for="item in clipboard">
        <a v-bind:href="'?clip='+item.label" target="_blank"><b>{{ item.label }}: </b><span>{{ humanFileSize(item.size) }}</span></a>
        <button v-if="isPreviewable(item.label)" v-on:click="item.expanded = !item.expanded">Preview</button>
        <iframe v-if="item.expanded" v-bind:src="'?cachekill='+item.hash+'&clip='+item.label"></iframe>
      </li>
    </ul>
  </div>
  <script>
  var app = new Vue({
    el: '#app',
    data: {
      clipboard: [],
      upload: {highlight: false, progress: 0}
    },
    methods: {
      isPreviewable: function(label) {
        return true;
      },
      humanFileSize: function(bytes, si=false, dp=1) {
        // function stolen from stack overflow
        const thresh = si ? 1000 : 1024;
        if (Math.abs(bytes) < thresh) {
          return bytes + ' B';
        }
        const units = si 
          ? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'] 
          : ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        let u = -1;
        const r = 10**dp;
        do {
          bytes /= thresh;
          ++u;
        } while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1);
        return bytes.toFixed(dp) + ' ' + units[u];
      },
      uploadFiles: function(files) {
        self = this;
        var formData = new FormData();
        for (let i = 0; i < files.length; i++) {
          formData.append(files[i].name, files[i]);
        }

        // Uploading - for Firefox, Google Chrome and Safari
        var xhr = new XMLHttpRequest();
        // Update progress bar
        xhr.upload.addEventListener('progress', function(evt) {
          // on progress
          self.upload.progress = (evt.loaded / evt.total) * 100;
        }, false);
        xhr.upload.addEventListener('load', function(evt) {
          // onFileUploadDone
          self.upload.highlight = true;
          setTimeout(() => {
            self.upload.highlight = false;
            setTimeout(() => {
              self.upload.progress = 0;
            }, 250);
          }, 250);
        }, false);
        xhr.upload.addEventListener('loadstart', function(evt) {
          // onFileUploadStarted
          self.upload.progress = 0;
          self.upload.highlight = false;
        }, false);
        xhr.addEventListener('error', function(evt) {
          // onFileUploadServerResponse
          console.log("Error");
          console.log(evt);
        }, false);
        
        
        xhr.open("POST", "?", true);

        // Set appropriate headers
        started_at = new Date();
        xhr.send(formData);
      },
      uploadClipboardContents: async function() {
        console.log("Copying from local clipboard. this may take a while");
        this.upload.progress = 100;
        this.upload.highlight = true;
        newClipboard = [];
        
        const clipboardItems = await navigator.clipboard.read();
        for (const clipboardItem of clipboardItems) {
          for (const type of clipboardItem.types) {
            let blob = await clipboardItem.getType(type);
            if(blob)
              newClipboard.push(new File([blob], "LABEL_"+encodeURIComponent(type)));
          }
        }

        // this.clipboard = newClipboard;
        this.uploadFiles(newClipboard)
      },
      getBlob: async function(label) {
        return new Promise(function (resolve, reject) {
          var xhttp = new XMLHttpRequest();
          xhttp.responseType = "blob";
          xhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) {
                // Typical action to be performed when the document is ready:
                resolve(new Blob([xhttp.response], {type: label}));
              }
          };
          xhttp.open("GET", "?clip="+label, true);
          xhttp.send();
        });
      },
      copyToLocalClipboard: async function() {
        console.log("Copying to local clipboard. this may take a while");
        let data = {};
        for (let i = 0; i < this.clipboard.length; i++) {
          let label = this.clipboard[i].label;
          let blob = await this.getBlob(label);
          if(blob.size == 0) blob = new Blob();
          data[label] = blob;
        }

        navigator.clipboard.write([new ClipboardItem(data)]).then(function(e) {
          /* success */
          console.log(e);
          console.log("WRITE SUCCESS");
        }, function(e) {
          console.log(e);
          console.log("WRITE FAIL");
          /* failure */
        });
      }
    },
    created: async function (){
      var self = this;

      function handleKeyboardShortcuts() {
        var ctrlDown = false,
            ctrlKey = 17,
            cmdKey = 91,
            vKey = 86,
            cKey = 67;
        
        document.addEventListener("keyup", function(e) {
            if (e.keyCode == ctrlKey || e.keyCode == cmdKey) ctrlDown = false;
        });
        document.addEventListener("keydown", function(e) {
            if (e.keyCode == ctrlKey || e.keyCode == cmdKey) ctrlDown = true;
            
            if (ctrlDown && (e.keyCode == cKey) && window.getSelection().isCollapsed) self.copyToLocalClipboard();
            if (ctrlDown && (e.keyCode == vKey)) self.uploadClipboardContents();
        });
      }

      function watchForChanges() {
        function getInfo(callback) {
          var xhttp = new XMLHttpRequest();
          xhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) {
                // Typical action to be performed when the document is ready:
                callback(xhttp.responseText);
              }
          };
          xhttp.open("GET", "?info", true);
          xhttp.send();
        }
        var currentEditedTime = 0;
        readRemote = function() {
          getInfo((info) => {
            var info = JSON.parse(info);
            if(currentEditedTime != info.time) {
              let l = [];
              for (let i = 0; i < info.labels.length; i++) {
                info.labels[i].expanded = false;
                l.push(info.labels[i]);
              }
              self.clipboard = l;
              currentEditedTime = info.time;
            }
          });
        }
        setInterval(readRemote, 1000);
        readRemote();
      }
      
      function handleDropFiles() {
        let prevDef = function(evt) {
          evt.preventDefault();
          evt.stopPropagation();
        };
        window.addEventListener("dragenter", prevDef, false);
        window.addEventListener("dragover", prevDef, false);
        window.addEventListener("dragleave", prevDef, false);

        window.addEventListener("drop", function (evt) {
          prevDef(evt);
          console.log(evt.dataTransfer.files);
          let fs = []
          for (let i = 0; i < evt.dataTransfer.files.length; i++) {
            const f = evt.dataTransfer.files[i];
            fs.push(new File([f], "DROP_"+i))
          }
          self.uploadFiles(fs);
        },false);
      }

      handleDropFiles();
      handleKeyboardShortcuts();
      watchForChanges();

    }
  })
</script>

</body>
</html>
