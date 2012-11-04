<?php

/*
  Plugin Name: DeMomentSomTres ChatRoom
  Plugin URI: http://demomentsomtres.com/catala
  Description: This plugin allows you to quickly and easily host ChatRooms in your blog. Get up and running in no time with the OpenTok platform.
  Version: 1.0.1
  Author: DeMomentSomTres
  Author URI: http://demomentsomtres.com
  License: GPLv2 or later
 */

/*
  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

define('DMST_CHATROOM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DMST_CHATROOM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DMST_CHATROOM_TEXT_DOMAIN', 'DeMomentSomTres-ChatRoom');
define('DMST_CHATROOM_OPTIONS', 'dmst_chatroom_options');
define('DMST_CHATROOM_PUBLIC_SESSION', 'dmst_chatroom_public_session');
define('DMST_CHATROOM_PRIVATE_SESSIONS', 'dmst_chatroom_private_session');
define('DMST_CHATROOM_OPEN', 'dmst_chatroom_open');
define('DMST_CHATROOM_SESSIONS_COUNT', 3);
define('DMST_CHATROOM_TRANSCIENT_LIVE', 36000);

require_once DMST_CHATROOM_PLUGIN_PATH . 'admin.php';
require_once DMST_CHATROOM_PLUGIN_PATH . 'functions.php';

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

if (!is_admin())
    add_action("wp_enqueue_scripts", "dmst_chatRoom_enqueue_jquery", 11);

load_plugin_textdomain(DMST_CHATROOM_TEXT_DOMAIN, false, DMST_CHATROOM_PLUGIN_URL . '/languages');

/**
 * Register shortcode: [DeMomentSomTresChatRoom]
 */
add_shortcode('DeMomentSomTresChatRoom', 'DMST_ChatRoom_sc');

/**
 * Adds ajax handlers for privileged users
 */
add_action('wp_ajax_dmst_chatRoom_open', 'dmst_chatRoom_open');
add_action('wp_ajax_dmst_chatRoom_close', 'dmst_chatRoom_close');

/**
 * The callback function that will replace [DeMomentSomTresChatRoom]
 * @return string 
 * @since 1.0.0
 */
