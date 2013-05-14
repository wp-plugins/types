<?php

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_textarea() {
    return array(
        'id' => 'wpcf-textarea',
        'title' => __('Multiple lines', 'wpcf'),
        'description' => __('Textarea', 'wpcf'),
        'validate' => array('required'),
    );
}

/**
 * Meta box form.
 * 
 * @param type $field
 * @return string 
 */
function wpcf_fields_textarea_meta_box_form($field) {
    $form = array();
    $form['name'] = array(
        '#type' => 'textarea',
        '#name' => 'wpcf[' . $field['slug'] . ']',
    );
    return $form;
}

/**
 * Formats display data.
 */
function wpcf_fields_textarea_view($params) {

    $value = $params['field_value'];

    // see if it's already wrapped in <p> ... </p>
    $wrapped_in_p = false;
    if (!empty($value) && strpos($value, '<p>') === 0 && strrpos($value,
                    "</p>\n") == strlen($value) - 5) {
        $wrapped_in_p = true;
    }

    // use wpautop for converting line feeds to <br />, etc
    $value = wpautop($value);

    if (!$wrapped_in_p) {
        // If it wasn't wrapped then remove the wrapping wpautop has added.
        if (!empty($value) && strpos($value, '<p>') === 0 && strrpos($value,
                        "</p>\n") == strlen($value) - 5) {
            // unwrapp the <p> ..... </p>
            $value = substr($value, 3, -5);
        }
    }

    return $value;
}

/**
 * Editor callback form.
 */
function wpcf_fields_textarea_editor_callback_nonpopup() {
    wp_enqueue_style( 'wpcf-fields-file',
            WPCF_EMBEDDED_RES_RELPATH . '/css/basic.css', array(), WPCF_VERSION );
	$form = array();
    $form['#form']['callback'] = 'wpcf_fields_textarea_editor_submit_nonpopup';
    
	// add usermeta form addon
	if ( isset($_GET['field_type']) && $_GET['field_type'] == 'usermeta' ){
		$temp_form = wpcf_get_usermeta_form_addon();
		$form = $form + $temp_form;
	}
    $form['submit'] = array(
        '#type' => 'submit',
        '#name' => 'submit',
        '#value' => __( 'Insert shortcode', 'wpcf' ),
        '#attributes' => array('class' => 'button-primary'),
    );
    $f = wpcf_form( 'wpcf-form', $form );
    wpcf_admin_ajax_head( 'Insert select', 'wpcf' );
    echo '<form method="post" action="">';
    echo $f->renderForm();
    echo '</form>';
    wpcf_admin_ajax_footer();
}


/**
 * Editor callback form submit.
 */
function wpcf_fields_textarea_editor_submit_nonpopup() {
    $add = '';
    if ( !empty($_POST['is_usermeta']) ){
		$add .= wpcf_get_usermeta_form_addon_submit();
	}
	//Get Field
	if ( !empty($_POST['is_usermeta']) ){
		$field = wpcf_admin_fields_get_field( $_GET['field_id'], false, false, false, 'wpcf-usermeta' );
		$types_attr = 'usermeta';
	}else{
		$field = wpcf_admin_fields_get_field( $_GET['field_id'] );	
	}
  	if ( !empty( $field ) ) {
        if ($types_attr == 'usermeta'){
			$shortcode = wpcf_usermeta_get_shortcode( $field, $add );
		}
		else{
			$shortcode = wpcf_fields_get_shortcode( $field, $add );
		}
        echo editor_admin_popup_insert_shortcode_js( $shortcode );
        die();
    }
}