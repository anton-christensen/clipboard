
<!DOCTYPE html>
<html>
<head>
  <title>Clipboard.AntonChristensen.net</title>
  <link rel="stylesheet" type="text/css" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
  <!-- <script src="https://cdn.jsdelivr.net/npm/vue@2"></script> -->
</head>
<body>
  <div id="app">
    <div class="progress">
      <div class="bar visible" v-bind:class="{ done: upload.highlight }" v-bind:style="{ width: upload.progress + '%' }"></div>
    </div>
    <ul>
      <li v-for="item in clipboard">
        <a v-bind:href="'/?clip='+item.label"><b>{{ item.label }}: </b><span>{{ item.size }}</span></a>
      </li>
    </ul>
  </div>

  <script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
  <script>
  var app = new Vue({
    el: '#app',
    data: {
      clipboard: [],
      upload: {highlight: false, progress: 0}
    },
    methods: {

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
          console.log(self.upload.progress);
        }, false);
        xhr.upload.addEventListener('load', function(evt) {
          // onFileUploadDone
          console.log("load");
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
          console.log("loadstart");
          self.upload.progress = 0;
          self.upload.highlight = false;
        }, false);
        xhr.addEventListener('readystatechange', function(evt) {
          // onFileUploadServerResponse
          // console.log("readystatechange");

        }, false);
        
        
        xhr.open("POST", "/", true);

        // Set appropriate headers
        started_at = new Date();
        xhr.send(formData);
      },
      uploadClipboardContents: async function() {
        console.log("Copying from clipboard. this may take a while");
        this.upload.progress = 100;
        this.upload.highlight = true;
        newClipboard = [];
        
        const clipboardItems = await navigator.clipboard.read();
        for (const clipboardItem of clipboardItems) {
          for (const type of clipboardItem.types) {
            let blob = await clipboardItem.getType(type);
            blob = blob ? blob : new Blob([])
            console.log(blob.type);

            newClipboard.push({label: type, size: blob.size, blob: blob})
          }
        }

        console.log(newClipboard);
        this.clipboard = newClipboard;
        this.uploadFiles(newClipboard.map(item => new File([item.blob], "LABEL_"+encodeURIComponent(item.label))))
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
          xhttp.open("GET", "/?clip="+label, true);
          xhttp.send();
        });
      },
      copyToLocalClipboard: async function() {
        console.log("Copying to local clipboard. this may take a while");
        let data = {};
        for (let i = 0; i < this.clipboard.length; i++) {
          let label = this.clipboard[i].label;
          let blob = await this.getBlob(label);
          console.log(blob)
          data[label] = blob;
        }

        navigator.clipboard.write([new ClipboardItem(data)]).then(function(e) {
          /* success */
          console.log(e)
          console.log("WRITE SUCCESS");
        }, function(e) {
          console.log(e)
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

        $(document).keydown(function(e) {
            if (e.keyCode == ctrlKey || e.keyCode == cmdKey) ctrlDown = true;
        }).keyup(function(e) {
            if (e.keyCode == ctrlKey || e.keyCode == cmdKey) ctrlDown = false;
        });
        // Document Ctrl + C/V 
        $(document).keydown(function(e) {
            if(e.target == $('textarea')[0])
              return true;
            
            if (ctrlDown && (e.keyCode == cKey)) self.copyToLocalClipboard(); // console.log("Document catch Ctrl+C");
            if (ctrlDown && (e.keyCode == vKey)) self.uploadClipboardContents(); //console.log("Document catch Ctrl+V")// uploadClipboardContents();
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
          xhttp.open("GET", "/?info", true);
          xhttp.send();
        }
        var currentHash = "";
        var currentSize = 0;
        var currentEditedTime = 0;
        setInterval(() => {
          getInfo((info) => {
            var info = JSON.parse(info);
            if(currentEditedTime != info.time) {
              // location.reload()
              let l = [];
              for (let i = 0; i < info.labels.length; i++) {
                l.push(info.labels[i]);
              }
              self.clipboard = l;
            }
            else {
              currentHash = info.hash;
              currentSize = info.size;
              currentEditedTime = info.time;
            }
          });
        }, 1000);
      }      

      handleKeyboardShortcuts();
      watchForChanges();

    }
  })
</script>
</body>
</html>
