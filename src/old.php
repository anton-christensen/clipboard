<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require("functions.php");
require("db.php");

function createIfDoesntExist($f) {
  if (!file_exists($f)) {
    touch($f, strtotime('-1 days'));
  }
}

$fileLocation = "/var/www/data/clipfile";
$nameLocation = "/var/www/data/clipfilename";

createIfDoesntExist($fileLocation);
createIfDoesntExist($nameLocation);


$isCurl = preg_match('/curl/', $_SERVER['HTTP_USER_AGENT']);

$forceDownload = $isCurl || isset($_GET['download']);
$mime = mime_content_type($fileLocation);

if($mime == 'inode/x-empty') {
  $mime = 'text/plain';
}

$fileName = file_get_contents($nameLocation);


if(count($_FILES) > 0) {
  $db = new Database();
  $db->replacePaste($_FILES);
  
  die();
}


// if file upload
if(isset($_FILES['paste'])) {
	file_put_contents($nameLocation, $_FILES['paste']['name']);
	if(!move_uploaded_file($_FILES['paste']['tmp_name'], $fileLocation)) {
		var_dump($_FILES);
		die("FAILED TO MOVE FILE");
	}
	else if($isCurl) {
		print("File method\n");
		print("Size ".filesize($fileLocation)."\n");
		print("Mime ".mime_content_type($fileLocation)."\n");
		die();
	}
	
}
// if post parameter upload
elseif(isset($_POST['paste'])) {
	file_put_contents($fileLocation, $_POST['paste']);
	$ext = mime2ext(mime_content_type($fileLocation));
	file_put_contents($nameLocation, "clipboard".($ext ? ".".$ext : ""));
	if($isCurl) {
		print("POST method\n");
		print("Size ".filesize($fileLocation)."\n");
		print("Mime ".mime_content_type($fileLocation)."\n");
		die();
	}
}


if(isset($_GET)) {
	if(isset($_GET['path'])) {
		die();
	}
	else if(isset($_GET['clip'])) {
		require("clip.php");
		die();
	}
	else if(isset($_GET['install'])) {
		require("install.php");
		die();
	}
	else if(isset($_GET['test'])) {
		include("test.php");
		die();
	}
	else if(isset($_GET['info'])) {
    include("info.php");
    die();
  }
  else if(isset($_GET['mime'])) {
    include("mime.php");
    die();
  }
	else {
		// include("404.php");
	}
}

