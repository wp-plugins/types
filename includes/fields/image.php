<?php
/**
 * Types-field: Image
 *
 * Description: Displays a file (image) upload or input to the user.
 *
 * Rendering: Raw DB data (image URI) or HTML formatted image.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My date: $value)
 * 'alt' => alternative text e.g. 'My image'
 * 'title' => hover text e.g. 'My image'
 * 'size' => 'thumbnail'|'medium'|'large'|'full' (WP predefined sizes)
 * 'width' => image width e.g. 300 (overriden if 'size' is specified)
 * 'height' => image height e.g. 100 (overriden if 'size' is specified)
 * 'proportional' => 'true'|'false' (overriden if 'size' is specified)
 *
 * Example usage:
 * With a short code use [types field="my-image"]
 * In a theme use types_render_field("my-image", $parameters)
 * 
 */

/**
 * Register data (called automatically).
 * @return type 
 */
function wpcf_fields_image() {
    return array(
        'id' => 'wpcf-image',
        'title' => __('Image', 'wpcf'),
        'description' => __('Image', 'wpcf'),
        'validate' => array('required'),
        'meta_box_js' => array(
            'wpcf-jquery-fields-file' => array(
                'inline' => 'wpcf_fields_file_meta_box_js_inline',
            ),
            'wpcf-jquery-fields-image' => array(
                'inline' => 'wpcf_fields_image_meta_box_js_inline',
            ),
        ),
        'inherited_field_type' => 'file',
    );
}

/**
 * Form data for group form.
 * 
 * @return type 
 */
function wpcf_fields_image_insert_form() {
    $filename = WPCF_INC_ABSPATH . '/fields/file.php';
    require_once $filename;
    
    if (function_exists('wpcf_fields_file_insert_form')) {
        return wpcf_fields_file_insert_form();
    }
}

/**
 * Form data for post edit page.
 * 
 * @param type $field 
 */
function wpcf_fields_image_meta_box_form($field) {
    $filename = WPCF_INC_ABSPATH . '/fields/file.php';
    require_once $filename;
    if (function_exists('wpcf_fields_file_meta_box_form')) {
        return wpcf_fields_file_meta_box_form($field, true);
    }
}

/**
 * Renders inline JS.
 */
function wpcf_fields_image_meta_box_js_inline() {
    global $post;

    ?>
    <script type="text/javascript">
        //<![CDATA[
        jQuery(document).ready(function(){
            wpcf_formfield = false;
            jQuery('.wpcf-fields-image-upload-link').click(function() {
                wpcf_formfield = '#'+jQuery(this).attr('id')+'-holder';
                tb_show('<?php _e('Upload image',
            'wpcf'); ?>', 'media-upload.php?post_id=<?php echo $post->ID; ?>&type=image&wpcf-fields-media-insert=1&TB_iframe=true');
                        return false;
                    }); 
                });
                //]]>
    </script>
    <?php
}

/**
 * Editor callback form.
 */
