<?php

// TODO 1.2.3 Allow saving numeric as 0.
// 
// Trigger after main hook
//add_action( 'save_post', 'wpcf_fields_numeric_save_check', 15, 3 );

add_filter( 'wpcf_fields_type_numeric_value_save',
        'wpcf_fields_numeric_value_save_filter', 10, 3 );
add_filter( 'wpcf_fields_type_numeric_value_display',
        'wpcf_fields_type_numeric_value_display_by_locale', 10, 3 );
add_filter( 'wpcf_fields_numeric_meta_box_form_value_display',
        'wpcf_fields_numeric_meta_box_form_value_display_by_locale', 10, 3 );		
		
	
/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_numeric() {
    return array(
        'id' => 'wpcf-numeric',
        'title' => __( 'Numeric', 'wpcf' ),
        'description' => __( 'Numeric', 'wpcf' ),
        'validate' => array('required', 'number' => array('forced' => true)),
        'inherited_field_type' => 'textfield',
        'meta_key_type' => 'NUMERIC',
		'meta_box_js' => array( 'wpcf_field_number_validation_fix' => array(
                'inline' => 'wpcf_field_number_validation_fix') ),
    );
}

function wpcf_fields_numeric_meta_box_form_value_display_by_locale($field){
	$locale = localeconv();	
	if ( $locale['decimal_point'] != '.' ){
		$field['#value'] = str_replace('.', ',', $field['#value']);
	}
	return $field;
}

/**
 * wpcf_fields_numeric_value_save_filter
 *
 * if decimal_point = comma, replace point to comma.
*/
function wpcf_fields_type_numeric_value_display_by_locale($val){
	$locale = localeconv();	
	if ( $locale['decimal_point'] != '.' ){
		$val = str_replace('.', ',', $val);	
	}
	return $val;
}

/**
 * wpcf_fields_numeric_value_save_filter
 *
 * if decimal_point = comma, replace comma to point.
*/
function wpcf_fields_numeric_value_save_filter($val){
	$locale = localeconv();	
	if ( $locale['decimal_point'] != '.' ){
		$val = str_replace(',', '.', $val);	
	}
	return $val;
}
/**
 * wpcf_field_number_validation_fix
 *
 * Fix JS validation for field:numeric. Allow comma validation 
 */
function wpcf_field_number_validation_fix(){
	$locale = localeconv();	
	if ( $locale['decimal_point'] != '.' ){
	wp_enqueue_script( 'wpcf-numeric',
            WPCF_EMBEDDED_RES_RELPATH
            . '/js/numeric_fix.js', array('jquery'), WPCF_VERSION );
	}
}


/**
 * Editor callback form.
 */
function wpcf_fields_numeric_editor_callback() {
    wp_enqueue_style( 'wpcf-fields',
            WPCF_EMBEDDED_RES_RELPATH . '/css/basic.css', array(), WPCF_VERSION );
    wp_enqueue_script( 'jquery' );

    // Get field
	if ( isset($_GET['field_type']) && $_GET['field_type'] == 'usermeta' ){
		//If usermeta
		$field = wpcf_admin_fields_get_field( $_GET['field_id'], false, false, false, 'wpcf-usermeta' );	
	}else{ 
		//If postmeta
		$field = wpcf_admin_fields_get_field( $_GET['field_id'] );	
	}
    if ( empty( $field ) ) {
        _e( 'Wrong field specified', 'wpcf' );
        die();
    }

    $last_settings = wpcf_admin_fields_get_field_last_settings( $_GET['field_id'] );

    $form = array();
    $form['#form']['callback'] = 'wpcf_fields_numeric_editor_submit';
    $form['format'] = array(
        '#type' => 'textfield',
        '#title' => __( 'Output format', 'wpcf' ),
        '#description' => __( "Similar to sprintf function. Default: 'FIELD_NAME: FIELD_VALUE'.",
                'wpcf' ),
        '#name' => 'format',
        '#value' => isset( $last_settings['format'] ) ? $last_settings['format'] : 'FIELD_NAME: FIELD_VALUE',
    );
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
    wpcf_admin_ajax_head( 'Insert numeric', 'wpcf' );
    echo '<form method="post" action="">';
    echo $f->renderForm();
    echo '</form>';
}

/**
 * Editor callback form submit.
 */
function wpcf_fields_numeric_editor_submit() {
    $add = '';
	$types_attr = 'field';
    if ( !empty( $_POST['format'] ) ) {
        $add .= ' format="' . strval( $_POST['format'] ) . '"';
    }
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
        wpcf_admin_fields_save_field_last_settings( $_GET['field_id'],
                array('format' => $_POST['format'])
        );
        echo editor_admin_popup_insert_shortcode_js( $shortcode );
        die();
    }
}

/**
 * View function.
 * 
 * @param type $params 
 */
function wpcf_fields_numeric_view( $params ) {
    $output = '';
    if ( !empty( $params['format'] ) ) {
        $patterns = array('/FIELD_NAME/', '/FIELD_VALUE/');
        $replacements = array($params['field']['name'], $params['field_value']);
        $output = preg_replace( $patterns, $replacements, $params['format'] );
        $output = sprintf( $output, $params['field_value'] );
    } else {
        $output = $params['field_value'];
    }
    return $output;
}