// if curl -> return clipboard only
if($isCurl) {
    require("clip.php");
    die();
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>Clipboard.AntonChristensen.net</title>
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
  <div class="contentwrapper">
    <div id="inputWrappers">
      <form id="fileForm" action="/" method="POST" enctype="multipart/form-data">
        <div id="drop-area">
          <span>Paste a file here</span>
        </div>
        <div id="progressbar"><div class="bar"><span class="bar-text"></span></div></div>
        
        <input type="file" name="paste" id="fileInput" style="visibility: hidden;">
        <input type="submit" value="Upload File" name="submit" id="fileSubmit" style="visibility: hidden;" />
      </form>

      <form id="textForm" method="POST" action="/">
        <textarea name="paste"></textarea>
        <input type="submit" value="upload snippet" />
      </form>
    </div>

    <div id="clipboardContent">
      <div id="contentContainer">
        <div class="downloadLinkWrapper download">
          <a class="downloadLink" target="_blank" href="/?clip&download">
            <span class="downloadLinkText">Download<br> <?= $fileName ?><br><?= human_filesize(filesize($fileLocation)) ?></span>
          </a>
        </div>
        <div class="downloadLinkWrapper preview">
          <a class="downloadLink" target="_blank" href="/?clip">
            <span class="downloadLinkText">Preview<br> <?= $fileName ?></span>
          </a>
        </div>
      </div>
    </div>
  </div>

  <script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
  <script type="text/javascript">
    $(function() {
      // prevent from uploading twice on refresh
      if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
      }

      function uploadFile (file) {
        var formData = new FormData();
        formData.append("paste", file);
        console.log(file);
        // return;
        // Uploading - for Firefox, Google Chrome and Safari
        var xhr = new XMLHttpRequest();
        // Update progress bar
        xhr.upload.addEventListener("progress", function (evt) {

        }, false);
        xhr.upload.addEventListener('progress', onFileUploadProgress, false);
        xhr.upload.addEventListener('load', onFileUploadDone, false);
        xhr.upload.addEventListener('loadstart', onFileUploadStarted, false);
        xhr.addEventListener('readystatechange', onFileUploadServerResponse, false);
        
        xhr.open("POST", "/", true);

        // Set appropriate headers
        started_at = new Date();
        xhr.send(formData);
      }

      function onFileUploadStarted(evt) {
        $('#drop-area').hide();
        $('#progressbar').show();
      }
      function onFileUploadDone(evt) {
        console.log("done")
        $('.bar-text').text("Finished uploading!");
        // window.location.reload();
      }
      function onFileUploadServerResponse(evt) {

        $('#drop-area').show();
        $('#progressbar').hide();
      }
      var started_at = new Date();
      function onFileUploadProgress(evt) {
        if (evt.lengthComputable) {
          $('#progressbar > .bar').css('width', (evt.loaded / evt.total) * 100 + "%");
          $('.bar-text').text( `${Math.round((evt.loaded / evt.total) * 100)}%`);

          // Time Remaining
          var seconds_elapsed =   ( new Date().getTime() - started_at.getTime() )/1000;
          var bytes_per_second =  seconds_elapsed ? evt.loaded / seconds_elapsed : 0 ;
          var Kbytes_per_second = bytes_per_second / 1000 ;
          var remaining_bytes =   evt.total - evt.loaded;
          var seconds_remaining = seconds_elapsed ? remaining_bytes / bytes_per_second : 'calculating' ;
          console.log("seconds_remaining: ", seconds_remaining);
        }
        else {
          // No data to calculate on
        }
      }


      function traverseFiles(files) {
        if (typeof files !== "undefined") {
          for (var i=0, l=files.length; i<l; i++) {
            uploadFile(files[i]);
          }
        }
      }
      
      $("#fileInput").change(function() {
        traverseFiles(this.files);
      });
      $('#drop-area').on("drop", function (evt) {
        evt = evt.originalEvent;
        traverseFiles(evt.dataTransfer.files);
        evt.preventDefault();
        evt.stopPropagation();
      });
      $('#drop-area').on("click", function (evt) {
        // traverseFiles(evt.dataTransfer.files);
        console.log("clicked drop-area");
        $('#fileInput').click();
        evt.preventDefault();
        evt.stopPropagation();
      });

      // disable default drop events
      $("html").on("dragover", function(event) {
        event.preventDefault();  
        event.stopPropagation();
      });

      $("html").on("dragleave", function(event) {
        event.preventDefault();  
        event.stopPropagation();
      });

      $("html").on("drop", function(event) {
        event.preventDefault();  
        event.stopPropagation();
      });



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

      $(".no-copy-paste").keydown(function(e) {
          if (ctrlDown && (e.keyCode == vKey || e.keyCode == cKey)) return false;
      });
      
      // Document Ctrl + C/V 
      $(document).keydown(function(e) {
          if(e.target == $('textarea')[0])
            return true;
          
          if (ctrlDown && (e.keyCode == cKey)) console.log("Document catch Ctrl+C");
          if (ctrlDown && (e.keyCode == vKey)) uploadClipboardContents();
      });


      async function uploadClipboardContents() {

        const clipboardItems = await navigator.clipboard.read();

        for (const clipboardItem of clipboardItems) {
          let largestBlob = undefined
          let largestBlobSize = 0

          for (const type of clipboardItem.types) {
            const blob = await clipboardItem.getType(type);
            if(blob && blob.size > largestBlobSize) {
              largestBlob = blob
              largestBlobSize = blob.size
            }

          }
          let f = new File([largestBlob], "clipboard")
          uploadFile(f)
        }
      }
    });
  </script>


  <script>
    var mimeCheck = function (type) {
      let previewableMimes = ['image/', 'text/'];
      for (var i = previewableMimes.length - 1; i >= 0; i--) {
        if(type.indexOf(previewableMimes[i]) != -1)
          return true;
      }
      return Array.prototype.reduce.call(navigator.plugins, function (supported, plugin) {
        return supported || Array.prototype.reduce.call(plugin, function (supported, mime) {
          return supported || mime.type == type;
        }, supported);
      }, false);
    };

    function getInfo(cb) {
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
             // Typical action to be performed when the document is ready:
             cb(xhttp.responseText);
          }
      };
      xhttp.open("GET", "/?info", true);
      xhttp.send();
    }
    var currentHash = "<?= hash_file("md5", $fileLocation) ?>";
    var currentSize = <?= filesize($fileLocation) ?>;
    setInterval(() => {
      getInfo((info) => {
        var info = JSON.parse(info);
        if(currentHash && currentHash != info.hash) {
          location.reload()
        }
        else {
          currentHash = info.hash;
          currentSize = info.size;
        }
      });
    }, 1000);

    var isPreviewable = mimeCheck("<?= $mime ?>");
    console.log("<?= $mime ?>", isPreviewable);

    if(isPreviewable) {
      if(currentSize <= 10000000) {
        document.getElementById('contentContainer').innerHTML += '<iframe id="previewIFrame" src="/?clip&preview"></iframe>';
      }
      document.getElementsByClassName('downloadLinkWrapper preview')[0].style.display = "block";
    }

  </script>
</body>
</html>
