<?php
/**
 * Types-field: Numeric
 *
 * Description: Displays a text input to user but forces numeric value to be
 * entered.
 *
 * Rendering: Raw DB data or HTML formatted output. Also predefined values can
 * be used to set rendering - FIELD_NAME and FIELD_VALUE. This works similar to
 * sprintf() PHP function.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My date: $value)
 * 'format' => e.g. 'Value of FIELD_NAME is FIELD_VALUE'
 *      FIELD_NAME will be replaced with field name
 *      FIELD_VALUE will be replaced with field value
 *
 * Example usage:
 * With a short code use [types field="my-numeric"]
 * In a theme use types_render_field("my-numeric", $parameters)
 * 
 */

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_numeric() {
    return array(
        'id' => 'wpcf-numeric',
        'title' => __('Numeric', 'wpcf'),
        'description' => __('Numeric', 'wpcf'),
        'validate' => array('required', 'number' => array('forced' => true)),
        'inherited_field_type' => 'textfield',
        'meta_key_type' => 'NUMERIC',
    );
}

/**
 * Editor callback form.
 */
function wpcf_fields_numeric_editor_callback() {
    wp_enqueue_style('wpcf-fields', WPCF_RES_RELPATH . '/css/basic.css',
            array(), WPCF_VERSION);
    wp_enqueue_script('jquery');

    // Get field
    $field = wpcf_admin_fields_get_field($_GET['field_id']);
    if (empty($field)) {
        _e('Wrong field specified', 'wpcf');
        die();
    }

    $last_settings = wpcf_admin_fields_get_field_last_settings($_GET['field_id']);

    $form = array();
    $form['#form']['callback'] = 'wpcf_fields_numeric_editor_submit';
    $form['format'] = array(
        '#type' => 'textfield',
        '#title' => __('Output format', 'wpcf'),
        '#description' => __("Similar to sprintf function. Default: 'FIELD_NAME: FIELD_VALUE'."),
        '#name' => 'format',
        '#value' => isset($last_settings['format']) ? $last_settings['format'] : 'FIELD_NAME: FIELD_VALUE',
    );
    $form['submit'] = array(
        '#type' => 'markup',
        '#markup' => get_submit_button(__('Insert shortcode', 'wpcf')),
    );
    $f = wpcf_form('wpcf-form', $form);
    wpcf_admin_ajax_head('Insert numeric', 'wpcf');
    echo '<form method="post" action="">';
    echo $f->renderForm();
    echo '</form>';
}

/**
 * Editor callback form submit.
 */
function wpcf_fields_numeric_editor_submit() {
    $add = '';
    if (!empty($_POST['format'])) {
        $add .= ' format="' . strval($_POST['format']) . '"';
    }
    $field = wpcf_admin_fields_get_field($_GET['field_id']);
    if (!empty($field)) {
        $shortcode = wpcf_fields_get_shortcode($field, $add);
        wpcf_admin_fields_save_field_last_settings($_GET['field_id'],
                array('format' => $_POST['format'])
        );
        echo wpcf_admin_fields_popup_insert_shortcode_js($shortcode);
        die();
    }
}

/**
 * View function.
 * 
 * @param type $params 
 */
function wpcf_fields_numeric_view($params) {
    $output = '';
    if (!empty($params['format'])) {
        $patterns = array('/FIELD_NAME/', '/FIELD_VALUE/');
        $replacements = array($params['field']['name'], $params['field_value']);
        $output = preg_replace($patterns, $replacements, $params['format']);
        $output = sprintf($output, $params['field_value']);
    }
    $output = wpcf_frontend_wrap_field_value($params['field'], $output, $params);
    $output = wpcf_frontend_wrap_field($params['field'], $output, $params);
    return $output;
}