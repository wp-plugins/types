<?php
/*
 * Fields and groups form functions.
 */
require_once WPCF_ABSPATH . '/classes/validate.php';

/**
 * Saves fields and groups.
 * 
 * If field name is changed in specific group - new one will be created,
 * otherwise old one will be updated and will appear in that way in other grups.
 * 
 * @return type 
 */
function wpcf_admin_save_fields_groups_submit($form) {
    if (!isset($_POST['wpcf']['group']['name'])) {
        return false;
    }
    global $wpdb;

    $new_group = false;

    $group_slug = $_POST['wpcf']['group']['slug'] = sanitize_title($_POST['wpcf']['group']['name']);

    // Basic check
    if (isset($_POST['group-id'])) {
        // Check if group exists
        $group_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpcf_groups
        WHERE id=" . intval($_POST['group-id']));
        if (empty($group_id)) {
            $form->triggerError();
            wpcf_admin_message(sprintf(__("Wrong group ID %d", 'wpcf'),
                            intval($_POST['group-id'])), 'error');
            return false;
        }
        // Check if group slug exists
        $doubled_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpcf_groups
        WHERE id<>" . intval($_POST['group-id']) . " AND slug='" . $group_slug . "'");
        if (!empty($doubled_id)) {
            $form->triggerError();
            wpcf_admin_message(sprintf(__("Group named &quot;%s&quot; already exists",
                                    'wpcf'), $_POST['wpcf']['group']['name']),
                    'error');
            return false;
        }
    } else {
        // If new check overwriting
        $group_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpcf_groups
        WHERE slug='$group_slug'");
        if (!empty($group_id)) {
            $form->triggerError();
            wpcf_admin_message(sprintf(__("Group named &quot;%s&quot; already exists",
                                    'wpcf'), $_POST['wpcf']['group']['name']),
                    'error');
            return false;
        }
        $new_group = true;
    }

    // Save fields for future use
    $fields = array();
    if (!empty($_POST['wpcf']['fields'])) {
        foreach ($_POST['wpcf']['fields'] as $key => $field) {
            $slug = $_POST['wpcf']['fields'][$key]['slug'] = sanitize_title($field['name']);
            $field_id = wpcf_admin_fields_save_field($field);
            if (!empty($field_id)) {
                $fields[] = $field_id;
            }
        }
    }

    // Save group
    $post_types = isset($_POST['wpcf']['group']['supports']) ? $_POST['wpcf']['group']['supports'] : array();
    $taxonomies_post = isset($_POST['wpcf']['group']['taxonomies']) ? $_POST['wpcf']['group']['taxonomies'] : array();
    $terms = array();
    foreach ($taxonomies_post as $taxonomy) {
        foreach ($taxonomy as $tax => $term) {
            $terms[] = $term;
        }
    }
    // Rename if needed
    if (isset($_POST['group-id'])) {
        $_POST['wpcf']['group']['id'] = $_POST['group-id'];
    }

    $group_id = wpcf_admin_fields_save_group($_POST['wpcf']['group']);

    // Set open fieldsets
    if ($new_group && !empty($group_id)) {
        $open_fieldsets = get_user_meta(get_current_user_id(),
                'wpcf-group-form-toggle', true);
        if (isset($open_fieldsets[-1])) {
            $open_fieldsets[$group_id] = $open_fieldsets[-1];
            unset($open_fieldsets[-1]);
            update_user_meta(get_current_user_id(), 'wpcf-group-form-toggle',
                    $open_fieldsets);
        }
    }

    // Rest of processes
    if (!empty($group_id)) {
        wpcf_admin_fields_save_group_fields($group_id, $fields);
        wpcf_admin_fields_save_group_post_types($group_id, $post_types);
        wpcf_admin_fields_save_group_terms($group_id, $terms);
        $_POST['wpcf']['group']['fields'] = isset($_POST['wpcf']['fields']) ? $_POST['wpcf']['fields'] : array();
        do_action('wpcf_save_group', $_POST['wpcf']['group']);
        wpcf_admin_message_store(__('Group saved', 'wpcf'));
        wp_redirect(admin_url('admin.php?page=wpcf-edit&group_id=' . $group_id));
        die();
    } else {
        wpcf_admin_message_store(__('Error saving group', 'wpcf'), 'error');
    }
}

/**
 * Generates form data.
 */
