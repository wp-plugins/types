<?php
/*
 * Import/export data.
 */

/**
 * Import/Export form data.
 * 
 * @return type 
 */
function wpcf_admin_import_export_form() {
    $form = array();
    $form['wpnonce'] = array(
        '#type' => 'hidden',
        '#name' => '_wpnonce',
        '#value' => wp_create_nonce('wpcf_import'),
    );
    if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'],
                    'wpcf_import')) {
        if (isset($_POST['import-final'])) {
            if ($_POST['mode'] == 'file' && !empty($_POST['file'])
                    && file_exists(urldecode($_POST['file']))) {
                $data = @file_get_contents(urldecode($_POST['file']));
                if ($data) {
                    wpcf_admin_import_data(html_entity_decode($data));
                }
            }
            if ($_POST['mode'] == 'text' && !empty($_POST['text'])) {
                $charset = !empty($_POST['text-encoding']) ? $_POST['text-encoding'] : get_option('blog_charset');
                wpcf_admin_import_data(stripslashes(html_entity_decode($_POST['text'],
                                        ENT_QUOTES, $charset)));
            }
        } else if (isset($_POST['step'])) {
            $mode = 'none';
            $data = '';
            if (!empty($_POST['import-file']) && !empty($_FILES['file']['tmp_name'])) {
                $_FILES['file']['name'] .= '.txt';
                $_POST['action'] = 'wp_handle_upload';
                $uploaded_file = wp_handle_upload($_FILES['file'],
                        array(
                    'test_form' => false,
                    'upload_error_handler' => 'wpcf_admin_import_export_file_upload_error',
                        )
                );
                if (isset($uploaded_file['error'])) {
                    return array();
                }
                $data = @file_get_contents($uploaded_file['file']);
                $form['file'] = array(
                    '#type' => 'hidden',
                    '#name' => 'file',
                    '#value' => urlencode($uploaded_file['file']),
                );
                $mode = 'file';
            } else if (!empty($_POST['import-text']) && !empty($_POST['text'])) {
                $data = stripslashes($_POST['text']);
                if (preg_match('/encoding=("[^"]*"|\'[^\']*\')/s', $data, $match)) {
                    $charset = trim($match[1], '"');
                } else {
                    $charset = !empty($_POST['text-encoding']) ? $_POST['text-encoding'] : get_option('blog_charset');
                }
                $form['text'] = array(
                    '#type' => 'hidden',
                    '#name' => 'text',
                    '#value' => htmlentities(stripslashes($_POST['text']),
                            ENT_QUOTES, $charset),
                );
                $form['text-encoding'] = array(
                    '#type' => 'hidden',
                    '#name' => 'text-encoding',
                    '#value' => $charset,
                );
                $mode = 'text';
            }
            if (empty($data)) {
                echo '<div class="message error"><p>'
                . __('Data not valid', 'wpcf')
                . '</p></div>';
                return array();
            }
            $data = wpcf_admin_import_export_settings($data);
            if (empty($data)) {
                echo '<div class="message error"><p>'
                . __('Data not valid', 'wpcf')
                . '</p></div>';
                return array();
            }
            $form = array_merge($form, $data);
            $form['mode'] = array(
                '#type' => 'hidden',
                '#name' => 'mode',
                '#value' => $mode,
            );
            $form['import-final'] = array(
                '#type' => 'hidden',
                '#name' => 'import-final',
                '#value' => 1,
            );
            $form['submit'] = array(
                '#type' => 'submit',
                '#name' => 'import',
                '#value' => __('Import', 'wpcf'),
                '#attributes' => array('class' => 'button-primary'),
            );
        }
    } else {
        $form['submit'] = array(
            '#type' => 'submit',
            '#name' => 'export',
            '#value' => __('Export', 'wpcf'),
            '#attributes' => array('class' => 'button-primary'),
            '#before' => '<h2>' . __('Export Types data', 'wpcf') . '</h2>'
            . __('Download all custom fields, custom post types and taxonomies created by Types plugin.',
                    'wpcf') . '<br />',
            '#after' => '<br /><br />',
        );
        if (extension_loaded('simplexml')) {
            $form['file'] = array(
                '#type' => 'file',
                '#name' => 'file',
                '#prefix' => __('Upload XML file', 'wpcf') . '<br />',
                '#before' => '<h2>' . __('Import Types data file', 'wpcf') . '</h2>',
                '#inline' => true,
            );
            $form['submit-file'] = array(
                '#type' => 'submit',
                '#name' => 'import-file',
                '#value' => __('Import file', 'wpcf'),
                '#attributes' => array('class' => 'button-primary'),
                '#prefix' => '<br />',
                '#suffix' => '<br /><br />',
            );
            $form['text'] = array(
                '#type' => 'textarea',
                '#title' => __('Paste code here', 'wpcf'),
                '#name' => 'text',
                '#attributes' => array('rows' => 20),
                '#before' => '<h2>' . __('Import Types data text input', 'wpcf') . '</h2>',
            );
            $form['text-encoding'] = array(
                '#type' => 'textfield',
                '#title' => __('Encoding', 'wpcf'),
                '#name' => 'text-encoding',
                '#value' => get_option('blog_charset'),
                '#description' => __('If encoding is set in text input, it will override this setting.',
                        'wpcf'),
            );
            $form['submit-text'] = array(
                '#type' => 'submit',
                '#name' => 'import-text',
                '#value' => __('Import text', 'wpcf'),
                '#attributes' => array('class' => 'button-primary'),
            );
            $form['step'] = array(
                '#type' => 'hidden',
                '#name' => 'step',
                '#value' => 1,
            );
        } else {
            echo '<div class="message error"><p>'
            . __('PHP SimpleXML extension not loaded: Importing not available',
                    'wpcf')
            . '</p></div>';
        }
    }

    return $form;
}

