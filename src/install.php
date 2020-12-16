<? header('Content-Type: text/strings'); ?>
### goes in .bashrc
alias clip='xclip -sel c'
alias paste='xclip -sel c -o'

alias backupClipboard="paste | xclip -sel backup"
alias restoreClipboard="xclip -sel backup -out | clip"

alias clipget="curl -s https://clipboard.antonchristensen.net 2>&1"
clipset() {
    cat /dev/stdin > /tmp/clipboardUpload
    curl -F 'paste=@/tmp/clipboardUpload' https://clipboard.antonchristensen.net/
}
alias ppaste='paste | clipset'
alias cclip='clipget | clip'



# goes in .xbindkeysrc
# clip selection to shared clipboard
#"xdotool keyup 'ctrl+meta+super+c'; xdotool key --clearmodifiers 'ctrl+c'; sleep 0.5; xclip -sel c -o > /tmp/clipboardUpload; curl -F 'paste=@/tmp/clipboardUpload' https://clipboard.antonchristensen.net/"
"xclip -sel c -o | xclip -sel backup; xdotool keyup 'ctrl+meta+super+c'; xdotool key --clearmodifiers 'ctrl+c'; xclip -sel c -o > /tmp/clipboardUpload; curl -F 'paste=@/tmp/clipboardUpload' https://clipboard.antonchristensen.net/; xclip -sel backup -out | xclip -sel c"
    Release+Control+Mod4 + c

# paste from shared clipboard
"xclip -sel c -o | xclip -sel backup; curl -s https://clipboard.antonchristensen.net | xclip -sel c; xdotool keyup 'ctrl+meta+super+v'; xdotool key --clearmodifiers 'ctrl+v'; xclip -sel backup -out | xclip -sel c"
    Control+Mod4 + v

# run "killall xbindkeys; xbindkeys -p" on startup