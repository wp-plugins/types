<?php
/**
 * Types-field: Texfield
 *
 * Description: Displays a textfield input to the user.
 *
 * Rendering: HTML formatted DB data.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My checkbox: $value)
 *
 * Example usage:
 * With a short code use [types field="my-textfield"]
 * In a theme use types_render_field("my-textfield", $parameters)
 * 
 */

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_textfield() {
    return array(
        'id' => 'wpcf-texfield',
        'title' => __('Single line', 'wpcf'),
        'description' => __('Texfield', 'wpcf'),
        'validate' => array('required'),
    );
}