/**
 * File upload error handler.
 * 
 * @param type $file
 * @param type $error_msg 
 */
function wpcf_admin_import_export_file_upload_error($file, $error_msg) {
    wpcf_admin_message(addslashes($error_msg), 'error');
}

/**
 * Import settings.
 * 
 * @global type $wpdb
 * @param SimpleXMLElement $data
 * @return string 
 */
function wpcf_admin_import_export_settings($data) {
    global $wpdb;
    $form = array();
    $form['title'] = array(
        '#type' => 'markup',
        '#markup' => '<h2>' . __('General Settings', 'wpcf') . '</h2>',
    );
    $form['overwrite-or-add-groups'] = array(
        '#type' => 'checkbox',
        '#title' => __('Bulk overwrite groups if exist', 'wpcf'),
        '#name' => 'overwrite-groups',
        '#inline' => true,
        '#after' => '<br />',
    );
    $form['delete-groups'] = array(
        '#type' => 'checkbox',
        '#title' => __("Delete group if don't exist", 'wpcf'),
        '#name' => 'delete-groups',
        '#inline' => true,
        '#after' => '<br />',
    );
    $form['delete-fields'] = array(
        '#type' => 'checkbox',
        '#title' => __("Delete field if don't exist", 'wpcf'),
        '#name' => 'delete-fields',
        '#inline' => true,
        '#after' => '<br />',
    );
    $form['delete-types'] = array(
        '#type' => 'checkbox',
        '#title' => __("Delete custom post type if don't exist", 'wpcf'),
        '#name' => 'delete-types',
        '#inline' => true,
        '#after' => '<br />',
    );
    $form['delete-tax'] = array(
        '#type' => 'checkbox',
        '#title' => __("Delete custom taxonomy if don't exist", 'wpcf'),
        '#name' => 'delete-tax',
        '#inline' => true,
        '#after' => '<br />',
    );
    libxml_use_internal_errors(true);
    $data = simplexml_load_string($data);
    if (!$data) {
        echo '<div class="message error"><p>' . __('Error parsing XML', 'wpcf') . '</p></div>';
        foreach (libxml_get_errors() as $error) {
            echo '<div class="message error"><p>' . $error->message . '</p></div>';
        }
        libxml_clear_errors();
        return false;
    }
//    $data = new SimpleXMLElement($data);
    // Check groups
    if (!empty($data->groups)) {
        $form['title-1'] = array(
            '#type' => 'markup',
            '#markup' => '<h2>' . __('Groups to be added/updated', 'wpcf') . '</h2>',
        );
        $groups_check = array();
        foreach ($data->groups->group as $group) {
            $group = (array) $group;
            $form['group-add-' . $group['ID']] = array(
                '#type' => 'checkbox',
                '#name' => 'groups[' . $group['ID'] . '][add]',
                '#default_value' => true,
                '#title' => '<strong>' . $group['post_title'] . '</strong>',
                '#inline' => true,
                '#after' => '<br /><br />',
            );
            //@todo Check if title needs some preparation
            $post = $wpdb->get_var($wpdb->prepare(
                            "SELECT ID FROM $wpdb->posts
                    WHERE post_title = %s AND post_type = %s",
                            $group['post_title'], $group['post_type']));
            if (!empty($post)) {
                $form['group-add-' . $group['ID']]['#after'] = wpcf_form_simple(
                        array('group-add-update-' . $group['ID'] => array(
                                '#type' => 'radios',
                                '#name' => 'groups[' . $group['ID'] . '][update]',
                                '#inline' => true,
                                '#options' => array(
                                    __('Update', 'wpcf') => 'update',
                                    __('Create new', 'wpcf') => 'add'
                                ),
                                '#default_value' => 'update',
                                '#before' => '<br />',
                                '#after' => '<br />',
                            )
                        )
                );
            }
            $groups_check[] = $group['post_title'];
        }
        $groups_existing = get_posts('post_type=wp-types-group&post_status=null');
        if (!empty($groups_existing)) {
            $groups_to_be_deleted = array();
            foreach ($groups_existing as $post) {
                if (!in_array($post->post_title, $groups_check)) {
                    $groups_to_be_deleted['<strong>' . $post->post_title . '</strong>'] = $post->ID;
                }
            }
            if (!empty($groups_to_be_deleted)) {
                $form['title-groups-deleted'] = array(
                    '#type' => 'markup',
                    '#markup' => '<h2>' . __('Groups to be deleted', 'wpcf') . '</h2>',
                );
                $form['groups-deleted'] = array(
                    '#type' => 'checkboxes',
                    '#name' => 'groups-to-be-deleted',
                    '#options' => $groups_to_be_deleted,
                );
            }
        }
    }

    // Check fields
    if (!empty($data->fields)) {
        $form['title-fields'] = array(
            '#type' => 'markup',
            '#markup' => '<h2>' . __('Fields to be added/updated', 'wpcf') . '</h2>',
        );
        $fields_existing = wpcf_admin_fields_get_fields();
        $fields_check = array();
        $fields_to_be_deleted = array();
        foreach ($data->fields->field as $field) {
            $field = (array) $field;
            $form['field-add-' . $field['id']] = array(
                '#type' => 'checkbox',
                '#name' => 'fields[' . $field['id'] . '][add]',
                '#default_value' => true,
                '#title' => '<strong>' . $field['name'] . '</strong>',
                '#inline' => true,
                '#after' => '<br />',
            );
            $fields_check[] = $field['id'];
        }

        foreach ($fields_existing as $field_id => $field) {
            if (!in_array($field_id, $fields_check)) {
                $fields_to_be_deleted['<strong>' . $field['name'] . '</strong>'] = $field['id'];
            }
        }

        if (!empty($fields_to_be_deleted)) {
            $form['title-fields-deleted'] = array(
                '#type' => 'markup',
                '#markup' => '<h2>' . __('Fields to be deleted', 'wpcf') . '</h2>',
            );
            $form['fields-deleted'] = array(
                '#type' => 'checkboxes',
                '#name' => 'fields-to-be-deleted',
                '#options' => $fields_to_be_deleted,
            );
        }
    }

    // Check types
    if (!empty($data->types)) {
        $form['title-types'] = array(
            '#type' => 'markup',
            '#markup' => '<h2>' . __('Custom post types to be added/updated', 'wpcf') . '</h2>',
        );
        $types_existing = get_option('wpcf-custom-types', array());
        $types_check = array();
        $types_to_be_deleted = array();
        foreach ($data->types->type as $type) {
            $type = (array) $type;
            $form['type-add-' . $type['id']] = array(
                '#type' => 'checkbox',
                '#name' => 'types[' . $type['id'] . '][add]',
                '#default_value' => true,
                '#title' => '<strong>' . $type['labels']->name . '</strong>',
                '#inline' => true,
                '#after' => '<br />',
            );
            $types_check[] = $type['id'];
        }

        foreach ($types_existing as $type_id => $type) {
            if (!in_array($type_id, $types_check)) {
                $types_to_be_deleted['<strong>' . $type['labels']['name'] . '</strong>'] = $type_id;
            }
        }

        if (!empty($types_to_be_deleted)) {
            $form['title-types-deleted'] = array(
                '#type' => 'markup',
                '#markup' => '<h2>' . __('Custom post types to be deleted', 'wpcf') . '</h2>',
            );
            $form['types-deleted'] = array(
                '#type' => 'checkboxes',
                '#name' => 'types-to-be-deleted',
                '#options' => $types_to_be_deleted,
            );
        }
    }

    // Check taxonomies
    if (!empty($data->taxonomies)) {
        $form['title-tax'] = array(
            '#type' => 'markup',
            '#markup' => '<h2>' . __('Custom taxonomies to be added/updated',
                    'wpcf') . '</h2>',
        );
        $taxonomies_existing = get_option('wpcf-custom-taxonomies', array());
        $taxonomies_check = array();
        $taxonomies_to_be_deleted = array();
        foreach ($data->taxonomies->taxonomy as $taxonomy) {
            $taxonomy = (array) $taxonomy;
            $form['taxonomy-add-' . $taxonomy['id']] = array(
                '#type' => 'checkbox',
                '#name' => 'taxonomies[' . $taxonomy['id'] . '][add]',
                '#default_value' => true,
                '#title' => '<strong>' . $taxonomy['labels']->name . '</strong>',
                '#inline' => true,
                '#after' => '<br />',
            );
            $taxonomies_check[] = $taxonomy['id'];
        }

        foreach ($taxonomies_existing as $taxonomy_id => $taxonomy) {
            if (!in_array($taxonomy_id, $taxonomies_check)) {
                $taxonomies_to_be_deleted['<strong>' . $taxonomy['labels']['name'] . '</strong>'] = $taxonomy_id;
            }
        }

        if (!empty($taxonomies_to_be_deleted)) {
            $form['title-taxonomies-deleted'] = array(
                '#type' => 'markup',
                '#markup' => '<h2>' . __('Custom taxonomies to be deleted',
                        'wpcf') . '</h2>',
            );
            $form['taxonomies-deleted'] = array(
                '#type' => 'checkboxes',
                '#name' => 'taxonomies-to-be-deleted',
                '#options' => $taxonomies_to_be_deleted,
            );
        }
    }

    return $form;
}

