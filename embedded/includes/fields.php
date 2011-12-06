<?php

/**
 * Gets all groups.
 * 
 * @global type $wpdb
 * @return type 
 */
function wpcf_admin_fields_get_groups() {
    $groups = get_posts('numberposts=-1&post_type=wp-types-group&post_status=null');
    if (!empty($groups)) {
        foreach ($groups as $k => $group) {
            $groups[$k] = wpcf_admin_fields_adjust_group($group);
        }
    }
    return $groups;
}

/**
 * Converts post data.
 * 
 * @param type $post
 * @return type 
 */
function wpcf_admin_fields_adjust_group($post) {
    if (empty($post)) {
        return false;
    }
    $group = array();
    $group['id'] = $post->ID;
    $group['slug'] = $post->post_name;
    $group['name'] = $post->post_title;
    $group['description'] = $post->post_content;
    $group['meta_box_context'] = 'normal';
    $group['meta_box_priority'] = 'high';
    $group['is_active'] = $post->post_status == 'publish' ? true : false;

    return $group;
}

/**
 * Gets all fields.
 * 
 * @global type $wpdb
 * @return type 
 */
function wpcf_admin_fields_get_fields($only_active = false,
        $disabled_by_type = false, $strictly_active = false) {
    $required_data = array('id', 'name', 'type', 'slug');
    $fields = get_option('wpcf-fields', array());
    foreach ($fields as $k => $v) {
        if ($strictly_active) {
            if (!empty($v['data']['disabled']) || !empty($v['data']['disabled_by_type'])) {
                unset($fields[$k]);
                continue;
            }
        } else if (($only_active && !empty($v['data']['disabled']))
                && (!$disabled_by_type && !empty($v['data']['disabled_by_type']))) {
            unset($fields[$k]);
            continue;
        }
        foreach ($required_data as $required) {
            if (!isset($v[$required])) {
                if (!defined('WPCF_RUNNING_EMBEDDED')) {
                    $link = admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=delete_field&amp;field_id=' . $v['id'] . '&amp;_wpnonce=' . wp_create_nonce('delete_field'));
                    wp_enqueue_script('wpcf-fields-edit',
                            WPCF_RES_RELPATH . '/js/basic.js',
                            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable'),
                            WPCF_VERSION);
                    $message = sprintf(__('Invalid field "%s". %sDelete it%s',
                                    'wpcf'), $v['id'],
                            '<a href="' . $link . '" class="wpcf-ajax-link" onclick="jQuery(this).parent().parent().fadeOut();">',
                            '</a>');
                }
                unset($fields[$k]);
                continue;
            }
        }
    }
    return $fields;
}

/**
 * Gets field by ID.
 * 
 * @global type $wpdb
 * @param type $field_id
 * @param type $only_active
 * @return type 
 */
function wpcf_admin_fields_get_field($field_id, $only_active = false) {
    $fields = wpcf_admin_fields_get_fields();
    if (!empty($fields[$field_id])) {
        $fields[$field_id]['id'] = $field_id;
        return $fields[$field_id];
    }
    return array();
}

/**
 * Gets field by slug.
 * 
 * @global type $wpdb
 * @param type $slug
 * @return type 
 */
function wpcf_fields_get_field_by_slug($slug) {
    return wpcf_admin_fields_get_field($slug);
}

/**
 * Gets all fields that belong to specific group.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @param type $key
 * @param type $only_active
 * @return type 
 */
function wpcf_admin_fields_get_fields_by_group($group_id, $key = 'slug',
        $only_active = false, $disabled_by_type = false,
        $strictly_active = false) {
    $group_fields = get_post_meta($group_id, '_wp_types_group_fields', true);
    if (empty($group_fields)) {
        return array();
    }
    $group_fields = explode(',', trim($group_fields, ','));
    $fields = wpcf_admin_fields_get_fields($only_active, $disabled_by_type,
            $strictly_active);
    $results = array();
    foreach ($group_fields as $field_id) {
        if (!isset($fields[$field_id])) {
            continue;
        }
        $field = wpcf_admin_fields_get_field($field_id);
        if (!empty($field)) {
            $results[$field_id] = $field;
        }
    }
    return $results;
}

/**
 * Gets groups that have specific term.
 * 
 * @global type $wpdb
 * @param type $term_id
 * @param type $fetch_empty
 * @param type $only_active
 * @return type 
 */
function wpcf_admin_fields_get_groups_by_term($term_id = false,
        $fetch_empty = true, $post_type = false, $only_active = true) {
    $args = array();
    $args['post_type'] = 'wp-types-group';
    $args['numberposts'] = -1;
    // Active
    if ($only_active) {
        $args['post_status'] = 'publish';
    }
    // Fetch empty
    if ($fetch_empty) {
        $args['meta_query'] = array(
            array(
                'key' => '_wp_types_group_terms',
                'value' => ',' . $term_id . ',',
                'compare' => 'LIKE',
            ),
            array(
                'key' => '_wp_types_group_terms',
                'value' => 'all',
                'compare' => '=',
            ),
        );
    } else {
        $args['meta_query'] = array(
            array(
                'key' => '_wp_types_group_terms',
                'value' => ',' . $term_id . ',',
                'compare' => 'LIKE',
            ),
        );
    }
    // Distinct post type
    if ($post_type) {
        if ($fetch_empty) {
            $args['meta_query'][] = array(
                'key' => '_wp_types_group_post_types',
                'value' => 'all',
                'compare' => '=',
            );
        }
        $args['meta_query'][] = array(
            'key' => '_wp_types_group_post_types',
            'value' => ',' . $post_type . ',',
            'compare' => 'LIKE',
        );
    }

    return get_posts($args);
}

