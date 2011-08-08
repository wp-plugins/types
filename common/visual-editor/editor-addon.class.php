<?php

if (!class_exists('Editor_addon')) {

    add_action('init', 'add_menu_css');
    function add_menu_css() {
        global $pagenow;
        
        if($pagenow == 'post.php' || $pagenow == 'post-new.php'){
            wp_enqueue_style('editor_addon_menu', plugins_url() . '/' . basename(dirname(dirname(dirname(__FILE__)))) . '/common/' . basename(dirname(__FILE__)) . '/res/css/pro_dropdown_2.css');
        }
    }

    class Editor_addon {
    
        function __construct($name, $button_text, $plugin_js_url, $media_button_image = ''){
            
            $this->name = $name;
            $this->plugin_js_url = $plugin_js_url;
            $this->button_text = $button_text;
            $this->media_button_image = $media_button_image;
            $this->initialized = false;
            
            $this->items = array();

            if ($media_button_image != '') {            
                // Media buttons
                //Adding "embed form" button
                add_action('media_buttons_context', array($this, 'add_form_button'));
            }
            
//            add_action('media_buttons', array($this, 'media_buttons'), 11);
//            wp_enqueue_style('editor_addon', plugins_url() . '/' . basename(dirname(dirname(dirname(__FILE__)))) . '/common/' . basename(dirname(__FILE__)) . '/res/css/style.css');
            
        }
    
        function __destruct(){
            
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
        
        function add_insert_shortcode_menu($text, $shortcode, $menu, $function_name = '') {
            $this->items[] = array($text, $shortcode, $menu, $function_name);
        }
        
        function add_form_button($context) {
            
            // sort the items into menu levels.
            
            $menus = array();
            $sub_menus = array();
            
            foreach ($this->items as $item) {
                $parts = explode('-!-', $item[2]);
                $menu_level = &$menus;
                foreach($parts as $part) {
                    if ($part != '') {
                        if (!array_key_exists($part, $menu_level)) {
                            $menu_level[$part] = array();
                        }
                        $menu_level = &$menu_level[$part];
                    }
                }
                $menu_level[$item[0]] = $item;
                
            }
            
            $out = '

<span class="preload1"></span>
<span class="preload2"></span>
            
<ul id="editor_addon">
	<li class="top"><a href="#nogo27" id="contacts" class="top_link"><span class="down"><img src="' . $this->media_button_image . '"></span></a>
		<ul class="sub">';
        
            $out .= $this->_output_media_menu($menus);
            
            $out .= '         
		</ul>
	</li>
</ul>
';
            
            return $context . $out;
        }

        function _output_media_menu($menu) {
            
            $out = '';
            foreach ($menu as $key => $menu_item) {
                if (isset($menu_item[0]) && !is_array($menu_item[0])) {
                    if ($menu_item[3] != '') {
                        $out .= '<li><a href="#" onclick="'. $menu_item[3] . '">' . $menu_item[0] . "</a></li>\n";
                    } else {
                        $short_code = '[' . str_replace('"', '\\\'', $menu_item[1]) . ']';
                        $out .= '<li><a href="#" onclick="jQuery(\'textarea#content\').insertAtCaret(\'' . $short_code . '\')">' . $menu_item[0] . "</a></li>\n";
                    }
                } else {
                    // a sum menu.
                    $out .= '<li><a href="#" class="fly">' . $key . "</a>\n<ul>\n";
                    $out .= $this->_output_media_menu($menu_item);
                    $out .= "</ul>\n</li>\n";
                }
            }
                
            return $out;

            
        }
        
        
        /*
         
            Render the javascript code to define the menus
            The views_editor_plugin.js will use the created javascript
            variables to create the menu.
            
        */
        function render_js() {
            if (sizeof($this->items) > 0) {
                $name = str_replace('-', '_', $this->name);
                ?>    
                <script type="text/javascript">
                var wp_editor_addon_<?php echo $name; ?> = new Array();
                var button_title = '<?php echo $this->button_text;?>';
                <?php
    
                $index = 0;
                foreach ($this->items as $item) {
                    $function_name = $name . base64_encode($item[0]) . '_' . $index;
                    $function_name = str_replace(array('+', '/', '='), '_', $function_name);
                    if ($item[3] != '') {
                        // we need to create an on-click function that calls the function passed
                        echo 'wp_editor_addon_' . $name . '[' . $index . '] = new Array("' . $item[0] . '", "' . $function_name . '", "'. $item[2] . '");' . "\n";

                        // create a js function to be called for the on_click
                        echo 'function ' . $function_name . "() { " . $item[3] . "};\n";
                        
                    } else {
                        // we need to create an on-click function that just inserts the shortcode.
                        echo 'wp_editor_addon_' . $name . '[' . $index . '] = new Array("' . $item[0] . '", "' . $function_name . '", "'. $item[2] . '");' . "\n";
                            
                        // create a js function to be called for the on_click
                        echo 'function ' . $function_name . "() { tinyMCE.activeEditor.execCommand('mceInsertContent', false, '[" . $item[1] . "]')};\n";
                    }
                            
                    $index++;
                }            
                
                ?>
                </script>
                <?php
                
                add_filter('mce_external_plugins', array($this, 'wpv_mce_register'));
                add_filter('mce_buttons', array($this, 'wpv_mce_add_button'), 0);
                
            }            
            
        }
        
        /*
          
          Add the wpv_views button to the toolbar.
          
        */
        function wpv_mce_add_button($buttons)
        {
            array_push($buttons, "separator", str_replace('-', '_', $this->name));
            return $buttons;
        }
        
        /*
         
            Register this plugin as a mce 'addon'
            Tell the mce editor the url of the javascript file.
        */
        
        function wpv_mce_register($plugin_array)
        {
            $plugin_array[str_replace('-', '_', $this->name)] = $this->plugin_js_url;
            return $plugin_array;
        }
        
    }
    
    if(is_admin()){
        global $pagenow;
        
        if($pagenow == 'post.php' || $pagenow == 'post-new.php'){
            add_action('admin_print_scripts', 'editor_add_js');
        }
    }
    
    function editor_add_js() {
        $url = plugins_url() . '/' . dirname(plugin_basename(__FILE__));
        
        wp_enqueue_script( 'icl_editor-script' , $url . '/res/js/icl_editor_addon_plugin.js', array());
    }
}

