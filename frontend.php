<?php
/*
 * Frontend functions.
 */
add_shortcode('types', 'wpcf_shortcode');

function wpcf_shortcode($atts, $content = null, $code = '') {
    $atts = array_merge(array(
        'field' => false,
        'style' => 'default',
        'show_name' => false,
        'raw' => false,
            ), $atts
    );
    if ($atts['field']) {
        return types_render_field($atts['field'], $atts);
    }
    return '';
}

/**
 * Calls view function for specific field type.
 * 
 * @param type $field
 * @param type $atts
 * @return type 
 */
function types_render_field($field, $params) {
    require_once WPCF_INC_ABSPATH . '/fields.php';

    // Count fields (if there are duplicates)
    static $count = array();

    // Get field
    $field = wpcf_fields_get_field_by_slug($field);
    if (empty($field)) {
        return '';
    }

    // Count it
    if (!isset($count[$field['slug']])) {
        $count[$field['slug']] = 1;
    } else {
        $count[$field['slug']] += 1;
    }

    // Get post field value
    global $post;
    $value = get_post_meta($post->ID, 'wpcf-' . $field['slug'], true);
    if (empty($value)) {
        return '';
    }
    
    // Load type
    $type = wpcf_fields_type_action($field['type']);

    // Apply filters to field value
    $value = apply_filters('wpcf_fields_value_display', $value);
    $value = apply_filters('wpcf_fields_slug_' . $field['slug'] . '_value_display',
            $value);
    $value = apply_filters('wpcf_fields_type_' . $field['type'] . '_value_display',
            $value);

    // Set values
    // @todo WPML check
    $field['name'] = wpcf_translate('field ' . $field['id'] . ' name', $field['name']);
//    $value = wpcf_translate('field ' . $field['id'] . ' value', $value);
    $params['field'] = $field;
    $params['post'] = $post;
    $params['field_value'] = $value;

    // Get output
    $output = wpcf_fields_type_action($field['type'], 'view', $params);

    // Convert to string
    if (!empty($output)) {
        $output = strval($output);
    }

    // If no output or 'raw' return default
    if (($params['raw'] == 'true' || empty($output)) && !empty($value)) {
        $field_name = '';
        if ($params['show_name'] == 'true') {
            $field_name = wpcf_frontend_wrap_field_name($field, $field['name']);
        }
        $field_value = wpcf_frontend_wrap_field_value($field, $value);
        $output = wpcf_frontend_wrap_field($field, $field_name . $field_value);
    }

    // Apply filters
    $output = strval(apply_filters('types_view', $output, $value,
                    $field['type'], $field['slug'], $field['name'], $params));

    // Add count
    // @todo Reconsider
//    if (isset($count[$field['slug']]) && intval($count[$field['slug']]) > 1) {
//        $add = '-' . intval($count[$field['slug']]);
//        $output = str_replace('id="wpcf-field-' . $field['slug'] . '"',
//                'id="wpcf-field-' . $field['slug'] . $add . '"', $output);
//    }

    return $output;
}

/**
 * Wraps field content.
 * 
 * @param type $field
 * @param type $content
 * @return type 
 */
function wpcf_frontend_wrap_field($field, $content, $params = array()) {
    // @todo Reconsider
    if (isset($params['show_name']) && $params['show_name'] == 'true'
            && strpos($content, $field['name']) === false) {
        $content = wpcf_frontend_wrap_field_name($field, $params['field']['name']) . $content;
    }
    return $content;
    
    // Add name if needed
    if (isset($params['show_name']) && $params['show_name'] == 'true'
            && strpos($content,
                    'class="wpcf-field-' . $field['type']
                    . '-name ') === false) {
        $content = wpcf_frontend_wrap_field_name($field, $field['name']) . $content;
    }
    return '<div id="wpcf-field-' . $field['slug'] . '"'
    . ' class="wpcf-field-' . $field['type'] . ' wpcf-field-'
    . $field['slug'] . '"' . '>' . $content . '</div>';
}

/**
 * Wraps field name.
 * 
 * @param type $field
 * @param type $content
 * @return type 
 */
function wpcf_frontend_wrap_field_name($field, $content) {
    // @todo Reconsider
    return $content . ': ';
    return '<span class="wpcf-field-' . $field['type'] . ' wpcf-field-'
    . $field['slug'] . '-name">' . $content . ':</span> ';
}

/**
 * Wraps field value.
 * 
 * @param type $field
 * @param type $content
 * @return type 
 */
function wpcf_frontend_wrap_field_value($field, $content) {
    // @todo Reconsider
    return $content;
    return '<span class="wpcf-field-' . $field['type'] . '-value wpcf-field-'
    . $field['slug'] . '-value">' . $content . '</span>';
}