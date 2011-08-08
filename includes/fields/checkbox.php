<?php
/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_checkbox() {
    return array(
        'id' => 'wpcf-checkbox',
        'title' => __('Checkbox', 'wpcf'),
        'description' => __('Checkbox', 'wpcf'),
        'validate' => array('required'),
    );
}

/**
 * Form data for group form.
 * 
 * @return type 
 */
function wpcf_fields_checkbox_insert_form() {
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
    $form['value'] = array(
        '#type' => 'textfield',
        '#title' => __('Value to store', 'wpcf'),
        '#name' => 'set_value',
        '#value' => 1,
    );
    $form['checked'] = array(
        '#type' => 'checkbox',
        '#title' => __('Set checked by default (on new post)?', 'wpcf'),
        '#name' => 'checked',
    );
    return $form;
}

/**
 * Form data for post edit page.
 * 
 * @param type $field 
 */
function wpcf_fields_checkbox_meta_box_form($field) {
    $checked = false;
    if (!empty($field['value'])) {
        $checked = true;
    }
    // If post is new check if it's checked by default
    global $pagenow;
    if ($pagenow == 'post-new.php' && !empty($field['data']['checked'])) {
        $checked = true;
    }
    return array(
        '#type' => 'checkbox',
        '#value' => $field['data']['set_value'],
        '#default_value' => $checked,
    );
}