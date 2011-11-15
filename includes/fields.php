<?php
/*
 * Fields and groups functions
 */

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
 * Gets group by ID.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_fields_get_group($group_id) {
    return wpcf_admin_fields_adjust_group(get_post($group_id));
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
 * Gets post_types supported by specific group.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_get_post_types_by_group($group_id) {
    $post_types = get_post_meta($group_id, '_wp_types_group_post_types', true);
    if ($post_types == 'all') {
        return array();
    }
    $post_types = explode(',', trim($post_types, ','));
    return $post_types;
}

/**
 * Gets taxonomies supported by specific group.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_get_taxonomies_by_group($group_id) {
    global $wpdb;
    $terms = get_post_meta($group_id, '_wp_types_group_terms', true);
    if ($terms == 'all') {
        return array();
    }
    $terms = explode(',', trim($terms, ','));
    $taxonomies = array();
    if (!empty($terms)) {
        foreach ($terms as $term) {
            $term = $wpdb->get_row("SELECT tt.term_taxonomy_id, tt.taxonomy,
                    t.term_id, t.slug, t.name
                    FROM {$wpdb->prefix}term_taxonomy tt
            JOIN {$wpdb->prefix}terms t
            WHERE t.term_id = tt.term_id AND tt.term_id="
                    . intval($term), ARRAY_A);
            if (!empty($term)) {
                $taxonomies[$term['taxonomy']][$term['term_id']] = $term;
            }
        }
    } else {
        return array();
    }
    return $taxonomies;
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
 * Gets all fields that belong to specific group.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @param type $key
 * @param type $only_active
 * @return type 
 */
function wpcf_admin_fields_get_fields_by_group($group_id, $key = 'slug',
        $only_active = true) {
    $group_fields = get_post_meta($group_id, '_wp_types_group_fields', true);
    if (empty($group_fields)) {
        return array();
    }
    $group_fields = explode(',', trim($group_fields, ','));
    $fields = get_option('wpcf-fields', array());
    $results = array();
    foreach ($group_fields as $field_id) {
        $field = wpcf_admin_fields_get_field($field_id);
        if (!empty($field)) {
            $results[$field_id] = $field;
        }
    }
    return $results;
}

/**
 * Gets field by ID.
 * 
 * @global type $wpdb
 * @param type $field_id
 * @param type $only_active
 * @return type 
 */
function wpcf_admin_fields_get_field($field_id, $only_active = true) {
    $fields = get_option('wpcf-fields', array());
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
 * Gets all fields.
 * 
 * @global type $wpdb
 * @return type 
 */
function wpcf_admin_fields_get_fields() {
    $fields = get_option('wpcf-fields', array());
    foreach ($fields as $k => $v) {
        $fields[$k] = wpcf_admin_fields_get_field($k);
    }
    return $fields;
}

/**
 * Activates group.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_fields_activate_group($group_id) {
    global $wpdb;
    return $wpdb->update($wpdb->posts, array('post_status' => 'publish'),
                    array('ID' => intval($group_id), 'post_type' => 'wp-types-group'),
                    array('%s'), array('%d', '%s')
    );
}

/**
 * Deactivates group.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_fields_deactivate_group($group_id) {
    global $wpdb;
    return $wpdb->update($wpdb->posts, array('post_status' => 'draft'),
                    array('ID' => intval($group_id), 'post_type' => 'wp-types-group'),
                    array('%s'), array('%d', '%s')
    );
}

/**
 * Removes specific field from group.
 * 
 * @global type $wpdb
 * @global type $wpdb
 * @param type $group_id
 * @param type $field_id
 * @return type 
 */
function wpcf_admin_fields_remove_field_from_group($group_id, $field_id) {
    $group_fields = get_post_meta($group_id, '_wp_types_group_fields', true);
    if (empty($group_fields)) {
        return false;
    }
    $group_fields = str_replace(',' . $field_id . ',', ',', $group_fields);
    update_post_meta($group_id, '_wp_types_group_fields', $group_fields);
}

/**
 * Deletes group by ID.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_fields_delete_group($group_id) {
    $group = get_post($group_id);
    if (empty($group) || $group->post_type != 'wp-types-group') {
        return false;
    }
    wp_delete_post($group_id, true);
}

/**
 * Saves group.
 * 
 * @param type $group
 * @return type 
 */
function wpcf_admin_fields_save_group($group) {
    if (!isset($group['name'])) {
        return false;
    }

    $post = array(
        'post_status' => 'publish',
        'post_type' => 'wp-types-group',
        'post_title' => $group['name'],
        'post_content' => !empty($group['description']) ? $group['description'] : '',
    );

    $update = false;
    if (isset($group['id'])) {
        $update = true;
        $post_to_update = get_post($group['id']);
        if (empty($post_to_update) || $post_to_update->post_type != 'wp-types-group') {
            return false;
        }
        $post['ID'] = $post_to_update->ID;
        $post['post_status'] = $post_to_update->post_status;
    }

    if ($update) {
        $group_id = wp_update_post($post);
        if (!$group_id) {
            return false;
        }
    } else {
        $group_id = wp_insert_post($post, true);
        if (is_wp_error($group_id)) {
            return false;
        }
    }

    // WPML register strings
    if (function_exists('icl_register_string')) {
        icl_register_string('plugin Types', 'group ' . $group_id . ' name',
                $group['name']);
        icl_register_string('plugin Types',
                'group ' . $group_id . ' description', $group['description']);
    }

    return $group_id;
}

/**
 * Saves field.
 * 
 * @param type $field
 * @return type 
 */
function wpcf_admin_fields_save_field($field) {
    if (!isset($field['name']) || !isset($field['type'])) {
        return false;
    }
    if (empty($field['slug'])) {
        $field['slug'] = sanitize_title($field['name']);
    } else {
        $field['slug'] = sanitize_title($field['slug']);
    }
    $field['id'] = $field['slug'];

    // Set field specific data
    $field['data'] = $field;
    // Unset default fields
    unset($field['data']['type'], $field['data']['slug'],
            $field['data']['name'], $field['data']['description'],
            $field['data']['user_id'], $field['data']['id'],
            $field['data']['data']);

    $field['data'] = apply_filters('wpcf_fields_' . $field['type'] . '_meta_data',
            $field['data'], $field);

    // Check validation
    if (isset($field['data']['validate'])) {
        foreach ($field['data']['validate'] as $method => $data) {
            if (!isset($data['active'])) {
                unset($field['data']['validate'][$method]);
            }
        }
        if (empty($field['data']['validate'])) {
            unset($field['data']['validate']);
        }
    }

    $save_data = array();
    $save_data['id'] = $field['id'];
    $save_data['slug'] = $field['slug'];
    $save_data['type'] = $field['type'];
    $save_data['name'] = $field['name'];
    $save_data['description'] = $field['description'];
    $save_data['data'] = $field['data'];

    // For radios or select
    if (!empty($field['data']['options'])) {
        foreach ($field['data']['options'] as $name => $option) {
            $option['title'] = $field['data']['options'][$name]['title'] = htmlspecialchars_decode($option['title']);
            $option['value'] = $field['data']['options'][$name]['value'] = htmlspecialchars_decode($option['value']);
            if (isset($option['display_value'])) {
                $option['display_value'] = $field['data']['options'][$name]['display_value'] = htmlspecialchars_decode($option['display_value']);
            }
        }
    }

    // For checkboxes
    if ($field['type'] == 'checkbox' && $field['set_value'] != '1') {
        $field['set_value'] = htmlspecialchars_decode($field['set_value']);
    }
    if ($field['type'] == 'checkbox' && !empty($field['display_value_selected'])) {
        $field['display_value_selected'] = htmlspecialchars_decode($field['display_value_selected']);
    }
    if ($field['type'] == 'checkbox' && !empty($field['display_value_not_selected'])) {
        $field['display_value_not_selected'] = htmlspecialchars_decode($field['display_value_not_selected']);
    }

    // Save field
    $fields = get_option('wpcf-fields', array());
    $fields[$field['slug']] = $save_data;
    update_option('wpcf-fields', $fields);
    $field_id = $field['slug'];

    // WPML register strings
    if (function_exists('icl_register_string')) {
        icl_register_string('plugin Types', 'field ' . $field_id . ' name',
                $field['name']);
        icl_register_string('plugin Types',
                'field ' . $field_id . ' description', $field['description']);

        // For radios or select
        if (!empty($field['data']['options'])) {
            foreach ($field['data']['options'] as $name => $option) {
                if ($name == 'default') {
                    continue;
                }
                icl_register_string('plugin Types',
                        'field ' . $field_id . ' option ' . $name . ' title',
                        $option['title']);
                icl_register_string('plugin Types',
                        'field ' . $field_id . ' option ' . $name . ' value',
                        $option['value']);
                if (isset($option['display_value'])) {
                    icl_register_string('plugin Types',
                            'field ' . $field_id . ' option ' . $name . ' display value',
                            $option['display_value']);
                }
            }
        }

        if ($field['type'] == 'checkbox' && $field['set_value'] != '1') {
            // we need to translate the check box value to store
            icl_register_string('plugin Types',
                    'field ' . $field_id . ' checkbox value',
                    $field['set_value']);
        }

        if ($field['type'] == 'checkbox' && !empty($field['display_value_selected'])) {
            // we need to translate the check box value to store
            icl_register_string('plugin Types',
                    'field ' . $field_id . ' checkbox value selected',
                    $field['display_value_selected']);
        }

        if ($field['type'] == 'checkbox' && !empty($field['display_value_not_selected'])) {
            // we need to translate the check box value to store
            icl_register_string('plugin Types',
                    'field ' . $field_id . ' checkbox value not selected',
                    $field['display_value_not_selected']);
        }

        // Validation message
        if (!empty($field['data']['validate'])) {
            foreach ($field['data']['validate'] as $method => $validation) {
                if (!empty($validation['message'])) {
                    // Skip if it's same as default
                    $default_message = wpcf_admin_validation_messages($method);
                    if ($validation['message'] != $default_message) {
                        icl_register_string('plugin Types',
                                'field ' . $field_id . ' validation message ' . $method,
                                $validation['message']);
                    }
                }
            }
        }
    }

    return $field_id;
}

/**
 * Saves group's fields.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @param type $fields 
 */
function wpcf_admin_fields_save_group_fields($group_id, $fields) {
    if (empty($fields)) {
        delete_post_meta($group_id, '_wp_types_group_fields');
        return false;
    }
    $fields = ',' . implode(',', (array) $fields) . ',';
    update_post_meta($group_id, '_wp_types_group_fields', $fields);
}

/**
 * Saves group's post types.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @param type $post_types 
 */
function wpcf_admin_fields_save_group_post_types($group_id, $post_types) {
    if (empty($post_types)) {
        update_post_meta($group_id, '_wp_types_group_post_types', 'all');
        return true;
    }
    $post_types = ',' . implode(',', (array) $post_types) . ',';
    update_post_meta($group_id, '_wp_types_group_post_types', $post_types);
}

/**
 * Saves group's terms.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @param type $terms 
 */
function wpcf_admin_fields_save_group_terms($group_id, $terms) {
    if (empty($terms)) {
        update_post_meta($group_id, '_wp_types_group_terms', 'all');
        return true;
    }
    $terms = ',' . implode(',', (array) $terms) . ',';
    update_post_meta($group_id, '_wp_types_group_terms', $terms);
}

/**
 * Returns HTML formatted AJAX activation link.
 * 
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_fields_get_ajax_activation_link($group_id) {
    return '<a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;'
                    . 'wpcf_action=activate_group&amp;group_id='
                    . $group_id . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
                    . $group_id) . '&amp;_wpnonce=' . wp_create_nonce('activate_group')
            . '" class="wpcf-ajax-link" id="wpcf-list-activate-'
            . $group_id . '">'
            . __('Activate') . '</a>';
}

/**
 * Returns HTML formatted AJAX deactivation link.
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_fields_get_ajax_deactivation_link($group_id) {
    return '<a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;'
                    . 'wpcf_action=deactivate_group&amp;group_id='
                    . $group_id . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
                    . $group_id) . '&amp;_wpnonce=' . wp_create_nonce('deactivate_group')
            . '" class="wpcf-ajax-link" id="wpcf-list-activate-'
            . $group_id . '">'
            . __('Deactivate') . '</a>';
}

/**
 * Loads type configuration file and calls action.
 * 
 * @param type $type
 * @param type $action
 * @param type $args 
 */
function wpcf_fields_type_action($type, $func = '', $args = array()) {
    $file = WPCF_INC_ABSPATH . '/fields/' . $type . '.php';
    if (file_exists($file)) {
        require_once $file;
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