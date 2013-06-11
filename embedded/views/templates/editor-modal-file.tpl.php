<?php
/*
 * File editor form.
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Security check' );
}

if ( !isset( $data ) ) {
    $data = array();
}

$data = array_merge( array(
    'title' => '',
    'link' => false,
    'file' => '',
        ), (array) $data );
?>

<div data-bind="template: {name:'tpl-types-modal-file'}"></div>

<!--TYPES MODAL FILE-->
<script id="tpl-types-modal-file" type="text/html">

<label for="file-link"><?php _e( 'Display as link', 'wpcf' ); ?></label>
<input id="file-link" type="checkbox" name="link" value="1"<?php echo $data['link'] ? ' checked="checked"' : ''; ?> data-bind="checked: file_mode" />

<label for="file-title"><?php _e( 'Link title', 'wpcf' ); ?></label>
<input id="file-title" type="text" name="title" value="<?php echo $data['title']; ?>" data-bind="disable: !file_mode()" />

</script><!--END TYPES MODAL FILE-->