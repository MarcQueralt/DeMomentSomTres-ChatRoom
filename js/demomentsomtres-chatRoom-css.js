// Script adapted by DeMomentSomTres
// for WordPress plugin demomentsomtres-chatRoom
// to manage conditional css include
// more info http://demomentsomtres.com
var head  = document.getElementsByTagName('head')[0];
var link  = document.createElement('link');
link.id   = 'chatRoomCSS';
link.rel  = 'stylesheet';
link.type = 'text/css';
link.href = CSS_FILE;
link.media = 'all';
head.appendChild(link);