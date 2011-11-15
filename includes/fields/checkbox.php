<?php
/**
 * Types-field: Checkbox
 *
 * Description: Displays a checkbox to the user. Checkboxes can be
 * used to get binary, yes/no responsers from a user.
 *
 * Rendering: The "Value to stored" for the checkbox the front end
 * if the checkbox is checked or 'Selected'|'Not selected' HTML
 * will be rendered. If 'Selected'|'Not selected' HTML is not specified then
 * nothing is rendered.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My checkbox: $value)
 * 'checked_html' => base64_encode('<img src="image-on.png" />')
 * 'unchecked_html' => base64_encode('<img src="image-off.png" />')
 *
 * Example usage:
 * With a short code use [types field="my-checkbox"]
 * In a theme use types_render_field("my-checkbox", $parameters)
 * 
 */

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
        'meta_key_type' => 'BINARY',
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
                '#title' => __('Show one of these two values:', 'wpcf'),
                '#name' => 'display',
                '#value' => 'value',
            ),
        ),
        '#inline' => true,
    );
    $form['display-value-1'] = array(
        '#type' => 'textfield',
        '#title' => __('Not selected:', 'wpcf'),
        '#name' => 'display_value_not_selected',
        '#value' => '',
        '#inline' => true,
    );
    $form['display-value-2'] = array(
        '#type' => 'textfield',
        '#title' => __('Selected:', 'wpcf'),
        '#name' => 'display_value_selected',
        '#value' => '',
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
    $field['data']['set_value'] = stripslashes($field['data']['set_value']);
    if ($field['value'] == $field['data']['set_value']) {
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

/**
 * Editor callback form.
 */
function wpcf_fields_checkbox_editor_callback() {
    $form = array();
    $value_not_selected = '';
    $value_selected = '';
    if (isset($_GET['field_id'])) {
        $field = wpcf_admin_fields_get_field($_GET['field_id']);
        if (!empty($field)) {
            if (isset($field['data']['display_value_not_selected'])) {
                $value_not_selected = $field['data']['display_value_not_selected'];
            }
            if (isset($field['data']['display_value_selected'])) {
                $value_selected = $field['data']['display_value_selected'];
            }
        }
    }
    $form['#form']['callback'] = 'wpcf_fields_checkbox_editor_submit';
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
                '#title' => __('Show one of these two values:', 'wpcf'),
                '#name' => 'display',
                '#value' => 'value',
            ),
        ),
        '#inline' => true,
    );
    $form['display-value-1'] = array(
        '#type' => 'textfield',
        '#title' => '<td style="text-align:right;">'
        . __('Not selected:', 'wpcf') . '</td><td>',
        '#name' => 'display_value_not_selected',
        '#value' => $value_not_selected,
        '#inline' => true,
        '#before' => '<table><tr>',
        '#after' => '</td></tr>',
    );
    $form['display-value-2'] = array(
        '#type' => 'textfield',
        '#title' => '<td style="text-align:right;">'
        . __('Selected:', 'wpcf') . '</td><td>',
        '#name' => 'display_value_selected',
        '#value' => $value_selected,
        '#after' => '</tr></table>'
    );
    $form['submit'] = array(
        '#type' => 'markup',
        '#markup' => get_submit_button(),
    );
    $f = wpcf_form('wpcf-form', $form);
    wpcf_admin_ajax_head('Insert checkbox', 'wpcf');
    echo '<form method="post" action="">';
    echo $f->renderForm();
    echo '</form>';
    wpcf_admin_ajax_footer();
}

/**
 * Editor callback form submit.
 */
function wpcf_fields_checkbox_editor_submit() {
    $add = '';
    if ($_POST['display'] == 'value') {
        $add .= ' checked_html="' . base64_encode($_POST['display_value_selected']) . '"';
        $add .= ' unchecked_html="' . base64_encode($_POST['display_value_not_selected']) . '"';
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
function wpcf_fields_checkbox_view($params) {
    $output = '';
    if (isset($params['unchecked_html']) && $params['field_value'] == '') {
        return base64_decode($params['unchecked_html']);
    } else if (isset($params['checked_html']) && $params['field_value'] != '') {
        return base64_decode($params['checked_html']);
    }
    if ($params['field']['data']['display'] == 'db' && $params['field_value'] != '') {
        $field = wpcf_fields_get_field_by_slug($params['field']['slug']);
        $output = $field['data']['set_value'];

        // Show the translated value if we have one.
        $output = wpcf_translate('field ' . $field['id'] . ' checkbox value',
                $output);
    } else if ($params['field']['data']['display'] == 'value'
            && $params['field_value'] != '') {
        if (!empty($params['field']['data']['display_value_selected'])) {
            $output = $params['field']['data']['display_value_selected'];
            $output = wpcf_translate('field ' . $params['field']['id'] . ' checkbox value selected',
                    $output);
        }
    } else if ($params['field']['data']['display'] == 'value') {
        if (!empty($params['field']['data']['display_value_not_selected'])) {
            $output = $params['field']['data']['display_value_not_selected'];
            $output = wpcf_translate('field ' . $params['field']['id'] . ' checkbox value not selected',
                    $output);
        }
    }

    return $output;
}