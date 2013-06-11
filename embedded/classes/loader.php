<?php
/*
 * View Class
 */

/**
 * View Class
 * 
 * @since Types 1.2
 * @package Types
 * @subpackage Classes
 * @version 0.1
 * @category Loader
 * @author srdjan <srdjan@icanlocalize.com>
 */
class WPCF_Loader
{

    /**
     * Register scripts.
     */
    public static function registerScripts() {

        wp_register_script( 'types', WPCF_EMBEDDED_RES_RELPATH . '/js/basic.js',
                array('jquery'), WPCF_VERSION, true );

        wp_register_script( 'types-knockout',
                WPCF_EMBEDDED_RES_RELPATH . '/js/knockout-2.2.1.js',
                array('jquery'), WPCF_VERSION, true );
    }

    /**
     * Register styles.
     */
    public static function registerStyles() {
        wp_register_style( 'types',
                WPCF_EMBEDDED_RES_RELPATH . '/css/basic.css', array(),
                WPCF_VERSION );
    }

    /**
     * Returns HTML formatted output.
     * 
     * @param string $view
     * @param mixed $data
     * @return string
     */
    public static function view( $view, $data = array() ) {

        $file = WPCF_EMBEDDED_ABSPATH . '/views/'
                . strtolower( strval( $view ) ) . '.php';

        if ( !file_exists( $file ) ) {
            return '<code>missing_view</code>';
        }

        ob_start();
        include $file;
        $output = ob_get_contents();
        ob_get_clean();

        return apply_filters( 'wpcf_get_view', $output, $view, $data );
    }

    /**
     * Returns HTML formatted output.
     * 
     * @param string $template
     * @param mixed $data
     * @return string
     */
    public static function template( $template, $data = array() ) {

        $file = WPCF_EMBEDDED_ABSPATH . '/views/templates/'
                . strtolower( strval( $template ) ) . '.tpl.php';

        if ( !file_exists( $file ) ) {
            return '<code>missing_template</code>';
        }

        ob_start();
        include $file;
        $output = ob_get_contents();
        ob_get_clean();

        return apply_filters( 'wpcf_get_template', $output, $template, $data );
    }

    /**
     * Loads model.
     * 
     * @param string $template
     * @param mixed $data
     * @return string
     */
    public static function loadModel( $model ) {

        $file = WPCF_EMBEDDED_ABSPATH . '/models/'
                . strtolower( strval( $model ) ) . '.php';

        if ( !file_exists( $file ) ) {
            return new WP_Error( 'types-loader-model', 'missing model ' . $model );
        }

        require_once $file;
    }

    /**
     * Loads class.
     * 
     * @param string $template
     * @param mixed $data
     * @return string
     */
    public static function loadClass( $class ) {

        $file = WPCF_EMBEDDED_ABSPATH . '/classes/'
                . strtolower( strval( $class ) ) . '.php';

        if ( !file_exists( $file ) ) {
            return new WP_Error( 'types-loader-class', 'missing class ' . $class );
        }

        require_once $file;
    }

    /**
     * Loads include.
     * 
     * @param string $template
     * @param mixed $data
     * @return string
     */
    public static function loadInclude( $name, $mode = 'embedded' ) {

        $path = $mode == 'plugin' ? WPCF_ABSPATH : WPCF_EMBEDDED_ABSPATH;
        $file = $path . '/includes/' . strtolower( strval( $name ) ) . '.php';

        if ( !file_exists( $file ) ) {
            return new WP_Error( 'types-loader-include', 'missing include ' . $name );
        }

        require_once $file;
    }

}