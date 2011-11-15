<?php
/**
 * Types-field: Date
 *
 * Description: Displays a datepicker to the user.
 *
 * Rendering: Date is stored in seconds (time()) but displayed as date
 * formatted.
 * 
 * Parameters:
 * 'raw' => 'true'|'false' (display raw data stored in DB, default false)
 * 'output' => 'html' (wrap data in HTML, optional)
 * 'show_name' => 'true' (show field name before value e.g. My date: $value)
 * 'style' => 'text'|'calendar' (display text or WP calendar)
 * 'format' => defaults to WP date format settings, can be any valid date format
 *     e.g. "j/n/Y"
 *
 * Example usage:
 * With a short code use [types field="my-date"]
 * In a theme use types_render_field("my-date", $parameters)
 * 
 */

add_filter('wpcf_fields_type_date_value_get',
        'wpcf_fields_date_value_get_filter');
add_filter('wpcf_fields_type_date_value_save',
        'wpcf_fields_date_value_save_filter');

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_date() {
    return array(
        'id' => 'wpcf-date',
        'title' => __('Date', 'wpcf'),
        'description' => __('Date', 'wpcf'),
        'validate' => array('required', 'date'),
        'meta_box_js' => array(
            'wpcf-jquery-fields-date' => array(
                'src' => WPCF_RES_RELPATH . '/js/jquery.ui.datepicker.min.js',
                'deps' => array('jquery-ui-core'),
            ),
            'wpcf-jquery-fields-date-inline' => array(
                'inline' => 'wpcf_fields_date_meta_box_js_inline',
            ),
        ),
        'meta_box_css' => array(
            'wpcf-jquery-fields-date' => array(
                'src' => WPCF_RES_RELPATH . '/css/jquery-ui/datepicker.css',
            ),
        ),
        'inherited_field_type' => 'textfield',
        'meta_key_type' => 'TIME',
    );
}

/**
 * From data for post edit page.
 * 
 * @param type $field 
 */
function wpcf_fields_date_meta_box_form($field) {
    return array(
        '#type' => 'textfield',
        '#attributes' => array('class' => 'wpcf-datepicker', 'style' => 'width:150px;'),
    );
}

/**
 * Renders inline JS.
 */
function wpcf_fields_date_meta_box_js_inline() {

    ?>
    <script type="text/javascript">
        //<![CDATA[
        jQuery(document).ready(function(){
            if (jQuery.isFunction(jQuery.fn.datepicker)) {
                jQuery('.wpcf-datepicker').each(function(index) {
                    if (!jQuery(this).is(':disabled')) {
                            jQuery(this).datepicker({
                            showOn: "button",
                            buttonImage: "<?php echo WPCF_RES_RELPATH; ?>/images/calendar.gif",
                            buttonImageOnly: true,
                            buttonText: "<?php _e('Select date',
                    'wpcf'); ?>"
                                });
                        }
                    });
                    }
                });
                function wpcfFieldsDateEditorCallback(field_id) {
                    var url = "<?php echo admin_url('admin-ajax.php'); ?>?action=wpcf_ajax&wpcf_action=editor_insert_date&_wpnonce=<?php echo wp_create_nonce('fields_insert'); ?>&field_id="+field_id+"&keepThis=true&TB_iframe=true&width=400&height=400";
                    tb_show("<?php _e('Insert date',
            'wpcf'); ?>", url);
                }
                //]]>
    </script>
    <?php
}

/**
 * Converts time to date on post edit page.
 * 
 * @param type $value
 * @return type 
 */
function wpcf_fields_date_value_get_filter($value) {
    if (empty($value)) {
        return $value;
    }
    return date('m/d/Y', intval($value));
}

/**
 * Converts date to time on post saving.
 * 
 * @param type $value
 * @return type 
 */
function wpcf_fields_date_value_save_filter($value) {
    if (empty($value)) {
        return $value;
    }
    return strtotime(strval($value));
}

/**
 * View function.
 * 
 * @param type $params 
 */
function wpcf_fields_date_view($params) {
    $defaults = array(
        'format' => get_option('date_format'),
    );
    $params = wp_parse_args($params, $defaults);
    $output = '';
    switch ($params['style']) {
        case 'calendar':
            $output .= wpcf_fields_date_get_calendar($params, true, false);
            break;

        default:
            $field_name = '';
            $field_value = wpcf_frontend_wrap_field_value($params['field'],
                    date($params['format'], intval($params['field_value'])),
                    $params);
            $output = wpcf_frontend_wrap_field($params['field'], $field_value,
                    $params);
            break;
    }

    return $output;
}

/**
 * Calendar view.
 * 
 * @global type $wpdb
 * @global type $m
 * @global type $wp_locale
 * @global type $posts
 * @param type $params
 * @param type $initial
 * @param type $echo
 * @return type 
 */
