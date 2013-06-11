<?php
/*
 * WP Post Editor class.
 */

/**
 * WP Post Editor class.
 * 
 * @since Types 1.4
 * @package Types
 * @subpackage Classes
 * @version 0.1
 * @category post
 * @author srdjan <srdjan@icanlocalize.com>
 */
class WPCF_Editor
{

    /**
     * Active Field.
     * @var type 
     */
    var $field = array();

    /**
     * Context (postmeta|usermeta).
     * @var type 
     */
    var $context = 'post';

    /**
     * Post object.
     * @var type 
     */
    var $post;

    /**
     * Collected data.
     * @var type 
     */
    var $data = array();

    /**
     * Construct function.
     */
    function __construct() {
        wp_register_script( 'types-editor',
                WPCF_EMBEDDED_RES_RELPATH . '/js/editor.js', array('jquery'),
                WPCF_VERSION, true );
        wp_register_style( 'types-editor',
                WPCF_EMBEDDED_RES_RELPATH . '/css/editor.css',
                array('admin-bar', 'wp-admin', 'buttons', 'media-views'),
                WPCF_VERSION );
        wp_register_style( 'types-editor-cloned',
                WPCF_EMBEDDED_RES_RELPATH . '/css/editor-cloned.css',
                array('admin-bar', 'wp-admin', 'buttons', 'media-views'),
                WPCF_VERSION );
    }

    /**
     * Renders Thickbox content.
     * 
     * Field should provide callback function
     * that will be called automatically.
     * 
     * Function should be named like:
     * 'wpcf_fields_' . $field_type . '_editor_callback'
     * e.g. 'wpcf_fields_checkbox__editor_callback'
     * 
     * Function should return array with elements:
     * 'supports' - parameters or other feature supported, e.g. 'style' will
     *     enable 'Styling' options
     * 
     * Tabs is array with elements:
     * 'menu_title' - used for menu title
     * 'title' - used for main title
     * 'content' - HTML content of tab
     * 
     * @param type $field
     * @param type $context
     * @param type $post_id
     */
    function thickbox( $field, $context = 'postmeta', $post_id = -1 ) {

        global $wp_version;

        wp_enqueue_script( 'types-knockout' );
        wp_enqueue_script( 'types-editor' );
        wp_enqueue_style( 'types-editor' );

        // Load cloned WP Media Modal CSS
        if ( version_compare( $wp_version, '3.5', '<' ) ) {
            wp_enqueue_style( 'types-editor-cloned' );
        }

        $settings = wpcf_admin_fields_get_field_last_settings( $field['id'] );
        $this->field = $field;
        $this->context = $context;
        $this->post = get_post( $post_id );
        $data = array(
            'context' => $context,
            'field' => $field,
            'saved_settings' => $settings,
            'tabs' => array(),
            'supports' => array('style'),
            'json_data' => array(),
            'post' => $this->post,
            'style' => isset( $settings['style'] ) ? $settings['style'] : '',
            'css' => isset( $settings['css'] ) ? $settings['css'] : '',
        );
        $json_data = array(
            'context' => $context,
            'field_id' => $field['id'],
            'supports' => array('style'),
            'post_id' => $this->post->ID,
        );

        /*
         * Callback
         */
        $function = 'wpcf_fields_' . $field['type'] . '_editor_callback';
        if ( function_exists( $function ) ) {

            $callback = call_user_func( $function, $field,
                    $data['saved_settings'], $context, $this->post );

            // Merge data
            $data['json_data'] = isset( $callback['json_data'] ) ? $callback['json_data'] : array();
            $data['tabs'] = $callback['tabs'];
            $data['supports'] = $callback['supports'];
        }

        // Add usermeta form
        if ( $context == 'usermeta' ) {
            $data['user_form'] = wpcf_form_simple( wpcf_get_usermeta_form_addon() );
        }

        // Get parents
        if ( !empty( $this->post->ID ) ) {
            $data['parents'] = WPCF_Relationship::get_parents( $this->post );
        }

        // Set data
        $this->data = $data;

        // Render header
        wpcf_admin_ajax_head();

        // Check if submitted
        $this->_thickbox_check_submit();

        // Render form
        echo '<form method="post" action="" id="types-editor-modal-form">';
        $this->modal_window( $data );
        wp_nonce_field( 'types_editor_thickbox', '__types_nonce' );
        echo '</form>';

        // Render JSON settings
        echo '<script type="text/javascript">var typesEditorOptions = '
        . json_encode( $json_data ) . ';</script>';
        echo '<script type="text/javascript">var typesEditorFieldOptions = '
        . json_encode( $data['json_data'] ) . ';</script>';

        // Render footer
        wpcf_admin_ajax_footer();
    }

