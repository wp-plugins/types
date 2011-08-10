<?php
require_once( WPCF_ABSPATH . '/common/visual-editor/editor-addon.class.php');

/*
 * Admin functions
 */
add_action('admin_init', 'wpcf_admin_init_hook');
add_action('admin_menu', 'wpcf_admin_menu_hook');
add_action('wp_ajax_wpcf_ajax', 'wpcf_ajax');

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

    // @todo No auto hooks for this yet
    if (isset($_GET['wpcf-fields-media-insert'])
            || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],
                    'wpcf-fields-media-insert=1'))) {
        require_once WPCF_INC_ABSPATH . '/fields/file.php';
        add_filter('attachment_fields_to_edit',
                'wpcf_fields_file_attachment_fields_to_edit_filter', 10, 2);
        add_action('admin_head', 'wpcf_fields_file_media_admin_head');
    }
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
    $hook = add_submenu_page('wpcf', __('Groups and Fields', 'wpcf'),
            __('Groups and Fields', 'wpcf'), 'manage_options', 'wpcf',
            'wpcf_admin_menu_summary');
    add_action('load-' . $hook, 'wpcf_admin_menu_summary_hook');
    $hook = add_submenu_page('wpcf', __('Add New', 'wpcf'),
            __('Add New', 'wpcf'), 'manage_options', 'wpcf-edit',
            'wpcf_admin_menu_edit_fields');
    add_action('load-' . $hook, 'wpcf_admin_menu_edit_fields_hook');
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
    echo wpcf_add_admin_header(__('Groups', 'wpcf'));
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
        $title = __('Add New', 'wpcf');
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
    // Moved to forms.php
    // Auto-add wpnonce (checked in Enlimbo_Forms_Wpcf::autoHandle())
