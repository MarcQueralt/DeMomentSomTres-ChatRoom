<?php
/*
 Plugin Name: DeMomentSomTres ChatRoom
 Plugin URI: http://demomentsomtres.com/catala
 Description: This plugin allows you to quickly and easily host ChatRooms in your blog. Get up and running in no time with the OpenTok platform.
 Version: 1.0.0
 Author: DeMomentSomTres
 Author URI: http://demomentsomtres.com
 */

// Register shortcode: [DeMomentSomTresChatRoom]
add_shortcode('DeMomentSomTresChatRoom','DMST_ChatRoom_sc');

// The callback function that will replace [DeMomentSomTresChatRoom]
function DMST_ChatRoom_sc(){
    return '<script id="TB_embed_js" src="http://api.opentok.com/hl/embed/2emb8400a0a607142875e7032786818bd23f7273/embed.js?width=550&height=265" type="text/javascript" charset="utf-8"></script>';
//    return '<script id="TB_tokshow_fan_embed_js" src="http://api.opentok.com/hl/tokshow/1emba2635329177205a75075d5acd3f247b93753/fan_embed.js?size=small&width=520&height=380" type="text/javascript" charset="utf-8"></script>';
}
?>