    /**
     * Renders editor modal window.
     * 
     * @param type $data
     */
    function modal_window( $data = array() ) {
        echo WPCF_Loader::view( 'editor-modal-window', $data );
    }

    /**
     * Process if submitted.
     * 
     * Field should provide callback function
     * that will be called automatically.
     * 
     * Function should be named like:
     * 'wpcf_fields_' . $field_type . '_editor_submit'
     * e.g. 'wpcf_fields_checkbox_editor_submit'
     * 
     * Function should return shortcode string.
     */
    function _thickbox_check_submit() {
        if ( !empty( $_POST['__types_nonce'] )
                && wp_verify_nonce( $_POST['__types_nonce'],
                        'types_editor_thickbox' ) ) {

            $function = 'wpcf_fields_' . strtolower( $this->field['type'] )
                    . '_editor_submit';

            if ( function_exists( $function ) ) {
                /*
                 * Callback
                 */
                $shortcode = call_user_func( $function, $_POST, $this->field,
                        $this->context );
            } else {
                /*
                 * Generic
                 */
                if ( $this->context == 'usermeta' ) {
                    $add = wpcf_get_usermeta_form_addon_submit();
                    $shortcode = wpcf_usermeta_get_shortcode( $this->field, $add );
                } else {
                    $shortcode = wpcf_fields_get_shortcode( $this->field );
                }
            }

            if ( !empty( $shortcode ) ) {

                // Add additional parameters if required
                $shortcode = $this->_add_parameters_to_shortcode( $shortcode,
                        $_POST );

                // Save style and CSS settings
                $settings = array();
                if ( isset( $_POST['style'] ) ) {
                    $settings['style'] = strval( $_POST['style'] );
                }
                if ( isset( $_POST['css'] ) ) {
                    $settings['css'] = strval( $_POST['css'] );
                }
                wpcf_admin_fields_save_field_last_settings( $this->field['id'],
                        $settings, 'append', 'overwrite' );

                // Insert shortcode
                echo editor_admin_popup_insert_shortcode_js( $shortcode );
            } else {
                echo '<div class="message error"><p>'
                . __( 'Shortcode generation failed', 'wpcf' ) . '</p></div>';
            }

            die();
        }
    }

    /**
     * Adds additional parameters if required.
     * 
     * @param type $shortcode
     * @param type $data
     * @return type
     */
    function _add_parameters_to_shortcode( $shortcode, $data ) {

        if ( isset( $data['css'] ) && $data['css'] != '' ) {
            $shortcode = preg_replace( '/\[types([^\]]*)/',
                    '$0 class="' . $data['css'] . '"', $shortcode );
        }
        if ( isset( $data['style'] ) && $data['style'] != '' ) {
            $shortcode = preg_replace( '/\[types([^\]]*)/',
                    '$0 style="' . $data['style'] . '"', $shortcode );
        }

        if ( isset( $data['show_name'] ) && $data['show_name'] == '1' ) {
            $shortcode = preg_replace( '/\[types([^\]]*)/',
                    '$0 show_name="true"', $shortcode );
        }


        if ( isset( $data['raw_mode'] ) && $data['raw_mode'] == '1' ) {
            $shortcode = preg_replace( '/\[types([^\]]*)/', '$0 raw="true"',
                    $shortcode );
        }

        if ( isset( $data['html_mode'] ) && $data['html_mode'] == '1' ) {
            $shortcode = preg_replace( '/\[types([^\]]*)/', '$0 output="html"',
                    $shortcode );
        }

        if ( isset( $data['post_id'] ) ) {
            $post_id = 'id=';
            if ( $data['post_id'] == 'post_id' ) {
                $post_id .= '"' . trim( strval( $data['specific_post_id'] ) ) . '"';
            } else if ( $data['post_id'] == 'current' ) {
                $post_id .= '""';
            } else if ( $data['post_id'] == 'parent' ) {
                $post_id .= '"$parent"';
            } else if ( $data['post_id'] == 'related' ) {
                $post_id .= '"$' . trim( strval( $data['related_post'] ) ) . '"';
            } else {
                $post_id .= '"' . strval( $data['post_id'] ) . '"';
            }
            $shortcode = preg_replace( '/\[types([^\]]*)/', '$0 ' . $post_id,
                    $shortcode );
        }

        return $shortcode;
    }

}