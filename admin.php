<?php
/*
 * Admin functions
 */
add_action('init', 'wpcf_admin_init_hook');
add_action('admin_menu', 'wpcf_admin_menu_hook');
if (defined('DOING_AJAX')) {
    require_once WPCF_INC_ABSPATH . '/ajax.php';
}

/**
 * admin_init hook.
 */
function wpcf_admin_init_hook() {
//    add_action('admin_footer', create_function('$a=""', 'echo "<pre>"; print_r(get_option("wpcf-fields")); echo "</pre>";'));
}

/**
 * admin_menu hook.
 */
function wpcf_admin_menu_hook() {
    add_menu_page('Types', 'Types', 'manage_options', 'wpcf',
            'wpcf_admin_menu_summary', WPCF_RES_RELPATH . '/images/logo-16.png');
    $hook = add_submenu_page('wpcf', __('Custom Fields', 'wpcf'),
            __('Custom Fields', 'wpcf'), 'manage_options', 'wpcf',
            'wpcf_admin_menu_summary');
    add_contextual_help($hook, wpcf_admin_plugin_help('wpcf'));
    add_action('load-' . $hook, 'wpcf_admin_menu_summary_hook');
    // Custom types and tax
    $hook = add_submenu_page('wpcf', __('Custom Types and Taxonomies', 'wpcf'),
            __('Custom Types and Taxonomies', 'wpcf'), 'manage_options',
            'wpcf-ctt', 'wpcf_admin_menu_summary_ctt');
    add_action('load-' . $hook, 'wpcf_admin_menu_summary_ctt_hook');
    add_contextual_help($hook, wpcf_admin_plugin_help('wpcf-ctt'));
    // Import/Export
    $hook = add_submenu_page('wpcf', __('Import/Export', 'wpcf'),
            __('Import/Export', 'wpcf'), 'manage_options', 'wpcf-import-export',
            'wpcf_admin_menu_import_export');
    add_action('load-' . $hook, 'wpcf_admin_menu_import_export_hook');
    add_contextual_help($hook, wpcf_admin_plugin_help('wpcf-import-export'));
    // Custom Fields Control
    $hook = add_submenu_page('wpcf', __('Custom Fields Control', 'wpcf'),
            __('Custom Fields Control', 'wpcf'), 'manage_options', 'wpcf-custom-fields-control',
            'wpcf_admin_menu_custom_fields_control');
    add_action('load-' . $hook, 'wpcf_admin_menu_custom_fields_control_hook');
    add_contextual_help($hook, wpcf_admin_plugin_help('wpcf-custom-fields-control'));

    if (isset($_GET['page'])) {
        switch ($_GET['page']) {
            case 'wpcf-edit':
                $title = isset($_GET['group_id']) ? __('Edit Group', 'wpcf') : __('Add New Group',
                                'wpcf');
                $hook = add_submenu_page('wpcf', $title, $title,
                        'manage_options', 'wpcf-edit',
                        'wpcf_admin_menu_edit_fields');
                add_action('load-' . $hook, 'wpcf_admin_menu_edit_fields_hook');
                add_contextual_help($hook, wpcf_admin_plugin_help('wpcf-edit'));
                break;

            case 'wpcf-edit-type':
                $title = isset($_GET['wpcf-post-type']) ? __('Edit Custom Post Type',
                                'wpcf') : __('Add New Custom Post Type', 'wpcf');
                $hook = add_submenu_page('wpcf', $title, $title,
                        'manage_options', 'wpcf-edit-type',
                        'wpcf_admin_menu_edit_type');
                add_action('load-' . $hook, 'wpcf_admin_menu_edit_type_hook');
                add_contextual_help($hook, wpcf_admin_plugin_help('wpcf-edit-type'));
                break;

            case 'wpcf-edit-tax':
                $title = isset($_GET['wpcf-tax']) ? __('Edit Taxonomy', 'wpcf') : __('Add New Taxonomy',
                                'wpcf');
                $hook = add_submenu_page('wpcf', $title, $title,
                        'manage_options', 'wpcf-edit-tax',
                        'wpcf_admin_menu_edit_tax');
                add_action('load-' . $hook, 'wpcf_admin_menu_edit_tax_hook');
                add_contextual_help($hook, wpcf_admin_plugin_help('wpcf-edit-tax'));
                break;
        }
    }
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_summary_hook() {
    wp_enqueue_script('wpcf-fields-edit', WPCF_RES_RELPATH . '/js/basic.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable'),
            WPCF_VERSION);
    wp_enqueue_style('wpcf-fields-edit', WPCF_RES_RELPATH . '/css/basic.css',
            array(), WPCF_VERSION);
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_summary() {
    echo wpcf_add_admin_header(__('Custom Fields', 'wpcf'));
    require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/fields-list.php';
    wpcf_admin_fields_list();
    echo wpcf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_edit_fields_hook() {
    wp_enqueue_script('wpcf-fields-edit', WPCF_EMBEDDED_RES_RELPATH . '/js/basic.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable'),
            WPCF_VERSION);
    wp_enqueue_style('wpcf-fields-edit', WPCF_EMBEDDED_RES_RELPATH . '/css/basic.css',
            array(), WPCF_VERSION);
    wp_enqueue_script('wpcf-form-validation',
            WPCF_EMBEDDED_RES_RELPATH . '/js/'
            . 'jquery-form-validation/jquery.validate.min.js', array('jquery'),
            WPCF_VERSION);
    wp_enqueue_script('wpcf-form-validation-additional',
            WPCF_EMBEDDED_RES_RELPATH . '/js/'
            . 'jquery-form-validation/additional-methods.min.js',
            array('jquery'), WPCF_VERSION);
    wp_enqueue_style('wpcf-scroll',
            WPCF_EMBEDDED_RELPATH . '/common/visual-editor/res/css/scroll.css');
    wp_enqueue_script('wpcf-scrollbar',
            WPCF_EMBEDDED_RELPATH . '/common/visual-editor/res/js/scrollbar.js',
            array('jquery'));
    wp_enqueue_script('wpcf-mousewheel',
            WPCF_EMBEDDED_RELPATH . '/common/visual-editor/res/js/mousewheel.js',
            array('wpcf-scrollbar'));
    add_action('admin_footer', 'wpcf_admin_fields_form_js_validation');
    require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/fields-form.php';
    $form = wpcf_admin_fields_form();
    wpcf_form('wpcf_form_fields', $form);
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_edit_fields() {
    if (isset($_GET['group_id'])) {
        $title = __('Edit Group', 'wpcf');
    } else {
        $title = __('Add New Group', 'wpcf');
    }
    echo wpcf_add_admin_header($title);
    $form = wpcf_form('wpcf_form_fields');
    echo '<br /><form method="post" action="" class="wpcf-fields-form '
    . 'wpcf-form-validate">';
    echo $form->renderForm();
    echo '</form>';
    echo wpcf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_summary_ctt_hook() {
    wp_enqueue_script('wpcf-ctt', WPCF_RES_RELPATH . '/js/basic.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable'),
            WPCF_VERSION);
    wp_enqueue_style('wpcf-ctt', WPCF_RES_RELPATH . '/css/basic.css', array(),
            WPCF_VERSION);
    require_once WPCF_INC_ABSPATH . '/custom-types.php';
    require_once WPCF_INC_ABSPATH . '/custom-taxonomies.php';
    require_once WPCF_INC_ABSPATH . '/custom-types-taxonomies-list.php';
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_summary_ctt() {
    echo wpcf_add_admin_header(__('Custom Post Types and Taxonomies', 'wpcf'));
    wpcf_admin_ctt_list();
    echo wpcf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_edit_type_hook() {
    wp_enqueue_script('wpcf-fields-edit', WPCF_RES_RELPATH . '/js/basic.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable'),
            WPCF_VERSION);
    wp_enqueue_style('wpcf-type-edit', WPCF_RES_RELPATH . '/css/basic.css',
            array(), WPCF_VERSION);
    wp_enqueue_script('wpcf-form-validation',
            WPCF_RES_RELPATH . '/js/'
            . 'jquery-form-validation/jquery.validate.min.js', array('jquery'),
            WPCF_VERSION);
    wp_enqueue_script('wpcf-form-validation-additional',
            WPCF_RES_RELPATH . '/js/'
            . 'jquery-form-validation/additional-methods.min.js',
            array('jquery'), WPCF_VERSION);
    add_action('admin_footer', 'wpcf_admin_types_form_js_validation');
    require_once WPCF_INC_ABSPATH . '/custom-types.php';
    require_once WPCF_INC_ABSPATH . '/custom-types-form.php';
    $form = wpcf_admin_custom_types_form();
    wpcf_form('wpcf_form_types', $form);
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_edit_type() {
    if (isset($_GET['wpcf-post-type'])) {
        $title = __('Edit Custom Post Type', 'wpcf');
    } else {
        $title = __('Add New Custom Post Type', 'wpcf');
    }
    echo wpcf_add_admin_header($title);
    $form = wpcf_form('wpcf_form_types');
    echo '<br /><form method="post" action="" class="wpcf-types-form '
    . 'wpcf-form-validate">';
    echo $form->renderForm();
    echo '</form>';
    echo wpcf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_edit_tax_hook() {
    wp_enqueue_script('wpcf-tax-edit', WPCF_RES_RELPATH . '/js/basic.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable'),
            WPCF_VERSION);
    wp_enqueue_style('wpcf-tax-edit', WPCF_RES_RELPATH . '/css/basic.css',
            array(), WPCF_VERSION);
    wp_enqueue_script('wpcf-form-validation',
            WPCF_RES_RELPATH . '/js/'
            . 'jquery-form-validation/jquery.validate.min.js', array('jquery'),
            WPCF_VERSION);
    wp_enqueue_script('wpcf-form-validation-additional',
            WPCF_RES_RELPATH . '/js/'
            . 'jquery-form-validation/additional-methods.min.js',
            array('jquery'), WPCF_VERSION);
    add_action('admin_footer', 'wpcf_admin_tax_form_js_validation');
    require_once WPCF_INC_ABSPATH . '/custom-taxonomies.php';
    require_once WPCF_INC_ABSPATH . '/custom-taxonomies-form.php';
    $form = wpcf_admin_custom_taxonomies_form();
    wpcf_form('wpcf_form_tax', $form);
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_edit_tax() {
    if (isset($_GET['wpcf-tax'])) {
        $title = __('Edit Taxonomy', 'wpcf');
    } else {
        $title = __('Add New Taxonomy', 'wpcf');
    }
    echo wpcf_add_admin_header($title);
    $form = wpcf_form('wpcf_form_tax');
    echo '<br /><form method="post" action="" class="wpcf-tax-form '
    . 'wpcf-form-validate">';
    echo $form->renderForm();
    echo '</form>';
    echo wpcf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_import_export_hook() {
    wp_enqueue_style('wpcf-import-export', WPCF_RES_RELPATH . '/css/basic.css',
            array(), WPCF_VERSION);
    require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/import-export.php';
    if (extension_loaded('simplexml') && isset($_POST['export'])
            && wp_verify_nonce($_POST['_wpnonce'], 'wpcf_import')) {
        wpcf_admin_export_data();
        die();
    }
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_import_export() {
    echo wpcf_add_admin_header(__('Import/Export', 'wpcf'));
    echo '<br /><form method="post" action="" class="wpcf-import-export-form '
    . 'wpcf-form-validate" enctype="multipart/form-data">';
    echo wpcf_form_simple(wpcf_admin_import_export_form());
    echo '</form>';
    echo wpcf_add_admin_footer();
}

/**
 * Menu page hook.
 */
function wpcf_admin_menu_custom_fields_control_hook() {
    add_action('admin_head', 'wpcf_admin_custom_fields_control_js');
    add_thickbox();
    wp_enqueue_script('wpcf-fields-edit', WPCF_RES_RELPATH . '/js/basic.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable'),
            WPCF_VERSION);
    wp_enqueue_style('wpcf-custom-fields-control', WPCF_RES_RELPATH . '/css/basic.css',
            array(), WPCF_VERSION);
    require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_EMBEDDED_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/fields-control.php';
    
    if (isset($_REQUEST['_wpnonce'])
            && wp_verify_nonce($_REQUEST['_wpnonce'], 'custom_fields_control_bulk')
            && (isset($_POST['action']) || isset($_POST['action2'])) && !empty($_POST['fields'])) {
        // @todo Is this right action
        $action = $_POST['action'] == '-1' ? $_POST['action2'] : $_POST['action'];
        wpcf_admin_custom_fields_control_bulk_actions($action);
    }
    
    global $wpcf_control_table;
    $wpcf_control_table = new WPCF_Custom_Fields_Control_Table(array(
                'ajax' => true,
                'singular' => __('Custom Field', 'wpcf'),
                'plural' => __('Custom Fields', 'wpcf'),
            ));
    $wpcf_control_table->prepare_items();
}

/**
 * Menu page display.
 */
function wpcf_admin_menu_custom_fields_control() {
    global $wpcf_control_table;
    echo wpcf_add_admin_header(__('Custom Fields Control', 'wpcf'));
    echo '<br /><form method="post" action="" id="wpcf-custom-fields-control-form" class="wpcf-custom-fields-control-form '
    . 'wpcf-form-validate" enctype="multipart/form-data">';
    echo wpcf_admin_custom_fields_control_form($wpcf_control_table);
    wp_nonce_field('custom_fields_control_bulk');
    echo '</form>';
    echo wpcf_add_admin_footer();
}

/**
 * Adds typical header on admin pages.
 *
 * @param string $title
 * @param string $icon_id Custom icon
 * @return string
 */
function wpcf_add_admin_header($title, $icon_id = 'icon-wpcf') {
    echo "\r\n" . '<div class="wrap">
	<div id="' . $icon_id . '" class="icon32"><br /></div>
    <h2>' . $title . '</h2>' . "\r\n";
    do_action('wpcf_admin_header');
    do_action('wpcf_admin_header_' . $_GET['page']);
}

/**
 * Adds footer on admin pages.
 *
 * <b>Strongly recomended</b> if wpcf_add_admin_header() is called before.
 * Otherwise invalid HTML formatting will occur.
 */
function wpcf_add_admin_footer() {
    do_action('wpcf_admin_footer_' . $_GET['page']);
    do_action('wpcf_admin_footer');
    echo "\r\n" . '</div>' . "\r\n";
}

/**
 * Returns HTML formatted 'widefat' table.
 * 
 * @param type $ID
 * @param type $header
 * @param type $rows
 * @param type $empty_message 
 */
function wpcf_admin_widefat_table($ID, $header, $rows = array(),
        $empty_message = 'No results') {
    $head = '';
    $footer = '';
    foreach ($header as $key => $value) {
        $head .= '<th id="wpcf-table-' . $key . '">' . $value . '</th>' . "\r\n";
        $footer .= '<th>' . $value . '</th>' . "\r\n";
    }
    echo '<table id="' . $ID . '" class="widefat" cellspacing="0">
            <thead>
                <tr>
                  ' . $head . '
                </tr>
            </thead>
            <tfoot>
                <tr>
                  ' . $footer . '
                </tr>
            </tfoot>
            <tbody>
              ';
    $row = '';
    if (empty($rows)) {
        echo '<tr><td colspan="' . count($header) . '">' . $empty_message
        . '</td></tr>';
    } else {
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $column_name => $column_value) {
                echo '<td class="wpcf-table-column-' . $column_name . '">';
                echo $column_value;
                echo '</td>' . "\r\n";
            }
            echo '</tr>' . "\r\n";
        }
    }
    echo '
            </tbody>
          </table>' . "\r\n";
}

/**
 * Saves open fieldsets.
 * 
 * @param type $action
 * @param type $fieldset
 */
function wpcf_admin_form_fieldset_save_toggle($action, $fieldset) {
    $data = get_user_meta(get_current_user_id(), 'wpcf-form-fieldsets-toggle',
            true);
    if ($action == 'open') {
        $data[$fieldset] = 1;
    } else if ($action == 'close') {
        unset($data[$fieldset]);
    }
    update_user_meta(get_current_user_id(), 'wpcf-form-fieldsets-toggle', $data);
}

/**
 * Check if fieldset is saved as open.
 * 
 * @param type $fieldset
 */
function wpcf_admin_form_fieldset_is_collapsed($fieldset) {
    $data = get_user_meta(get_current_user_id(), 'wpcf-form-fieldsets-toggle',
            true);
    if (empty($data)) {
        return true;
    }
    return array_key_exists($fieldset, $data) ? false : true;
}

/**
 * Adds help on admin pages.
 * 
 * @param type $contextual_help
 * @param type $screen_id
 * @param type $screen
 * @return type 
 */
function wpcf_admin_plugin_help($page) {
    $call = false;
    $contextual_help = '';
    $page = $page;
    if (isset($page)) {
        switch ($page) {
            case 'wpcf':
                $call = 'custom_fields';
                break;

            case 'wpcf-ctt':
                $call = 'custom_types_and_taxonomies';
                break;

            case 'wpcf-import-export':
                $call = 'import_export';
                break;

            case 'wpcf-edit':
                $call = 'edit_group';
                break;

            case 'wpcf-edit-type':
                $call = 'edit_type';
                break;

            case 'wpcf-edit-tax':
                $call = 'edit_tax';
                break;
        }
    }
    if ($call) {
        require_once WPCF_ABSPATH . '/help.php';
        $contextual_help = wpcf_admin_help($call, $contextual_help);
    }
    return $contextual_help;
}