function wpcf_admin_fields_form() {
    $default = array();

    // If it's update, get data
    $update = false;
    if (isset($_GET['group_id'])) {
        $update = wpcf_admin_fields_get_group($_GET['group_id']);
        if (empty($update)) {
            $update = false;
            // @todo BUG
            wpcf_admin_message(sprintf(__("Group with ID %d don't exist", 'wpcf'),
                            intval($_GET['group_id'])));
        } else {
            $update['fields'] = wpcf_admin_fields_get_fields_by_group($_GET['group_id']);
            $update['post_types'] = wpcf_admin_get_post_types_by_group($_GET['group_id']);
            $update['taxonomies'] = wpcf_admin_get_taxonomies_by_group($_GET['group_id']);
        }
    }

    $form = array();
    $form['#form']['callback'] = array('wpcf_admin_save_fields_groups_submit');

    // Form sidebars

    $form['open-sidebar'] = array(
        '#type' => 'markup',
        '#markup' => '<div class="wpcf-form-fields-align-right">',
    );
    $form['fields'] = array(
        '#type' => 'fieldset',
        '#title' => __('Available fields', 'wpcf'),
    );

    // Get built-in field types
    foreach (glob(WPCF_INC_ABSPATH . '/fields/*.php') as $filename) {
        require_once $filename;
        if (function_exists('wpcf_fields_' . basename($filename, '.php'))) {
            $data = call_user_func('wpcf_fields_' . basename($filename, '.php'));
            $form['fields'][basename($filename, '.php')] = array(
                '#type' => 'markup',
                '#markup' => '<a href="' . admin_url('admin-ajax.php'
                        . '?action=wpcf_ajax&amp;wpcf_action=fields_insert'
                        . '&amp;field=' . basename($filename, '.php')) . '" '
                . 'class="wpcf-fields-add-ajax-link button-secondary">' . $data['title'] . '</a> ',
            );
        }
    }

    // Get fields created by user
    $fields = wpcf_admin_fields_get_fields();
    if (!empty($fields)) {
        $form['fields-existing'] = array(
            '#type' => 'fieldset',
            '#title' => __('User created fields', 'wpcf'),
        );
        foreach ($fields as $key => $field) {
            $form['fields-existing'][$key] = array(
                '#type' => 'markup',
                '#markup' => '<a href="' . admin_url('admin-ajax.php'
                        . '?action=wpcf_ajax'
                        . '&amp;wpcf_action=fields_insert_existing'
                        . '&amp;field=' . $field['id']) . '" '
                . 'class="wpcf-fields-add-ajax-link button-secondary">'
                . htmlspecialchars(stripslashes($field['name'])) . '</a> ',
            );
        }
    }
    $form['close-sidebar'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );

    // Group data

    $form['open-main'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="wpcf-form-fields-main">',
    );

    $form['title'] = array(
        '#type' => 'textfield',
        '#name' => 'wpcf[group][name]',
        '#title' => __('Group title', 'wpcf'),
        '#value' => $update ? $update['name'] : '',
        '#validate' => array(
            'required' => array(
                'value' => true,
            ),
        )
    );
    $form['description'] = array(
        '#type' => 'textarea',
        '#name' => 'wpcf[group][description]',
        '#description' => __('This will be used as intro text on edit page',
                'wpcf'),
        '#title' => __('Description', 'wpcf'),
        '#value' => $update ? $update['description'] : '',
    );

    // Support post types

    $post_types = get_post_types('', 'objects');
    $options = array();

    foreach ($post_types as $post_type_slug => $post_type) {
        if (in_array($post_type_slug,
                        array('attachment', 'revision', 'nav_menu_item',
                            'view', 'view-template'))) {
            continue;
        }
        $options[$post_type_slug]['#name'] = 'wpcf[group][supports][' . $post_type_slug . ']';
        $options[$post_type_slug]['#title'] = $post_type->label;
        $options[$post_type_slug]['#default_value'] = ($update && !empty($update['post_types']) && in_array($post_type_slug,
                        $update['post_types'])) ? 1 : 0;
        $options[$post_type_slug]['#value'] = $post_type_slug;
        $options[$post_type_slug]['#inline'] = TRUE;
        $options[$post_type_slug]['#suffix'] = '<br />';
    }

    $form['types'] = array(
        '#type' => 'fieldset',
        '#id' => 'post_types',
        '#title' => __('Support post types', 'wpcf'),
        '#description' => __('Select if you want post to be of any specific type',
                'wpcf'),
        '#collapsible' => true,
        '#collapsed' => wpcf_admin_fields_form_fieldset_is_collapsed('post_types'),
    );
    $form['types']['content'] = array(
        '#type' => 'checkboxes',
        '#options' => $options,
        '#name' => 'wpcf[group][supports]',
    );

    // Support taxonomies

    $taxonomies = get_taxonomies('', 'objects');
    $options = array();

    $form['taxonomies'] = array(
        '#type' => 'fieldset',
        '#id' => 'taxonomies',
        '#title' => __('Support taxonomies', 'wpcf'),
        '#description' => __('Select if you want post to belong to any specific taxonomy',
                'wpcf'),
        '#collapsible' => true,
        '#collapsed' => wpcf_admin_fields_form_fieldset_is_collapsed('taxonomies'),
    );

    foreach ($taxonomies as $category_slug => $category) {
        if ($category_slug == 'nav_menu' || $category_slug == 'link_category'
                || $category_slug == 'post_format') {
            continue;
        }
        $terms = get_terms($category_slug);
        $options = array();
        if (!empty($terms)) {
            $add_title = '<div class="taxonomy-title">' . $category->labels->name . '</div>';
            foreach ($terms as $term) {
                $checked = 0;
                if ($update && !empty($update['taxonomies']) && array_key_exists($category_slug, $update['taxonomies'])) {
                    if (array_key_exists($term->term_id, $update['taxonomies'][$category_slug])) {
                        $checked = 1;
                    }
                }
                $options[$term->term_id]['#name'] = 'wpcf[group][taxonomies]['
                        . $category_slug . '][' . $term->term_id . ']';
                $options[$term->term_id]['#title'] = $term->name;
                $options[$term->term_id]['#default_value'] = $checked;
                $options[$term->term_id]['#value'] = $term->term_id;
                $options[$term->term_id]['#inline'] = TRUE;
                $options[$term->term_id]['#prefix'] = $add_title;
                $options[$term->term_id]['#suffix'] = '<br />';
                $add_title = '';
            }
            $form['taxonomies'][$category_slug] = array(
                '#type' => 'checkboxes',
                '#options' => $options,
                '#name' => 'wpcf[group][taxonomies][' . $category_slug . ']',
                '#suffix' => '<br />',
            );
        }
    }

    // Group fields

    $form['ajax-response-open'] = array(
        '#type' => 'markup',
        '#markup' => '<h2>' . __('Fields', 'wpcf') . '</h2>'
        . '<div id="wpcf-fields-sortable" class="ui-sortable">',
    );

    // If it's update, display existing fields
    $existing_fields = array();
    if ($update && isset($update['fields'])) {
        foreach ($update['fields'] as $slug => $field) {
            $field['submitted_key'] = $slug;
            $field['group_id'] = $update['id'];
            $form_field = wpcf_fields_get_field_form_data($field['type'], $field);
            if (is_array($form_field)) {
                $form['draggable-open-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '<div class="ui-draggable">'
                );
                $form = $form + $form_field;
                $form['draggable-close-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '</div>'
                );
            }
            $existing_fields[] = $slug;
        }
    }
    // Any new fields submitted but failed? (Don't double it)
    if (!empty($_POST['wpcf']['fields'])) {
        foreach ($_POST['wpcf']['fields'] as $key => $field) {
            if (in_array($key, $existing_fields)) {
                continue;
            }
            $field['submitted_key'] = $key;
            $form_field = wpcf_fields_get_field_form_data($field['type'], $field);
            if (is_array($form_field)) {
                $form['draggable-open-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '<div class="ui-draggable">'
                );
                $form = $form + $form_field;
                $form['draggable-close-' . rand()] = array(
                    '#type' => 'markup',
                    '#markup' => '</div>'
                );
            }
        }
    }
    $form['ajax-response-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>' . '<div id="wpcf-ajax-response"></div>',
    );

    // If update, create ID field
    if ($update) {
        $form['group-id'] = array(
            '#type' => 'hidden',
            '#name' => 'group-id',
            '#value' => $update['id'],
        );
    }

    $form['submit'] = array(
        '#type' => 'submit',
        '#name' => 'save',
        '#value' => __('Save', 'wpcf'),
        '#attributes' => array('class' => 'button-primary'),
    );

    // Close main div
    $form['close-sidebar'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );

    $form = apply_filters('wpcf_form_fields', $form);

    return $form;
}

