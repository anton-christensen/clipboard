<!DOCTYPE html>
<html>
<head>
  <title>Clipboard.AntonChristensen.net</title>
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
  <div class="contentwrapper">
    debug page
  </div>

  <script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
  <script type="text/javascript">
    $(function() {
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
          if (ctrlDown && (e.keyCode == cKey)) console.log("Document catch Ctrl+C");
          if (ctrlDown && (e.keyCode == vKey)) uploadClipboardContents();
      });


      async function uploadClipboardContents() {

        const clipboardItems = await navigator.clipboard.read();
        let i = 1;
        for (const clipboardItem of clipboardItems) {
          let j = 1;

          for (const type of clipboardItem.types) {
            const blob = await clipboardItem.getType(type);
            console.log(i,j,"type", type)
            console.log(i,j,"blob", blob)
            console.log(i,j,"size", blob.size)

          }
        }
      }
    });
  </script>


  <script>
  </script>
</body>
</html>
