<?php

/*
  Plugin Name: DeMomentSomTres ChatRoom
  Plugin URI: http://demomentsomtres.com/catala
  Description: This plugin allows you to quickly and easily host ChatRooms in your blog. Get up and running in no time with the OpenTok platform.
  Version: 1.0.2
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
define('DMST_CHATROOM_OPEN', 'dmst_chatroom_open');
define('DMST_CHATROOM_WAITING_LIST', 'dmst_chatroom_waiting_list');
define('DMST_CHATROOM_IN_CHATROOM', 'dmst_chatroom_in_chatroom');
define('DMST_CHATROOM_TRANSCIENT_LIVE', 36000);
define('DMST_CHATROOM_SESSION_USER_ID', 'dmst_chatroom_user_id');
define('DMST_CHATROOM_P2P_SESSIONID', 'dmst_chatroom_p2p_sessionid');
define('DMST_CHATROOM_P2P_TOKEN_MODERATOR', 'dmst_chatroom_p2p_token_moderator');
define('DMST_CHATROOM_P2P_TOKEN_PUBLISHER', 'dmst_chatroom_p2p_token_publisher');

require_once DMST_CHATROOM_PLUGIN_PATH . 'admin.php';
require_once DMST_CHATROOM_PLUGIN_PATH . 'functions.php';

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

if (!is_admin())
    add_action("wp_enqueue_scripts", "dmst_chatRoom_enqueue_jquery", 11);

/**
 * Add session
 */
add_action('init', 'dmst_chatRoom_init');
/**
 * Register shortcode: [DeMomentSomTresChatRoom]
 */
add_shortcode('DeMomentSomTresChatRoom', 'DMST_ChatRoom_sc');

/**
 * Adds ajax handlers for privileged users
 */
add_action('wp_ajax_dmst_chatRoom_open', 'dmst_chatRoom_open');
add_action('wp_ajax_dmst_chatRoom_close', 'dmst_chatRoom_close');
add_action('wp_ajax_dmst_chatRoom_add_to_queue', 'dmst_chatRoom_add_to_queue');
add_action('wp_ajax_dmst_chatRoom_queue_count', 'dmst_chatRoom_queue_count');
add_action('wp_ajax_dmst_chatRoom_queue_list', 'dmst_chatRoom_queue_list');
add_action('wp_ajax_dmst_chatRoom_to_chatRoom', 'dmst_chatRoom_to_chatRoom');
add_action('wp_ajax_dmst_chatRoom_in_chatRoom', 'dmst_chatRoom_in_chatRoom');
add_action('wp_ajax_dmst_chatRoom_in_list', 'dmst_chatRoom_in_list');
add_action('wp_ajax_dmst_chatRoom_status', 'dmst_chatRoom_status');
add_action('wp_ajax_dmst_chatRoom_clear_waiting_list', 'dmst_chatRoom_clear_waiting_list');
add_action('wp_ajax_dmst_chatRoom_list_length', 'dmst_chatRoom_list_lenght');
add_action('wp_ajax_dmst_chatRoom_pretty_list', 'dmst_chatRoom_manager_pretty_waiting_list');
add_action('wp_ajax_dmst_chatRoom_resetSession', 'dmst_chatRoom_resetSession');

/**
 * Adds ajax handlers for unprivileged users
 */
