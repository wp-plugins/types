<?php
/*
 * Custom types functions.
 * @todo Investigate supports post-formats
 */

/**
 * Returns default custom type structure.
 *
 * @return array
 */
function wpcf_custom_types_default() {
    return array(
        'labels' => array(
            'name' => '',
            'singular_name' => '',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New %s',
//          'edit' => 'Edit',
            'edit_item' => 'Edit %s',
            'new_item' => 'New %s',
//          'view' => 'View',
            'view_item' => 'View %s',
            'search_items' => 'Search %s',
            'not_found' => 'No %s found',
            'not_found_in_trash' => 'No %s found in Trash',
            'parent_item_colon' => 'Parent %s',
            'menu_name' => '%s',
            'all_items' => '%s',
        ),
        'slug' => '',
        'description' => '',
        'public' => true,
        'capabilities' => false,
        'menu_position' => 20,
        'menu_icon' => '',
        'taxonomies' => array(
            'category' => false,
            'post_tag' => false,
        ),
        'supports' => array(
            'title' => true,
            'editor' => true,
            'trackbacks' => false,
            'comments' => false,
            'revisions' => false,
            'author' => false,
            'excerpt' => false,
            'thumbnail' => false,
            'custom-fields' => false,
            'page-attributes' => false,
            'post-formats' => false,
        ),
        'rewrite' => array(
            'enabled' => true,
            'slug' => '',
            'with_front' => true,
            'feeds' => true,
            'pages' => true,
        ),
        'has_archive' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'hierarchical' => false,
        'query_var' => '',
        'can_export' => true,
        'show_in_nav_menus' => true,
        'register_meta_box_cb' => '',
        'permalink_epmask' => 'EP_PERMALINK'
    );
}

/**
 * Inits custom types.
 */
function wpcf_custom_types_init() {
    $custom_types = get_option('wpcf-custom-types', array());
    if (!empty($custom_types)) {
        foreach ($custom_types as $post_type => $data) {
            wpcf_custom_types_register($post_type, $data);
        }
    }
}

/**
 * Registers custom post type.
 * 
 * @param type $post_type
 * @param type $data 
 */
function wpcf_custom_types_register($post_type, $data) {
    if (!empty($data['disabled'])) {
        return false;
    }
    // Set labels
    if (!empty($data['labels'])) {
        if (!isset($data['labels']['name'])) {
            $data['labels']['name'] = $post_type;
        }
        if (!isset($data['labels']['singular_name'])) {
            $data['labels']['singular_name'] = $data['labels']['name'];
        }
        foreach ($data['labels'] as $label_key => $label) {
            switch ($label_key) {
                case 'add_new_item':
                case 'edit_item':
                case 'new_item':
                case 'view_item':
                case 'parent_item_colon':
                case 'menu_name':
                    $data['labels'][$label_key] = sprintf($label,
                            $data['labels']['singular_name']);
                    break;

                case 'search_items':
                case 'all_items':
                case 'not_found':
                case 'not_found_in_trash':
                    $data['labels'][$label_key] = sprintf($label,
                            $data['labels']['name']);
                    break;
            }
        }
    }
    $data['description'] = isset($data['description']) ? htmlspecialchars(stripslashes($data['description']),
                    ENT_QUOTES) : '';
    $data['public'] = isset($data['public']);
    $data['publicly_queryable'] = isset($data['publicly_queryable']);
    $data['exclude_from_search'] = isset($data['exclude_from_search']);
    $data['show_ui'] = isset($data['show_ui']);
    $data['menu_position'] = !empty($data['menu_position']) ? intval($data['menu_position']) : 20;
    $data['hierarchical'] = isset($data['hierarchical']);
    $data['supports'] = !empty($data['supports']) && is_array($data['supports']) ? array_keys($data['supports']) : array();
    $data['taxonomies'] = !empty($data['taxonomies']) && is_array($data['taxonomies']) ? array_keys($data['taxonomies']) : array();
    $data['has_archive'] = isset($data['has_archive']);
    $data['can_export'] = isset($data['can_export']);
    $data['show_in_nav_menus'] = isset($data['show_in_nav_menus']);
    $data['show_in_menu'] = isset($data['show_in_menu']);
    if (empty($data['query_var'])) {
        unset($data['query_var']);
    }
    if (!empty($data['show_in_menu_page'])) {
        $data['show_in_menu'] = $data['show_in_menu_page'];
    }
    if (empty($data['menu_icon'])) {
        unset($data['menu_icon']);
    }
    if (isset($data['rewrite']['enabled']) && $data['rewrite']['enabled']) {
        $data['rewrite']['feeds'] = isset($data['rewrite']['feeds']);
        $data['rewrite']['pages'] = isset($data['rewrite']['pages']);
    } else {
        $data['rewrite'] = false;
    }
    register_post_type($post_type, $data);
}

/**
 * Returns HTML formatted AJAX activation link.
 * 
 * @param type $post_type
 * @return type 
 */
function wpcf_admin_custom_types_get_ajax_activation_link($post_type) {
    return '<a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=activate_post_type&amp;wpcf-post-type='
                    . $post_type . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
                    . $post_type) . '" class="wpcf-ajax-link" id="wpcf-list-activate-'
            . $post_type . '">'
            . __('Activate') . '</a>';
}

/**
 * Returns HTML formatted AJAX deactivation link.
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_custom_types_get_ajax_deactivation_link($post_type) {
    return '<a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=deactivate_post_type&amp;wpcf-post-type='
                    . $post_type . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
                    . $post_type) . '" class="wpcf-ajax-link" id="wpcf-list-activate-'
            . $post_type . '">'
            . __('Deactivate') . '</a>';
}