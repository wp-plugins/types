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
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpcf_groups",
            ARRAY_A);
}

/**
 * Gets group by ID
 * 
 * @global type $wpdb
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_fields_get_group($group_id) {
    global $wpdb;
    return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wpcf_groups WHERE id="
            . intval($group_id), ARRAY_A);
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
        $only_active = true) {
    global $wpdb;
    $sql_type = $fetch_empty ? "((r.type='post_type' AND r.value=%s) OR (r.type='post_type' AND r.value IS NULL))" : "r.type='post_type' AND r.value=%s";
    $add = '';
    $add .= $only_active ? ' AND g.is_active=1' : '';
    $results = $wpdb->get_results($wpdb->prepare("SELECT g.id, g.slug, g.name, g.description, g.meta_box_context, g.meta_box_priority, g. is_active, g.user_id
            FROM {$wpdb->prefix}wpcf_groups g
    JOIN {$wpdb->prefix}wpcf_relationships r
    ON g.id=r.group_id WHERE $sql_type" . $add,
                            $post_type), ARRAY_A);
    return $results;
}

/**
 * Gets post_types supported by specific group.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_get_post_types_by_group($group_id) {
    global $wpdb;
    $post_types = $wpdb->get_results($wpdb->prepare("SELECT value
            FROM {$wpdb->prefix}wpcf_relationships
            WHERE group_id=%d AND type='post_type' AND value IS NOT NULL",
                            intval($group_id)), ARRAY_A);
    $results = array();
    foreach ($post_types as $post_type) {
        $results[] = $post_type['value'];
    }
    return $results;
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
    $terms = $wpdb->get_results($wpdb->prepare("SELECT value
            FROM {$wpdb->prefix}wpcf_relationships
            WHERE group_id=%d AND type='term' AND value IS NOT NULL",
                            intval($group_id)), ARRAY_A);
    $taxonomies = array();
    if (!empty($terms)) {
        foreach ($terms as $term) {
            $term = $wpdb->get_row("SELECT tt.term_taxonomy_id, tt.taxonomy,
                    t.term_id, t.slug, t.name
                    FROM {$wpdb->prefix}term_taxonomy tt
            JOIN {$wpdb->prefix}terms t
            WHERE t.term_id = tt.term_id AND tt.term_id="
                            . intval($term['value']), ARRAY_A);
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
        $fetch_empty = true, $only_active = true) {
    global $wpdb;
    if ($term_id) {
        $sql_term = $fetch_empty ? "((r.type='term' AND r.value=%s) OR (r.type='term' AND r.value IS NULL))" : "r.type='term' AND r.value=%s";
    } else {
        $sql_term = "r.type='term' AND r.value IS NULL";
    }
    $add = '';
    $add .= $only_active ? ' AND g.is_active=1' : '';
    $results = $wpdb->get_results($wpdb->prepare("SELECT g.id, g.slug, g.name, g.description, g.meta_box_context, g.meta_box_priority, g. is_active, g.user_id
            FROM {$wpdb->prefix}wpcf_groups g
    JOIN {$wpdb->prefix}wpcf_relationships r
    ON g.id=r.group_id WHERE $sql_term" . $add,
                            $term_id), ARRAY_A);
    return $results;
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
    global $wpdb;
    $results = array();
    $fields = $wpdb->get_results($wpdb->prepare("SELECT value
            FROM {$wpdb->prefix}wpcf_relationships
            WHERE group_id=%d AND type='field' ORDER BY id",
                            intval($group_id)), ARRAY_A);
    foreach ($fields as $field) {
        $temp = wpcf_admin_fields_get_field($field['value'], $only_active);
        if (!empty($temp)) {
            if (isset($temp[$key])) {
                $results[$temp[$key]] = $temp;
            } else {
                $results[] = $temp;
            }
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
    global $wpdb;
    $add = $only_active ? ' AND is_active=1' : '';
    $field = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wpcf_fields
    WHERE id=" . intval($field_id) . $add,
                    ARRAY_A);
    if (!empty($field)) {
        $field['data'] = unserialize($field['data']);
    }
    return $field;
}

/**
 * Gets field by slug.
 * 
 * @global type $wpdb
 * @param type $slug
 * @return type 
 */
