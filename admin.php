<?php
require_once( WPCF_ABSPATH . '/common/visual-editor/editor-addon.class.php');

/*
 * Admin functions
 */
add_action('init', 'wpcf_admin_init_hook');
add_action('admin_menu', 'wpcf_admin_menu_hook');
if (defined('DOING_AJAX')) {
    require_once WPCF_INC_ABSPATH . '/ajax.php';
    add_action('wp_ajax_wpcf_ajax', 'wpcf_ajax');
}

/**
 * admin_init hook.
 */
function wpcf_admin_init_hook() {
    // Add callbacks for post edit pages
    add_action('load-post.php', 'wpcf_admin_post_page_load_hook');
    add_action('load-post-new.php', 'wpcf_admin_post_page_load_hook');

    // Add save_post callback
    add_action('save_post', 'wpcf_admin_save_post_hook', 10, 2);

    // Render messages
    wpcf_show_admin_messages();

    // Render JS settings
    add_action('admin_head', 'wpcf_admin_render_js_settings');

    // @todo No auto hooks for this yet
    if (isset($_GET['wpcf-fields-media-insert'])
            || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],
                    'wpcf-fields-media-insert=1'))) {
        require_once WPCF_INC_ABSPATH . '/fields/file.php';
        // Add types button
        add_filter('attachment_fields_to_edit',
                'wpcf_fields_file_attachment_fields_to_edit_filter', 10, 2);
        // Add JS
        add_action('admin_head', 'wpcf_fields_file_media_admin_head');
        // Filter media TABs
        add_filter('media_upload_tabs',
                'wpcf_fields_file_media_upload_tabs_filter');
    }

    register_post_type('wp-types-group',
            array(
        'public' => false,
        'label' => 'Types Groups',
            )
    );

    add_filter('icl_custom_fields_to_be_copied',
            'wpcf_custom_fields_to_be_copied', 10, 2);

    // WPML editor filters
    add_filter('icl_editor_cf_name', 'wpcf_icl_editor_cf_name_filter');
    add_filter('icl_editor_cf_description',
            'wpcf_icl_editor_cf_description_filter', 10, 2);
    add_filter('icl_editor_cf_style', 'wpcf_icl_editor_cf_style_filter', 10, 2);
}

/**
 * wpcf_custom_fields_to_be_copied
 *
 * Hook the copy custom fields from WPML and remove any of the fields
 * that wpcf will copy.
 */
function wpcf_custom_fields_to_be_copied($copied_fields, $original_post_id) {

    // see if this is one of our fields.
    $groups = wpcf_admin_post_get_post_groups_fields(get_post($original_post_id));

    foreach ($copied_fields as $id => $copied_field) {
        foreach ($groups as $group) {
            foreach ($group['fields'] as $field) {
                if ($copied_field == WPCF_META_PREFIX . $field['slug']) {
                    unset($copied_fields[$id]);
                }
            }
        }
    }
    return $copied_fields;
}

/**
 * save_post hook.
 * 
 * @param type $post_ID
 * @param type $post 
 */