add_action('wp_ajax_nopriv_dmst_chatRoom_add_to_queue', 'dmst_chatRoom_add_to_queue');
add_action('wp_ajax_nopriv_dmst_chatRoom_queue_count', 'dmst_chatRoom_queue_count');
add_action('wp_ajax_nopriv_dmst_chatRoom_in_list', 'dmst_chatRoom_in_list');
add_action('wp_ajax_nopriv_dmst_chatRoom_in_chatRoom', 'dmst_chatRoom_in_chatRoom');
add_action('wp_ajax_nopriv_dmst_chatRoom_status', 'dmst_chatRoom_status');
add_action('wp_ajax_nopriv_dmst_chatRoom_pretty_list', 'dmst_chatRoom_pretty_waiting_list');
add_action('wp_ajax_nopriv_dmst_chatRoom_p2pSession', 'dmst_chatRoom_p2pSession');

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

    wp_enqueue_script('opentok', 'http://static.opentok.com/v0.91/js/TB.min.js', array(), '0.91', true);
    if (dmst_chatRoom_debug_mode()):
        wp_enqueue_script('dmst-chatroom-debug', plugins_url('js/demomentsomtres-chatRoom-debug.js', __FILE__), array('opentok'), '1.0.1', true);
    endif;
    wp_enqueue_script('dmst-chatroom-css', plugins_url('js/demomentsomtres-chatRoom-css.js', __FILE__), array(), '1.0.1', true);
    $dmst_chatroom_isModerator = (current_user_can('manage_options'));
    if ($dmst_chatroom_isModerator):
        $role = RoleConstants::MODERATOR;
        wp_enqueue_script('dmst-chatroom-shop', plugins_url('js/demomentsomtres-chatRoom-manager.js', __FILE__), array('opentok'), '1.0.1', true);
    else:
        $role = RoleConstants::SUBSCRIBER;
        wp_enqueue_script('dmst-chatroom-client', plugins_url('js/demomentsomtres-chatRoom-user.js', __FILE__), array('opentok'), '1.0.1', true);
    endif;
    $connection_data = '';
    $token = $apiObj->generateToken($sessionId, $role, NULL, $connection_data);

    $protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
    $ajaxurl = admin_url('admin-ajax.php', $protocol);

    $cos = '<div id="DeMomentSomTres-chatRoom">';
    $cos.= '<script type="text/javascript" charset="utf-8">' . "\n";
    $cos.='var apiKey = "' . dmst_chatRoom_api_key() . '";' . "\n";
    $cos.='var sessionId = "' . $sessionId . '";' . "\n";
    $cos.='var token = "' . $token . '";' . "\n";
    $cos.='var session;' . "\n";
    $cos.='var publisher;' . "\n";
    $cos.='var subscribers = {};' . "\n";
    $cos.='var p2pSessionId;' . "\n";
    $cos.='var p2pToken;' . "\n";
    $cos.='var p2pSession;' . "\n";
    $cos.='var p2pPublisher;' . "\n";
    $cos.='var p2pSubscribers = {};' . "\n";
