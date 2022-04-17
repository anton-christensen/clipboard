<!DOCTYPE html>
<html>
<head>
  <title>Clipboard.AntonChristensen.net</title>
  <link rel="stylesheet" type="text/css" href="style.css?v1">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <? if(getenv('DEVMODE') == '1'): ?> 
  <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
  <? else: ?>
  <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
  <? endif; ?>
</head>
<body>
<p>
    ### goes in .bashrc
    <pre><code>
alias clip='xclip -sel c'
alias paste='xclip -sel c -o'

alias backupClipboard="paste | xclip -sel backup"
alias restoreClipboard="xclip -sel backup -out | clip"

alias clipget="curl -s https://clipboard.achri.dk 2>&1"
clipset() {
    cat /dev/stdin > /tmp/clipboardUpload
    curl -F 'paste=@/tmp/clipboardUpload' https://clipboard.achri.dk/
}
alias ppaste='paste | clipset'
alias cclip='clipget | clip'
    </code></pre>

    Setup the following keyboard shortcuts for and <kbd>Ctrl+Win+C</kbd>
    <code>bash -c "xclip -sel c -o | xclip -sel backup; xdotool keyup 'ctrl+meta+super+c'; xdotool key --clearmodifiers 'ctrl+c'; xclip -sel c -o > /tmp/clipboardUpload; curl -F 'paste=@/tmp/clipboardUpload' https://clipboard.achri.dk/; xclip -sel backup -out | xclip -sel c"</code>
    
    and <kbd>Ctrl+Win+V</kbd>
    <code>bash -c "xclip -sel c -o | xclip -sel backup; curl -s https://clipboard.achri.dk | xclip -sel c; xdotool keyup 'ctrl+meta+super+v'; xdotool key --clearmodifiers 'ctrl+v'; xclip -sel backup -out | xclip -sel c"</code>
</p>
    </body></html>