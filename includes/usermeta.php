<?php
/*
 * User Meta and groups functions exitend includes/fields.php 
 */
require_once WPCF_EMBEDDED_INC_ABSPATH . '/usermeta.php';

/**
 * Saves group's user roles.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @param type $post_types 
 */
function wpcf_admin_fields_save_group_showfor($group_id, $post_types) {
    if (empty($post_types)) {
        update_post_meta($group_id, '_wp_types_group_showfor', 'all');
        return true;
    }
    $post_types = ',' . implode(',', (array) $post_types) . ',';
    update_post_meta($group_id, '_wp_types_group_showfor', $post_types);
}



/**
 * Gets user roles supported by specific group.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_get_groups_showfor_by_group($group_id) {
    $for_users = get_post_meta($group_id, '_wp_types_group_showfor', true);
    if ($for_users == 'all') {
        return array();
    }
    $for_users = explode(',', trim($for_users, ','));
    return $for_users;
}




/**
 * Returns HTML formatted AJAX activation link for usermeta.
 * 
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_usermeta_get_ajax_activation_link($group_id) {
    return '<a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;'
                    . 'wpcf_action=activate_user_group&amp;group_id='
                    . $group_id . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
                    . $group_id) . '&amp;_wpnonce=' . wp_create_nonce('activate_user_group')
            . '" class="wpcf-ajax-link" id="wpcf-list-activate-'
            . $group_id . '">'
            . __('Activate', 'wpcf') . '</a>';
}

/**
 * Returns HTML formatted AJAX deactivation link for usermeta.
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_usermeta_get_ajax_deactivation_link($group_id) {
    return '<a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;'
                    . 'wpcf_action=deactivate_user_group&amp;group_id='
                    . $group_id . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
                    . $group_id) . '&amp;_wpnonce=' . wp_create_nonce('deactivate_user_group')
            . '" class="wpcf-ajax-link" id="wpcf-list-activate-'
            . $group_id . '">'
            . __('Deactivate', 'wpcf') . '</a>';
}