//    $cos.='var VIDEO_WIDTH = 320;' . "\n";
//    $cos.='var VIDEO_HEIGHT = 240;' . "\n";
    $cos.='var VIDEO_WIDTH = 445;' . "\n";
    $cos.='var VIDEO_HEIGHT = 333;' . "\n";
    $cos.='var WP_ADMIN_URL = "' . $ajaxurl . '";' . "\n";
    $cos.='var CSS_FILE="' . plugins_url('demomentsomtres-chatRoom.css', __FILE__) . '";' . "\n";
    $cos.='var P2P_1013_MESSAGE="' . __('Trying to connect to a P2P session that has 2 users', DMST_CHATROOM_TEXT_DOMAIN) . '";';
    $cos.='var ERROR_REQUIREMENTS="' . __('You don\'t have the minimum requirements to run this application. Please upgrade to the latest version of Flash.', DMST_CHATROOM_TEXT_DOMAIN) . '";';
    if (!$dmst_chatroom_isModerator):
        $cos.='var userId = "' . dmst_get_user_id() . '";' . "\n";
    endif;
    $cos.='</script>' . "\n";
    if ($dmst_chatroom_isModerator):
        $cos.='<div id="DeMomentSomTres-chatRoom-links">' . "\n";
        $cos.='<input type="button" value="' . __('Connect', DMST_CHATROOM_TEXT_DOMAIN) . '" id ="connectLink" />';
        $cos.='<input type="button" value="' . __('Start Publishing', DMST_CHATROOM_TEXT_DOMAIN) . '" id ="publishLink" />';
        $cos.='<input type="button" value="' . __('Stop Publishing', DMST_CHATROOM_TEXT_DOMAIN) . '" id ="unpublishLink" />';
        $cos.='<input type="button" value="' . __('Disconnect', DMST_CHATROOM_TEXT_DOMAIN) . '" id ="disconnectLink" />';
        $cos.='</div>' . "\n";
        if ($shopIsOpen):
            $cos.='<div id="myCamera" class="publisherContainer"></div>' . "\n";
        else:
            $cos.='<div id="myCamera" class="publisherContainer" class="dmst_chatRoom_shop_closed"></div>' . "\n";
        endif;
    else:
        if ($shopIsOpen):
            $cos.='<div id="subscribers"></div>' . "\n";
        else:
            $cos.='<div id="subscribers" class="dmst_chatRoom_shop_closed"></div>' . "\n";
        endif;
    endif;
    $cos.='<div id="DeMomentSomTres-chatRoom-messages"></div>' . "\n";
    $cos.='<div id="opentok_console"></div>' . "\n";
    $cos.='<audio preload="true" id="alarm">';
    $cos.='<source src="' . plugins_url('sound/alarm.mp3', __FILE__) . '" type="audio/mpeg">';
    $cos.='</audio>';
    $cos.='</div>' . "\n";
    $cos.='<div id="DeMomentSomTres-chatRoom-listAccess">' . "\n";
    $cos.='<div id="DeMomentSomTres-chatRoom-waitingList"></div>' . "\n";
    if ($dmst_chatroom_isModerator):
        $cos.='<input type="button" value="' . __('Go', DMST_CHATROOM_TEXT_DOMAIN) . '" id="goLink" />';
        $cos.='<input type="button" value="' . __('p2p Stop', DMST_CHATROOM_TEXT_DOMAIN) . '" id="p2pStopLink" />';
    else:
        $cos.='<input type="button" value="' . __('Ask your Turn', DMST_CHATROOM_TEXT_DOMAIN) . '" id="turnLink" />';
        $cos.='<input type="button" value="' . __('Refresh', DMST_CHATROOM_TEXT_DOMAIN) . '" id="refreshLink" />';
    endif;
    $cos.='</div> <!--DeMomentSomTres-chatRoom-listAccess-->' . "\n";
    $cos.='<div id="p2p">' . "\n" . '<div id="p2pMe"></div>' . "\n" . '<div id="p2pYou"></div>' . "\n" . '</div>' . "\n";
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
 * Opens the chatRoom
 * Ajax handler
 * @since 1.0.1
 */
function dmst_chatRoom_open() {
    set_transient(DMST_CHATROOM_OPEN, true, DMST_CHATROOM_TRANSCIENT_LIVE);
    echo __('ChatRoom is open', DMST_CHATROOM_TEXT_DOMAIN);
    die();
}

/**
 * Closes the chatRoom
 * Ajax handler
 * @since 1.0.1
 */
function dmst_chatRoom_close() {
    set_transient(DMST_CHATROOM_OPEN, false, DMST_CHATROOM_TRANSCIENT_LIVE);
    echo __('ChatRoom is closed', DMST_CHATROOM_TEXT_DOMAIN);
    die();
}

/**
 * Check chatRoom status
 * Ajax handler
 * @since 1.0.1
 */
function dmst_chatRoom_status() {
    $status = get_transient(DMST_CHATROOM_OPEN);
    if ($status):
        echo 1;
    else:
        echo 0;
    endif;
    die();
}

/**
 * Inserts a new candidate to the queue
 * Ajax handler
 * @since 1.0.1
 */
