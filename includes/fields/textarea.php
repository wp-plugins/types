<?php
/**
 * Types-field: Textarea
 *
 * Description: Displays a textarea input to the user.
 *
 * Rendering: HTML formatted DB data.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My checkbox: $value)
 *
 * Example usage:
 * With a short code use [types field="my-textarea"]
 * In a theme use types_render_field("my-textarea", $parameters)
 * 
 */

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
        'validate' => array('required'),
    );
}

/**
 * Formats display data.
 */

function wpcf_fields_textarea_value_display_filter($value) {
    
    // see if it's already wrapped in <p> ... </p>
    $wrapped_in_p = false;
    if (!empty($value) && strpos($value, '<p>') === 0 && strrpos($value, "</p>\n") == strlen($value) - 5 ) {
        $wrapped_in_p = true;
    }
    
    // use wpautop for converting line feeds to <br />, etc
    $value = wpautop($value);
    
    if (!$wrapped_in_p) {
        // If it wasn't wrapped then remove the wrapping wpautop has added.
        if(!empty($value) && strpos($value, '<p>') === 0 && strrpos($value, "</p>\n") == strlen($value) - 5 ) {
            // unwrapp the <p> ..... </p>
            $value = substr($value, 3, -5);
        }
    }

    return $value;
}