/**
 * Dynamically adds new field on AJAX call.
 * 
 * @param type $form_data 
 */
function wpcf_fields_insert_ajax($form_data = array()) {
    echo wpcf_fields_get_field_form($_GET['field']);
}

/**
 * Dynamically adds existing field on AJAX call.
 * 
 * @param type $form_data 
 */
function wpcf_fields_insert_existing_ajax() {
    $field = wpcf_admin_fields_get_field(intval($_GET['field']));
    if (!empty($field)) {
        echo wpcf_fields_get_field_form($field['type'], $field);
    } else {
        echo '<div>' . __("Requested field don't exist", 'wpcf');
    }
}

/**
 * Returns HTML formatted field form (draggable).
 * 
 * @param type $type
 * @param type $form_data
 * @return type 
 */
function wpcf_fields_get_field_form($type, $form_data = array()) {
    $form = wpcf_fields_get_field_form_data($type, $form_data);
    if ($form) {
        return '<div class="ui-draggable">'
                . wpcf_form_simple($form)
                . '</div>';
    }
    return '<div>' . __('Wrong field requested', 'wpcf') . '</div>';
}

/**
 * Processes field form data.
 * 
 * @param type $type
 * @param type $form_data
 * @return type 
 */
function wpcf_fields_get_field_form_data($type, $form_data = array()) {

    // Get field type data
    $filename = WPCF_INC_ABSPATH . '/fields/' . $type . '.php';

    if (file_exists($filename)) {
        require_once $filename;
        $form = array();

        // Set right ID if existing field
        if (isset($form_data['submitted_key'])) {
            $id = $form_data['submitted_key'];
        } else {
            $id = $type . '-' . rand();
        }

        // Set remove link
        $remove_link = isset($form_data['group_id']) ? admin_url('admin-ajax.php?wpcf_ajax_callback=wpcfFieldsFormDeleteElement&amp;wpcf_warning=' . __('Are you sure?',
                                'wpcf') . '&amp;action=wpcf_ajax&amp;wpcf_action=remove_field_from_group&amp;group_id=' . intval($form_data['group_id']) . '&amp;field_id=' . intval($form_data['id'])) : 'javascript:void(0);';

        // Set move button
        $form['wpcf-' . $id . '-control'] = array(
            '#type' => 'markup',
            '#markup' => '<img src="' . WPCF_RES_RELPATH
            . '/images/move.png" class="wpcf-fields-form-move-field" alt="'
            . __('Move this field', 'wpcf') . '" /><a href="'
            . $remove_link . '" '
            . 'class="wpcf-form-fields-delete wpcf-ajax-link">'
            . '<img src="' . WPCF_RES_RELPATH . '/images/delete.png" alt="'
            . __('Delete this field', 'wpcf') . '" /></a>',
        );

        // Set fieldset

        $collapsed = wpcf_admin_fields_form_fieldset_is_collapsed('fieldset-' . $id);
        // Set collapsed on AJAX call (insert)
        $collapsed = defined('DOING_AJAX') ? false : $collapsed;

        // Set title
        $title = !empty($form_data['name']) ? $form_data['name'] : __('Untitled');
        $title = '<span class="wpcf-legend-update">' . $title . '</span> - '
                . sprintf(__('%s field', 'wpcf'), $type);
        $form['wpcf-' . $id] = array(
            '#type' => 'fieldset',
            '#title' => $title,
            '#id' => 'fieldset-' . $id,
            '#collapsible' => true,
            '#collapsed' => $collapsed,
        );

        // Get init data
        $field_init_data = wpcf_fields_type_action($type);

        // See if field inherits some other
        $inherited_field_data = false;
        if (isset($field_init_data['inherited_field_type'])) {
            $inherited_field_data = wpcf_fields_type_action($field_init_data['inherited_field_type']);
        }

        // If insert form callback is not provided, use generic form data
        if (function_exists('wpcf_fields_' . $type . '_insert_form')) {
            $form_field = call_user_func('wpcf_fields_' . $type
                    . '_insert_form', $form_data,
                    'wpcf[fields]['
                    . $id . ']');
        } else {
            $form_field['name'] = array(
                '#type' => 'textfield',
                '#title' => __('Name of custom field', 'wpcf'),
                '#description' => __('Under this name field will be stored in DB (sanitized)',
                        'wpcf'),
                '#name' => 'name',
                '#attributes' => array('class' => 'wpcf-forms-set-legend'),
                '#validate' => array('required' => array('value' => true)),
            );
            $form_field['description'] = array(
                '#type' => 'textarea',
                '#title' => __('Description', 'wpcf'),
                '#description' => __('Text that describes function to user',
                        'wpcf'),
                '#name' => 'description',
                '#attributes' => array('rows' => 5, 'cols' => 1),
            );
        }

        // Process all form fields
        foreach ($form_field as $k => $field) {
            $form['wpcf-' . $id][$k] = $field;
            // Check if nested
            if (isset($field['#name']) && strpos($field['#name'], '[') === false) {
                $form['wpcf-' . $id][$k]['#name'] = 'wpcf[fields]['
                        . $id . '][' . $field['#name'] . ']';
            } else if (isset($field['#name'])) {
                $form['wpcf-' . $id][$k]['#name'] = 'wpcf[fields]['
                        . $id . ']' . $field['#name'];
            }
            if (!isset($field['#id'])) {
                $form['wpcf-' . $id][$k]['#id'] = $type . '-'
                        . $field['#type'] . '-' . rand();
            }
            if (isset($field['#name']) && isset($form_data[$field['#name']])) {
                $form['wpcf-'
                        . $id][$k]['#value'] = $form_data[$field['#name']];
                // @todo Added because of e.g. checkbox. Check if can be better.
                $form['wpcf-'
                        . $id][$k]['#default_value'] = $form_data[$field['#name']];
                // Check if it's in 'data'
            } else if (isset($field['#name']) && isset($form_data['data'][$field['#name']])) {
                $form['wpcf-'
                        . $id][$k]['#value'] = $form_data['data'][$field['#name']];
                // @todo Added because of e.g. checkbox. Check if can be better.
                $form['wpcf-'
                        . $id][$k]['#default_value'] = $form_data['data'][$field['#name']];
            }
        }

        // Set type
        $form['wpcf-' . $id]['type'] = array(
            '#type' => 'hidden',
            '#name' => 'wpcf[fields][' . $id . '][type]',
            '#value' => $type,
            '#id' => $id . '-type',
        );

        // Add validation box
        $form['wpcf-' . $id]['validate'] = wpcf_admin_fields_form_validation('wpcf[fields]['
                . $id . '][validate]', call_user_func('wpcf_fields_' . $type),
                $form_data);

        return $form;
    }
    return false;
}

