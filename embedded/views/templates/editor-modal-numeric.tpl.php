<?php
/*
 * Numeric editor form.
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Security check' );
}

if ( !isset( $data ) ) {
    $data = array();
}

$data = array_merge( array(
    'format' => 'FIELD_NAME: FIELD_VALUE',
        ), (array) $data );
?>

<div data-bind="template: {name:'tpl-types-modal-numeric'}"></div>

<!--TYPES MODAL NUMERIC-->
<script id="tpl-types-modal-numeric" type="text/html">

<label for="numeric-format"><?php _e( 'Output format', 'wpcf' ); ?></label>
<input id="numeric-format" type="text" name="format" value="<?php echo $data['format']; ?>" />
<p><?php _e( "Similar to sprintf function. Default: 'FIELD_NAME: FIELD_VALUE'.", 'wpcf' ); ?></p>

</script><!--END TYPES MODAL NUMERIC-->