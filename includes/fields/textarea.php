<?php
add_filter('wpcf_fields_type_textarea_value_display',
        'wpcf_fields_textarea_value_display_filter');
/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_textarea() {
    return array(
        'id' => 'wpcf-textarea',
        'title' => __('Multiple lines', 'wpcf'),
        'description' => __('Textarea', 'wpcf'),
        'validate' => array('required', 'minlength'),
        'type' => 'standard-field',
        'insert_form', // to render on add fields form
        'meta_form', // to render on post edit page
        'insert_callback', // callback when added to form
        'meta_callback', // callback when added to post
        'insert_js',
        'meta_box_js',
        'insert_css',
        'meta_box_css',
        'insert_help',
        'meta_help',
    );
}

/**
 * Form data for group form.
 * 
 * @return type 
 */
function wpcf_fields_textarea_insert_form() {
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
    return $form;
}

/**
 * Formats display data.
 */
function wpcf_fields_textarea_value_display_filter($value) {
    if (!empty($value)) {
        $value = wpautop($value);
    }
    return $value;
}