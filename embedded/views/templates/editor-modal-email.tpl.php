<?php
/*
 * Email editor form.
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Security check' );
}

if ( !isset( $data ) ) {
    $data = array();
}

$data = array_merge( array(
    'title' => '',
        ), (array) $data );
?>

<div data-bind="template: {name:'tpl-types-modal-email'}"></div>

<!--TYPES MODAL EMAIL-->
<script id="tpl-types-modal-email" type="text/html">

<label for="email-title"><?php _e( 'Title', 'wpcf' ); ?></label>
<input id="email-title" type="text" name="title" value="<?php echo $data['title']; ?>" />
<p><?php _e( 'If set, this text will be displayed instead of raw data', 'wpcf' ); ?></p>

</script><!--END TYPES MODAL EMAIL-->