function wpcf_fields_get_field_by_slug($slug) {
    global $wpdb;
    $field = $wpdb->get_row($wpdb->prepare("SELECT * FROM
        {$wpdb->prefix}wpcf_fields WHERE slug='%s'",
                            strval($slug)), ARRAY_A);
    if (!empty($field)) {
        $field['data'] = unserialize($field['data']);
    }
    return $field;
}

/**
 * Gets all fields.
 * 
 * @global type $wpdb
 * @return type 
 */
function wpcf_admin_fields_get_fields() {
    global $wpdb;
    $fields = $wpdb->get_results("SELECT * FROM
        {$wpdb->prefix}wpcf_fields",
                    ARRAY_A);
    if (!empty($fields)) {
        foreach ($fields as $key => $field) {
            $fields[$key]['data'] = unserialize($field['data']);
        }
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
    return $wpdb->update($wpdb->prefix . 'wpcf_groups', array('is_active' => 1),
            array('id' => intval($group_id)), array('%d'), array('%d')
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
    return $wpdb->update($wpdb->prefix . 'wpcf_groups', array('is_active' => 0),
            array('id' => intval($group_id)), array('%d'), array('%d')
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
    global $wpdb;
    $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wpcf_relationships
    WHERE group_id=%d AND type='field' AND value=%d",
                            intval($group_id), intval($field_id)));
    if (empty($id)) {
        return false;
    }
    $wpdb->query("DELETE FROM {$wpdb->prefix}wpcf_relationships WHERE id="
            . intval($id));
}

/**
 * Deletes group by ID.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_fields_delete_group($group_id) {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->prefix}wpcf_groups
    WHERE id=" . intval($group_id));
    $wpdb->query("DELETE FROM {$wpdb->prefix}wpcf_relationships
    WHERE id=" . intval($group_id));
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
    global $wpdb;
    if (!isset($group['slug'])) {
        $group['slug'] = sanitize_title($group['name']);
    }
    $defaults = array(
        'description' => '',
        'meta_box_context' => 'normal',
        'meta_box_priority' => 'default',
        'is_active' => 1,
        'user_id' => 1,
    );
    $group = array_merge($defaults, $group);

    if (!empty($group['id'])) {
        $group_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpcf_groups
        WHERE id=" . intval($group['id']));
        if (empty($group_id)) {
            return false;
        }
        $wpdb->update($wpdb->prefix . 'wpcf_groups',
                array(
            'slug' => $group['slug'],
            'name' => $group['name'], // @todo Sanitize?
            'description' => $group['description'],
            'meta_box_context' => $group['meta_box_context'],
            'meta_box_priority' => $group['meta_box_priority'],
                ), array('id' => $group_id),
                array('%s', '%s', '%s', '%s', '%s'), array('%d')
        );
    } else {
        $success = $wpdb->insert($wpdb->prefix . 'wpcf_groups',
                        array(
                    'slug' => $group['slug'],
                    'name' => $group['name'], // @todo Sanitize?
                    'description' => $group['description'],
                    'meta_box_context' => $group['meta_box_context'],
                    'meta_box_priority' => $group['meta_box_priority'],
                    'user_id' => get_current_user_id(),
                        ), array('%s', '%s', '%s', '%s', '%s', '%d')
        );
        if (empty($success)) {
            return false;
        }
        $group_id = $wpdb->insert_id;
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
    global $wpdb;
    if (!isset($field['slug'])) {
        $field['slug'] = sanitize_title($field['name']);
    }

    // Set field specific data
    $field['data'] = $field;
    // Unset default fields
    unset($field['data']['type'], $field['data']['slug'],
            $field['data']['name'], $field['data']['description'],
            $field['data']['user_id']);
    
    $field['data'] = apply_filters('wpcf_fields_' . $field['type'] . '_meta_data',
            $field['data'], $field);

    // Check validation
    if (isset($field['data']['validate'])) {
        foreach ($field['data']['validate'] as $method => $data) {
            if (!isset($data['active'])) {
                unset($field['data']['validate'][$method]);
            }
        }
    }

    // By checking slug we force creating variation
    $field_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wpcf_fields
            WHERE slug='" . $field['slug'] . "'");
    if (!empty($field_id)) {
        $wpdb->update($wpdb->prefix . 'wpcf_fields',
                array(
            'description' => $field['description'],
            'data' => serialize($field['data'])
                ), array('id' => $field_id), array('%s', '%s'), array('%d'));
    } else {
        $success = $wpdb->insert($wpdb->prefix . 'wpcf_fields',
                        array(
                    'type' => $field['type'],
                    'slug' => $field['slug'],
                    'name' => $field['name'], // @todo Sanitize?
                    'data' => serialize($field['data']),
                    'user_id' => get_current_user_id(),
                        ),
                        array(
                    '%s', '%s', '%s', '%s', '%d'
                ));
        if (empty($success)) {
            return false;
        }
        $field_id = $wpdb->insert_id;
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
    global $wpdb;
    // Clear all
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wpcf_relationships
    WHERE group_id=%d AND type='field'",
                    intval($group_id)));
    // Insert
    foreach ($fields as $field_id) {
        $wpdb->insert($wpdb->prefix . 'wpcf_relationships',
                array(
            'group_id' => intval($group_id),
            'type' => 'field',
            'value' => strval($field_id)
                ), array('%d', '%s', '%s'));
    }
}

/**
 * Saves group's post types.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @param type $post_types 
 */
function wpcf_admin_fields_save_group_post_types($group_id, $post_types) {
    global $wpdb;
    // Clear all
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wpcf_relationships
    WHERE group_id=%d AND type='post_type'",
                    intval($group_id)));
    // Insert
    if (empty($post_types)) {
        $wpdb->insert($wpdb->prefix . 'wpcf_relationships',
                array(
            'group_id' => intval($group_id),
            'type' => 'post_type'
                ), array('%d', '%s'));
    } else {
        foreach ($post_types as $post_type) {
            $wpdb->insert($wpdb->prefix . 'wpcf_relationships',
                    array(
                'group_id' => intval($group_id),
                'type' => 'post_type',
                'value' => strval($post_type)
                    ), array('%d', '%s', '%s'));
        }
    }
}

