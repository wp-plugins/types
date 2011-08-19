<?php
/*
 * Custom types form
 */

/**
 * Add/edit form
 */
function wpcf_admin_custom_types_form() {

    $ct = array();
    $id = false;
    $update = false;

    if (isset($_GET['wpcf-post-type'])) {
        $id = $_GET['wpcf-post-type'];
    } else if (isset($_POST['wpcf-post-type'])) {
        $id = $_POST['wpcf-post-type'];
    }

    if ($id) {
        $custom_types = get_option('wpcf-custom-types', array());
        if (isset($custom_types[$id])) {
            $ct = $custom_types[$id];
            $update = true;
            // Set rewrite if needed
            if (isset($_GET['wpcf-rewrite'])) {
                flush_rewrite_rules();
            }
        } else {
            wpcf_admin_message(__('Wrong custom type specified', 'wpcf'),
                    'error');
            return false;
        }
    } else {
        $ct = wpcf_custom_types_default();
    }

    $form = array();
    $form['#form']['callback'] = 'wpcf_admin_custom_types_form_submit';
    $form['#form']['redirection'] = false;

    if ($update) {
        $form['id'] = array(
            '#type' => 'hidden',
            '#value' => $id,
            '#name' => 'ct[wpcf-post-type]',
        );
    }

    $form['name'] = array(
        '#type' => 'textfield',
        '#name' => 'ct[labels][name]',
        '#title' => __('Custom type name plural', 'wpcf') . ' (' . __('required',
                'wpcf') . ')',
        '#description' => '<strong>' . __('Enter in plural!', 'wpcf')
        . '</strong><br />' . __('Alphanumeric with whitespaces only', 'wpcf')
        . '.',
        '#value' => isset($ct['labels']['name']) ? $ct['labels']['name'] : '',
        '#validate' => array(
            'required' => array('value' => true),
            'alphanumeric' => array('value' => true),
        ),
    );
    $form['name-singular'] = array(
        '#type' => 'textfield',
        '#name' => 'ct[labels][singular_name]',
        '#title' => __('Custom type name singular', 'wpcf') . ' (' . __('required',
                'wpcf') . ')',
        '#description' => '<strong>' . __('Enter in singular!', 'wpcf')
        . '</strong><br />'
        . __('Alphanumeric with whitespaces only', 'wpcf')
        . '.',
        '#value' => isset($ct['labels']['singular_name']) ? $ct['labels']['singular_name'] : '',
        '#validate' => array(
            'required' => array('value' => true),
            'alphanumeric' => array('value' => true),
        ),
    );
    $form['slug'] = array(
        '#type' => 'textfield',
        '#name' => 'ct[slug]',
        '#title' => __('Slug'),
        '#description' => '<strong>' . __('Enter in singular!', 'wpcf')
        . '</strong><br />' . __('Machine readable name.', 'wpcf')
//        . '<br />' . __('Alphanumeric, whitespaces or scores only.', 'wpcf')
        . '<br />' . __('If not provided - will be created from singular name.',
                'wpcf') . '<br />',
        '#value' => isset($ct['slug']) ? $ct['slug'] : '',
    );
    $form['description'] = array(
        '#type' => 'textarea',
        '#name' => 'ct[description]',
        '#title' => __('Description'),
        '#value' => isset($ct['description']) ? $ct['description'] : '',
        '#attributes' => array(
            'rows' => 4,
            'cols' => 60,
        ),
    );
    $form['public'] = array(
        '#type' => 'checkbox',
        '#name' => 'ct[public]',
        '#title' => __('Public'),
        '#description' => __('Make this type visible to visitors.', 'wpcf'),
        '#default_value' => !empty($ct['public']),
        '#value' => 1,
    );
//    $form['capabilities'] = array(
//        '#type' => 'checkbox',
//        '#force_boolean' => true,
//        '#name' => 'ct[capabilities]',
//        '#title' => __('Enable capabilities'),
//        '#description' => '<a href="javascript:void(0);" onclick="jQuery(\'#roles\').slideToggle();"><strong>' . __("Edit capabilities by roles", 'wpcf') . '</strong></a>',
//        '#default_value' => $ct['capabilities'],
//        '#value' => 1,
//    );
//    global $wp_roles;
//    $roles = $wp_roles->get_names();
//    $roles_array = array();
//    foreach ($roles as $name => $title) {
//        if ($name == 'administrator') {
//            continue;
//        }
//        $roles_array[$name . 'read_post'] = array(
//            '#title' => $title . ' ' . __('can read posts', 'wpcf'),
//            '#value' => 1,
//            '#name' => 'ct[capabilities_roles][' . $name . '][read_post]',
//            '#default_value' => isset($ct['capabilities_roles'][$name]['read_post']) ? $ct['capabilities_roles'][$name]['read_post'] : 1,
//        );
//        $roles_array[$name . 'read_private_posts'] = array(
//            '#title' => $title . ' ' . __('can read private posts', 'wpcf'),
//            '#value' => 1,
//            '#name' => 'ct[capabilities_roles][' . $name . '][read_private_posts]',
//            '#default_value' => isset($ct['capabilities_roles'][$name]['read_private_posts']) ? $ct['capabilities_roles'][$name]['read_private_posts'] : 0,
//        );
//        $roles_array[$name . 'edit_post'] = array(
//            '#title' => $title . ' ' . __('can edit post', 'wpcf'),
//            '#value' => 1,
//            '#name' => 'ct[capabilities_roles][' . $name . '][edit_post]',
//            '#default_value' => isset($ct['capabilities_roles'][$name]['edit_post']) ? $ct['capabilities_roles'][$name]['edit_post'] : 0,
//        );
//        $roles_array[$name . 'edit_posts'] = array(
//            '#title' => $title . ' ' . __('can edit posts', 'wpcf'),
//            '#value' => 1,
//            '#name' => 'ct[capabilities_roles][' . $name . '][edit_posts]',
//            '#default_value' => isset($ct['capabilities_roles'][$name]['edit_posts']) ? $ct['capabilities_roles'][$name]['edit_posts'] : 0,
//        );
//        $roles_array[$name . 'edit_others_posts'] = array(
//            '#title' => $title . ' ' . __('can edit others posts', 'wpcf'),
//            '#value' => 1,
//            '#name' => 'ct[capabilities_roles][' . $name . '][edit_others_posts]',
//            '#default_value' => isset($ct['capabilities_roles'][$name]['edit_others_posts']) ? $ct['capabilities_roles'][$name]['edit_others_posts'] : 0,
//        );
//        $roles_array[$name . 'publish_posts'] = array(
//            '#title' => $title . ' ' . __('can publish posts', 'wpcf'),
//            '#value' => 1,
//            '#name' => 'ct[capabilities_roles][' . $name . '][publish_posts]',
//            '#default_value' => isset($ct['capabilities_roles'][$name]['publish_posts']) ? $ct['capabilities_roles'][$name]['publish_posts'] : 0,
//        );
//        $roles_array[$name . 'delete_post'] = array(
//            '#title' => $title . ' ' . __('can delete post', 'wpcf'),
//            '#value' => 1,
//            '#name' => 'ct[capabilities_roles][' . $name . '][delete_post]',
//            '#after' => '<br /><br />',
//            '#default_value' => isset($ct['capabilities_roles'][$name]['delete_post']) ? $ct['capabilities_roles'][$name]['delete_post'] : 0,
//        );
//    }
//    $form['capabilities_roles'] = array(
//        '#before' => '<div id="roles" style="display:none;">',
//        '#type' => 'checkboxes',
//        '#options' => $roles_array,
//        '#after' => '</div>',
//    );
    $form['menu_position'] = array(
        '#type' => 'textfield',
        '#name' => 'ct[menu_position]',
        '#title' => __('Menu position', 'wpcf'),
        '#value' => isset($ct['menu_position']) ? $ct['menu_position'] : 20,
        '#validation' => array('numeric' => array('value' => true)),
    );
    $form['menu_icon'] = array(
        '#type' => 'textfield',
        '#name' => 'ct[menu_icon]',
        '#title' => __('Menu icon', 'wpcf'),
        '#description' => __('The url to the icon to be used for this menu. Default: null - defaults to the posts icon.',
                'wpcf'),
        '#value' => isset($ct['menu_icon']) ? $ct['menu_icon'] : '',
    );

    $taxonomies = get_taxonomies('', 'objects');
    $options = array();

    foreach ($taxonomies as $category_slug => $category) {
        if ($category_slug == 'nav_menu' || $category_slug == 'link_category'
                || $category_slug == 'post_format') {
            continue;
        }
        $options[$category_slug]['#name'] = 'ct[taxonomies][' . $category_slug . ']';
        $options[$category_slug]['#title'] = $category->labels->name;
        $options[$category_slug]['#default_value'] = !empty($ct['taxonomies'][$category_slug]);
        $options[$category_slug]['#inline'] = true;
        $options[$category_slug]['#after'] = '&nbsp;&nbsp;';
    }

    $form['taxonomies'] = array(
        '#type' => 'checkboxes',
        '#options' => $options,
        '#title' => __('Select taxonomies', 'wpcf'),
        '#description' => __('Registered taxonomies that will be used with this post type.',
                'wpcf'),
        '#name' => 'ct[taxonomies]',
    );
    $fieldset_id = $update ? 'fieldset-custom-types-' . $id . '-labels' : 'fieldset-cutom-types-new-labels';
    $collapsed = wpcf_admin_form_fieldset_is_collapsed($fieldset_id);
    $form['labels'] = array(
        '#type' => 'fieldset',
        '#id' => $fieldset_id,
        '#title' => _('Labels'),
        '#collapsible' => true,
        '#collapsed' => $collapsed,
        '#description' => __('Enter label values for custom type.<br />%s will be used to dinamically wrap text around singular name or name.',
                'wpcf')
    );
    $labels = array(
        'add_new' => array('title' => __('Add New'), 'description' => __('The add new text. The default is Add New for both hierarchical and non-hierarchical types.',
                    'wpcf')),
        'add_new_item' => array('title' => __('Add New %s'), 'description' => __('The add new item text. Default is Add New Post/Add New Page.',
                    'wpcf')),
//        'edit' => array('title' => __('Edit'), 'description' => __('The edit item text. Default is Edit Post/Edit Page.', 'wpcf')),
        'edit_item' => array('title' => __('Edit %s'), 'description' => __('The edit item text. Default is Edit Post/Edit Page.',
                    'wpcf')),
        'new_item' => array('title' => __('New %s'), 'description' => __('The view item text. Default is View Post/View Page.',
                    'wpcf')),
//        'view' => array('title' => __('View'), 'description' => __('', 'wpcf')),
        'view_item' => array('title' => __('View %s'), 'description' => __('The view item text. Default is View Post/View Page.',
                    'wpcf')),
        'search_items' => array('title' => __('Search %s'), 'description' => __('The search items text. Default is Search Posts/Search Pages.',
                    'wpcf')),
        'not_found' => array('title' => __('No %s found'), 'description' => __('The not found text. Default is No posts found/No pages found.',
                    'wpcf')),
        'not_found_in_trash' => array('title' => __('No %s found in Trash'), 'description' => __('The not found in trash text. Default is No posts found in Trash/No pages found in Trash.',
                    'wpcf')),
        'parent_item_colon' => array('title' => __('Parent text'), 'description' => __("The parent text. This string isn't used on non-hierarchical types. In hierarchical ones the default is Parent Page.",
                    'wpcf')),
        'all_items' => array('title' => __('All items'), 'description' => __('The all items text used in the menu. Default is the Name label.',
                    'wpcf')),
    );

    foreach ($labels as $name => $data) {
        $form['labels'][$name] = array(
            '#type' => 'textfield',
            '#name' => 'ct[labels][' . $name . ']',
            '#suffix' => '&nbsp;' . $data['title'],
            '#description' => $data['description'],
            '#value' => isset($ct['labels'][$name]) ? $ct['labels'][$name] : '',
        );
    }
    $fieldset_id = $update ? 'fieldset-custom-types-' . $id . '-supports' : 'fieldset-cutom-types-new-supports';
    $collapsed = wpcf_admin_form_fieldset_is_collapsed($fieldset_id);
    $form['fields'] = array(
        '#type' => 'fieldset',
        '#id' => $fieldset_id,
        '#title' => __('Supports', 'wpcf'),
        '#collapsible' => true,
        '#collapsed' => $collapsed,
    );
    $options = array(
        'title' => array(
            '#name' => 'ct[supports][title]',
            '#default_value' => !empty($ct['supports']['title']),
            '#title' => __('Title'),
            '#description' => __('Text input field to create a post title.',
                    'wpcf'),
        ),
        'editor' => array(
            '#name' => 'ct[supports][editor]',
            '#default_value' => !empty($ct['supports']['editor']),
            '#title' => __('Editor'),
            '#description' => __('Content input box for writing.', 'wpcf'),
        ),
        'comments' => array(
            '#name' => 'ct[supports][comments]',
            '#default_value' => !empty($ct['supports']['comments']),
            '#title' => __('Comments'),
            '#description' => __('Ability to turn comments on/off.', 'wpcf'),
        ),
        'trackbacks' => array(
            '#name' => 'ct[supports][trackbacks]',
            '#default_value' => !empty($ct['supports']['trackbacks']),
            '#title' => __('Trackbacks'),
            '#description' => __('Ability to turn trackbacks and pingbacks on/off.',
                    'wpcf'),
        ),
        'revisions' => array(
            '#name' => 'ct[supports][revisions]',
            '#default_value' => !empty($ct['supports']['revisions']),
            '#title' => __('Revisions'),
            '#description' => __('Allows revisions to be made of your post.',
                    'wpcf'),
        ),
        'author' => array(
            '#name' => 'ct[supports][author]',
            '#default_value' => !empty($ct['supports']['author']),
            '#title' => __('Author'),
            '#description' => __('Displays a select box for changing the post author.',
                    'wpcf'),
        ),
        'excerpt' => array(
            '#name' => 'ct[supports][excerpt]',
            '#default_value' => !empty($ct['supports']['excerpt']),
            '#title' => __('Excerpt'),
            '#description' => __('A textarea for writing a custom excerpt.',
                    'wpcf'),
        ),
        'thumbnail' => array(
            '#name' => 'ct[supports][thumbnail]',
            '#default_value' => !empty($ct['supports']['thumbnail']),
            '#title' => __('Thumbnail'),
            '#description' => __('The thumbnail (featured image in 3.0) uploading box.',
                    'wpcf'),
        ),
        'custom-fields' => array(
            '#name' => 'ct[supports][custom-fields]',
            '#default_value' => !empty($ct['supports']['custom-fields']),
            '#title' => __('Custom-fields'),
            '#description' => __('Custom fields input area.', 'wpcf'),
        ),
        'page-attributes' => array(
            '#name' => 'ct[supports][page-attributes]',
            '#default_value' => !empty($ct['supports']['page-attributes']),
            '#title' => __('page-attributes'),
            '#description' => __('Menu order, hierarchical must be true to show Parent option',
                    'wpcf'),
        ),
        'post-formats' => array(
            '#name' => 'ct[supports][post-formats]',
            '#default_value' => !empty($ct['supports']['post-formats']),
            '#title' => __('post-formats'),
            '#description' => sprintf(__('Add post formats, see %sPost Formats%s',
                            'wpcf'),
                    '<a href="http://codex.wordpress.org/Post_Formats" title="Post Formats" target="_blank">',
                    '</a>'),
        ),
    );
    $form['fields']['supports'] = array(
        '#type' => 'checkboxes',
        '#options' => $options,
        '#name' => 'ct[supports]',
    );
    $fieldset_id = $update ? 'fieldset-custom-types-' . $id . '-advanced' : 'fieldset-cutom-types-new-advanced';
    $collapsed = wpcf_admin_form_fieldset_is_collapsed($fieldset_id);
    $form['advanced'] = array(
        '#type' => 'fieldset',
        '#id' => $fieldset_id,
        '#title' => __('Advanced settings'),
        '#collapsible' => true,
        '#collapsed' => $collapsed,
    );
    $form['advanced']['rewrite'] = array(
        '#type' => 'fieldset',
        '#title' => __('Rewrite'),
        '#collapsible' => false,
    );
    $form['advanced']['rewrite']['enabled'] = array(
        '#type' => 'checkbox',
        '#force_boolean' => true,
        '#title' => __('Rewrite'),
        '#name' => 'ct[rewrite][enabled]',
        '#description' => __('Rewrite permalinks with this format. False to prevent rewrite. Default: true and use post type as slug.',
                'wpcf'),
        '#default_value' => !empty($ct['rewrite']['enabled']),
        '#inline' => true,
    );
    $form['advanced']['rewrite']['slug'] = array(
        '#type' => 'textfield',
        '#name' => 'ct[rewrite][slug]',
        '#title' => __('Prepend posts with this slug', 'wpcf'),
        '#description' => __("Prepend posts with this slug - defaults to post type's name.",
                'wpcf'),
        '#value' => isset($ct['rewrite']['slug']) ? $ct['rewrite']['slug'] : '',
        '#validation' => array('numeric' => '#submitted'),
        '#inline' => true,
    );
    $form['advanced']['rewrite']['with_front'] = array(
        '#type' => 'checkbox',
        '#force_boolean' => true,
        '#title' => __('Allow permalinks to be prepended with front base',
                'wpcf'),
        '#name' => 'ct[rewrite][with_front]',
        '#description' => __('Example: if your permalink structure is /blog/, then your links will be: false->/news/, true->/blog/news/.',
                'wpcf') . ' ' . __('Defaults to true.', 'wpcf'),
        '#default_value' => !empty($ct['rewrite']['with_front']),
        '#inline' => true,
    );
    $form['advanced']['rewrite']['feeds'] = array(
        '#type' => 'checkbox',
        '#name' => 'ct[rewrite][feeds]',
        '#title' => __('Feeds'),
        '#description' => __('Defaults to has_archive value.', 'wpcf'),
        '#default_value' => !empty($ct['rewrite']['feeds']),
        '#value' => 1,
        '#inline' => true,
    );
    $form['advanced']['rewrite']['pages'] = array(
        '#type' => 'checkbox',
        '#name' => 'ct[rewrite][pages]',
        '#title' => __('Pages'),
        '#description' => __('Defaults to true.', 'wpcf'),
        '#default_value' => !empty($ct['rewrite']['pages']),
        '#value' => 1,
        '#inline' => true,
    );
    $show_in_menu_page = isset($ct['show_in_menu_page']) ? $ct['show_in_menu_page'] : '';
    $form['advanced']['vars'] = array(
        '#type' => 'checkboxes',
        '#name' => 'ct[vars]',
        '#options' => array(
            'has_archive' => array(
                '#name' => 'ct[has_archive]',
                '#default_value' => !empty($ct['has_archive']),
                '#title' => __('has_archive', 'wpcf'),
                '#description' => __('Allow custom type to have index page.',
                        'wpcf') . '<br />' . __('Default: not set.', 'wpcf')),
            'show_in_menu' => array(
                '#name' => 'ct[show_in_menu]',
                '#default_value' => !empty($ct['show_in_menu']),
                '#title' => __('show_in_menu', 'wpcf'),
                '#description' => __('Whether to show the post type in the admin menu and where to show that menu. Note that show_ui must be true.',
                        'wpcf') . '<br />' . __('Default: null.', 'wpcf'),
                '#after' => '<input type="text" name="ct[show_in_menu_page]" value="' . $show_in_menu_page . '" />&nbsp;' . __("Top level page like 'tools.php' or 'edit.php?post_type=page'",
                        'wpcf') . '<br /><br />',
            ),
            'show_ui' => array(
                '#name' => 'ct[show_ui]',
                '#default_value' => !empty($ct['show_ui']),
                '#title' => __('show_ui', 'wpcf'),
                '#description' => __('Generate a default UI for managing this post type.',
                        'wpcf') . '<br />' . __('Default: value of public argument.',
                        'wpcf')),
            'publicly_queryable' => array(
                '#name' => 'ct[publicly_queryable]',
                '#default_value' => !empty($ct['publicly_queryable']),
                '#title' => __('publicly_queryable', 'wpcf'),
                '#description' => __('Whether post_type queries can be performed from the front end.',
                        'wpcf') . '<br />' . __('Default: value of public argument.',
                        'wpcf')),
            'exclude_from_search' => array(
                '#name' => 'ct[exclude_from_search]',
                '#default_value' => !empty($ct['exclude_from_search']),
                '#title' => __('exclude_from_search', 'wpcf'),
                '#description' => __('Whether to exclude posts with this post type from search results.',
                        'wpcf') . '<br />' . __('Default: value of the opposite of the public argument.',
                        'wpcf')),
            'hierarchical' => array(
                '#name' => 'ct[hierarchical]',
                '#default_value' => !empty($ct['hierarchical']),
                '#title' => __('hierarchical', 'wpcf'),
                '#description' => __('Whether the post type is hierarchical. Allows Parent to be specified.',
                        'wpcf') . '<br />' . __('Default: false.', 'wpcf')),
            'can_export' => array(
                '#name' => 'ct[can_export]',
                '#default_value' => !empty($ct['can_export']),
                '#title' => __('can_export', 'wpcf'),
                '#description' => __('Can this post_type be exported.', 'wpcf') . '<br />' . __('Default: true.',
                        'wpcf')),
            'show_in_nav_menus' => array(
                '#name' => 'ct[show_in_nav_menus]',
                '#default_value' => !empty($ct['show_in_nav_menus']),
                '#title' => __('show_in_nav_menus', 'wpcf'),
                '#description' => __('Whether post_type is available for selection in navigation menus.',
                        'wpcf') . '<br />' . __('Default: value of public argument.',
                        'wpcf')),
        ),
    );
    $form['advanced']['query_var'] = array(
        '#type' => 'textfield',
        '#name' => 'ct[query_var]',
        '#title' => 'query_var',
        '#description' => __('String to customize query_var. Leave empty to use default.',
                'wpcf'),
        '#value' => isset($ct['query_var']) ? $ct['query_var'] : '',
    );
    $form['advanced']['permalink_epmask'] = array(
        '#type' => 'textfield',
        '#name' => 'ct[permalink_epmask]',
        '#title' => __('Permalink epmask', 'wpcf'),
        '#description' => sprintf(__('Default value EP_PERMALINK. More info here %s.',
                        'wpcf'),
                '<a href="http://core.trac.wordpress.org/ticket/12605" target="_blank">link</a>'),
        '#value' => isset($ct['permalink_epmask']) ? $ct['permalink_epmask'] : '',
    );
    $form['submit'] = array(
        '#type' => 'markup',
        '#markup' => get_submit_button(__('Save Type', 'wpcf')),
    );

    return $form;
}

