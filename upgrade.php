<?php
/*
 * Update functions
 */

/**
 * Main upgrade function.
 */
function wpcf_upgrade() {
    $upgrade_failed = false;
    $upgrade_debug = array();
    $version = get_option('wpcf-version', false);
    if (empty($version)) {
        $version = WPCF_VERSION;
        wpcf_install();
    }
    if (version_compare($version, WPCF_VERSION, '<')) {
        $first_step = str_replace('.', '', $version);
        $last_step = str_replace('.', '', WPCF_VERSION);
        for ($index = $first_step; $index <= $last_step; $index++) {
            if (function_exists('wpcf_upgrade_' . $index)) {
                $response = call_user_func('wpcf_upgrade_' . $index);
                if ($response !== true) {
                    $upgrade_failed = true;
                    $upgrade_debug[$first_step][$index] = $response;
                }
            }
        }
    }
    if ($upgrade_failed == true) {
        update_option('wpcf_upgrade_debug', $upgrade_debug);
        // @todo Add perm message to display for admin
    }
    update_option('wpcf-version', WPCF_VERSION);
}

/**
 * Install function.
 * 
 * @global type $wpdb 
 */
function wpcf_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . "wpcf_groups";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
//        $sql = "CREATE TABLE " . $table_name . " (
//	  id bigint(20) NOT NULL AUTO_INCREMENT,
//      slug varchar(20) NOT NULL,
//      name varchar(20) NOT NULL,
//      description varchar(255) NOT NULL,
//      post_types varchar(255) DEFAULT NULL,
//      taxonomies varchar(255) DEFAULT NULL,
//      fields varchar(255) DEFAULT NULL,
//      meta_box_context varchar(20) NOT NULL DEFAULT 'normal',
//      meta_box_priority varchar(20) NOT NULL DEFAULT 'default',
//      is_active tinyint(1) NOT NULL DEFAULT '1',
//      user_id bigint(20) NOT NULL,
//	  PRIMARY KEY (id),
//      KEY slug (slug),
//      KEY post_types (post_types),
//      KEY taxonomies (taxonomies)
//	);";
        
        $sql = "CREATE TABLE " . $table_name . " (
	  id bigint(20) NOT NULL AUTO_INCREMENT,
      slug varchar(20) NOT NULL,
      name varchar(20) NOT NULL,
      description varchar(255) NOT NULL,
      meta_box_context varchar(20) NOT NULL DEFAULT 'normal',
      meta_box_priority varchar(20) NOT NULL DEFAULT 'high',
      is_active tinyint(1) NOT NULL DEFAULT '1',
      user_id bigint(20) NOT NULL,
	  PRIMARY KEY (id),
      KEY slug (slug)
	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    $table_name = $wpdb->prefix . "wpcf_fields";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE " . $table_name . " (
	  id bigint(20) NOT NULL AUTO_INCREMENT,
      type varchar(20) NOT NULL,
      slug varchar(20) NOT NULL,
      name varchar(20) NOT NULL,
      description varchar(255) NOT NULL,
      data longtext,
      is_active tinyint(1) NOT NULL DEFAULT '1',
      user_id bigint(20) NOT NULL,
	  PRIMARY KEY (id),
      KEY type (type),
      KEY slug (slug)
	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    $table_name = $wpdb->prefix . "wpcf_relationships";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE " . $table_name . " (
	  id bigint(20) NOT NULL AUTO_INCREMENT,
      group_id bigint(20) NOT NULL,
      type varchar(20) NOT NULL,
      value varchar(20) DEFAULT NULL,
	  PRIMARY KEY (id),
      KEY group_id (group_id),
      KEY type (type),
      KEY value (value)
	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}