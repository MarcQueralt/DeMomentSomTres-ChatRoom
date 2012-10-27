<?php
/*
 * Settings and administration
 * @since 1.0.1
 */

add_action('admin_menu', 'dmst_chatroom_add_page');

/**
 * Add admin page
 * @since 1.0.1 
 */
function dmst_chatroom_add_page() {
    add_options_page(__('DMS3-ChatRoom', DMST_CHATROOM_TEXT_DOMAIN), __('DMS3-ChatRoom', DMST_CHATROOM_TEXT_DOMAIN), 'manage_options', 'dmst_chatroom', 'dmst_chatroom_option_page');
}

/**
 * Draw the option page
 * @since 1.0.1
 */
function dmst_chatroom_option_page() {
    ?>
    <div class="wrap">
        <?php screen_icon(); ?>
        <h2><?php _e('DMS3-ChatRoom', DMST_CHATROOM_TEXT_DOMAIN); ?></h2>
        <form action="options.php" method="post">
            <?php settings_fields('dmst_chatroom_options'); ?>
            <?php do_settings_sections('dmst_chatroom'); ?>
            <input name="Submit" type="submit" value="<?php _e('Save Changes', DMST_CHATROOM_TEXT_DOMAIN); ?>"/>
        </form>
    </div>
    <?php
}

// Register and define the settings
add_action('admin_init', 'dmst_chatroom_admin_init');

/**
 *  Register and define the settings
 *  @since 1.0.1
 */
function dmst_chatroom_admin_init() {
    register_setting('dmst_chatroom_options', 'dmst_chatroom_options', 'dmst_chatroom_validate_options');
    add_settings_field('dmst_chatroom_field_apikey', __('OpenTok API Key', DMST_CHATROOM_TEXT_DOMAIN), 'dmst_chatroom_field_apikey', 'dmst_chatroom', 'dmst_chatroom_main');
    add_settings_field('dmst_chatroom_field_apisecret', __('OpenTok Api Secret', DMST_CHATROOM_TEXT_DOMAIN), 'dmst_chatroom_field_apisecret', 'dmst_chatroom', 'dmst_chatroom_main');
    add_settings_field('dmst_chatroom_field_debug', __('Debug Mode', DMST_CHATROOM_TEXT_DOMAIN), 'dmst_chatroom_field_debug', 'dmst_chatroom', 'dmst_chatroom_main');
    add_settings_section('dmst_chatroom_main', __('OpenTok Settings', DMST_CHATROOM_TEXT_DOMAIN), 'dmst_chatroom_main_section_text', 'dmst_chatroom');
}

/*
 * Write main section intro text
 * @since 1.0.1
 */

function dmst_chatroom_main_section_text() {
    
}

/*
 * API Key field
 * @since 1.0.1
 */

function dmst_chatroom_field_apikey() {
    $options = get_option(DMST_CHATROOM_OPTIONS);
    $text = $options['apikey'];
    echo "<input id='apikey' name='" . DMST_CHATROOM_OPTIONS . "[apikey]' type='text' value='$text'/>";
    echo "<br/>" . __('Replace with your API key. See <a href="http://www.tokbox.com" target="_blank">http://www.tokbox.com/</a>.', DMST_CHATROOM_TEXT_DOMAIN);
}

/*
 * API Secret field
 * @since 1.0.1
 */

function dmst_chatroom_field_apisecret() {
    $options = get_option(DMST_CHATROOM_OPTIONS);
    $text = $options['apisecret'];
    echo "<input id='apisecret' name='" . DMST_CHATROOM_OPTIONS . "[apisecret]' type='text' value='$text' style='width:85%;'/>";
    echo "<br/>" . __('Replace with your API secret.', DMST_CHATROOM_TEXT_DOMAIN);
}

/*
 * Debug field
 * @since 1.0.1
 */

function dmst_chatroom_field_debug() {
    $options = get_option(DMST_CHATROOM_OPTIONS);
    $text = $options['debug'];
    echo "<input id='debug' name='" . DMST_CHATROOM_OPTIONS . "[debug]' type='text' value='$text' />";
    echo "<br/>" . __('1 means debug activated', DMST_CHATROOM_TEXT_DOMAIN);
}

/*
 * Validate options
 * @since 1.0.1
 */

function dmst_chatroom_validate_options($input) {
    return $input;
}
?>