/**
 * Exports data to XML.
 */
function wpcf_admin_export_data() {
    require_once WPCF_ABSPATH . '/classes/array2xml.php';
    $xml = new Wpcf_Array2XML();
    $data = array();

    // Get groups
    $groups = get_posts('post_type=wp-types-group&post_status=null');
    if (!empty($groups)) {
        $data['groups'] = array('__key' => 'group');
        foreach ($groups as $key => $post) {
            $post = (array) $post;
            $post_data = array();
            $copy_data = array('ID', 'post_content', 'post_title',
                'post_excerpt', 'post_type', 'post_status');
            foreach ($copy_data as $copy) {
                if (isset($post[$copy])) {
                    $post_data[$copy] = $post[$copy];
                }
            }
            $data['groups']['group-' . $post['ID']] = $post_data;
            $meta = get_post_custom($post['ID']);
            if (!empty($meta)) {
                $data['groups']['group-' . $post['ID']]['meta'] = array();
                foreach ($meta as $meta_key => $meta_value) {
                    if (in_array($meta_key,
                                    array('_wp_types_group_terms',
                                '_wp_types_group_post_types',
                                '_wp_types_group_fields'))) {
                        $data['groups']['group-' . $post['ID']]['meta'][$meta_key] = $meta_value[0];
                    }
                }
                if (empty($data['groups']['group-' . $post['ID']]['meta'])) {
                    unset($data['groups']['group-' . $post['ID']]['meta']);
                }
            }
        }
    }

    // Get fields
    $fields = wpcf_admin_fields_get_fields();
    if (!empty($fields)) {
        $data['fields'] = $fields;
        $data['fields']['__key'] = 'field';
    }

    // Get custom types
    $custom_types = get_option('wpcf-custom-types', array());
    if (!empty($custom_types)) {
        foreach ($custom_types as $key => $type) {
            $custom_types[$key]['id'] = $key;
        }
        $data['types'] = $custom_types;
        $data['types']['__key'] = 'type';
    }

    // Get custom tax
    $custom_taxonomies = get_option('wpcf-custom-taxonomies', array());
    if (!empty($custom_taxonomies)) {
        foreach ($custom_taxonomies as $key => $tax) {
            $custom_taxonomies[$key]['id'] = $key;
        }
        $data['taxonomies'] = $custom_taxonomies;
        $data['taxonomies']['__key'] = 'taxonomy';
    }

    // Offer for download
    $data = $xml->array2xml($data);

    $sitename = sanitize_key(get_bloginfo('name'));
    if (!empty($sitename)) {
        $sitename .= '.';
    }
    $filename = $sitename . 'types.' . date('Y-m-d') . '.xml';

    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
    echo $data;
    die();
}

