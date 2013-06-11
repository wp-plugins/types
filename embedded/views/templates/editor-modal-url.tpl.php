<?php
/*
 * URL field editor form.
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Security check' );
}

if ( !isset( $data ) ) {
    $data = array();
}

$data = array_merge( array(
    'title' => '',
    'target' => '_self',
    'framename' => '',
        ), (array) $data );
?>

<div data-bind="template: {name:'tpl-types-modal-url'}"></div>

<!--TYPES MODAL URL-->
<script id="tpl-types-modal-url" type="text/html">

<label for="url-title"><?php _e( 'Title', 'wpcf' ); ?></label>
<input id="url-title" type="text" name="title" value="<?php echo $data['title']; ?>" />
<p><?php _e( 'If set, this text will be displayed instead of raw data', 'wpcf' ); ?></p>

</script><!--END TYPES MODAL URL-->

<!--TYPES MODAL URL TARGET-->
<script id="tpl-types-modal-url-target" type="text/html">

<?php foreach ( $data['target_options'] as $target => $title ): ?>
<input id="url-target-<?php echo $target; ?>" type="radio" name="target" value="<?php echo $target; ?>" data-bind="checked: url_target" />
<label for="url-target-<?php echo $target; ?>"><?php echo $title; ?></label>
<?php endforeach; ?>

<div data-bind="visible: url_target() == 'framename'">
    <label for="url-target-framename"><?php _e( 'Enter framename', 'wpcf' ); ?></label>
    <input id="url-target-framename" type="text" name="framename" value="<?php echo $data['framename']; ?>" />
</div>

</script><!--END TYPES MODAL URL TARGET-->