function dmst_chatRoom_add_to_queue() {
    if (isset($_REQUEST["id"])):
        $id = $_REQUEST["id"];
        $list = get_transient(DMST_CHATROOM_WAITING_LIST);
        if (!$list):
            $list = array();
        endif;
        if (!in_array($id, $list)):
            array_push($list, $id);
            set_transient(DMST_CHATROOM_WAITING_LIST, $list, DMST_CHATROOM_TRANSCIENT_LIVE);
            echo __('You\'ve been added to the list', DMST_CHATROOM_TEXT_DOMAIN);
        else:
            echo __('You cannot add yourself twice', DMST_CHATROOM_TEXT_DOMAIN);
        endif;
    else:
        echo __('ERROR: You must specify an id', DMST_CHATROOM_TEXT_DOMAIN);
    endif;
    die();
}

/**
 * Counts candidates on queue
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_queue_count() {
    $list = get_transient(DMST_CHATROOM_WAITING_LIST);
    if (!$list):
        $list = array();
    endif;
    echo count($list);
    die();
}

/**
 * List all candidates on queue
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_queue_list() {
    $list = get_transient(DMST_CHATROOM_WAITING_LIST);
    if (!$list):
        $list = array();
    endif;
    echo print_r($list, true);
    die();
}

/**
 * Move the first in queue to the chatRoom and return a P2PSessionID
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_to_chatRoom() {
    require_once 'SDK/OpenTokSDK.php';

    if (isset($_REQUEST["pos"])):
        $pos = $_REQUEST["pos"];
        $list = get_transient(DMST_CHATROOM_WAITING_LIST);
        if (!$list):
            $list = array();
        endif;
        $list = array_values($list);
        if (isset($list[$pos - 1])):
            $client = $list[$pos - 1];
            $list = array_diff($list, array($client));
            $list = array_values($list);
            set_transient(DMST_CHATROOM_WAITING_LIST, $list, DMST_CHATROOM_TRANSCIENT_LIVE);
            set_transient(DMST_CHATROOM_IN_CHATROOM, $client, DMST_CHATROOM_TRANSCIENT_LIVE);
            /* Prepare session */
            $apiObj = new OpenTokSDK(dmst_chatRoom_api_key(), dmst_chatRoom_api_secret());
            $p2pSession = $apiObj->createSession('', array(SessionPropertyConstants::P2P_PREFERENCE => "enabled"));
            $p2pSessionId = $p2pSession->getSessionId();
            $p2pTokenModerator = $apiObj->generateToken($p2pSessionId, RoleConstants::PUBLISHER, NULL, '');
            $p2pTokenPublisher = $apiObj->generateToken($p2pSessionId, RoleConstants::PUBLISHER, NULL, '');
//            $p2pTokenPublisher = $p2pTokenModerator;
            set_transient(DMST_CHATROOM_P2P_SESSIONID, $p2pSessionId, DMST_CHATROOM_TRANSCIENT_LIVE);
            set_transient(DMST_CHATROOM_P2P_TOKEN_MODERATOR, $p2pTokenModerator, DMST_CHATROOM_TRANSCIENT_LIVE);
            set_transient(DMST_CHATROOM_P2P_TOKEN_PUBLISHER, $p2pTokenPublisher, DMST_CHATROOM_TRANSCIENT_LIVE);
            $result = array('id' => $p2pSessionId, 'token' => $p2pTokenModerator);
            echo json_encode($result);
        endif;
    endif;
    die();
}