function wpcf_admin_save_post_hook($post_ID, $post) {
    require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/fields-post.php';
    wpcf_admin_post_save_post_hook($post_ID, $post);
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
 * Triggers post procceses.
 */
function wpcf_admin_post_page_load_hook() {
    require_once WPCF_INC_ABSPATH . '/fields.php';
    require_once WPCF_INC_ABSPATH . '/fields-post.php';

    // Get post
    if (isset($_GET['post'])) {
        $post_id = (int) $_GET['post'];
    } else if (isset($_POST['post_ID'])) {
        $post_id = (int) $_POST['post_ID'];
    } else {
        $post_id = 0;
    }

    // Init processes
    if ($post_id) {
        $post = get_post($post_id);
        if (!empty($post)) {
            wpcf_admin_post_init($post);
        }
    } else {
        wpcf_admin_post_init();
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
    wp_enqueue_script('wpcf-fields-edit', WPCF_RES_RELPATH . '/js/basic.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable'),
            WPCF_VERSION);
    wp_enqueue_style('wpcf-fields-edit', WPCF_RES_RELPATH . '/css/basic.css',
            array(), WPCF_VERSION);
    wp_enqueue_script('wpcf-form-validation',
            WPCF_RES_RELPATH . '/js/'
            . 'jquery-form-validation/jquery.validate.min.js', array('jquery'),
            WPCF_VERSION);
    wp_enqueue_script('wpcf-form-validation-additional',
            WPCF_RES_RELPATH . '/js/'
            . 'jquery-form-validation/additional-methods.min.js',
            array('jquery'), WPCF_VERSION);
    wp_enqueue_style('wpcf-scroll',
            WPCF_RELPATH . '/common/visual-editor/res/css/scroll.css');
    wp_enqueue_script('wpcf-scrollbar',
            WPCF_RELPATH . '/common/visual-editor/res/js/scrollbar.js',
            array('jquery'));
    wp_enqueue_script('wpcf-mousewheel',
            WPCF_RELPATH . '/common/visual-editor/res/js/mousewheel.js',
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
 * Initiates/returns specific form.
 * 
 * @staticvar array $wpcf_forms
 * @param type $id
 * @param type $form
 * @return array 
 */
function wpcf_form($id, $form = array()) {
    static $wpcf_forms = array();
    if (isset($wpcf_forms[$id])) {
        return $wpcf_forms[$id];
    }
    require_once WPCF_ABSPATH . '/classes/forms.php';
    $new_form = new Enlimbo_Forms_Wpcf();
    $new_form->autoHandle($id, $form);
    $wpcf_forms[$id] = $new_form;
    return $wpcf_forms[$id];
}

/**
 * Renders form elements.
 * 
 * @staticvar string $form
 * @param type $elements
 * @return type 
 */
function wpcf_form_simple($elements) {
    static $form = NULL;
    require_once WPCF_ABSPATH . '/classes/forms.php';
    if (is_null($form)) {
        $form = new Enlimbo_Forms_Wpcf();
    }
    return $form->renderElements($elements);
}

/**
 * Validates form elements (simple).
 * 
 * @staticvar string $form
 * @param type $elements
 * @return type 
 */
function wpcf_form_simple_validate(&$elements) {
    static $form = NULL;
    require_once WPCF_ABSPATH . '/classes/forms.php';
    if (is_null($form)) {
        $form = new Enlimbo_Forms_Wpcf();
    }
    $form->validate(&$elements);
    return $form;
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
 * Stores JS validation rules.
 * 
 * @staticvar array $validation
 * @param type $element
 * @return array 
 */
function wpcf_form_add_js_validation($element) {
    static $validation = array();
    if ($element == 'get') {
        $temp = $validation;
        $validation = array();
        return $temp;
    }
    $validation[$element['#id']] = $element;
}

/**
 * Renders JS validation rules.
 * 
 * @return type 
 */
function wpcf_form_render_js_validation($form = '.wpcf-form-validate') {
    $elements = wpcf_form_add_js_validation('get');
    if (empty($elements)) {
        return '';
    }
    echo "\r\n" . '<script type="text/javascript">' . "\r\n" . '/* <![CDATA[ */'
    . "\r\n" . 'jQuery(document).ready(function(){' . "\r\n"
    . 'if (jQuery("' . $form . '").length > 0){' . "\r\n"
    . 'jQuery("' . $form . '").validate({
        errorClass: "wpcf-form-error",
        errorPlacement: function(error, element){
            error.insertBefore(element);
        },
        highlight: function(element, errorClass, validClass) {
            jQuery(element).parents(\'.collapsible\').slideDown();
            jQuery("input#publish").addClass("button-primary-disabled");
            jQuery("input#save-post").addClass("button-disabled");
            jQuery("#save-action .ajax-loading").css("visibility", "hidden");
            jQuery("#publishing-action #ajax-loading").css("visibility", "hidden");
//            jQuery.validator.defaults.highlight(element, errorClass, validClass); // Do not add class to element
		},
        unhighlight: function(element, errorClass, validClass) {
			jQuery("input#publish, input#save-post").removeClass("button-primary-disabled").removeClass("button-disabled");
//            jQuery.validator.defaults.unhighlight(element, errorClass, validClass);
		},
    });' . "\r\n";
    foreach ($elements as $id => $element) {
        if (in_array($element['#type'], array('radios'))) {
            echo 'jQuery(\'input:[name="' . $element['#name'] . '"]\').rules("add", {' . "\r\n";
        } else {
            echo 'jQuery("#' . $id . '").rules("add", {' . "\r\n";
        }
        $rules = array();
        $messages = array();
        foreach ($element['#validate'] as $method => $args) {
            if (!isset($args['value'])) {
                $args['value'] = 'true';
            }
            $rules[] = $method . ': ' . $args['value'];
            if (empty($args['message'])) {
                $args['message'] = wpcf_admin_validation_messages($method);
            }
            // TODO Why is this here?
//            if (!empty($args['message'])) {
//                $messages[] = $method . ': "' . wpcf_translate('field ' . $element['wpcf-id'] . ' validation message ' . $method, $args['message']) . '"';
//            }
        }
        echo implode(',' . "\r\n", $rules);
        if (!empty($messages)) {
            echo ',' . "\r\n" . 'messages: {' . "\r\n"
            . implode(',' . "\r\n", $messages) . "\r\n" . '}';
        }
        echo "\r\n" . '});' . "\r\n";
    }
    echo "\r\n" . '/* ]]> */' . "\r\n" . '}' . "\r\n" . '})' . "\r\n"
    . '</script>' . "\r\n";
}

function wpcf_admin_add_js_settings($id, $setting = '') {
    static $settings = array();
    $settings['wpcf_nonce_ajax_callback'] = '\'' . wp_create_nonce('execute') . '\'';
    if ($id == 'get') {
        $temp = $settings;
        $settings = array();
        return $temp;
    }
    $settings[$id] = $setting;
}

function wpcf_admin_render_js_settings() {
    $settings = wpcf_admin_add_js_settings('get');
    if (empty($settings)) {
        return '';
    }

    ?>
    <script type="text/javascript">
        //<![CDATA[
    <?php
    foreach ($settings as $id => $setting) {
        echo 'var ' . $id . ' = ' . $setting . ';' . "\r\n";
    }

    ?>
        //]]>
    </script>
    <?php
}

/**
 * Holds validation messages.
 * 
 * @param type $method
 * @return type 
 */
function wpcf_admin_validation_messages($method = false) {
    $messages = array(
        'required' => __('This Field is required', 'wpcf'),
        'email' => __('Please enter a valid email address', 'wpcf'),
        'url' => __('Please enter a valid URL address', 'wpcf'),
        'date' => __('Please enter a valid date', 'wpcf'),
        'digits' => __('Please enter numeric data', 'wpcf'),
        'number' => __('Please enter numeric data', 'wpcf'),
        'alphanumeric' => __('Letters, numbers, spaces or underscores only please',
                'wpcf'),
    );
    if ($method) {
        return isset($messages[$method]) ? $messages[$method] : '';
    }
    return $messages;
}

/**
 * Adds admin notice.
 * 
 * @param type $message
 * @param type $class 
 */
function wpcf_admin_message($message, $class = 'updated') {
    add_action('admin_notices',
            create_function('$a=1, $class=\'' . $class . '\', $message=\''
                    . $message . '\'',
                    'echo "<div class=\"message $class\"><p>$message</p></div>";'));
}

/**
 * Shows stored messages.
 */
function wpcf_show_admin_messages() {
    $messages = get_option('wpcf-messages', array());
    $messages_for_user = isset($messages[get_current_user_id()]) ? $messages[get_current_user_id()] : array();
    if (!empty($messages_for_user)) {
        foreach ($messages_for_user as $message) {
            wpcf_admin_message($message['message'], $message['class']);
        }
        unset($messages[get_current_user_id()]);
    }
    update_option('wpcf-messages', $messages);
}

/**
 * Stores admin notices if redirection is performed.
 * 
 * @param type $message
 * @param type $class
 * @return type 
 */
function wpcf_admin_message_store($message, $class = 'updated') {
    $messages = get_option('wpcf-messages', array());
    $messages[get_current_user_id()][md5($message)] = array(
        'message' => $message,
        'class' => $class
    );
    update_option('wpcf-messages', $messages);
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

function wpcf_cookies_add($data) {
    if (isset($_COOKIE['wpcf'])) {
        $data = array_merge((array) $_COOKIE['wpcf'], $data);
    }
    setcookie('wpcf', $data, time() + $lifetime, COOKIEPATH, COOKIE_DOMAIN);
}

/**
 * Renders page head.
 * 
 * @global type $pagenow
 * @param type $title
 */
function wpcf_admin_ajax_head($title) {
    global $pagenow;
    $hook_suffix = $pagenow;

    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
        <head>
            <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
            <title><?php echo $title; ?></title>
            <?php
            wp_admin_css('global');
            wp_admin_css();
            wp_admin_css('colors');
            wp_admin_css('ie');
            do_action('admin_enqueue_scripts', $hook_suffix);
            do_action("admin_print_styles-$hook_suffix");
            do_action('admin_print_styles');
            do_action("admin_print_scripts-$hook_suffix");
            do_action('admin_print_scripts');
            // TODO Check if needed
//            do_action("admin_head-$hook_suffix");
//            do_action('admin_head');
            do_action('admin_head_wpcf_ajax');

            ?>
            <style type="text/css">
                html { height: auto; }
            </style>
        </head>
        <body style="padding: 20px;">
            <?php
        }

        /**
         * Renders page footer
         */
        function wpcf_admin_ajax_footer() {
            global $pagenow;
            do_action('admin_footer_wpcf_ajax');
//    do_action('admin_footer', '');
//    do_action('admin_print_footer_scripts');
//    do_action("admin_footer-" . $pagenow);

            ?>
        </body>
    </html>

    <?php
}

/**
 * Gets var from $_SERVER['HTTP_REFERER'].
 * 
 * @param type $var 
 */
function wpcf_admin_get_var_from_referer($var) {
    $value = false;
    if (isset($_SERVER['HTTP_REFERER'])) {
        $parts = explode('?', $_SERVER['HTTP_REFERER']);
        if (!empty($parts[1])) {
            parse_str($parts[1], $vars);
            if (isset($vars[$var])) {
                $value = $vars[$var];
            }
        }
    }
    return $value;
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

/**
 * WPML editor filter
 * 
 * @param type $cf_name
 * @return type 
 */
function wpcf_icl_editor_cf_name_filter($cf_name) {
    require_once WPCF_INC_ABSPATH . '/fields.php';
    $fields = wpcf_admin_fields_get_fields();
    if (empty($fields)) {
        return $cf_name;
    }
    $field_id = str_replace('field-' . WPCF_META_PREFIX, '', $cf_name);
    if (isset($fields[$field_id]['name'])) {
        $cf_name = wpcf_translate('field ' . $fields[$field_id]['id'] . ' name',
                $fields[$field_id]['name']);
    }
    return $cf_name;
}

/**
 * WPML editor filter
 * 
 * @param type $cf_name
 * @param type $description
 * @return type 
 */
function wpcf_icl_editor_cf_description_filter($description, $cf_name) {
    require_once WPCF_INC_ABSPATH . '/fields.php';
    $fields = wpcf_admin_fields_get_fields();
    if (empty($fields)) {
        return $description;
    }
    $field_id = str_replace('field-' . WPCF_META_PREFIX, '', $cf_name);
    if (isset($fields[$field_id]['description'])) {
        $description = wpcf_translate('field ' . $fields[$field_id]['id'] . ' description',
                $fields[$field_id]['description']);
    }

    return $description;
}

/**
 * WPML editor filter
 * 
 * @param type $cf_name
 * @param type $style
 * @return type 
 */
function wpcf_icl_editor_cf_style_filter($style, $cf_name) {
    require_once WPCF_INC_ABSPATH . '/fields.php';
    $fields = wpcf_admin_fields_get_fields();
    if (empty($fields)) {
        return $style;
    }
    $field_id = str_replace('field-' . WPCF_META_PREFIX, '', $cf_name);
    if (isset($fields[$field_id]['type']) && $fields[$field_id]['type'] == 'textarea') {
        $style = 1;
    }

    return $style;
}