function wpcf_fields_image_editor_callback() {
    wp_enqueue_style('wpcf-fields-image', WPCF_RES_RELPATH . '/css/basic.css',
            array(), WPCF_VERSION);
    wp_enqueue_script('jquery');

    // Get field
    $field = wpcf_admin_fields_get_field($_GET['field_id']);
    if (empty($field)) {
        _e('Wrong field specified', 'wpcf');
        die();
    }

    // Get post_ID
    $post_ID = false;
    if (isset($_POST['post_id'])) {
        $post_ID = intval($_POST['post_id']);
    } else {
        $http_referer = explode('?', $_SERVER['HTTP_REFERER']);
        parse_str($http_referer[1], $http_referer);
        if (isset($http_referer['post'])) {
            $post_ID = $http_referer['post'];
        }
    }

    // Get attachment
    $attachment_id = false;
    if ($post_ID) {
        $image = get_post_meta($post_ID, WPCF_META_PREFIX . $field['slug'], true);
        if (!empty($image)) {
            // Get attachment by guid
            global $wpdb;
            $attachment_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts}
    WHERE post_type = 'attachment' AND guid=%s",
                            $image));
        }
    }

    $last_settings = wpcf_admin_fields_get_field_last_settings($_GET['field_id']);

    $form = array();
    $form['#form']['callback'] = 'wpcf_fields_image_editor_submit';
    if ($attachment_id) {
        $form['preview'] = array(
            '#type' => 'markup',
            '#markup' => '<div style="position:absolute; margin-left:300px;">'
            . wp_get_attachment_image($attachment_id, 'thumbnail') . '</div>',
        );
    }
    $alt = '';
    $title = '';
    if ($attachment_id) {
        $alt = trim(strip_tags(get_post_meta($attachment_id,
                                '_wp_attachment_image_alt', true)));
        $attachment_post = get_post($attachment_id);
        if (!empty($attachment_post)) {
            $title = trim(strip_tags($attachment_post->post_title));
        } else if (!empty($alt)) {
            $title = $alt;
        }
        if (empty($alt)) {
            $alt = $title;
        }
    }
    $form['title'] = array(
        '#type' => 'textfield',
        '#title' => __('Image title', 'wpcf'),
        '#description' => __('Title text for the image, e.g. &#8220;The Mona Lisa&#8221;'),
        '#name' => 'title',
        '#value' => $title,
    );
    $form['alt'] = array(
        '#type' => 'textfield',
        '#title' => __('Alternate Text'),
        '#description' => __('Alt text for the image, e.g. &#8220;The Mona Lisa&#8221;'),
        '#name' => 'alt',
        '#value' => $alt,
    );
    $form['alignment'] = array(
        '#type' => 'radios',
        '#title' => __('Alignment'),
        '#name' => 'alignment',
        '#default_value' => isset($last_settings['alignment']) ? $last_settings['alignment'] : 'none',
        '#options' => array(
            __('None') => 'none',
            __('Left') => 'left',
            __('Center') => 'center',
            __('Right') => 'right',
        ),
    );
    $form['size'] = array(
        '#type' => 'radios',
        '#title' => __('Pre-defined sizes', 'wpcf'),
        '#name' => 'image-size',
        '#default_value' => isset($last_settings['image-size']) ? $last_settings['image-size'] : 'thumbnail',
        '#options' => array(
            __('Thumbnail') => 'thumbnail',
            __('Medium') => 'medium',
            __('Large') => 'large',
            __('Full Size') => 'full',
            __('Custom size', 'wpcf') => 'wpcf-custom',
        ),
    );
    $form['toggle-open'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="wpcf-toggle" style="display:none;">',
    );
    $form['width'] = array(
        '#type' => 'textfield',
        '#title' => __('Width'),
        '#description' => __('Specify custom width', 'wpcf'),
        '#name' => 'width',
        '#value' => isset($last_settings['width']) ? $last_settings['width'] : '',
        '#suffix' => '&nbsp;px',
    );
    $form['height'] = array(
        '#type' => 'textfield',
        '#title' => __('Height'),
        '#description' => __('Specify custom height', 'wpcf'),
        '#name' => 'height',
        '#value' => isset($last_settings['height']) ? $last_settings['height'] : '',
        '#suffix' => '&nbsp;px',
    );
    $form['proportional'] = array(
        '#type' => 'checkbox',
        '#title' => __('Keep proportional', 'wpcf'),
        '#name' => 'proportional',
        '#default_value' => 1,
    );
    $form['toggle-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );
    if ($post_ID) {
        $form['post_id'] = array(
            '#type' => 'hidden',
            '#name' => 'post_id',
            '#value' => $post_ID,
        );
    }
    $form['submit'] = array(
        '#type' => 'markup',
        '#markup' => get_submit_button(__('Insert shortcode', 'wpcf')),
    );
    $f = wpcf_form('wpcf-form', $form);
    wpcf_admin_ajax_head('Insert email', 'wpcf');
    echo '<form method="post" action="">';
    echo $f->renderForm();
    echo '</form>';

    ?>
    <script type="text/javascript">
        //<![CDATA[
        jQuery(document).ready(function(){
            jQuery('input:radio[name="image-size"]').change(function(){
                if (jQuery(this).val() == 'wpcf-custom') {
                    jQuery('#wpcf-toggle').slideDown();
                } else {
                    jQuery('#wpcf-toggle').slideUp();
                }
            });
            if (jQuery('input:radio[name="image-size"]:checked').val() == 'wpcf-custom') {
                jQuery('#wpcf-toggle').show();
            }
        });
        //]]>
    </script>
    <?php
    wpcf_admin_ajax_footer();
}

