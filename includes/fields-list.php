<?php
/*
 * Fields and groups list functions
 */

/**
 * Renders 'widefat' table.
 */
function wpcf_admin_fields_list() {
    $groups = wpcf_admin_fields_get_groups();
    if (empty($groups)) {
        echo __('No groups.', 'wpcf') . ' <a href="'
                . admin_url('admin.php?page=wpcf-edit')
                . '">' . __('Create some.') . '</a>';
    } else {
        $rows = array();
        $header = array(
            'group_name' => __('Group name', 'wpcf'),
            'group_description' => __('Description', 'wpcf'),
//            'group_owner' => __('Created by', 'wpcf'),
            'group_active' => __('Active', 'wpcf'),
            'group_post_types' => __('Post types', 'wpcf'),
            'group_taxonomies' => __('Taxonomies', 'wpcf'),
        );
        foreach ($groups as $group) {
            
            // Set 'name' column
            $name = '';
            $name .= $group['name'];
            $name .= '<br />';
            $name .= '<a href="'
                    . admin_url('admin.php?page=wpcf-edit&amp;group_id='
                            . $group['id']) . '">' . __('Edit') . '</a> | ';

            $name .= $group['is_active'] ? wpcf_admin_fields_get_ajax_deactivation_link($group['id']) . ' | ' : wpcf_admin_fields_get_ajax_activation_link($group['id']) . ' | ';

            $name .= '<a href="'
                    . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=delete_group&amp;group_id='
                            . $group['id'] . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
                            . $group['id']) . '" class="wpcf-ajax-link wpcf-warning-delete" id="wpcf-list-delete-'
                    . $group['id'] . '">'
                    . __('Delete Permanently') . '</a>';

            $name .= '<div id="wpcf_list_ajax_response_' . $group['id'] . '"></div>';

            $rows[$group['id']]['name'] = $name;
            
            
            $rows[$group['id']]['description'] = $group['description'];
//            $created_by = new WP_User($group['user_id']);
//            $rows[$group['id']]['owner'] = $created_by->display_name;
            $rows[$group['id']]['active-' . $group['id']] = $group['is_active'] ? __('Yes') : __('No');
            
            // Set 'post_tpes' column
            $post_types = wpcf_admin_get_post_types_by_group($group['id']);
            $rows[$group['id']]['post_types'] = empty($post_types) ? __('None', 'wpcf') : implode(', ',
                    $post_types);
            
            // Set 'taxonomies' column
            $taxonomies = wpcf_admin_get_taxonomies_by_group($group['id']);
            $output = '';
            if (empty($taxonomies)) {
                $output = __('None', 'wpcf');
            } else {
                foreach ($taxonomies as $taxonomy => $terms) {
                    $output .= '<em>' . $taxonomy . '</em>: ';
                    $terms_output = array();
                    foreach ($terms as $term_id => $term) {
                        $terms_output[] = $term['name'];
                    }
                    $output .= implode(', ', $terms_output) . '<br />';
                }
            }
            $rows[$group['id']]['tax'] = $output;
        }
        
        // Render table
        wpcf_admin_widefat_table('wpcf_groups_list', $header, $rows);
    }
}