/**
 * Adds validation box.
 * 
 * @param type $name
 * @param string $field
 * @param type $form_data
 * @return type 
 */
function wpcf_admin_fields_form_validation($name, $field, $form_data = array()) {
    $form = array(
        '#type' => 'fieldset',
        '#title' => __('Validation', 'wpcf'),
        '#description' => __('Require validation', 'wpcf'),
        '#name' => 'fieldset_random',
        '#collapsible' => false,
        '#collapsed' => false,
    );
    if (isset($field['validate'])) {

        // Process methods
        foreach ($field['validate'] as $method) {

            // Get method form data
            if (Wpcf_Validate::canValidate($method)
                    && Wpcf_Validate::hasForm($method)) {

                $field['#name'] = $name . '[' . $method . ']';
                $form_validate = call_user_func_array(
                        array('Wpcf_Validate', $method . '_form'),
                        array(
                    $field,
                    isset($form_data['data']['validate'][$method]) ? $form_data['data']['validate'][$method] : array()
                        )
                );

                // Set unique IDs
                foreach ($form_validate as $key => $element) {
                    if (isset($element['#type'])) {
                        $form_validate[$key]['#id'] = $element['#type'] . '-'
                                . mt_rand();
                    }
                }

                // Join
                $form = $form + $form_validate;
            }
        }
    }
    return $form;
}

