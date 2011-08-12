<?php

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

    if (!empty($form_data['data']['options'])) {
        foreach ($form_data['data']['options'] as $option_key => $option) {
            if ($option_key == 'default') {
                continue;
            }
            $option['key'] = $option_key;
            $option['default'] = isset($form_data['data']['options']['default']) ? $form_data['data']['options']['default'] : null;
            $form = $form + wpcf_fields_radio_get_option('', $option);
        }
    } else {
        $form = $form + wpcf_fields_radio_get_option();
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
    
    $form['options-markup-close'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="'
        . $id . '-add-option"></div><br /><a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=add_radio_option&amp;wpcf_ajax_update_add=' . $id . '-sortable&amp;parent_name=' . urlencode($parent_name)) . '"'
        . ' class="button-secondary wpcf-ajax-link">'
        . __('Add option', 'wpcf') . '</a>',
    );
    
    $form['options-close'] = array(
        '#type' => 'markup',
        '#markup' => '<br /><br />',
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
    $value = isset($form_data['title']) ? $form_data['title'] : __('Option title',
                    'wpcf');
    $form[$id . '-title'] = array(
        '#type' => 'textfield',
        '#name' => $parent_name . '[options][' . $id . '][title]',
        '#value' => $value,
        '#inline' => true,
        '#attributes' => array('style' => 'width:80px;'),
        '#before' => '<div class="wpcf-fields-radio-draggable"><img src="'
        . WPCF_RES_RELPATH
        . '/images/move.png" class="wpcf-fields-form-radio-move-field" alt="'
        . __('Move this option', 'wpcf') . '" /><img src="'
        . WPCF_RES_RELPATH . '/images/delete.png"'
        . ' class="wpcf-fields-radio-delete-option wpcf-pointer"'
        . ' onclick="if (confirm(\'' . __('Are you sure?', 'wpcf')
        . '\')) { jQuery(this).parent().fadeOut(function(){jQuery(this).remove();}); }"'
        . 'alt="' . __('Delete this option', 'wpcf') . '" />',
    );
    $value = isset($form_data['value']) ? $form_data['value'] : __('Option value',
                    'wpcf');
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
        '#default_value' => isset($form_data['default']) ? $form_data['default'] : '',
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
            $options[$option['title']] = $option['value'];
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
        foreach ($field['data']['options'] as $option) {
            if (isset($option['value'])
                    && $option['value'] == $params['field_value']) {
                $field_value = $option['title'];
            }
        }
        $field_value = wpcf_frontend_wrap_field_value($params['field'],
                $field_value);
        $output = wpcf_frontend_wrap_field($params['field'], $field_value);
    }
    return $output;
}