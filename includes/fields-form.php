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
        $post = get_post($_POST['group-id']);
        if (empty($post) || $post->post_type != 'wp-types-group') {
            $form->triggerError();
            wpcf_admin_message(sprintf(__("Wrong group ID %d", 'wpcf'),
                            intval($_POST['group-id'])), 'error');
            return false;
        }
        $group_id = $post->ID;
    } else {
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
            // WPML
            if (function_exists('wpml_cf_translation_preferences_store')) {
                $wpml_save_cf = wpml_cf_translation_preferences_store($key,
                        WPCF_META_PREFIX . $slug);
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
    wpcf_admin_add_js_settings('wpcf_nonce_toggle_group', '\'' . wp_create_nonce('group_form_collapsed') . '\'');
    wpcf_admin_add_js_settings('wpcf_nonce_toggle_fieldset', '\'' . wp_create_nonce('form_fieldset_toggle') . '\'');
    $default = array();

    // If it's update, get data
    $update = false;
    if (isset($_GET['group_id'])) {
        $update = wpcf_admin_fields_get_group($_GET['group_id']);
        if (empty($update)) {
            $update = false;
            wpcf_admin_message(sprintf(__("Group with ID %d do not exist",
                                    'wpcf'), intval($_GET['group_id'])));
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
                        . '&amp;field=' . basename($filename, '.php'))
                . '&amp;_wpnonce=' . wp_create_nonce('fields_insert') . '" '
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
            '#id' => 'wpcf-form-groups-user-fields',
        );
        foreach ($fields as $key => $field) {
            $form['fields-existing'][$key] = array(
                '#type' => 'markup',
                '#markup' => '<a href="' . admin_url('admin-ajax.php'
                        . '?action=wpcf_ajax'
                        . '&amp;wpcf_action=fields_insert_existing'
                        . '&amp;field=' . $field['id']) . '&amp;_wpnonce='
                . wp_create_nonce('fields_insert_existing') . '" '
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
//        '#title' => __('Group title', 'wpcf'),
        '#value' => $update ? $update['name'] : __('Enter group title', 'wpcf'),
        '#inline' => true,
        '#attributes' => array('style' => 'width:100%;margin-bottom:10px;'),
        '#validate' => array(
            'required' => array(
                'value' => true,
            ),
        )
    );
    if (!$update) {
        $form['title']['#attributes']['onfocus'] = 'if (jQuery(this).val() == \'' . __('Enter group title',
                        'wpcf') . '\') { jQuery(this).val(\'\'); }';
        $form['title']['#attributes']['onblur'] = 'if (jQuery(this).val() == \'\') { jQuery(this).val(\'' . __('Enter group title',
                        'wpcf') . '\') }';
    }
    $form['description'] = array(
        '#type' => 'textarea',
        '#name' => 'wpcf[group][description]',
        '#value' => $update ? $update['description'] : __('Enter a description for this group',
                        'wpcf'),
    );
    if (!$update) {
        $form['description']['#attributes']['onfocus'] = 'if (jQuery(this).val() == \''
                . __('Enter a description for this group', 'wpcf') . '\') { jQuery(this).val(\'\'); }';
        $form['description']['#attributes']['onblur'] = 'if (jQuery(this).val() == \'\') { jQuery(this).val(\''
                . __('Enter a description for this group', 'wpcf') . '\') }';
    }

    // Support post types and taxonomies

    $post_types = get_post_types('', 'objects');
    $options = array();
    $post_types_currently_supported = array();
    $form_types = array();

    foreach ($post_types as $post_type_slug => $post_type) {
        if (in_array($post_type_slug,
                        array('attachment', 'revision', 'nav_menu_item',
                    'view', 'view-template'))
                || !$post_type->show_ui) {
            continue;
        }
        $options[$post_type_slug]['#name'] = 'wpcf[group][supports][' . $post_type_slug . ']';
        $options[$post_type_slug]['#title'] = $post_type->label;
        $options[$post_type_slug]['#default_value'] = ($update && !empty($update['post_types']) && in_array($post_type_slug,
                        $update['post_types'])) ? 1 : 0;
        $options[$post_type_slug]['#value'] = $post_type_slug;
        $options[$post_type_slug]['#inline'] = TRUE;
        $options[$post_type_slug]['#suffix'] = '<br />';
        $options[$post_type_slug]['#id'] = 'wpcf-form-groups-support-post-type-' . $post_type_slug;
        $options[$post_type_slug]['#attributes'] = array('class' => 'wpcf-form-groups-support-post-type');
        if ($update && !empty($update['post_types']) && in_array($post_type_slug,
                        $update['post_types'])) {
            $post_types_currently_supported[] = $post_type->label;
        }
    }

    if (empty($post_types_currently_supported)) {
        $post_types_currently_supported[] = __('Displayed on all content types',
                'wpcf');
    }
    
    $post_types_no_currently_supported_txt = __('Post Types:', 'wpcf') . ' '
            . __('Displayed on all content types', 'wpcf');

    $form_types = array(
        '#type' => 'checkboxes',
        '#options' => $options,
        '#name' => 'wpcf[group][supports]',
        '#inline' => true,
        '#before' => '<span id="wpcf-group-form-update-types-ajax-response"'
        . ' style="font-style:italic;font-weight:bold;display:inline-block;">'
        . __('Post Types:', 'wpcf') . ' ' . implode(', ', $post_types_currently_supported) . '</span>'
        . '&nbsp;&nbsp;<a href="javascript:void(0);" style="line-height: 30px;"'
        . ' class="button-secondary" onclick="'
        . 'window.wpcfPostTypesText = new Array(); window.wpcfFormGroupsSupportPostTypesState = new Array(); '
        . 'jQuery(this).next().slideToggle()'
        . '.find(\'.checkbox\').each(function(index){'
        . 'if (jQuery(this).is(\':checked\')) { '
        . 'window.wpcfPostTypesText.push(jQuery(this).next().html()); '
        . 'window.wpcfFormGroupsSupportPostTypesState.push(jQuery(this).attr(\'id\'));'
        . '}'
        . '});'
        . ' jQuery(this).css(\'visibility\', \'hidden\');">'
        . __('Edit') . '</a>' . '<div class="hidden">',
        '#after' => '<a href="javascript:void(0);" style="line-height: 35px;" '
        . 'class="button-primary wpcf-groups-form-ajax-update-post-types-ok"'
        . ' onclick="window.wpcfPostTypesText = new Array(); window.wpcfFormGroupsSupportPostTypesState = new Array(); '
        . 'jQuery(this).parent().slideUp().find(\'.checkbox\').each(function(index){'
        . 'if (jQuery(this).is(\':checked\')) { '
        . 'window.wpcfPostTypesText.push(jQuery(this).next().html()); '
        . 'window.wpcfFormGroupsSupportPostTypesState.push(jQuery(this).attr(\'id\'));'
        . '}'
        . '});'
        . 'if (window.wpcfPostTypesText.length < 1) { '
        . 'jQuery(\'#wpcf-group-form-update-types-ajax-response\').html(\''
        . $post_types_no_currently_supported_txt . '\'); '
        . '} else { jQuery(\'#wpcf-group-form-update-types-ajax-response\').html(\''
        . __('Post Types:', 'wpcf') . ' \'+wpcfPostTypesText.join(\', \'));}'
        . ' jQuery(this).parent().parent().children(\'a\').css(\'visibility\', \'visible\');'
        . '">'
        . __('OK') . '</a>&nbsp;'
        . '<a href="javascript:void(0);" style="line-height: 35px;" '
        . 'class="button-secondary wpcf-groups-form-ajax-update-post-types-cancel"'
        . ' onclick="jQuery(this).parent().slideUp().find(\'input\').removeAttr(\'checked\');'
        . 'if (window.wpcfFormGroupsSupportPostTypesState.length > 0) { '
        . 'for (var element in window.wpcfFormGroupsSupportPostTypesState) { '
        . 'jQuery(\'#\'+window.wpcfFormGroupsSupportPostTypesState[element]).attr(\'checked\', \'checked\'); }}'
        . 'jQuery(\'#wpcf-group-form-update-types-ajax-response\').html(\''
        . __('Post Types:', 'wpcf'). ' \'+window.wpcfPostTypesText.join(\', \'));'
        . ' jQuery(this).parent().parent().children(\'a\').css(\'visibility\', \'visible\');'
        . '">'
        . __('Cancel') . '</a>' . '</div></div><br />',
    );

    $taxonomies = get_taxonomies('', 'objects');
    $options = array();
    $tax_currently_supported = array();
    $form_tax = array();
    $form_tax_single = array();

    foreach ($taxonomies as $category_slug => $category) {
        if ($category_slug == 'nav_menu' || $category_slug == 'link_category'
                || $category_slug == 'post_format') {
            continue;
        }
        $terms = get_terms($category_slug, array('hide_empty' => false));
        if (!empty($terms)) {
            $options = array();
            $add_title = '<div class="taxonomy-title">' . $category->labels->name . '</div>';
//            $title = $category->labels->name . ': ';
            $title = '';
            foreach ($terms as $term) {
                $checked = 0;
                if ($update && !empty($update['taxonomies']) && array_key_exists($category_slug,
                                $update['taxonomies'])) {
                    if (array_key_exists($term->term_id,
                                    $update['taxonomies'][$category_slug])) {
                        $checked = 1;
                        $tax_currently_supported[$category_slug] = $title . $term->name;
                        $title = '';
                    }
                }
                $options[$term->term_id]['#name'] = 'wpcf[group][taxonomies]['
                        . $category_slug . '][' . $term->term_id . ']';
                $options[$term->term_id]['#title'] = $term->name;
                $options[$term->term_id]['#default_value'] = $checked;
                $options[$term->term_id]['#value'] = $term->term_id;
                $options[$term->term_id]['#inline'] = true;
                $options[$term->term_id]['#prefix'] = $add_title;
                $options[$term->term_id]['#suffix'] = '<br />';
                $options[$term->term_id]['#id'] = 'wpcf-form-groups-support-tax-' . $term->term_id;
                $options[$term->term_id]['#attributes'] = array('class' => 'wpcf-form-groups-support-tax');
                $add_title = '';
            }
            if (empty($tax_currently_supported)) {
                $tax_currently_supported[] = __('No terms associated',
                        'wpcf');
            }
            $form_tax_single['taxonomies-' . $category_slug] = array(
                '#type' => 'checkboxes',
                '#options' => $options,
                '#name' => 'wpcf[group][taxonomies][' . $category_slug . ']',
                '#suffix' => '<br />',
                '#inline' => true,
            );
        }
    }
    
    $tax_no_currently_supported_txt = __('Terms:', 'wpcf') . ' ' . __('No terms associated',
                        'wpcf');

    $form_tax['taxonomies-open'] = array(
        '#type' => 'markup',
        '#markup' => '<span id="wpcf-group-form-update-tax-ajax-response" '
        . 'style="font-style:italic;font-weight:bold;display:inline-block;">'
        . __('Terms:', 'wpcf') . ' ' . implode(', ', $tax_currently_supported) . '</span>'
        . '&nbsp;&nbsp;<a href="javascript:void(0);" style="line-height: 30px;" '
        . 'class="button-secondary" onclick="'
        . 'window.wpcfTaxText = new Array(); window.wpcfFormGroupsSupportTaxState = new Array(); '
        . 'jQuery(this).next().slideToggle()'
        . '.find(\'.checkbox\').each(function(index){'
        . 'if (jQuery(this).is(\':checked\')) { '
        . 'window.wpcfTaxText.push(jQuery(this).next().html()); '
        . 'window.wpcfFormGroupsSupportTaxState.push(jQuery(this).attr(\'id\'));'
        . '}'
        . '});'
        . ' jQuery(this).css(\'visibility\', \'hidden\');">'
        . __('Edit') . '</a>' . '<div class="hidden">',
    );

    $form_tax = $form_tax + $form_tax_single;

    $form_tax['taxonomies-close'] = array(
        '#type' => 'markup',
        '#markup' => '<a href="javascript:void(0);" style="line-height: 35px;" '
        . 'class="button-primary wpcf-groups-form-ajax-update-tax-ok"'
        . ' onclick="window.wpcfTaxText = new Array(); window.wpcfFormGroupsSupportTaxState = new Array(); '
        . 'jQuery(this).parent().slideUp().find(\'.checkbox\').each(function(index){'
        . 'if (jQuery(this).is(\':checked\')) { '
        . 'window.wpcfTaxText.push(jQuery(this).next().html()); '
        . 'window.wpcfFormGroupsSupportTaxState.push(jQuery(this).attr(\'id\'));'
        . '}'
        . '});'
        . 'if (window.wpcfTaxText.length < 1) { '
        . 'jQuery(\'#wpcf-group-form-update-tax-ajax-response\').html(\''
        . $tax_no_currently_supported_txt . '\'); '
        . '} else { jQuery(\'#wpcf-group-form-update-tax-ajax-response\').html(\''
        . __('Terms:', 'wpcf') . ' \'+wpcfTaxText.join(\', \'));'
        . '}'
        . ' jQuery(this).parent().parent().children(\'a\').css(\'visibility\', \'visible\');'
        . '">'
        . __('OK') . '</a>&nbsp;'
        . '<a href="javascript:void(0);" style="line-height: 35px;" '
        . 'class="button-secondary wpcf-groups-form-ajax-update-tax-cancel"'
        . ' onclick="jQuery(this).parent().slideUp().find(\'input\').removeAttr(\'checked\');'
        . 'if (window.wpcfFormGroupsSupportTaxState.length > 0) { '
        . 'for (var element in window.wpcfFormGroupsSupportTaxState) { '
        . 'jQuery(\'#\'+window.wpcfFormGroupsSupportTaxState[element]).attr(\'checked\', \'checked\'); }}'
        . 'jQuery(\'#wpcf-group-form-update-tax-ajax-response\').html(\'' . __('Terms:', 'wpcf')
        . ' \'+window.wpcfTaxText.join(\', \'));'
        . ' jQuery(this).parent().parent().children(\'a\').css(\'visibility\', \'visible\');'
        . '">'
        . __('Cancel') . '</a>' . '</div><br />',
    );

    $form['supports-table-open'] = array(
        '#type' => 'markup',
        '#markup' => '<table class="widefat"><thead><tr><th>'
        . __('Where to display this group', 'wpcf')
        . '</th></tr></thead><tbody><tr><td>'
        . __('Each custom fields group can display on different content types or different taxonomy.',
                'wpcf') . '<br />',
    );

    $form['types'] = $form_types;
    $form = $form + $form_tax;

    $form['supports-table-close'] = array(
        '#type' => 'markup',
        '#markup' => '</td></tr></tbody></table><br />',
    );

    // Group fields

    $form['fields_title'] = array(
        '#type' => 'markup',
        '#markup' => '<h2>' . __('Fields', 'wpcf') . '</h2>',
    );
    $show_under_title = true;

    $form['ajax-response-open'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="wpcf-fields-sortable" class="ui-sortable">',
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
            $show_under_title = false;
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
        $show_under_title = false;
    }
    $form['ajax-response-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>' . '<div id="wpcf-ajax-response"></div>',
    );

    if ($show_under_title) {
        $form['fields_title']['#markup'] = $form['fields_title']['#markup']
                . '<div id="wpcf-fields-under-title">'
                . __('There are no fields in this group. To add a field, click on the field buttons at the right.',
                        'wpcf')
                . '</div>';
    }

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

    // Add JS settings
    wpcf_admin_add_js_settings('wpcfFormUniqueValuesCheckText',
            '\'' . __('Warning: same values selected', 'wpcf') . '\'');

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
    $field = wpcf_admin_fields_get_field($_GET['field']);
    if (!empty($field)) {
        echo wpcf_fields_get_field_form($field['type'], $field);
    } else {
        echo '<div>' . __("Requested field don't exist", 'wpcf') . '</div>';
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
        $remove_link = isset($form_data['group_id']) ? admin_url('admin-ajax.php?'
                        . 'wpcf_ajax_callback=wpcfFieldsFormDeleteElement&amp;wpcf_warning='
                        . __('Are you sure?', 'wpcf')
                        . '&amp;action=wpcf_ajax&amp;wpcf_action=remove_field_from_group'
                        . '&amp;group_id=' . intval($form_data['group_id'])
                        . '&amp;field_id=' . $form_data['id'])
                . '&amp;_wpnonce=' . wp_create_nonce('remove_field_from_group') : admin_url('admin-ajax.php?'
                        . 'wpcf_ajax_callback=wpcfFieldsFormDeleteElement&amp;wpcf_warning='
                        . __('Are you sure?', 'wpcf')
                        . '&amp;action=wpcf_ajax&amp;wpcf_action=remove_field_from_group')
                . '&amp;_wpnonce=' . wp_create_nonce('remove_field_from_group');

        // Set move button
        $form['wpcf-' . $id . '-control'] = array(
            '#type' => 'markup',
            '#markup' => '<img src="' . WPCF_RES_RELPATH
            . '/images/move.png" class="wpcf-fields-form-move-field" alt="'
            . __('Move this field', 'wpcf') . '" /><a href="'
            . $remove_link . '" '
            . 'class="wpcf-form-fields-delete wpcf-ajax-link">'
            . '<img src="' . WPCF_RES_RELPATH . '/images/delete-2.png" alt="'
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
        }

        // Force name and description
        $form_field['name'] = array(
            '#type' => 'textfield',
            '#name' => 'name',
            '#attributes' => array('class' => 'wpcf-forms-set-legend', 'style' => 'width:100%;margin:10px 0 10px 0;'),
            '#validate' => array('required' => array('value' => true)),
            '#inline' => true,
            '#value' => __('Enter field name', 'wpcf'),
        );
        if (empty($form_data['name'])) {
            $form_field['name']['#attributes']['onclick'] = 'if (jQuery(this).val() == \''
                    . __('Enter field name', 'wpcf') . '\') { jQuery(this).val(\'\'); }';
            $form_field['name']['#attributes']['onblur'] = 'if (jQuery(this).val() == \'\') { jQuery(this).val(\''
                    . __('Enter field name', 'wpcf') . '\') }';
        }
        $form_field['description'] = array(
            '#type' => 'textarea',
            '#name' => 'description',
            '#attributes' => array('rows' => 5, 'cols' => 1, 'style' => 'margin:0 0 10px 0;'),
            '#inline' => true,
            '#value' => __('Describe this field', 'wpcf'),
        );
        if (empty($form_data['description'])) {
            $form_field['description']['#attributes']['onfocus'] = 'if (jQuery(this).val() == \''
                    . __('Describe this field', 'wpcf') . '\') { jQuery(this).val(\'\'); }';
            $form_field['description']['#attributes']['onblur'] = 'if (jQuery(this).val() == \'\') { jQuery(this).val(\''
                    . __('Describe this field', 'wpcf') . '\') }';
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
        $form_validate = wpcf_admin_fields_form_validation('wpcf[fields]['
                . $id . '][validate]', call_user_func('wpcf_fields_' . $type),
                $form_data);
        foreach ($form_validate as $k => $v) {
            $form['wpcf-' . $id][$k] = $v;
        }

        // WPML Translation Preferences
        if (function_exists('wpml_cf_translation_preferences')) {
            $custom_field = !empty($form_data['slug']) ? WPCF_META_PREFIX . $form_data['slug'] : false;
            $translatable = array('textfield', 'textarea');
            $action = in_array($type, $translatable) ? 'translate' : 'copy';
            $form['wpcf-' . $id]['wpml-preferences'] = array(
                '#type' => 'fieldset',
                '#title' => __('Translation preferences', 'wpcf'),
                '#collapsed' => true,
            );
            $form['wpcf-' . $id]['wpml-preferences']['form'] = array(
                '#type' => 'markup',
                '#markup' => wpml_cf_translation_preferences($id, $custom_field,
                        'wpcf', false, $action),
            );
        }

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
    $form = array();
    $form['validate-table-open'] = array(
        '#type' => 'markup',
        '#markup' => '<table class="wpcf-fields-form-validate-table" '
        . 'cellspacing="0" cellpadding="0"><thead><tr><td>'
        . __('Validation', 'wpcf') . '</td><td>' . __('Error message', 'wpcf')
        . '</td></tr></thead><tbody>',
    );
    if (isset($field['validate'])) {

        // Process methods
        foreach ($field['validate'] as $k => $method) {

            // Set additional method data
            if (is_array($method)) {
                $form_data['data']['validate'][$k]['method_data'] = $method;
                $method = $k;
            }

            if (!Wpcf_Validate::canValidate($method)
                    || !Wpcf_Validate::hasForm($method)) {
                continue;
            }

            $form['validate-tr-' . $method] = array(
                '#type' => 'markup',
                '#markup' => '<tr><td>',
            );

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
                    if (isset($element['#name']) && strpos($element['#name'],
                                    '[message]') !== FALSE) {
                        $before = '</td><td>';
                        $after = '</td></tr>';
                        $form_validate[$key]['#before'] = isset($element['#before']) ? $element['#before'] . $before : $before;
                        $form_validate[$key]['#after'] = isset($element['#after']) ? $element['#after'] . $after : $after;
                    }
                }

                // Join
                $form = $form + $form_validate;
            }
        }
    }
    $form['validate-table-close'] = array(
        '#type' => 'markup',
        '#markup' => '</tbody></table>',
    );
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
    $data = get_user_meta(get_current_user_id(), 'wpcf-group-form-toggle', true);
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
    $data = get_user_meta(get_current_user_id(), 'wpcf-group-form-toggle', true);
    if (!isset($data[$group_id])) {
        return true;
    }
    return array_key_exists($fieldset, $data[$group_id]) ? false : true;
}