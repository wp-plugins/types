<?php
/**
 * Types-field: Select
 *
 * Description: Displays a select box to the user.
 *
 * Rendering: The option title will be rendered or if set - specific value.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My checkbox: $value)
 *
 * Example usage:
 * With a short code use [types field="my-select"]
 * In a theme use types_render_field("my-select", $parameters)
 * 
 */

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_select() {
    return array(
        'id' => 'wpcf-select',
        'title' => __('Select', 'wpcf'),
        'description' => __('Select', 'wpcf'),
        'validate' => array('required'),
    );
}

/**
 * Form data for group form.
 * 
 * @return type 
 */
function wpcf_fields_select_insert_form($form_data = array(), $parent_name = '') {
    $id = 'wpcf-fields-select-' . mt_rand();
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
        . ' class="wpcf-fields-select-sortable wpcf-compare-unique-value-wrapper">',
    );
    if (!empty($form_data['data']['options'])) {
        foreach ($form_data['data']['options'] as $option_key => $option) {
            if ($option_key == 'default') {
                continue;
            }
            $option['key'] = $option_key;
            $option['default'] = isset($form_data['data']['options']['default']) ? $form_data['data']['options']['default'] : null;
            $form = $form + wpcf_fields_select_get_option('', $option);
        }
    } else {
        $form = $form + wpcf_fields_select_get_option();
    }

    if (!empty($form_data['data']['options'])) {
        $count = count($form_data['data']['options']);
    } else {
        $count = 1;
    }

    $form['options-markup-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div><div id="'
        . $id . '-add-option"></div><br /><a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=add_select_option&amp;_wpnonce='
                . wp_create_nonce('add_select_option') . '&amp;wpcf_ajax_update_add=' . $id . '-sortable&amp;parent_name=' . urlencode($parent_name)
                . '&amp;count=' . $count)
        . '" onclick="wpcfFieldsFormCountOptions(jQuery(this));"'
        . ' class="button-secondary wpcf-ajax-link">'
        . __('Add option', 'wpcf') . '</a>',
    );
    $form['options-close'] = array(
        '#type' => 'markup',
        '#markup' => '<br /><br />',
    );
    return $form;
}

function wpcf_fields_select_get_option($parent_name = '', $form_data = array()) {
    $id = isset($form_data['key']) ? $form_data['key'] : 'wpcf-fields-select-option-' . mt_rand();
    $form = array();
    $value = isset($_GET['count']) ? __('Option title', 'wpcf') . ' ' . $_GET['count'] : __('Option title',
                    'wpcf') . ' 1';
    $value = isset($form_data['title']) ? $form_data['title'] : $value;
    $form[$id . '-title'] = array(
        '#type' => 'textfield',
        '#name' => $parent_name . '[options][' . $id . '][title]',
        '#value' => $value,
        '#inline' => true,
        '#attributes' => array('style' => 'width:80px;'),
        '#before' => '<div class="wpcf-fields-select-draggable"><img src="'
        . WPCF_RES_RELPATH
        . '/images/move.png" class="wpcf-fields-form-select-move-field" alt="'
        . __('Move this option', 'wpcf') . '" /><img src="'
        . WPCF_RES_RELPATH . '/images/delete.png"'
        . ' class="wpcf-fields-select-delete-option wpcf-pointer"'
        . ' onclick="if (confirm(\'' . __('Are you sure?', 'wpcf')
        . '\')) { jQuery(this).parent().fadeOut(function(){jQuery(this).remove();}); }"'
        . 'alt="' . __('Delete this option', 'wpcf') . '" />',
    );
    $value = isset($_GET['count']) ? $_GET['count'] : 1;
    $value = isset($form_data['value']) ? $form_data['value'] : $value;
    $form[$id . '-value'] = array(
        '#type' => 'textfield',
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
        '#inline' => true,
        '#title' => __('Default', 'wpcf'),
        '#after' => '</div>',
        '#name' => $parent_name . '[options][default]',
        '#value' => $id,
        '#default_value' => isset($form_data['default']) ? $form_data['default'] : false,
    );
    return $form;
}

/**
 * Form data for post edit page.
 * 
 * @param type $field 
 */
function wpcf_fields_select_meta_box_form($field) {
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

    $element = array(
        '#type' => 'select',
        '#default_value' => $default_value,
        '#options' => $options,
    );

    return $element;
}

/**
 * View function.
 * 
 * @param type $params 
 */
function wpcf_fields_select_view($params) {
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
                $field_value = wpcf_translate('field ' . $params['field']['id'] . ' option '
                        . $option_key . ' title', $option['title']);
            }
        }
        $field_value = wpcf_frontend_wrap_field_value($params['field'],
                $field_value, $params);
        $output = wpcf_frontend_wrap_field($params['field'], $field_value,
                $params);
    }
    return $output;
}