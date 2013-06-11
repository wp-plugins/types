<?php
/*
 * Image editor form.
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Security check' );
}

if ( !isset( $data ) ) {
    $data = array();
}

$data = array_merge( array(
    'alignment' => 'none',
    'alignment_options' => array(),
    'alt' => '',
    'height' => '',
    'image' => '',
    'image_data' => array(),
    'image-size' => 'thumbnail',
    'post_id' => -1,
    'preview' => '',
    'size_options' => array(),
    'title' => '',
    'warning_remote' => false,
    'width' => '',
        ), (array) $data );
?>

<div data-bind="template: {name:'tpl-types-modal-image'}"></div>

<!--TYPES MODAL IMAGE-->
<script id="tpl-types-modal-image" type="text/html">

<label for="image-title"><?php _e( 'Image title', 'wpcf' ); ?></label>
<input id="image-title" type="text" name="title" value="<?php echo $data['title']; ?>" />
<p><?php _e( 'Title text for the image, e.g. &#8220;The Mona Lisa&#8221;', 'wpcf' ); ?></p>

<label for="image-alt"><?php _e( 'Alternate Text', 'wpcf' ); ?></label>
<input id="image-alt" type="text" name="alt" value="<?php echo $data['alt']; ?>" />
<p><?php _e( 'Alt text for the image, e.g. &#8220;The Mona Lisa&#8221;', 'wpcf' ); ?></p>

<h2><?php _e( 'Alignment', 'wpcf' ); ?></h2>
<?php foreach ( $data['alignment_options'] as $align => $title ): ?>
<input id="image-align-<?php echo $align; ?>" type="radio" name="alignment" value="<?php echo $align; ?>"<?php if ( $data['alignment'] == $align ) echo ' checked="checked"'; ?> />
<label for="image-align-<?php echo $align; ?>"><?php echo $title; ?></label>
<?php endforeach; ?>

<?php if ( $data['warning_remote'] ) : ?>
<div class="message error"><p><?php echo $data['warning_remote']; ?></p></div>
<?php endif; ?>

<h2><?php _e( 'Pre-defined sizes', 'wpcf' ); ?></h2>
<?php foreach ( $data['size_options'] as $size => $title ): ?>
<input id="image-size-<?php echo $size; ?>" type="radio" name="image-size" value="<?php echo $size; ?>" data-bind="checked: image_size, disable: <?php echo $data['warning_remote'] ? 'true' : 'false'; ?>" />
<label for="image-size-<?php echo $size; ?>"><?php echo $title; ?></label>
<?php endforeach; ?>

<div data-bind="visible: image_size() == 'wpcf-custom'">
    <label for="image-width"><?php _e( 'Width', 'wpcf' ); ?></label>
    <input id="image-width" type="text" name="width" value="<?php echo $data['width']; ?>" />
    <p><?php _e( 'Specify custom width', 'wpcf' ); ?></p>

    <label for="image-height"><?php _e( 'Height', 'wpcf' ); ?></label>
    <input id="image-height" type="text" name="height" value="<?php echo $data['height']; ?>" />
    <p><?php _e( 'Specify custom height', 'wpcf' ); ?></p>

    <label for="image-proportional"><?php _e( 'Keep proportional', 'wpcf' ); ?></label>
    <input id="image-proportional" type="checkbox" name="proportional" value="1" checked="checked" />
</div>

</script><!--END TYPES MODAL IMAGE-->