<?php
/**
 * Get the apikey
 * @return string The API key
 * @since 1.0.1
 */
function dmst_chatRoom_api_key() {
   $opcions=get_option(DMST_CHATROOM_OPTIONS);
   $text=$opcions['apikey'];
   return $text;
}

/**
 * Get the API Secret
 * @return string The API Secret
 * @since 1.0.1
 */
function dmst_chatRoom_api_secret() {    
   $opcions=get_option(DMST_CHATROOM_OPTIONS);
   $text=$opcions['apisecret'];
   return $text;
}
/**
 * Get the API Secret
 * @return string The API Secret
 * @since 1.0.1
 */
function dmst_chatRoom_debug_mode() {    
   $opcions=get_option(DMST_CHATROOM_OPTIONS);
   $text=$opcions['debug'];
   return (1==$text);
}
?>