function DMST_ChatRoom_sc() {

    require_once 'SDK/OpenTokSDK.php';

    $apiObj = new OpenTokSDK(dmst_chatRoom_api_key(), dmst_chatRoom_api_secret());
    $shopIsOpen = get_transient(DMST_CHATROOM_OPEN);
    $sessionId = get_transient(DMST_CHATROOM_PUBLIC_SESSION);
    if (false === $sessionId):
        $session = $apiObj->createSession();
        $sessionId = $session->getSessionId();
        set_transient(DMST_CHATROOM_PUBLIC_SESSION, $sessionId, DMST_CHATROOM_TRANSCIENT_LIVE);
    else:
        set_transient(DMST_CHATROOM_PUBLIC_SESSION, $sessionId, DMST_CHATROOM_TRANSCIENT_LIVE);
    endif;
    $p2pSessionIds = get_transient(DMST_CHATROOM_PRIVATE_SESSIONS);
    if (false === $p2pSessionIds):
        $p2pSessionIds = array();
        for ($i = 1; $i <= DMST_CHATROOM_SESSIONS_COUNT; $i++):
            $session = $apiObj->createSession();
            $p2pSessionIds[$i] = $session->getSessionId();
        endfor;
        $sessionId = $session->getSessionId();
        set_transient(DMST_CHATROOM_PRIVATE_SESSIONS, $sessionId, DMST_CHATROOM_TRANSCIENT_LIVE);
    else:
        set_transient(DMST_CHATROOM_PRIVATE_SESSIONS, $sessionId, DMST_CHATROOM_TRANSCIENT_LIVE);
    endif;

    wp_enqueue_script('opentok', 'http://static.opentok.com/v0.91/js/TB.min.js', array(), '0.91', true);
    if (dmst_chatRoom_debug_mode()):
        wp_enqueue_script('dmst-chatroom-debug', plugins_url('js/demomentsomtres-chatRoom-debug.js', __FILE__), array('opentok'), '1.0.1', true);
    endif;
    wp_enqueue_script('dmst-chatroom-css', plugins_url('js/demomentsomtres-chatRoom-css.js', __FILE__), array(), '1.0.1', true);
    $dmst_chatroom_isModerator = (current_user_can('manage_options'));
    if ($dmst_chatroom_isModerator):
        $role = RoleConstants::MODERATOR;
        wp_enqueue_script('dmst-chatroom-shop', plugins_url('js/demomentsomtres-chatRoom-shop.js', __FILE__), array('opentok'), '1.0.1', true);
    else:
        $role = RoleConstants::SUBSCRIBER;
        wp_enqueue_script('dmst-chatroom-client', plugins_url('js/demomentsomtres-chatRoom-client.js', __FILE__), array('opentok'), '1.0.1', true);
    endif;
    $connection_data = '';
    $token = $apiObj->generateToken($sessionId, $role, NULL, $connection_data);

    $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
    $ajaxurl = admin_url('admin-ajax.php', $protocol);

    $cos = '<script type="text/javascript" charset="utf-8">';
    $cos.='var apiKey = "' . dmst_chatRoom_api_key() . '";';
    $cos.='var sessionId = "' . $sessionId . '";';
    $cos.='var privateSessionsIds = {';
    $cos.='};';
    $cos.='var token = "' . $token . '";';
    $cos.='var session;';
    $cos.='var publisher;';
    $cos.='var subscribers = {};';
    $cos.='var VIDEO_WIDTH = 320;';
    $cos.='var VIDEO_HEIGHT = 240;';
    $cos.='var WP_ADMIN_URL = "' . $ajaxurl . '";';
    $cos.='var VIDEO_BACKGROUND = "' . 'http://t2.gstatic.com/images?q=tbn:ANd9GcQp7U_QQbwOuEQ9QwnrG5K4oyUVPlaLrf4BkkH2L9_axlHB-VTk' . '";';
    $cos.='var CSS_FILE="' . plugins_url('demomentsomtres-chatRoom.css', __FILE__) . '"';
    $cos.='</script>';
    if ($dmst_chatroom_isModerator):
        $cos.='<div id="messages"></div>';
        $cos.='<div id="links">';
        $cos.='<input type="button" value="' . __('Connect', DMST_CHATROOM_TEXT_DOMAIN) . '" id ="connectLink" />';
        $cos.='<input type="button" value="' . __('Disconnect', DMST_CHATROOM_TEXT_DOMAIN) . '" id ="disconnectLink" />';
        $cos.='<input type="button" value="' . __('Start Publishing', DMST_CHATROOM_TEXT_DOMAIN) . '" id ="publishLink" />';
        $cos.='<input type="button" value="' . __('Stop Publishing', DMST_CHATROOM_TEXT_DOMAIN) . '" id ="unpublishLink" />';
        $cos.='</div>';
        if ($shopIsOpen):
            $cos.='<div id="myCamera" class="publisherContainer"></div>';
        else:
            $cos.='<div id="myCamera" class="publisherContainer" class="dmst_chatRoom_shop_closed"></div>';
        endif;
        $cos.='<div id="subscribers"></div>';
    else:
//        $cos.='<div id="myCamera" class="publisherContainer"></div>';
        if ($shopIsOpen):
            $cos.='<div id="subscribers"></div>';
        else:
            $cos.='<div id="subscribers" class="dmst_chatRoom_shop_closed"></div>';
        endif;
    endif;
    $cos.='<div id="llista"></div>';
    $cos.='<div id="p2p"></div>';
    $cos.='<div id="opentok_console"></div>';
    return $cos;
}

/**
 * Assure jquery is enqueued
 * @since 1.0.1
 */
function dmst_chatRoom_enqueue_jquery() {
    wp_enqueue_script('jquery');
}

/**
 * Opens the shop
 * Ajax handler
 * @since 1.0.1
 */
function dmst_chatRoom_open() {
    set_transient(DMST_CHATROOM_OPEN, true, DMST_CHATROOM_TRANSCIENT_LIVE);
    echo __('ChatRoom is open', DMST_CHATROOM_TEXT_DOMAIN);
    die();
}

/**
 * Closes the shop
 * Ajax handler
 * @since 1.0.1
 */
function dmst_chatRoom_close() {
    set_transient(DMST_CHATROOM_OPEN, false, DMST_CHATROOM_TRANSCIENT_LIVE);
    echo __('ChatRoom is closed', DMST_CHATROOM_TEXT_DOMAIN);
    die();
}

?>
