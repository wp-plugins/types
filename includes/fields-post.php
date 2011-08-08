<?php
/*
 * Edit post page functions
 */

/**
 * Init functions for post edit pages.
 * 
 * @param type $upgrade 
 */
function wpcf_admin_post_init($post = false) {

    // Get post_type
    if ($post) {
        $post_type = get_post_type($post);
    } else {
        if (!isset($_GET['post_type'])) {
            $post_type = 'post';
        } else if (in_array($_GET['post_type'],
                        get_post_types(array('show_ui' => true)))) {
            $post_type = $_GET['post_type'];
        } else {
            return false;
        }
    }

    // Get groups
    $groups = wpcf_admin_post_get_post_groups_fields($post);
    $wpcf_active = false;
    foreach ($groups as $key => $group) {
        if (!empty($group['fields'])) {
            $wpcf_active = true;
            // Process fields
            $group['fields'] = wpcf_admin_post_process_fields($post,
                    $group['fields']);
        }
        // Add meta boxes
        add_meta_box($group['slug'], $group['name'], 'wpcf_admin_post_meta_box',
                $post_type, $group['meta_box_context'],
                $group['meta_box_priority'], $group);
    }

    // Activate scripts
    if ($wpcf_active) {
        wp_enqueue_script('wpcf-fields-post',
                WPCF_RES_RELPATH . '/js/fields-post.js', array('jquery'),
                WPCF_VERSION);
        wp_enqueue_script('wpcf-form-validation',
                WPCF_RES_RELPATH . '/js/'
                . 'jquery-form-validation/jquery.validate.min.js',
                array('jquery'), WPCF_VERSION);
        wp_enqueue_style('wpcf-fields-basic',
                WPCF_RES_RELPATH . '/css/basic.css', array(), WPCF_VERSION);
        wp_enqueue_style('wpcf-fields-post',
                WPCF_RES_RELPATH . '/css/fields-post.css',
                array('wpcf-fields-basic'), WPCF_VERSION);
        add_action('admin_footer', 'wpcf_admin_post_js_validation');
    }
}

/**
 * Renders meta box content.
 * 
 * @param type $post
 * @param type $group 
 */
function wpcf_admin_post_meta_box($post, $group) {
    if (!empty($group['args']['fields'])) {
        // Display description
        if (!empty($group['args']['description'])) {
            echo '<div class="wpcf-meta-box-description">'
            . wpautop($group['args']['description']) . '</div>';
        }
        foreach ($group['args']['fields'] as $field_slug => $field) {
            // Render form elements
            echo wpcf_form_simple(array($field['#id'] => $field));
            do_action('wpcf_fields_' . $field_slug . '_meta_box_form', $field);
            if (isset($field['wpcf-type'])) { // May be ignored
                do_action('wpcf_fields_' . $field['wpcf-type'] . '_meta_box_form',
                        $field);
            }
        }
    }
}

/**
 * save_post hook.
 * 
 * @param type $post_ID
 * @param type $post 
 */