/**
 * Adds JS validation script.
 */
function wpcf_admin_fields_form_js_validation() {
    wpcf_form_render_js_validation();
}

/**
 * Saves open fieldsets.
 * 
 * @param type $action
 * @param type $fieldset
 * @param type $group_id 
 */
function wpcf_admin_fields_form_save_open_fieldset($action, $fieldset,
        $group_id = false) {
    $data = get_user_meta(get_current_user_id(), 'wpcf-group-form-toggle',
            true);
    if ($group_id && $action == 'open') {
        $data[intval($group_id)][$fieldset] = 1;
    } else if ($group_id && $action == 'close') {
        unset($data[intval($group_id)][$fieldset]);
    } else if ($action == 'open') {
        $data[-1][$fieldset] = 1;
    } else if ($action == 'close') {
        unset($data[-1][$fieldset]);
    }
    update_user_meta(get_current_user_id(), 'wpcf-group-form-toggle', $data);
}

/**
 * Saves open fieldsets.
 * 
 * @param type $action
 * @param type $fieldset
 * @param type $group_id 
 */
function wpcf_admin_fields_form_fieldset_is_collapsed($fieldset) {
    if (isset($_GET['group_id'])) {
        $group_id = intval($_GET['group_id']);
    } else {
        $group_id = -1;
    }
    $data = get_user_meta(get_current_user_id(), 'wpcf-group-form-toggle',
            true);
    if (!isset($data[$group_id])) {
        return true;
    }
    return array_key_exists($fieldset, $data[$group_id]) ? false : true;
}