/**
 * Saves group's terms.
 * 
 * @global type $wpdb
 * @param type $group_id
 * @param type $terms 
 */
function wpcf_admin_fields_save_group_terms($group_id, $terms) {
    global $wpdb;
    // Clear all
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wpcf_relationships
    WHERE group_id=%d AND type='term'",
                    intval($group_id)));
    // Insert
    if (empty($terms)) {
        $wpdb->insert($wpdb->prefix . 'wpcf_relationships',
                array(
            'group_id' => intval($group_id),
            'type' => 'term'
                ), array('%d', '%s'));
    } else {
        foreach ($terms as $term_id) {
            $wpdb->insert($wpdb->prefix . 'wpcf_relationships',
                    array(
                'group_id' => intval($group_id),
                'type' => 'term',
                'value' => strval($term_id)
                    ), array('%d', '%s', '%s'));
        }
    }
}

/**
 * Returns HTML formatted AJAX activation link.
 * 
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_fields_get_ajax_activation_link($group_id) {
    return '<a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=activate_group&amp;group_id='
            . $group_id . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
            . $group_id) . '" class="wpcf-ajax-link" id="wpcf-list-activate-'
    . $group_id . '">'
    . __('Activate') . '</a>';
}

/**
 * Returns HTML formatted AJAX deactivation link.
 * @param type $group_id
 * @return type 
 */
function wpcf_admin_fields_get_ajax_deactivation_link($group_id) {
    return '<a href="' . admin_url('admin-ajax.php?action=wpcf_ajax&amp;wpcf_action=deactivate_group&amp;group_id='
            . $group_id . '&amp;wpcf_ajax_update=wpcf_list_ajax_response_'
            . $group_id) . '" class="wpcf-ajax-link" id="wpcf-list-activate-'
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
    $shortcode .= 'types field="' . $field['slug'] .'"' . $add;
    $shortcode .= ']';
    $shortcode = apply_filters('wpcf_fields_shortcode', $shortcode, $field);
    $shortcode = apply_filters('wpcf_fields_shortcode_type_' . $field['type'], $shortcode, $field);
    $shortcode = apply_filters('wpcf_fields_shortcode_slug_' . $field['slug'], $shortcode, $field);
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
        window.parent.tinyMCE.activeEditor.execCommand('mceInsertContent', false, '<?php echo $shortcode; ?>');
        //]]>
    </script>
    <?php
}