function wpcf_admin_post_save_post_hook($post_ID, $post) {
    if (!empty($_POST['wpcf'])
            && !in_array($post->post_type, array('revision', 'attachment'))) {

        // Get groups
        $groups = wpcf_admin_post_get_post_groups_fields($post);
        $all_fields = array();
        foreach ($groups as $group) {
            // Process fields
            $fields = wpcf_admin_post_process_fields($post, $group['fields'],
                    true);
            // Validate fields
            $form = wpcf_form_simple_validate($fields);
            $all_fields = $all_fields + $fields;
            $error = $form->isError();
            // Trigger form error
            if ($error) {
                wpcf_admin_message_store(
                        __('Please check your input data', 'wpcf'), 'error');
            }
        }

        // Save invalid elements so user can be informed after redirect
        if (!empty($all_fields)) {
            update_post_meta($post_ID, 'wpcf-invalid-fields', $all_fields);
        }

        // Save meta fields
        foreach ($_POST['wpcf'] as $field_slug => $field_value) {

            // Don't save invalid
            if (isset($all_fields['wpcf-' . $field_slug])
                    && isset($all_fields['wpcf-' . $field_slug]['#error'])) {
                continue;
            }

            // Get field by slug
            $field = wpcf_fields_get_field_by_slug($field_slug);
            if (!empty($field)) {

                // Apply filters
                $field_value = apply_filters('wpcf_fields_value_save',
                        $field_value, $field['type'], $field_slug);
                $field_value = apply_filters('wpcf_fields_slug_' . $field_slug
                        . '_value_save', $field_value);
                $field_value = apply_filters('wpcf_fields_type_' . $field['type']
                        . '_value_save', $field_value);

                // Save field
                update_post_meta($post_ID, 'wpcf-' . $field_slug, $field_value);

                do_action('wpcf_fields_slug_' . $field_slug . '_save',
                        $field_value);
                do_action('wpcf_fields_type_' . $field['type'] . '_save',
                        $field_value);
            }
        }

        // Process checkboxes (unset)
        foreach ($all_fields as $field) {
            if ($field['#type'] == 'checkbox'
                    && !isset($_POST['wpcf'][$field['wpcf-slug']])) {
                delete_post_meta($post_ID, 'wpcf-' . $field['wpcf-slug']);
            }
        }
    }
}

/**
 * Renders JS validation script.
 */
function wpcf_admin_post_js_validation() {
    wpcf_form_render_js_validation('#post');
    // @todo check this

    ?>
    <script type="text/javascript">
        //<![CDATA[
        function wpcfFieldsEditorCallback(field_id) {
            var url = "<?php echo admin_url('admin-ajax.php'); ?>?action=wpcf_ajax&wpcf_action=editor_callback&field_id="+field_id+"&keepThis=true&TB_iframe=true&height=400&width=400";
            tb_show("<?php _e('Insert field',
            'wpcf'); ?>", url);
                }
                //]]>
    </script>
    <?php
}

/**
 * Creates form elements.
 * 
 * @param type $post
 * @param type $fields
 * @return type 
 */