function wpcf_fields_date_get_calendar($params, $initial = true, $echo = true) {

    global $wpdb, $m, $wp_locale, $posts;

    // wpcf Set our own date
    $monthnum = date('n', $params['field_value']);
    $year = date('Y', $params['field_value']);
    $wpcf_date = date('j', $params['field_value']);

    $cache = array();
    // wpcf
    $key = md5($params['field']['slug']);
//    $key = md5($m . $monthnum . $year);
    if ($cache = wp_cache_get('get_calendar', 'calendar')) {
        if (is_array($cache) && isset($cache[$key])) {
            if ($echo) {
                echo apply_filters('get_calendar', $cache[$key]);
                return;
            } else {
                return apply_filters('get_calendar', $cache[$key]);
            }
        }
    }

    if (!is_array($cache))
        $cache = array();

    if (isset($_GET['w']))
        $w = '' . intval($_GET['w']);

    // week_begins = 0 stands for Sunday
    $week_begins = intval(get_option('start_of_week'));

    // Let's figure out when we are
    if (!empty($monthnum) && !empty($year)) {
        $thismonth = '' . zeroise(intval($monthnum), 2);
        $thisyear = '' . intval($year);
    } elseif (!empty($w)) {
        // We need to get the month from MySQL
        $thisyear = '' . intval(substr($m, 0, 4));
        $d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
        $thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')");
    } elseif (!empty($m)) {
        $thisyear = '' . intval(substr($m, 0, 4));
        if (strlen($m) < 6)
            $thismonth = '01';
        else
            $thismonth = '' . zeroise(intval(substr($m, 4, 2)), 2);
    } else {
        $thisyear = gmdate('Y', current_time('timestamp'));
        $thismonth = gmdate('m', current_time('timestamp'));
    }

    $unixmonth = mktime(0, 0, 0, $thismonth, 1, $thisyear);
    $last_day = date('t', $unixmonth);

    /* translators: Calendar caption: 1: month name, 2: 4-digit year */
    $calendar_caption = _x('%1$s %2$s', 'calendar caption');
    $calendar_output = '<table id="wp-calendar" summary="' . esc_attr__('Calendar') . '">
	<caption>' . sprintf($calendar_caption,
                    $wp_locale->get_month($thismonth), date('Y', $unixmonth)) . '</caption>
	<thead>
	<tr>';

    $myweek = array();

    for ($wdcount = 0; $wdcount <= 6; $wdcount++) {
        $myweek[] = $wp_locale->get_weekday(($wdcount + $week_begins) % 7);
    }

    foreach ($myweek as $wd) {
        $day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
        $wd = esc_attr($wd);
        $calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
    }

    $calendar_output .= '
	</tr>
	</thead>

	<tfoot>
	<tr>';

    $calendar_output .= '
	</tr>
	</tfoot>

	<tbody>
	<tr>';
    
    // See how much we should pad in the beginning
    $pad = calendar_week_mod(date('w', $unixmonth) - $week_begins);
    if (0 != $pad)
        $calendar_output .= "\n\t\t" . '<td colspan="' . esc_attr($pad) . '" class="pad">&nbsp;</td>';

    $daysinmonth = intval(date('t', $unixmonth));
    for ($day = 1; $day <= $daysinmonth; ++$day) {
        if (isset($newrow) && $newrow)
            $calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
        $newrow = false;

        if ($day == gmdate('j', current_time('timestamp')) && $thismonth == gmdate('m',
                        current_time('timestamp')) && $thisyear == gmdate('Y',
                        current_time('timestamp')))
            $calendar_output .= '<td id="today">';
        else
            $calendar_output .= '<td>';

        // wpcf
        if ($wpcf_date == $day) {
            $calendar_output .= '<a href="javascript:void(0);">' . $day . '</a>';
        } else {
            $calendar_output .= $day;
        }

        $calendar_output .= '</td>';

        if (6 == calendar_week_mod(date('w',
                                mktime(0, 0, 0, $thismonth, $day, $thisyear)) - $week_begins))
            $newrow = true;
    }

    $pad = 7 - calendar_week_mod(date('w',
                            mktime(0, 0, 0, $thismonth, $day, $thisyear)) - $week_begins);
    if ($pad != 0 && $pad != 7)
        $calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . esc_attr($pad) . '">&nbsp;</td>';

    $calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";

    $cache[$key] = $calendar_output;
    wp_cache_set('get_calendar', $cache, 'calendar');

    if ($echo)
        echo apply_filters('get_calendar', $calendar_output);
    else
        return apply_filters('get_calendar', $calendar_output);
}

/**
 * TinyMCE editor form.
 */