/**
 * Show the candidate that is been attended
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_in_chatRoom() {
    $inChatRoom = get_transient(DMST_CHATROOM_IN_CHATROOM);
    if (!inChatRoom):
        echo '0';
    else:
        echo $inChatRoom;
    endif;
    die();
}

/**
 * Shows the position of a candidate in the list.
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_in_list() {
    if (isset($_REQUEST["id"])):
        $id = $_REQUEST["id"];
        $list = get_transient(DMST_CHATROOM_WAITING_LIST);
        if (!$list):
            $list = array();
        endif;
        $pos = array_search($id, $list);
        if ($pos === false):
            echo __('Your not in the waiting list', DMST_CHATROOM_TEXT_DOMAIN);
        else:
            $keys = array_keys($list);
            $posi = array_search($pos, $keys);
            echo __('Your position in the waiting list is', DMST_CHATROOM_TEXT_DOMAIN);
            echo ' ';
            echo $posi;
        endif;
    endif;
    die();
}

/**
 * Shows the number of people in the waiting List
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_list_lenght() {
    $list = get_transient(DMST_CHATROOM_WAITING_LIST);
    if (!$list):
        echo 0;
    else:
        echo count($list);
    endif;
    die();
}

/**
 * Clears the waiting list
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_clear_waiting_list() {
    $client = array_shift($list);
    set_transient(DMST_CHATROOM_WAITING_LIST, $list, DMST_CHATROOM_TRANSCIENT_LIVE);
    die();
}

/**
 * Prints a pretty waiting list
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_pretty_waiting_list() {
    $list = get_transient(DMST_CHATROOM_WAITING_LIST);
    if (!$list):
        $list = array();
    endif;
    if (isset($_REQUEST["id"])):
        $id = $_REQUEST["id"];
        $pos = array_search($id, $list);
        if ($pos === false):
            $posi = -1;
        else:
            $keys = array_keys($list);
            $posi = array_search($pos, $keys);
        endif;
    else:
        $posi = -1;
    endif;
    for ($i = 0; $i < count($list); $i++):
        if ($i == $posi):
            echo '<div class="waiting_list_you">';
            echo '<span class="you">' . __('You', DMST_CHATROOM_TEXT_DOMAIN) . '</span>';
//          echo $i + 1;
            echo '</div>';
        else:
            echo '<div class="waiting_list">';
            echo $i + 1;
            echo '</div>';
        endif;
    endfor;
    die();
}

/**
 * Prints a pretty waiting list
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_manager_pretty_waiting_list() {
    $list = get_transient(DMST_CHATROOM_WAITING_LIST);
    if (!$list):
        $list = array();
    endif;
    for ($i = 0; $i < count($list); $i++):
        echo '<div class="waiting_list">';
        echo $i + 1;
        echo '</div>';
    endfor;
    die();
}

/**
 * returns the p2psession and the publisher token id if the userId is ok
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_p2pSession() {
    if (isset($_REQUEST["userId"])):
        $userId = $_REQUEST["userId"];
        $expectedUser = get_transient(DMST_CHATROOM_IN_CHATROOM);
        if ($expectedUser == $userId):
            $p2pSessionId = get_transient(DMST_CHATROOM_P2P_SESSIONID);
            $p2pToken = get_transient(DMST_CHATROOM_P2P_TOKEN_PUBLISHER);
            $result = array('id' => $p2pSessionId, 'token' => $p2pToken);
            echo json_encode($result);
        endif;
    endif;
    die();
}

/**
 * clears in chatroom
 * AJAX handler
 * @since 1.0.1
 */
function dmst_chatRoom_resetSession() {
    delete_transient(DMST_CHATROOM_IN_CHATROOM);
    die();
}

/**
 * Create a session
 */
function dmst_chatRoom_init() {
    load_plugin_textdomain(DMST_CHATROOM_TEXT_DOMAIN, false, '/languages/');
    if (!session_id()) {
        session_start();
    }
}

/**
 * Get user id if it is stored in session or create a new one
 * @return string the user id
 * @since 1.0.1
 */
function dmst_get_user_id() {
    if (isset($_SESSION[DMST_CHATROOM_SESSION_USER_ID])):
        $user_id = $_SESSION[DMST_CHATROOM_SESSION_USER_ID];
    else:
        $user_id = dmst_chatRoom_user_id_calculate();
        $_SESSION[DMST_CHATROOM_SESSION_USER_ID] = $user_id;
    endif;
    return $user_id;
}

/**
 * Create a unique user identification
 * @return string the new user identifier
 * @since 1.0.1
 */
function dmst_chatRoom_user_id_calculate() {
    return '' . time() . rand(10, 99);
}

?>