function wpcf_admin_post_process_fields($post = false, $fields = array()) {

    // Get cached
    static $cache = array();
    $cache_key = $post ? $post->ID : false;
    if ($cache_key && isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $fields_processed = array();

    // Get invalid fields (if submitted)
    if ($post) {
        $invalid_fields = get_post_meta($post->ID, 'wpcf-invalid-fields', true);
        delete_post_meta($post->ID, 'wpcf-invalid-fields');
    }

    foreach ($fields as $field) {
        $field = wpcf_admin_fields_get_field($field['id']);
        if (!empty($field)) {

            $field_id = 'wpcf-' . $field['type'] . '-' . $field['slug'];
            $field_init_data = wpcf_fields_type_action($field['type']);

            // Get inherited field
            $inherited_field_data = false;
            if (isset($field_init_data['inherited_field_type'])) {
                $inherited_field_data = wpcf_fields_type_action($field_init_data['inherited_field_type']);
            }

            // Set value
            $field['value'] = '';
            if ($post) {
                $field['value'] = get_post_meta($post->ID,
                        'wpcf-' . $field['slug'], true);
            }

            // Apply filters
            $field['value'] = apply_filters('wpcf_fields_value_get',
                    $field['value'], $field, $field_init_data);
            $field['value'] = apply_filters('wpcf_fields_slug_' . $field['slug']
                    . '_value_get', $field['value'], $field, $field_init_data);
            $field['value'] = apply_filters('wpcf_fields_type_' . $field['type']
                    . '_value_get', $field['value'], $field, $field_init_data);

            // Process JS
            if (!empty($field_init_data['meta_box_js'])) {
                foreach ($field_init_data['meta_box_js'] as $handle => $data) {
                    if (isset($data['inline'])) {
                        add_action('admin_footer', $data['inline']);
                        continue;
                    }
                    $deps = !empty($data['deps']) ? $data['deps'] : array();
                    wp_enqueue_script($handle, $data['src'], $deps, WPCF_VERSION);
                }
            }

            // Process CSS
            if (!empty($field_init_data['meta_box_css'])) {
                foreach ($field_init_data['meta_box_css'] as $handle => $data) {
                    $deps = !empty($data['deps']) ? $data['deps'] : array();
                    wp_enqueue_style($handle, $data['src'], $deps, WPCF_VERSION);
                }
            }

            $element = array();

            // Set inherited values
            if ($inherited_field_data) {
                if (function_exists('wpcf_fields_'
                                . $field_init_data['inherited_field_type']
                                . '_meta_box_form')) {
                    $element = call_user_func('wpcf_fields_'
                            . $field_init_data['inherited_field_type']
                            . '_meta_box_form', $field);
                }
            }

            // Set generic values
            $element = array_merge(array(
                '#type' => isset($field_init_data['inherited_field_type']) ? $field_init_data['inherited_field_type'] : $field['type'],
                '#id' => $field_id,
                '#title' => $field['name'],
                '#description' => $field['description'],
                '#name' => 'wpcf[' . $field['slug'] . ']',
                '#value' => isset($field['value']) ? $field['value'] : '',
                'wpcf-id' => $field['id'],
                'wpcf-slug' => $field['slug'],
                'wpcf-type' => $field['type'],
                    ), $element);

            // Set specific values
            require_once WPCF_INC_ABSPATH . '/fields/' . $field['type']
                    . '.php';
            if (function_exists('wpcf_fields_' . $field['type']
                            . '_meta_box_form')) {
                $element_specific = call_user_func('wpcf_fields_'
                        . $field['type'] . '_meta_box_form', $field);
                // Check if it's single
                if (isset($element_specific['#type'])) {
                    $element = array_merge($element, $element_specific);
                } else { // More fields, loop all
                    foreach ($element_specific as $element_specific_fields_key => $element_specific_fields_value) {
                        // If no ID
                        if (!isset($element_specific_fields_value['#id'])) {
                            $element_specific_fields_value['#id'] = 'wpcf-'
                                    . $field['slug'] . '-' . mt_rand();
                        }
                        // If no name, name = #ignore or id = #ignore - IGNORE
                        if (!isset($element_specific_fields_value['#name'])
                                || $element_specific_fields_value['#name'] == '#ignore'
                                || $element_specific_fields_value['#id'] == '#ignore') {
                            $element_specific_fields_value['#id'] = 'wpcf-'
                                    . $field['slug'] . '-' . mt_rand();
                            $element_specific_fields_value['#name'] = 'wpcf[ignore][' . mt_rand() . ']';
                            $fields_processed[$element_specific_fields_value['#id']] = $element_specific_fields_value;
                            continue;
                        }
                        // This one is actually value and keep it (#name is required)
                        $element = array_merge($element,
                                $element_specific_fields_value);
                        // Add it here to keep order
                        $fields_processed[$element['#id']] = $element;
                    }
                }
            }

            // Set validation element
            if (isset($field['data']['validate'])) {
                $element['#validate'] = $field['data']['validate'];
            }

            // Check if it was invalid no submit and add error message
            if ($post && !empty($invalid_fields)) {
                if (isset($invalid_fields[$element['#id']]['#error'])) {
                    $element['#error'] = $invalid_fields[$element['#id']]['#error'];
                }
            }

            // Add to editor
            wpcf_admin_post_add_to_editor($field);

            // Add shortcode info
            $shortcode = '<div class="wpcf-shortcode">'
                    . __('Shortcode:', 'wpcf') . ' '
                    . '<span class="code">' . wpcf_fields_get_shortcode($field)
                    . '</span></div>';
            // @todo Check how 3 times get processed on saving
            if (isset($element['#after']) && strpos($element['#after'],
                            'class="wpcf-shortcode"') === FALSE) {
                $element['#after'] .= $shortcode;
            } else {
                $element['#after'] = $shortcode;
            }

            $fields_processed[$element['#id']] = $element;
        }
    }

    if ($cache_key && isset($cache[$cache_key])) {
        $cache[$cache_key] = $fields_processed;
    }

    return $fields_processed;
}

/**
 * Gets all groups and fields for post.
 * 
 * @param type $post_ID
 * @return type 
 */
function wpcf_admin_post_get_post_groups_fields($post = false) {
    $post_type = get_post_type();
    $groups = array();

    // Get by post_type
    $groups_by_post_type = wpcf_admin_get_groups_by_post_type($post_type, true);
    if (!empty($groups_by_post_type)) {
        foreach ($groups_by_post_type as $key => $group) {
            $groups[$group['id']] = $group;
            $groups[$group['id']]['fields'] = wpcf_admin_fields_get_fields_by_group($group['id']);
        }
    }

    // Get by taxonomy
    if ($post) {
        $taxonomies = get_taxonomies('', 'objects');
        foreach ($taxonomies as $tax_slug => $tax) {
            $terms = wp_get_post_terms($post->ID, $tax_slug,
                    array('fields' => 'ids'));
            foreach ($terms as $term_id) {
                $groups_by_term = wpcf_admin_fields_get_groups_by_term($term_id);
                if (!empty($groups_by_term)) {
                    foreach ($groups_by_term as $group) {
                        if (!isset($groups[$group['id']])) {
                            $groups[$group['id']] = $group;
                            $groups[$group['id']]['fields'] = wpcf_admin_fields_get_fields_by_group($group['id']);
                        }
                    }
                }
            }
        }
    } else {
        $groups_by_term = wpcf_admin_fields_get_groups_by_term();
        if (!empty($groups_by_term)) {
            foreach ($groups_by_term as $group) {
                if (!isset($groups[$group['id']])) {
                    $groups[$group['id']] = $group;
                    $groups[$group['id']]['fields'] = wpcf_admin_fields_get_fields_by_group($group['id']);
                }
            }
        }
    }

    $groups = apply_filters('wpcf_post_groups', $groups, $post);
    return $groups;
}

/**
 * Stores fields for editor menu.
 * 
 * @staticvar array $fields
 * @param type $field
 * @return array 
 */
function wpcf_admin_post_add_to_editor($field) {
    static $fields = array();
    if ($field == 'get') {
        return $fields;
    }
    if (empty($fields)) {
        add_action('admin_enqueue_scripts', 'wpcf_admin_post_add_to_editor_js');
    }
    $fields[] = $field;
}

/**
 * Renders JS for editor menu.
 * 
 * @return type 
 */
function wpcf_admin_post_add_to_editor_js() {
    $fields = wpcf_admin_post_add_to_editor('get');
    if (empty($fields)) {
        return false;
    }
    $editor_addon = new Editor_addon('types',
                    __('Insert Types Shortcode', 'wpcf'),
                    WPCF_RES_RELPATH . '/js/types_editor_plugin.js',
                    WPCF_RES_RELPATH . '/images/bw-logo-16.png');
    foreach ($fields as $field) {
        $data = wpcf_fields_type_action($field['type']);
        $callback = '';
        if (isset($data['editor_callback'])) {
            $callback = sprintf($data['editor_callback'], $field['id']);
        } else {
//        $callback = isset($field['wp_editor_callback']) ? $field['wp_editor_callback'] : '';
//        $args = isset($field['wp_editor_callback_args']) ? $field['wp_editor_callback_args'] : '';
            // Set callback if function exists
            // @todo Will all fields use AJAX popup callback? If not, adjust this
            $function = 'wpcf_fields_' . $field['type'] . '_editor_callback';
            $callback = function_exists($function) ? 'wpcfFieldsEditorCallback(' . $field['id'] . ')' : '';
        }

        $editor_addon->add_insert_shortcode_menu($field['name'],
                trim(wpcf_fields_get_shortcode($field), '[]'), '', $callback);
    }
    $editor_addon->render_js();
}