function wpcf_fields_date_editor_callback() {
    $last_settings = wpcf_admin_fields_get_field_last_settings($_GET['field_id']);
    $form = array();
    $form['#form']['callback'] = 'wpcf_fields_date_editor_form_submit';
    $form['style'] = array(
        '#type' => 'radios',
        '#name' => 'wpcf[style]',
        '#options' => array(
            __('Show as calendar', 'wpcf') => 'calendar',
            __('Show as text', 'wpcf') => 'text',
        ),
        '#default_value' => isset($last_settings['style']) ? $last_settings['style'] : 'text',
        '#after' => '<br />',
    );
    $date_formats = apply_filters('date_formats',
            array(
        __('F j, Y'),
        'Y/m/d',
        'm/d/Y',
        'd/m/Y',
            )
    );
    $options = array();
    foreach ($date_formats as $format) {
        $title = date($format, time());
        $field['#title'] = $title;
        $field['#value'] = $format;
        $options[] = $field;
    }
    $custom_format = isset($last_settings['format-custom']) ? $last_settings['format-custom'] : get_option('date_format');
    $options[] = array(
        '#title' => __('Custom'),
        '#value' => 'custom',
        '#suffix' => wpcf_form_simple(array('custom' => array(
                '#name' => 'wpcf[format-custom]',
                '#type' => 'textfield',
                '#value' => $custom_format,
                '#suffix' => '&nbsp;' . date($custom_format, time()),
                '#inline' => true,
                ))
        ),
    );
    $form['toggle-open'] = array(
        '#type' => 'markup',
        '#markup' => '<div id="wpcf-toggle" style="display:none;">',
    );
    $form['format'] = array(
        '#type' => 'radios',
        '#name' => 'wpcf[format]',
        '#options' => $options,
        '#default_value' => isset($last_settings['format']) ? $last_settings['format'] : get_option('date_format'),
        '#after' => '<a href="http://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">'
        . __('Documentation on date and time formatting') . '</a>',
    );
    $form['toggle-close'] = array(
        '#type' => 'markup',
        '#markup' => '</div>',
    );
    $form['field_id'] = array(
        '#type' => 'hidden',
        '#name' => 'wpcf[field_id]',
        '#value' => $_GET['field_id'],
    );
    $form['submit'] = array(
        '#type' => 'markup',
        '#markup' => get_submit_button(__('Insert date', 'wpcf')),
    );
    $f = wpcf_form('wpcf-fields-date-editor', $form);
    add_action('admin_head_wpcf_ajax', 'wpcf_fields_date_editor_form_script');
    wpcf_admin_ajax_head(__('Insert date', 'wpcf'));
    echo '<form id="wpcf-form" method="post" action="">';
    echo $f->renderForm();
    echo '</form>';
    wpcf_admin_ajax_footer();
}

/**
 * AJAX window JS.
 */
function wpcf_fields_date_editor_form_script() {

    ?>
    <script type="text/javascript">
        // <![CDATA[
        jQuery(document).ready(function(){
            jQuery('input[name|="wpcf[style]"]').change(function(){
                if (jQuery(this).val() == 'text') {
                    jQuery('#wpcf-toggle').slideDown();
                } else {
                    jQuery('#wpcf-toggle').slideUp();
                }
            });
            if (jQuery('input:radio[name="wpcf[style]"]:checked').val() == 'text') {
                jQuery('#wpcf-toggle').show();
            }
        });
        // ]]>
    </script>
    <?php
}

/**
 * Inserts shortcode in editor.
 * 
 * @return type 
 */
function wpcf_fields_date_editor_form_submit() {
    require_once WPCF_INC_ABSPATH . '/fields.php';
    if (!isset($_POST['wpcf']['field_id'])) {
        return false;
    }
    $field = wpcf_admin_fields_get_field($_POST['wpcf']['field_id']);
    if (empty($field)) {
        return false;
    }
    $add = ' ';
    $style = isset($_POST['wpcf']['style']) ? $_POST['wpcf']['style'] : 'text';
    $add .= 'style="' . $style . '"';
    $format = '';
    if ($style == 'text') {
        if ($_POST['wpcf']['format'] == 'custom') {
            $format = $_POST['wpcf']['format-custom'];
        } else {
            $format = $_POST['wpcf']['format'];
        }
        if (empty($format)) {
            $format = get_option('date_format');
        }
        $add .= ' format="' . $format . '"';
    }
    $shortcode = wpcf_fields_get_shortcode($field, $add);
    wpcf_admin_fields_save_field_last_settings($_POST['wpcf']['field_id'],
            array(
        'style' => $style,
        'format' => $_POST['wpcf']['format'],
        'format-custom' => $_POST['wpcf']['format-custom'],
            )
    );
    echo wpcf_admin_fields_popup_insert_shortcode_js($shortcode);
    die();
}