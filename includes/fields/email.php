<?php

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_email() {
    return array(
        'id' => 'wpcf-email',
        'title' => __('Email', 'wpcf'),
        'description' => __('Email', 'wpcf'),
        'validate' => array('required', 'email'),
        'inherited_field_type' => 'textfield',
        'meta_box_js' => array(
            'wpcf-fields-email-inline' => array(
                'inline' => 'wpcf_fields_email_editor_callback_js',
            ),
        ),
        'editor_callback' => 'wpcfFieldsEmailEditorCallback(%d)'
    );
}

/**
 * Form data for group form.
 * 
 * @return type 
 */
function wpcf_fields_email_insert_form() {
    $form['name'] = array(
        '#type' => 'textfield',
        '#title' => __('Name of custom field', 'wpcf'),
        '#description' => __('Under this name field will be stored in DB (sanitized)',
                'wpcf'),
        '#name' => 'name',
        '#attributes' => array('class' => 'wpcf-forms-set-legend'),
        '#validate' => array('required' => array('value' => true)),
    );
    $form['description'] = array(
        '#type' => 'textarea',
        '#title' => __('Description', 'wpcf'),
        '#description' => __('Text that describes function to user', 'wpcf'),
        '#name' => 'description',
        '#attributes' => array('rows' => 5, 'cols' => 1),
    );
    return $form;
}

/**
 * View function.
 * 
 * @param type $params 
 */
function wpcf_fields_email_view($params) {
    if ($params['style'] == 'raw') {
        return '';
    }
    $title = '';
    $add = '';
    if (!empty($params['title'])) {
        $add .= ' title="' . $params['title'] . '"';
        $title .= $params['title'];
    } else {
        $add .= ' title="' . $params['field_value'] . '"';
        $title .= $params['field_value'];
    }
    $output = '<a href="mailto:' . $params['field_value'] . '"' . $add . '>'
            . $title . '</a>';
    $output = wpcf_frontend_wrap_field_value($params['field'], $output);
    return wpcf_frontend_wrap_field($params['field'], $output, $params);
}

/**
 * Editor callback JS function
 */
function wpcf_fields_email_editor_callback_js() {

    ?>
    <script type="text/javascript">
        //<![CDATA[
        function wpcfFieldsEmailEditorCallback(field_id) {
            var url = "<?php echo admin_url('admin-ajax.php'); ?>?action=wpcf_ajax&wpcf_action=editor_callback&field_id="+field_id+"&keepThis=true&TB_iframe=true&width=400&height=400";
            tb_show("<?php _e('Insert email',
            'wpcf'); ?>", url);
                }
                //]]>
    </script>
    <?php
}

/**
 * Editor callback form.
 */
function wpcf_fields_email_editor_callback() {
    $form = array();
    $form['#form']['callback'] = 'wpcf_fields_email_editor_submit';
    $form['title'] = array(
        '#type' => 'textfield',
        '#title' => __('Title', 'wpcf'),
        '#description' => __('If set, this text will be displayed instead of raw data'),
        '#name' => 'title',
    );
    $form['submit'] = array(
        '#type' => 'markup',
        '#markup' => get_submit_button(),
    );
    $f = wpcf_form('wpcf-form', $form);
    wpcf_admin_ajax_head('Insert email', 'wpcf');
    echo '<form method="post" action="">';
    echo $f->renderForm();
    echo '</form>';
    wpcf_admin_ajax_footer();
}

/**
 * Editor callback form submit.
 */
function wpcf_fields_email_editor_submit() {
    $add = '';
    if (!empty($_POST['title'])) {
        $add = ' title="' . strval($_POST['title']) . '"';
    }
    $field = wpcf_admin_fields_get_field($_GET['field_id']);
    if (!empty($field)) {
        $shortcode = wpcf_fields_get_shortcode($field, $add);
        echo wpcf_admin_fields_popup_insert_shortcode_js($shortcode);
        die();
    }
}