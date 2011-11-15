<?php
/*
  Plugin Name: Types
  Plugin URI: http://wordpress.org/extend/plugins/types/
  Description: Define custom post types, custom taxonomy and custom fields.
  Author: ICanLocalize
  Author URI: http://wp-types.com
  Version: 0.9
 */
define('WPCF_VERSION', '0.9');
define('WPCF_ABSPATH', dirname(__FILE__));
define('WPCF_RELPATH', plugins_url() . '/' . basename(WPCF_ABSPATH));
define('WPCF_INC_ABSPATH', WPCF_ABSPATH . '/includes');
define('WPCF_INC_RELPATH', WPCF_RELPATH . '/includes');
define('WPCF_RES_ABSPATH', WPCF_ABSPATH . '/resources');
define('WPCF_RES_RELPATH', WPCF_RELPATH . '/resources');
require_once WPCF_INC_ABSPATH . '/constants.inc';

add_action('plugins_loaded', 'wpcf_init');
register_activation_hook(__FILE__, 'wpcf_upgrade_init');

/**
 * Main init hook.
 */
function wpcf_init() {
    if (is_admin()) {
        require_once WPCF_ABSPATH . '/admin.php';
    } else {
        require_once WPCF_ABSPATH . '/frontend.php';
    }
    // Init custom types
    add_action('init', 'wpcf_init_custom_types_taxonomies');
    // TODO resolve this
    if (defined('DOING_AJAX')) {
        require_once WPCF_ABSPATH . '/frontend.php';
    }
}

/**
 * Inits custom types and taxonomies.
 */
function wpcf_init_custom_types_taxonomies() {
    $custom_types = get_option('wpcf-custom-types', array());
    if (!empty($custom_types)) {
        require_once WPCF_INC_ABSPATH . '/custom-types.php';
        wpcf_custom_types_init();
    }
    $custom_taxonomies = get_option('wpcf-custom-taxonomies', array());
    if (!empty($custom_taxonomies)) {
        require_once WPCF_INC_ABSPATH . '/custom-taxonomies.php';
        wpcf_custom_taxonomies_init();
    }
}

/**
 * Upgrade hook.
 */
function wpcf_upgrade_init() {
    require_once WPCF_ABSPATH . '/upgrade.php';
    wpcf_upgrade();
}

/**
 * WPML translate call.
 * 
 * @param type $name
 * @param type $string
 * @return type 
 */
function wpcf_translate($name, $string) {
    if (!function_exists('icl_t')) {
        return $string;
    }
    return icl_t('plugin Types', $name, $string);
}

/**
 * Returns meta_key type for specific field type.
 * 
 * @param type $type
 * @return type 
 */
function types_get_field_type($type) {
    require_once WPCF_INC_ABSPATH . '/fields.php';
    $data = wpcf_fields_type_action($type);
    if (!empty($data['meta_key_type'])) {
        return $data['meta_key_type'];
    }
    return 'CHAR';
}