/**
 * Editor callback form submit.
 */
function wpcf_fields_image_editor_submit() {
    $add = '';
    if (!empty($_POST['alt'])) {
        $add .= ' alt="' . strval($_POST['alt']) . '"';
    }
    if (!empty($_POST['title'])) {
        $add .= ' title="' . strval($_POST['title']) . '"';
    }
    $size = $_POST['image-size'];
    if ($size == 'wpcf-custom') {
        if (!empty($_POST['width'])) {
            $add .= ' width="' . intval($_POST['width']) . '"';
        }
        if (!empty($_POST['height'])) {
            $add .= ' height="' . intval($_POST['height']) . '"';
        }
        if (!empty($_POST['proportional'])) {
            $add .= ' proportional="true"';
        }
    } else {
        $add .= ' size="' . $size . '"';
    }
    if (!empty($_POST['alignment'])) {
        $add .= ' align="' . $_POST['alignment'] . '"';
    }
    $field = wpcf_admin_fields_get_field($_GET['field_id']);
    if (!empty($field)) {
        $shortcode = wpcf_fields_get_shortcode($field, $add);
        wpcf_admin_fields_save_field_last_settings($_GET['field_id'], $_POST);
        echo wpcf_admin_fields_popup_insert_shortcode_js($shortcode);
        die();
    }
}

/**
 * View function.
 * 
 * @param type $params 
 */
function wpcf_fields_image_view($params) {
    $output = '';
    $alt = false;
    $title = false;
    $class = array();
    global $wpdb;
    $attachment_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts}
    WHERE post_type = 'attachment' AND guid=%s",
                    $params['field_value']));

    // Set alt
    if (isset($params['alt'])) {
        $alt = $params['alt'];
    }

    // Set title
    if (isset($params['title'])) {
        $title = $params['title'];
    }

    // Set attachment class
    if (!empty($params['size'])) {
        $class[] = 'attachment-' . $params['size'];
    }

    // Set align class
    if (!empty($params['align']) && $params['align'] != 'none') {
        $class[] = 'align' . $params['align'];
    }

    // Pre-configured size (use WP function)
    if (!empty($attachment_id) && !empty($params['size'])) {
        $output = wp_get_attachment_image($attachment_id, $params['size'],
                false,
                array(
            'class' => implode(' ', $class),
            'alt' => $alt,
            'title' => $title
                )
        );
        $output = wpcf_frontend_wrap_field_value($params['field'], $output,
                $params);
        $output = wpcf_frontend_wrap_field($params['field'], $output, $params);
    } else { // Custom size
        $width = !empty($params['width']) ? intval($params['width']) : null;
        $height = !empty($params['height']) ? intval($params['height']) : null;
        $crop = (!empty($params['proportional']) && $params['proportional'] == 'true') ? false : true;
        $resized_image = wpcf_fields_image_resize_image(
                $params['field_value'], $width, $height, 'relpath', false, $crop
        );
        if (!$resized_image) {
            $resized_image = $params['field_value'];
        }
        $output = '<img alt="';
        $output .= $alt !== false ? $alt : $resized_image;
        $output .= '" title="';
        $output .= $title !== false ? $title : $resized_image;
        $output .= '"';
        $output .=!empty($class) ? ' class="' . implode(' ', $class) . '"' : '';
        $output .= ' src="' . $resized_image . '" />';
        $output = wpcf_frontend_wrap_field_value($params['field'], $output,
                $params);
        $output = wpcf_frontend_wrap_field($params['field'], $output, $params);
    }

    return $output;
}

