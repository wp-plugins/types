<?php

if( !class_exists( 'Editor_addon_generic' ) )
{
	if ( !defined( 'ICL_COMMON_FUNCTIONS' ) ) {
        require_once dirname( dirname( __FILE__ ) ) . '/functions.php';
    }

    define( 'EDITOR_ADDON_ABSPATH', dirname( __FILE__ ) );
    if ( !defined( 'EDITOR_ADDON_RELPATH' ) ) {
        define( 'EDITOR_ADDON_RELPATH', icl_get_file_relpath( __FILE__ ) );
    }

    add_action( 'admin_print_styles', 'add_menu_css' );

    function add_menu_css() {
        global $pagenow;

        if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {
            wp_enqueue_style( 'editor_addon_menu',
                    EDITOR_ADDON_RELPATH . '/res/css/pro_dropdown_2.css' );
            wp_enqueue_style( 'editor_addon_menu_scroll',
                    EDITOR_ADDON_RELPATH . '/res/css/scroll.css' );
        }
    }

    if ( is_admin() ) {
        add_action( 'admin_print_scripts', 'editor_add_js' );
    }
    
    class Editor_addon_generic
    {
		protected $items;
		public $logger = null;
		protected $template = '';
				
        public function __construct( $name, $button_text, $plugin_js_url,
            $media_button_image = '' ) {
            
            global $wplogger;
            $this->logger = $wplogger;
	        
            $this->name = $name;
            $this->plugin_js_url = $plugin_js_url;
            $this->button_text = $button_text;
            $this->media_button_image = $media_button_image;
            $this->initialized = false;

            

            if ( $media_button_image != '' ) {
                // Media buttons
                //Adding "embed form" button
                // WP 3.3 changes
                global $wp_version;
                if ( version_compare( $wp_version, '3.1.4', '>' ) ) {
                    add_action( 'media_buttons',
                            array($this, 'add_form_button'), 10, 2 );
                } else {
                    add_action( 'media_buttons_context',
                            array($this, 'add_form_button'), 10, 2 );
                }
            }

//            add_action('media_buttons', array($this, 'media_buttons'), 11);
//            wp_enqueue_style('editor_addon', plugins_url() . '/' . basename(dirname(dirname(dirname(__FILE__)))) . '/common/' . basename(dirname(__FILE__)) . '/res/css/style.css');
        }

        public function __destruct() {
            
        }

        /*

          Add a menu item that will insert the shortcode.

          To use sub menus, add a '-!-' separator between levels in
          the $menu parameter.
          eg.  Field-!-image
          This will create/use a menu "Field" and add a sub menu "image"

          $function_name is the javascript function to call for the on-click
          If it's left blank then a function will be created that just
          inserts the shortcode.

         */

        public function add_insert_shortcode_menu( $text, $shortcode, $menu,
                $function_name = '' ) {
            $this->items[] = array($text, $shortcode, $menu, $function_name);
        }
        
        public function add_form_button( $context, $text_area )
        {
        	throw new Exception( 'You should implement this method '. __METHOD__ );
        }
                         
    }

}

