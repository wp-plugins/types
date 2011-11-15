<?php
/**
 * Types-field: Radio
 *
 * Description: Displays a radio selection to the user.
 *
 * Rendering: The option title will be rendered or if set - specific value.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My checkbox: $value)
 *
 * Example usage:
 * With a short code use [types field="my-radios"]
 * In a theme use types_render_field("my-radios", $parameters)
 * 
 */

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_radio() {
    return array(
        'id' => 'wpcf-radio',
        'title' => __('Radio', 'wpcf'),
        'description' => __('Radio', 'wpcf'),
        'validate' => array('required'),
    );
}

/**
 * Form data for group form.
 * 
 * @return type 
 */
function wpcf_fields_radio_insert_form($form_data = array(), $parent_name = '') {
    $id = 'wpcf-fields-radio-' . mt_rand();
    $form['name'] = array(
        '#type' => 'textfield',
        '#title' => __('Name of custom field', 'wpcf'),
        '#description' => __('Under this name field will be stored in DB (sanitized)',
                'wpcf'),
        '#name' => 'name',
        '#attributes' => array('class' => 'wpcf-forms-set-legend'),
        '#validate' => array('required' => array('value' => true)),
    );
    $form['description'] = array(
        '#type' => 'textarea',
        '#title' => __('Description', 'wpcf'),
        '#description' => __('Text that describes function to user', 'wpcf'),
        '#name' => 'description',
        '#attributes' => array('rows' => 5, 'cols' => 1),
    );
    $form['options-markup-open'] = array(
        '#type' => 'markup',
        '#markup' => '<strong>' . __('Options', 'wpcf')
        . '</strong><br /><br /><div id="' . $id . '-sortable"'
        . ' class="wpcf-fields-radio-sortable wpcf-compare-unique-value-wrapper">',
    );

    $existing_options = array();
    if (!empty($form_data['data']['options'])) {
        foreach ($form_data['data']['options'] as $option_key => $option) {
            if ($option_key == 'default') {
                continue;
            }
            $option['key'] = $option_key;
            $option['default'] = isset($form_data['data']['options']['default']) ? $form_data['data']['options']['default'] : null;
            $form_option = wpcf_fields_radio_get_option('', $option);
            $existing_options[array_shift($form_option)] = $option;
            $form = $form + $form_option;
        }
    } else {
        $form_option = wpcf_fields_radio_get_option();
        $existing_options[array_shift($form_option)] = array();
        $form = $form + $form_option;
    }

    $form['options-response-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );

    $form['options-no-default'] = array(
        '#type' => 'radio',
        '#inline' => true,
        '#title' => __('No Default', 'wpcf'),
        '#name' => '[options][default]',
        '#value' => 'no-default',
        '#default_value' => isset($form_data['data']['options']['default']) ? $form_data['data']['options']['default'] : null,
    );

    if (!empty($form_data['data']['options'])) {
        $count = count($form_data['data']['options']);
    } else {
        $count = 1;
    }

    $form['options-markup-close'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="'
        . $id . '-add-option"></div><br /><a href="'
        . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=add_radio_option&amp;_wpnonce='
                . wp_create_nonce('add_radio_option') .'&amp;wpcf_ajax_update_add='
                . $id . '-sortable&amp;parent_name=' . urlencode($parent_name)
                . '&amp;count=' . $count)
        . '" onclick="wpcfFieldsFormCountOptions(jQuery(this));"'
        . ' class="button-secondary wpcf-ajax-link">'
        . __('Add option', 'wpcf') . '</a>',
    );
    $form['options-close'] = array(
        '#type' => 'markup',
        '#markup' => '<br /><br />',
    );
    $form['display'] = array(
        '#type' => 'radios',
        '#default_value' => 'db',
        '#name' => 'display',
        '#options' => array(
            'display_from_db' => array(
                '#title' => __('Display the value of this field from the database',
                        'wpcf'),
                '#name' => 'display',
                '#value' => 'db',
                '#inline' => true,
                '#after' => '<br />'
            ),
            'display_values' => array(
                '#title' => __('Show one of these values:', 'wpcf'),
                '#name' => 'display',
                '#value' => 'value',
                '#inline' => true,
            ),
        ),
        '#inline' => true,
    );
    $form['display-open'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="wpcf-form-groups-radio-ajax-response-'
        . $id . '-sortable" style="margin: 10px 0 20px 0;">',
    );
    if (!empty($existing_options)) {
        foreach ($existing_options as $option_id => $option_form_data) {
            $form_option = wpcf_fields_radio_get_option_alt_text($option_id, '',
                    $option_form_data);
            $form = $form + $form_option;
        }
    }
    $form['display-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );
    return $form;
}

/**
 * Returns form data for radio.
 * 
 * @param type $parent_name Used for AJAX adding options
 * @param type $form_data
 * @return type 
 */
function wpcf_fields_radio_get_option($parent_name = '', $form_data = array()) {
    $id = isset($form_data['key']) ? $form_data['key'] : 'wpcf-fields-radio-option-' . mt_rand();
    $form = array();
    $value = isset($_GET['count']) ? __('Option title', 'wpcf') . ' ' . $_GET['count'] : __('Option title',
                    'wpcf') . ' 1';
    $value = isset($form_data['title']) ? $form_data['title'] : $value;
    $form[$id . '-id'] = $id;
    $form[$id . '-title'] = array(
        '#type' => 'textfield',
        '#id' => $id . '-title',
        '#name' => $parent_name . '[options][' . $id . '][title]',
        '#value' => $value,
        '#inline' => true,
        '#attributes' => array(
            'style' => 'width:80px;',
            'class' => 'wpcf-form-groups-radio-update-title-display-value',
        ),
        '#before' => '<div class="wpcf-fields-radio-draggable"><img src="'
        . WPCF_RES_RELPATH
        . '/images/move.png" class="wpcf-fields-form-radio-move-field" alt="'
        . __('Move this option', 'wpcf') . '" /><img src="'
        . WPCF_RES_RELPATH . '/images/delete.png"'
        . ' class="wpcf-fields-radio-delete-option wpcf-pointer"'
        . ' onclick="if (confirm(\'' . __('Are you sure?', 'wpcf')
        . '\')) { jQuery(this).parent().fadeOut(function(){jQuery(this).remove(); '
        . '}); '
        . 'jQuery(\'#\'+jQuery(this).parent().find(\'input\').attr(\'id\')+\''
        . '-display-value-wrapper\').fadeOut(function(){jQuery(this).remove();}); }"'
        . 'alt="' . __('Delete this option', 'wpcf') . '" />',
    );
    $value = isset($_GET['count']) ? $_GET['count'] : 1;
    $value = isset($form_data['value']) ? $form_data['value'] : $value;
    $form[$id . '-value'] = array(
        '#type' => 'textfield',
        '#id' => $id . '-value',
        '#name' => $parent_name . '[options][' . $id . '][value]',
        '#value' => $value,
        '#inline' => true,
        '#attributes' => array(
            'style' => 'width:80px;',
            'class' => 'wpcf-compare-unique-value',
        ),
    );
    $form[$id . '-default'] = array(
        '#type' => 'radio',
        '#id' => $id . '-default',
        '#inline' => true,
        '#title' => __('Default', 'wpcf'),
        '#after' => '</div>',
        '#name' => $parent_name . '[options][default]',
        '#value' => $id,
        '#default_value' => isset($form_data['default']) ? $form_data['default'] : '',
    );
    return $form;
}

/**
 * Returns form data for radio.
 * 
 * @param type $parent_name Used for AJAX adding options
 * @param type $form_data
 * @return type 
 */
function wpcf_fields_radio_get_option_alt_text($id, $parent_name = '',
        $form_data = array()) {
    $form = array();
    $title = isset($_GET['count']) ? __('Option title', 'wpcf') . ' ' . $_GET['count'] : __('Option title',
                    'wpcf') . ' 1';
    $title = isset($form_data['title']) ? $form_data['title'] : $title;
    $value = isset($_GET['count']) ? $_GET['count'] : 1;
    $value = isset($form_data['value']) ? $form_data['value'] : $value;
    $value = isset($form_data['display_value']) ? $form_data['display_value'] : $value;
    $form[$id . '-display-value'] = array(
        '#type' => 'textfield',
        '#id' => $id . '-title-display-value',
        '#name' => $parent_name . '[options][' . $id . '][display_value]',
        '#title' => $title,
        '#value' => $value,
        '#inline' => true,
        '#before' => '<div id="' . $id . '-title-display-value-wrapper">',
        '#after' => '</div>',
    );
    return $form;
}

/**
 * Form data for post edit page.
 * 
 * @param type $field 
 */
function wpcf_fields_radio_meta_box_form($field) {
    $options = array();
    $default_value = null;

    if (!empty($field['data']['options'])) {
        foreach ($field['data']['options'] as $option_key => $option) {
            // Skip default value record
            if ($option_key == 'default') {
                continue;
            }
            // Set default value
            if (!empty($field['data']['options']['default'])
                    && $option_key == $field['data']['options']['default']) {
                $default_value = $option['value'];
            }
            $options[$option['title']] = array(
                '#value' => $option['value'],
                '#title' => wpcf_translate('field ' . $field['id'] . ' option '
                        . $option_key . ' title', $option['title']),
            );
        }
    }

    if (!empty($field['value'])) {
        $default_value = $field['value'];
    }

    return array(
        '#type' => 'radios',
        '#default_value' => $default_value,
        '#options' => $options,
    );
}

/**
 * Editor callback form.
 */
function wpcf_fields_radio_editor_callback() {
    wpcf_admin_ajax_head('Insert checkbox', 'wpcf');
    $field = wpcf_admin_fields_get_field($_GET['field_id']);
    if (empty($field)) {
        echo '<div class="message error"><p>' . __('Wrong field specified',
                'wpcf') . '</p></div>';
        wpcf_admin_ajax_footer();
        return '';
    }
    $form = array();
    $form['#form']['callback'] = 'wpcf_fields_radio_editor_submit';
    $form['display'] = array(
        '#type' => 'radios',
        '#default_value' => 'db',
        '#name' => 'display',
        '#options' => array(
            'display_from_db' => array(
                '#title' => __('Display the value of this field from the database',
                        'wpcf'),
                '#name' => 'display',
                '#value' => 'db',
                '#inline' => true,
                '#after' => '<br />'
            ),
            'display_values' => array(
                '#title' => __('Show one of these values:', 'wpcf'),
                '#name' => 'display',
                '#value' => 'value',
            ),
        ),
        '#inline' => true,
    );
    if (!empty($field['data']['options'])) {
        $form['table-open'] = array(
            '#type' => 'markup',
            '#markup' => '<table style="margin-top:20px;" cellpadding="0" cellspacing="8">',
        );
        foreach ($field['data']['options'] as $option_id => $option) {
            if ($option_id == 'default') {
                continue;
            }
            $value = isset($option['display_value']) ? $option['display_value'] : $option['value'];
            $form['display-value-' . $option_id] = array(
                '#type' => 'textfield',
                '#title' => $option['title'],
                '#name' => 'options[' . $option_id . ']',
                '#value' => $value,
                '#inline' => true,
                '#pattern' => '<tr><td style="text-align:right;"><LABEL></td><td><ELEMENT></td></tr>',
                '#attributes' => array('style' => 'width:200px;'),
            );
        }
        $form['table-close'] = array(
            '#type' => 'markup',
            '#markup' => '</table>',
        );
    }
    $form['submit'] = array(
        '#type' => 'markup',
        '#markup' => get_submit_button(),
    );
    $f = wpcf_form('wpcf-form', $form);

    echo '<form method="post" action="">';
    echo $f->renderForm();
    echo '</form>';
    wpcf_admin_ajax_footer();
}

/**
 * Editor callback form submit.
 */
function wpcf_fields_radio_editor_submit() {
    $add = '';
    if ($_POST['display'] == 'value' && !empty($_POST['options'])) {
        foreach ($_POST['options'] as $option_id => $value) {
            $add .= ' checked_' . md5($option_id) . '="' . base64_encode($value) . '"';
        }
    }
    $field = wpcf_admin_fields_get_field($_GET['field_id']);
    if (!empty($field)) {
        $shortcode = wpcf_fields_get_shortcode($field, $add);
        echo wpcf_admin_fields_popup_insert_shortcode_js($shortcode);
        die();
    }
}

/**
 * View function.
 * 
 * @param type $params 
 */
function wpcf_fields_radio_view($params) {
    if ($params['style'] == 'raw') {
        return '';
    }
    $field = wpcf_fields_get_field_by_slug($params['field']['slug']);
    $output = '';
    if (!empty($field['data']['options'])) {
        $field_value = $params['field_value'];
        foreach ($field['data']['options'] as $option_key => $option) {
            if (isset($option['value'])
                    && $option['value'] == $params['field_value']) {
                
                if (isset($params['checked_' . md5($option_key)])) {
                    $field_value = base64_decode($params['checked_' . md5($option_key)]);
                    break;
                }
                
                $field_value = wpcf_translate('field ' . $params['field']['id'] . ' option '
                        . $option_key . ' title', $option['title']);
                if (isset($params['field']['data']['display'])
                        && $params['field']['data']['display'] != 'db'
                        && !empty($option['display_value'])) {
                    $field_value = wpcf_translate('field ' . $params['field']['id'] . ' option '
                            . $option_key . ' display value',
                            $option['display_value']);
                }
            }
        }
        $field_value = wpcf_frontend_wrap_field_value($params['field'],
                $field_value, $params);
        $output = wpcf_frontend_wrap_field($params['field'], $field_value);
    }
    return $output;
}