/**
 * Adds JS validation script.
 */
function wpcf_admin_types_form_js_validation() {
    wpcf_form_render_js_validation();
}

/**
 * Submit function
 */
function wpcf_admin_custom_types_form_submit($form) {
    if (!isset($_POST['ct'])) {
        return false;
    }
    $data = $_POST['ct'];
    $update = false;

    // Sanitize data
    if (isset($data['wpcf-post-type'])) {
        $update = true;
        $data['wpcf-post-type'] = sanitize_title($data['wpcf-post-type']);
    }
    if (isset($data['slug'])) {
        $data['slug'] = sanitize_title($data['slug']);
    }
    if (isset($data['rewrite']['slug'])) {
        $data['rewrite']['slug'] = sanitize_title($data['rewrite']['slug']);
    }

    // Set post type name
    $post_type = '';
    if (!empty($data['slug'])) {
        $post_type = $data['slug'];
    } else if (!empty($data['wpcf-post-type'])) {
        $post_type = $data['wpcf-post-type'];
    } else if (!empty($data['labels']['singular_name'])) {
        $post_type = sanitize_title($data['labels']['singular_name']);
    }

    if (empty($post_type)) {
        wpcf_admin_message(__('Please set post type name', 'wpcf'),
                'error');
//        $form->triggerError();
        return false;
    }

    $data['slug'] = $post_type;
    $custom_types = get_option('wpcf-custom-types', array());

    // Check overwriting
    if (!$update && array_key_exists($post_type, $custom_types)) {
        wpcf_admin_message(__('Custom type already exists', 'wpcf'),
                'error');
//            $form->triggerError();
        return false;
    }

    // Check if renaming then rename all post entries and delete old type
    if (!empty($data['wpcf-post-type'])
            && $data['wpcf-post-type'] != $post_type) {
        global $wpdb;
        $wpdb->update($wpdb->posts, array('post_type' => $post_type),
                array('post_type' => $data['wpcf-post-type']), array('%s'),
                array('%s')
        );
        // Delete old type
        unset($custom_types[$data['wpcf-post-type']]);
    }

    $custom_types[$post_type] = $data;
    update_option('wpcf-custom-types', $custom_types);

    // Redirect
    wp_redirect(admin_url('admin.php?page=wpcf-edit-type&wpcf-post-type=' . $post_type . '&wpcf-rewrite=1'));
    die();
}