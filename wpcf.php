<?php
/*
  Plugin Name: Types
  Plugin URI: http://wpml.org/
  Description: WPML Multilingual CMS. <a href="http://wpml.org">Documentation</a>.
  Author: ICanLocalize
  Author URI: http://wpml.org
  Version: 0.0.1
 */
define('WPCF_VERSION', '0.0.1');
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
    if (!function_exists('icl_translate')) {
        return $string;
    }
    return icl_translate('plugin Types', $name, $string);
}