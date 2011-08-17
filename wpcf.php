<?php
/*
  Plugin Name: Types
  Plugin URI: http://wordpress.org/extend/plugins/types/
  Description: A flexible GUI for managing custom fields on different content types. Allows grouping fields together, includes a drag-and-drop interface for arranging fields, data validation and placement selection.
  Author: ICanLocalize
  Author URI: http://wpml.org
  Version: 0.0.7
 */
define('WPCF_VERSION', '0.0.7');
define('WPCF_ABSPATH', dirname(__FILE__));
define('WPCF_RELPATH', plugins_url() . '/' . basename(WPCF_ABSPATH));
define('WPCF_INC_ABSPATH', WPCF_ABSPATH . '/includes');
define('WPCF_INC_RELPATH', WPCF_RELPATH . '/includes');
define('WPCF_RES_ABSPATH', WPCF_ABSPATH . '/resources');
define('WPCF_RES_RELPATH', WPCF_RELPATH . '/resources');

add_action('plugins_loaded', 'wpcf_init');
register_activation_hook(__FILE__, 'wpcf_upgrade_init');
//register_deactivation_hook($file, $function);

/**
 * Main init hook.
 */
function wpcf_init() {
    if (is_admin()) {
        require_once WPCF_ABSPATH . '/admin.php';
    } else {
        require_once WPCF_ABSPATH . '/frontend.php';
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