/**
 * Gets groups that have specific post_type.
 * 
 * @global type $wpdb
 * @param type $post_type
 * @param type $fetch_empty
 * @param type $only_active
 * @return type 
 */
function wpcf_admin_get_groups_by_post_type($post_type, $fetch_empty = true,
        $terms = null, $only_active = true) {
    $args = array();
    $args['post_type'] = 'wp-types-group';
    $args['numberposts'] = -1;
    // Active
    if ($only_active) {
        $args['post_status'] = 'publish';
    }
    // Fetch empty
    if ($fetch_empty) {
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key' => '_wp_types_group_post_types',
                'value' => ',' . $post_type . ',',
                'compare' => 'LIKE',
            ),
            array(
                'key' => '_wp_types_group_post_types',
                'value' => 'all',
                'compare' => '=',
            ),
        );
    } else {
        $args['meta_query'] = array(
            array(
                'key' => '_wp_types_group_post_types',
                'value' => ',' . $post_type . ',',
                'compare' => 'LIKE',
            ),
        );
    }

    $results_by_post_type = array();
    $results_by_terms = array();

    // Get posts by post type
    $groups = get_posts($args);
    if (!empty($groups)) {
        foreach ($groups as $key => $group) {
            $group = wpcf_admin_fields_adjust_group($group);
            $results_by_post_type[$group['id']] = $group;
        }
    }

    // Distinct terms
    if (!is_null($terms)) {
        if (!empty($terms)) {
            $args['meta_query'] = array('relation' => 'OR');
            if ($fetch_empty) {
                $args['meta_query'][] = array(
                    'key' => '_wp_types_group_terms',
                    'value' => 'all',
                    'compare' => '=',
                );
            }
            foreach ($terms as $term) {
                $args['meta_query'][] = array(
                    'key' => '_wp_types_group_terms',
                    'value' => ',' . $term . ',',
                    'compare' => 'LIKE',
                );
            }
            // Get posts by terms
            $groups = get_posts($args);
            if (!empty($groups)) {
                foreach ($groups as $key => $group) {
                    $group = wpcf_admin_fields_adjust_group($group);
                    $results_by_terms[$group['id']] = $group;
                }
            }
            foreach ($results_by_post_type as $key => $value) {
                if (!array_key_exists($key, $results_by_terms)) {
                    unset($results_by_post_type[$key]);
                }
            }
        }
    }

    return $results_by_post_type;
}

/**
 * Loads type configuration file and calls action.
 * 
 * @param type $type
 * @param type $action
 * @param type $args 
 */
function wpcf_fields_type_action($type, $func = '', $args = array()) {
    if (defined('WPCF_INC_ABSPATH')) {
        $file = WPCF_INC_ABSPATH . '/fields/' . $type . '.php';
    } else {
        $file = '';
    }
    $file_embedded = WPCF_EMBEDDED_INC_ABSPATH . '/fields/' . $type . '.php';
    if (file_exists($file) || file_exists($file_embedded)) {
        if (file_exists($file)) {
            require_once $file;
        }
        if (file_exists($file_embedded)) {
            require_once $file_embedded;
        }
        if (empty($func)) {
            $func = 'wpcf_fields_' . $type;
        } else {
            $func = 'wpcf_fields_' . $type . '_' . $func;
        }
        if (function_exists($func)) {
            return call_user_func($func, $args);
        }
    }
    return array();
}

/**
 * Returns shortcode for specified field.
 * 
 * @param type $field
 * @param type $add Additional attributes
 */
function wpcf_fields_get_shortcode($field, $add = '') {
    $shortcode = '[';
    $shortcode .= 'types field="' . $field['slug'] . '"' . $add;
    $shortcode .= ']';
    $shortcode = apply_filters('wpcf_fields_shortcode', $shortcode, $field);
    $shortcode = apply_filters('wpcf_fields_shortcode_type_' . $field['type'],
            $shortcode, $field);
    $shortcode = apply_filters('wpcf_fields_shortcode_slug_' . $field['slug'],
            $shortcode, $field);
    return $shortcode;
}

/**
 * Renders JS for inserting shortcode from thickbox popup to editor.
 * 
 * @param type $shortcode 
 */
function wpcf_admin_fields_popup_insert_shortcode_js($shortcode) {

    ?>
    <script type="text/javascript">
        //<![CDATA[
        window.parent.jQuery('#TB_closeWindowButton').trigger('click');
        if (window.parent.wpcfInsertMetaHTML == false) {
            if (window.parent.jQuery('textarea#content:visible').length) {
                // HTML editor
                window.parent.jQuery('textarea#content').insertAtCaret('<?php echo $shortcode; ?>');
            } else {
                // Visual editor
                window.parent.tinyMCE.activeEditor.execCommand('mceInsertContent', false, '<?php echo $shortcode; ?>');
            }
        } else {
            window.parent.jQuery('#'+window.parent.wpcfInsertMetaHTML).insertAtCaret('<?php echo $shortcode; ?>');
            window.parent.wpcfInsertMetaHTML = false;
        }
                                        
        //]]>
    </script>
    <?php
}

/**
 * Saves last field settings when inserting from toolbar.
 * 
 * @param type $field_id
 * @param type $settings 
 */
function wpcf_admin_fields_save_field_last_settings($field_id, $settings) {
    $data = get_user_meta(get_current_user_id(), 'wpcf-field-settings', true);
    $data[$field_id] = $settings;
    update_user_meta(get_current_user_id(), 'wpcf-field-settings', $data);
}

/**
 * Gets last field settings when inserting from toolbar.
 * 
 * @param type $field_id
 */
function wpcf_admin_fields_get_field_last_settings($field_id) {
    $data = get_user_meta(get_current_user_id(), 'wpcf-field-settings', true);
    if (isset($data[$field_id])) {
        return $data[$field_id];
    }
    return array();
}