/**
 * Resizes image using WP image_resize() function.
 *
 * Caches return data if called more than one time in one pass.
 *
 * @staticvar array $cached Caches calls in one pass
 * @param <type> $url_path Full URL path (works only with images on same domain)
 * @param <type> $width
 * @param <type> $height
 * @param <type> $refresh Set to true if you want image re-created or not cached
 * @param <type> $crop Set to true if you want apspect ratio to be preserved
 * @param string $suffix Optional (default 'wpcf_$widthxheight)
 * @param <type> $dest_path Optional (defaults to original image)
 * @param <type> $quality
 * @return array
 */
function wpcf_fields_image_resize_image($url_path, $width = 300, $height = 200,
        $return = 'relpath', $refresh = FALSE, $crop = TRUE, $suffix = '',
        $dest_path = NULL, $quality = 75) {
    if (empty($url_path)) {
        return $url_path;
    }
    static $cached = array();
    $cache_key = md5($url_path . $width . $height . intval($crop) . $suffix . $dest_path);

    // Check if cached in this call
    if (!$refresh && isset($cached[$cache_key][$return])) {
        return $cached[$cache_key][$return];
    }

    $width = intval($width);
    $height = intval($height);

    $info = pathinfo($url_path);
    $upload_dir = wp_upload_dir();

    // Do this to enable different subdomains with same upload path
    $path = parse_url($url_path);
    $temp = parse_url(get_option('siteurl'));
    $info['dirname'] = $temp['scheme'] . '://' . $temp['host'] . dirname($path['path']);
    $abspath = str_replace(
            $upload_dir['baseurl'], $upload_dir['basedir'], $info['dirname']
    );

    $image_original_relpath = $url_path;
    $image_original_abspath = $abspath . '/' . $info['basename'];

    // Get size of new file
    $size = @getimagesize($image_original_abspath);
    if (!$size) {
        return false;
    }
    list($orig_w, $orig_h, $orig_type) = $size;
    $dims = image_resize_dimensions($orig_w, $orig_h, $width, $height, $crop);
    if (!$dims) {
        return false;
    }
    list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $dims;

    // Set suffix
    if (empty($suffix)) {
        $suffix = 'wpcf_' . $dst_w . 'x' . $dst_h;
    } else {
        $suffix .= '_wpcf_' . $dst_w . 'x' . $dst_h;
    }

    $image_relpath = $info['dirname'] . '/' . $info['filename'] . '-'
            . $suffix . '.' . $info['extension'];
    $image_abspath = $abspath . '/' . $info['filename'] . '-' . $suffix . '.'
            . $info['extension'];

    // Check if already resized
    if (!$refresh && file_exists($image_abspath)) {
        // Cache it
        $cached[$cache_key]['relpath'] = $image_relpath;
        $cached[$cache_key]['abspath'] = $image_abspath;
        return $return == 'relpath' ? $image_relpath : $image_abspath;
    }

    // If original file don't exists
    if (!file_exists($image_original_abspath)) {
        return false;
    }

    // Resize image
    $resized_image = image_resize(
            $image_original_abspath, $width, $height, $crop, $suffix,
            $dest_path, $quality
    );

    // Check if error
    if (is_wp_error($resized_image)) {
        return false;
    }

    $image_abspath = $resized_image;

    // Cache it
    $cached[$cache_key]['relpath'] = $image_relpath;
    $cached[$cache_key]['abspath'] = $image_abspath;

    return $return == 'relpath' ? $image_relpath : $image_abspath;
}