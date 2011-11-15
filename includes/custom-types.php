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
        'query_var_enabled' => true,
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
                    $data['labels'][$label_key] = sprintf($label,
                            $data['labels']['singular_name']);
                    break;

                case 'search_items':
                case 'all_items':
                case 'not_found':
                case 'not_found_in_trash':
                case 'menu_name':
                    $data['labels'][$label_key] = sprintf($label,
                            $data['labels']['name']);
                    break;
            }
        }
    }
    $data['description'] = !empty($data['description']) ? htmlspecialchars(stripslashes($data['description']),
                    ENT_QUOTES) : '';
    $data['public'] = (empty($data['public']) || strval($data['public']) == 'hidden') ? false : true;
    $data['publicly_queryable'] = !empty($data['publicly_queryable']);
    $data['exclude_from_search'] = !empty($data['exclude_from_search']);
    $data['show_ui'] = (empty($data['show_ui']) || !$data['public']) ? false : true;
    $data['menu_position'] = !empty($data['menu_position']) ? intval($data['menu_position']) : 20;
    $data['hierarchical'] = !empty($data['hierarchical']);
    $data['supports'] = !empty($data['supports']) && is_array($data['supports']) ? array_keys($data['supports']) : array();
    $data['taxonomies'] = !empty($data['taxonomies']) && is_array($data['taxonomies']) ? array_keys($data['taxonomies']) : array();
    $data['has_archive'] = !empty($data['has_archive']);
    $data['can_export'] = !empty($data['can_export']);
    $data['show_in_nav_menus'] = !empty($data['show_in_nav_menus']);
    $data['show_in_menu'] = !empty($data['show_in_menu']);
    if (empty($data['query_var_enabled'])) {
        $data['query_var'] = false;
    } else if (empty($data['query_var'])) {
        $data['query_var'] = true;
    }
    if (!empty($data['show_in_menu_page'])) {
        $data['show_in_menu'] = $data['show_in_menu_page'];
    }
    if (empty($data['menu_icon'])) {
        unset($data['menu_icon']);
    } else {
        $data['menu_icon'] = stripslashes($data['menu_icon']);
    }
    if (!empty($data['rewrite']['enabled'])) {
        $data['rewrite']['with_front'] = !empty($data['rewrite']['with_front']);
        $data['rewrite']['feeds'] = !empty($data['rewrite']['feeds']);
        $data['rewrite']['pages'] = !empty($data['rewrite']['pages']);
        if (!empty($data['rewrite']['custom']) && $data['rewrite']['custom'] != 'custom') {
            unset($data['rewrite']['slug']);
        }
        unset($data['rewrite']['custom']);
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
    return '<a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;'
                    . 'wpcf_action=activate_post_type&amp;wpcf-post-type='
                    . $post_type . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
                    . $post_type) . '&amp;_wpnonce=' . wp_create_nonce('activate_post_type')
            . '" class="wpcf-ajax-link" id="wpcf-list-activate-'
            . $post_type . '">'
            . __('Activate') . '</a>';
}

/**
 * Returns HTML formatted AJAX deactivation link.
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_custom_types_get_ajax_deactivation_link($post_type) {
    return '<a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;'
                    . 'wpcf_action=deactivate_post_type&amp;wpcf-post-type='
                    . $post_type . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
                    . $post_type) . '&amp;_wpnonce=' . wp_create_nonce('deactivate_post_type')
            . '" class="wpcf-ajax-link" id="wpcf-list-activate-'
            . $post_type . '">'
            . __('Deactivate') . '</a>';
}