//    $form['_wpnonce'] = array(
//        '#type' => 'markup',
//        '#markup' => wp_nonce_field($id, '_wpnonce_wpcf', true, false)
//    );
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
//            jQuery.validator.defaults.highlight(element, errorClass, validClass); // Do not add class to element
		},
        unhighlight: function(element, errorClass, validClass) {
			jQuery("input#publish").removeClass("button-primary-disabled");
            jQuery.validator.defaults.unhighlight(element, errorClass, validClass);
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
            if (!empty($args['message'])) {
                $messages[] = $method . ': "' . $args['message'] . '"';
            }
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
        'url' => __('Please enter a valid email address', 'wpcf'),
        'date' => __('Please enter a valid date', 'wpcf'),
        'digits' => __('Please enter numeric data', 'wpcf'),
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
    $messages[get_current_user_id()][md5($message)] = array('message' => $message, 'class' => $class);
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
            do_action("admin_head-$hook_suffix");
            do_action('admin_head');
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
 * All AJAX calls go here.
 */
function wpcf_ajax() {
    switch ($_REQUEST['wpcf_action']) {
        case 'fields_insert':
            require_once WPCF_INC_ABSPATH . '/fields.php';
            require_once WPCF_INC_ABSPATH . '/fields-form.php';
            wpcf_fields_insert_ajax();
            wpcf_form_render_js_validation();
            break;

        case 'fields_insert_existing':
            require_once WPCF_INC_ABSPATH . '/fields.php';
            require_once WPCF_INC_ABSPATH . '/fields-form.php';
            wpcf_fields_insert_existing_ajax();
            wpcf_form_render_js_validation();
            break;

        case 'remove_field_from_group':
            require_once WPCF_INC_ABSPATH . '/fields.php';
            wpcf_admin_fields_remove_field_from_group($_GET['group_id'],
                    $_GET['field_id']);
            break;

        case 'deactivate_group':
            require_once WPCF_INC_ABSPATH . '/fields.php';
            $success = wpcf_admin_fields_deactivate_group(intval($_GET['group_id']));
            if ($success) {
                echo json_encode(array(
                    'output' => __('Group deactivated', 'wpcf'),
                    'execute' => 'jQuery("#wpcf-list-activate-'
                    . intval($_GET['group_id']) . '").replaceWith(\''
                    . wpcf_admin_fields_get_ajax_activation_link(intval($_GET['group_id']))
                    . '\');jQuery(".wpcf-table-column-active-'
                    . intval($_GET['group_id']) . '").html("' . __('No') . '");',
                ));
            } else {
                echo json_encode(array(
                    'output' => __('Error occured', 'wpcf')
                ));
            }
            break;

        case 'activate_group':
            require_once WPCF_INC_ABSPATH . '/fields.php';
            $success = wpcf_admin_fields_activate_group(intval($_GET['group_id']));
            if ($success) {
                echo json_encode(array(
                    'output' => __('Group activated', 'wpcf'),
                    'execute' => 'jQuery("#wpcf-list-activate-'
                    . intval($_GET['group_id']) . '").replaceWith(\''
                    . wpcf_admin_fields_get_ajax_deactivation_link(intval($_GET['group_id']))
                    . '\');jQuery(".wpcf-table-column-active-'
                    . intval($_GET['group_id']) . '").html("' . __('Yes') . '");',
                ));
            } else {
                echo json_encode(array(
                    'output' => __('Error occured', 'wpcf')
                ));
            }
            break;

        case 'delete_group':
            require_once WPCF_INC_ABSPATH . '/fields.php';
            wpcf_admin_fields_delete_group(intval($_GET['group_id']));
            echo json_encode(array(
                'output' => '',
                'execute' => 'jQuery("#wpcf-list-activate-'
                . intval($_GET['group_id'])
                . '").parents("tr").css("background-color", "#FF0000").fadeOut();',
            ));
            break;

        case 'add_radio_option':
            require_once WPCF_INC_ABSPATH . '/fields/radio.php';
            $element = wpcf_fields_radio_get_option(
                    urldecode($_GET['parent_name']));
            echo json_encode(array(
                'output' => wpcf_form_simple($element)
            ));
            break;

        case 'add_select_option':
            require_once WPCF_INC_ABSPATH . '/fields/select.php';
            $element = wpcf_fields_select_get_option(
                    urldecode($_GET['parent_name']));
            echo json_encode(array(
                'output' => wpcf_form_simple($element)
            ));
            break;

        case 'editor_insert_date':
            require_once WPCF_INC_ABSPATH . '/fields/date.php';
            wpcf_fields_date_editor_form();
            break;

        case 'insert_skype_button':
            require_once WPCF_INC_ABSPATH . '/fields/skype.php';
            wpcf_fields_skype_meta_box_ajax();
            break;

        case 'editor_callback':
            require_once WPCF_INC_ABSPATH . '/fields.php';
            $field = wpcf_admin_fields_get_field($_GET['field_id']);
            if (!empty($field)) {
                $file = WPCF_INC_ABSPATH . '/fields/' . $field['type'] . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    $function = 'wpcf_fields_' . $field['type'] . '_editor_callback';
                    if (function_exists($function)) {
                        call_user_func($function);
                    }
                }
            }
            break;

        case 'group_form_collapsed':
            require_once WPCF_INC_ABSPATH . '/fields-form.php';
            $group_id = $_GET['group_id'];
            $action = $_GET['toggle'];
            $fieldset = $_GET['id'];
            wpcf_admin_fields_form_save_open_fieldset($action, $fieldset,
                    $group_id);
            break;

        default:
            break;
    }
    die();
}

/////////////////////////////////////////////////


if (is_admin()) {
    global $pagenow;

    if ($pagenow == 'post.php' || $pagenow == 'post-new.php') {
//		add_action('admin_head', 'wpcf_post_edit_tinymce');
    }
}

function wpcf_post_edit_tinymce() {
    ///////////////////////////////////////
    add_thickbox();
    $editor_addon = new Editor_addon('types',
                    'Insert Types Shortcode',
                    WPCF_RES_RELPATH . '/js/types_editor_plugin.js');
    $editor_addon->add_insert_shortcode_menu("testing", 'testing', '');
    $editor_addon->add_insert_shortcode_menu("text", 'next', '');
    $editor_addon->add_insert_shortcode_menu("more", 'more', 'menu');

    $editor_addon->add_insert_shortcode_menu('<a href=\"admin_ajax.php\" class=\"thickbox\">popup</a>',
            'date', '', 'wpcf_popup_date');

    $editor_addon->render_js();

    ?>
    <script type="text/javascript">
        function wpcf_popup_date( ) {
            //		alert('Demonstrate a simple popup.');
            var answer = confirm('insert');
            if (answer) {
                tinyMCE.activeEditor.execCommand('mceInsertContent', false, 'some_text_to_insert');
            }
        }
    </script>
    <?php
}