<?php
/* 
 * Custom Types and Taxonomies list functions
 */

/**
 * Renders 'widefat' table.
 */
function wpcf_admin_ctt_list() {
    $custom_types = get_option('wpcf-custom-types', array());
    if (empty($custom_types)) {
        echo '<br />' . __('No Custom Types.', 'wpcf') . ' <a href="'
                . admin_url('admin.php?page=wpcf-edit-type')
                . '">' . __('Create some.') . '</a>';
    } else {
        $rows = array();
        $header = array(
            'name' => __('Type name', 'wpcf'),
            'description' => __('Description', 'wpcf'),
            'active' => __('Active', 'wpcf'),
            'tax' => __('Taxonomies', 'wpcf'),
        );
        foreach ($custom_types as $post_type => $type) {
            $name = '';
            $name .= $type['labels']['name'];
            $name .= '<br />';
            $name .= '<a href="'
                    . admin_url('admin.php?page=wpcf-edit-type&amp;wpcf-post-type='
                            . $post_type) . '">' . __('Edit') . '</a> | ';
            $name .= empty($type['disabled']) ? wpcf_admin_custom_types_get_ajax_deactivation_link($post_type) . ' | ' : wpcf_admin_custom_types_get_ajax_activation_link($post_type) . ' | ';
            $name .= '<a href="'
                    . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=delete_post_type&amp;wpcf-post-type='
                            . $post_type . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
                            . $post_type) . '" class="wpcf-ajax-link wpcf-warning-delete" id="wpcf-list-delete-'
                    . $post_type . '">'
                    . __('Delete Permanently') . '</a>';
            $name .= '<div id="wpcf_list_ajax_response_' . $post_type . '"></div>';
            $rows[$post_type]['name'] = $name;
            $rows[$post_type]['description'] = htmlspecialchars(stripslashes($type['description']),
                    ENT_QUOTES);
            $rows[$post_type]['active-' . $post_type] = !empty($type['disabled']) ? __('No') : __('Yes');
            $output = !empty($type['taxonomies']) ? implode(', ', array_keys($type['taxonomies'])) : __('None', 'wpcf');
            $rows[$post_type]['tax'] = $output;
        }
        
        // Render table
        wpcf_admin_widefat_table('wpcf_groups_list', $header, $rows);
    }
}