/**
 * Imports data from XML.
 */
function wpcf_admin_import_data($data = '') {
    global $wpdb;
//    $data = new SimpleXMLElement($data);
    libxml_use_internal_errors(true);
    $data = simplexml_load_string($data);
    if (!$data) {
        echo '<div class="message error"><p>' . __('Error parsing XML', 'wpcf') . '</p></div>';
        foreach (libxml_get_errors() as $error) {
            echo '<div class="message error"><p>' . $error->message . '</p></div>';
        }
        libxml_clear_errors();
        return false;
    }
    $overwrite_groups = isset($_POST['overwrite-groups']);
    $overwrite_fields = isset($_POST['overwrite-fields']);
    $overwrite_types = isset($_POST['overwrite-types']);
    $overwrite_tax = isset($_POST['overwrite-tax']);
    $delete_groups = isset($_POST['delete-groups']);
    $delete_fields = isset($_POST['delete-fields']);
    $delete_types = isset($_POST['delete-types']);
    $delete_tax = isset($_POST['delete-tax']);

    // Process groups

    if (!empty($data->groups)) {
        $groups = array();
        // Set insert data from XML
        foreach ($data->groups->group as $group) {
            $group = wpcf_admin_import_export_simplexml2array($group);
            $groups[$group['ID']] = $group;
        }
        // Set insert data from POST
        if (!empty($_POST['groups'])) {
            foreach ($_POST['groups'] as $group_id => $group) {
                if (empty($groups[$group_id])) {
                    continue;
                }
                $groups[$group_id]['add'] = !empty($group['add']);
                $groups[$group_id]['update'] = (isset($group['update']) && $group['update'] == 'update') ? true : false;
            }
        }

        // Insert groups
        $groups_check = array();
        foreach ($groups as $group_id => $group) {
            $post = array(
                'post_status' => $group['post_status'],
                'post_type' => 'wp-types-group',
                'post_title' => $group['post_title'],
                'post_content' => !empty($group['post_content']) ? $group['post_content'] : '',
            );
            if ($group['add']) {
                $post_to_update = $wpdb->get_var($wpdb->prepare(
                                "SELECT ID FROM $wpdb->posts
                    WHERE post_title = %s AND post_type = %s",
                                $group['post_title'], 'wp-types-group'));
                // Update (may be forced by bulk action)
                if ($group['update'] || ($overwrite_groups && !empty($post_to_update))) {
                    if (!empty($post_to_update)) {
                        $post['ID'] = $post_to_update;
                        $group_wp_id = wp_update_post($post);
                        if (!$group_wp_id) {
                            wpcf_admin_message_store(sprintf(__('Group "%s" update failed',
                                                    'wpcf'),
                                            $group['post_title']), 'error');
                        } else {
                            wpcf_admin_message_store(sprintf(__('Group "%s" updated',
                                                    'wpcf'),
                                            $group['post_title']));
                        }
                    } else {
                        wpcf_admin_message_store(sprintf(__('Group "%s" update failed',
                                                'wpcf'), $group['post_title']),
                                'error');
                    }
                } else { // Insert
                    $group_wp_id = wp_insert_post($post, true);
                    if (is_wp_error($group_wp_id)) {
                        wpcf_admin_message_store(sprintf(__('Group "%s" insert failed',
                                                'wpcf'), $group['post_title']),
                                'error');
                    } else {
                        wpcf_admin_message_store(sprintf(__('Group "%s" added',
                                                'wpcf'), $group['post_title']));
                    }
                }
                // Update meta
                if (!empty($group['meta'])) {
                    foreach ($group['meta'] as $meta_key => $meta_value) {
                        update_post_meta($group_wp_id, $meta_key, $meta_value);
                    }
                }
                $group_check[] = $group_wp_id;
                if (!empty($post_to_update)) {
                    $group_check[] = $post_to_update;
                }
            }
        }
        // Delete groups (forced, set in bulk actions)
        if ($delete_groups) {
            $groups_to_delete = get_posts('post_type=wp-types-group&status=null');
            if (!empty($groups_to_delete)) {
                foreach ($groups_to_delete as $group_to_delete) {
                    if (!in_array($group_to_delete->ID, $group_check)) {
                        $deleted = wp_delete_post($group_to_delete->ID, true);
                        if (!$deleted) {
                            wpcf_admin_message_store(sprintf(__('Group "%s" delete failed',
                                                    'wpcf'),
                                            $group_to_delete->post_title),
                                    'error');
                        } else {
                            wpcf_admin_message_store(sprintf(__('Group "%s" deleted',
                                                    'wpcf'),
                                            $group_to_delete->post_title));
                        }
                    }
                }
            }
        } else { // If not forced, look in POST
            if (!empty($_POST['groups-to-be-deleted'])) {
                foreach ($_POST['groups-to-be-deleted'] as $group_to_delete) {
                    $group_to_delete_post = get_post($group_to_delete);
                    if (!empty($group_to_delete_post) && $group_to_delete_post->post_type == 'wp-types-group') {
                        $deleted = wp_delete_post($group_to_delete, true);
                        if (!$deleted) {
                            wpcf_admin_message_store(sprintf(__('Group "%s" delete failed',
                                                    'wpcf'),
                                            $group_to_delete_post->post_title),
                                    'error');
                        } else {
                            wpcf_admin_message_store(sprintf(__('Group "%s" deleted',
                                                    'wpcf'),
                                            $group_to_delete_post->post_title));
                        }
                    } else {
                        wpcf_admin_message_store(sprintf(__('Group "%s" delete failed',
                                                'wpcf'), $group_to_delete),
                                'error');
                    }
                }
            }
        }
    }

    // Process fields

    if (!empty($data->fields)) {
        $fields_existing = wpcf_admin_fields_get_fields();
        $fields = array();
        $fields_check = array();
        // Set insert data from XML
        foreach ($data->fields->field as $field) {
            $field = wpcf_admin_import_export_simplexml2array($field);
            $fields[$field['id']] = $field;
        }
        // Set insert data from POST
        if (!empty($_POST['fields'])) {
            foreach ($_POST['fields'] as $field_id => $field) {
                if (empty($fields[$field_id])) {
                    continue;
                }
                $fields[$field_id]['add'] = !empty($field['add']);
                $fields[$field_id]['update'] = (isset($field['update']) && $field['update'] == 'update') ? true : false;
            }
        }
        // Insert fields
        foreach ($fields as $field_id => $field) {
            if (!$field['add'] && !$overwrite_fields) {
                continue;
            }
            $field_data = array();
            $field_data['name'] = $field['name'];
            $field_data['description'] = $field['description'];
            $field_data['type'] = $field['type'];
            $field_data['slug'] = $field['slug'];
            $field_data['data'] = (isset($field['data']) && is_array($field['data'])) ? $field['data'] : array();
            $fields_existing[$field_id] = $field_data;
            $fields_check[] = $field_id;

            wpcf_admin_message_store(sprintf(__('Field "%s" added/updated',
                                    'wpcf'), $field['name']));
        }
        // Delete fields
        if ($delete_fields) {
            foreach ($fields_existing as $k => $v) {
                if (!in_array($k, $fields_check)) {
                    wpcf_admin_message_store(sprintf(__('Field "%s" deleted',
                                            'wpcf'),
                                    $fields_existing[$k]['name']));
                    unset($fields_existing[$k]);
                }
            }
        } else {
            if (!empty($_POST['fields-to-be-deleted'])) {
                foreach ($_POST['fields-to-be-deleted'] as $field_to_delete) {
                    wpcf_admin_message_store(sprintf(__('Field "%s" deleted',
                                            'wpcf'),
                                    $fields_existing[$field_to_delete]['name']));
                    unset($fields_existing[$field_to_delete]);
                }
            }
        }
        update_option('wpcf-fields', $fields_existing);
    }

    // Process types

    if (!empty($data->types)) {
        $types_existing = get_option('wpcf-custom-types', array());
        $types = array();
        $types_check = array();
        // Set insert data from XML
        foreach ($data->types->type as $type) {
            $type = wpcf_admin_import_export_simplexml2array($type);
            $types[$type['id']] = $type;
        }
        // Set insert data from POST
        if (!empty($_POST['types'])) {
            foreach ($_POST['types'] as $type_id => $type) {
                if (empty($types[$type_id])) {
                    continue;
                }
                $types[$type_id]['add'] = !empty($type['add']);
                $types[$type_id]['update'] = (isset($type['update']) && $type['update'] == 'update') ? true : false;
            }
        }
        // Insert types
        foreach ($types as $type_id => $type) {
            if (!$type['add'] && !$overwrite_types) {
                continue;
            }
            unset($type['add'], $type['update']);
            $types_existing[$type_id] = $type;
            $types_check[] = $type_id;
            wpcf_admin_message_store(sprintf(__('Custom post type "%s" added/updated',
                                    'wpcf'), $type_id));
        }
        // Delete types
        if ($delete_types) {
            foreach ($types_existing as $k => $v) {
                if (!in_array($k, $types_check)) {
                    unset($types_existing[$k]);
                    wpcf_admin_message_store(sprintf(__('Custom post type "%s" deleted',
                                            'wpcf'), $k));
                }
            }
        } else {
            if (!empty($_POST['types-to-be-deleted'])) {
                foreach ($_POST['types-to-be-deleted'] as $type_to_delete) {
                    wpcf_admin_message_store(sprintf(__('Custom post type "%s" deleted',
                                            'wpcf'),
                                    $types_existing[$type_to_delete]['labels']['name']));
                    unset($types_existing[$type_to_delete]);
                }
            }
        }
        update_option('wpcf-custom-types', $types_existing);
    }

    // Process taxonomies

    if (!empty($data->taxonomies)) {
        $taxonomies_existing = get_option('wpcf-custom-taxonomies', array());
        $taxonomies = array();
        $taxonomies_check = array();
        // Set insert data from XML
        foreach ($data->taxonomies->taxonomy as $taxonomy) {
            $taxonomy = wpcf_admin_import_export_simplexml2array($taxonomy);
            $taxonomies[$taxonomy['id']] = $taxonomy;
        }
        // Set insert data from POST
        if (!empty($_POST['taxonomies'])) {
            foreach ($_POST['taxonomies'] as $taxonomy_id => $taxonomy) {
                if (empty($taxonomies[$taxonomy_id])) {
                    continue;
                }
                $taxonomies[$taxonomy_id]['add'] = !empty($taxonomy['add']);
                $taxonomies[$taxonomy_id]['update'] = (isset($taxonomy['update']) && $taxonomy['update'] == 'update') ? true : false;
            }
        }
        // Insert taxonomies
        foreach ($taxonomies as $taxonomy_id => $taxonomy) {
            if (!$taxonomy['add'] && !$overwrite_tax) {
                continue;
            }
            unset($taxonomy['add'], $taxonomy['update']);
            $taxonomies_existing[$taxonomy_id] = $taxonomy;
            $taxonomies_check[] = $taxonomy_id;
            wpcf_admin_message_store(sprintf(__('Custom taxonomy "%s" added/updated',
                                    'wpcf'), $taxonomy_id));
        }
        // Delete taxonomies
        if ($delete_tax) {
            foreach ($taxonomies_existing as $k => $v) {
                if (!in_array($k, $taxonomies_check)) {
                    unset($taxonomies_existing[$k]);
                    wpcf_admin_message_store(sprintf(__('Custom taxonomy "%s" deleted',
                                            'wpcf'), $k));
                }
            }
        } else {
            if (!empty($_POST['taxonomies-to-be-deleted'])) {
                foreach ($_POST['taxonomies-to-be-deleted'] as $taxonomy_to_delete) {
                    wpcf_admin_message_store(sprintf(__('Custom taxonomy "%s" deleted',
                                            'wpcf'),
                                    $taxonomies_existing[$taxonomy_to_delete]['labels']['name']));
                    unset($taxonomies_existing[$taxonomy_to_delete]);
                }
            }
        }
        update_option('wpcf-custom-taxonomies', $taxonomies_existing);
    }
    echo '<script type="text/javascript">
<!--
window.location = "' . admin_url('admin.php?page=wpcf-import-export') . '"
//-->
</script>';
    die();
}

/**
 * Loops over elements and convert to array or empty string.
 * 
 * @param type $element
 * @return string 
 */
function wpcf_admin_import_export_simplexml2array($element) {
    $element = is_string($element) ? trim($element) : $element;
    if (!empty($element) && is_object($element)) {
        $element = (array) $element;
    }
    if (empty($element)) {
        $element = '';
    } else if (is_array($element)) {
        foreach ($element as $k => $v) {
            $v = is_string($v) ? trim($v) : $v;
            if (empty($v)) {
                $element[$k] = '';
                continue;
            }
            $add = wpcf_admin_import_export_simplexml2array($v);
            if (!empty($add)) {
                $element[$k] = $add;
            } else {
                $element[$k] = '';
            }
        }
    }

    if (empty($element)) {
        $element = '';
    }

    return $element;
}