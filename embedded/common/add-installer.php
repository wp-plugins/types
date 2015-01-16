<?php

include dirname( __FILE__ ) . '/installer/loader.php';
WP_Installer_Setup($wp_installer_instance,
array(
    'plugins_install_tab' => '1',
    'repositories_include' => array('toolset', 'wpml')
));

if (!function_exists('toolset_installer_content')) {
    
    global $toolset_installer_menus;
    $toolset_installer_menus = array();
    
    //Render Installer packages
    function toolset_installer_content()
    {
        echo '<div class="wrap">';
        $config['repository'] = array(); // required
        WP_Installer_Show_Products($config);
        echo "</div>";
    }

    //Add submenu Installer to selected menus
    function toolset_setup_installer()
    {
        global $toolset_installer_menus;
        foreach( $toolset_installer_menus as $menu ) {
            add_submenu_page($menu, __('Installer', 'installer'), __('Installer', 'installer'), 'manage_options', 'installer', 'toolset_installer_content');
        }
    }

    if ( is_admin() ) {
        add_action('admin_menu', 'toolset_setup_installer');
    }

    // Note: This function only needs to be called if you want an
    // Installer menu under an existing menu.
    // The installer code puts a menu under the options menu by default.
    function toolset_add_installer_to_menu ( $menu ) {
        global $toolset_installer_menus;
        $toolset_installer_menus[] = $menu;
    }
    
}
