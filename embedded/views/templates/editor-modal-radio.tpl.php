<?php
/*
 * Radio editor form.
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Security check' );
}

if ( !isset( $data ) ) {
    $data = array();
}

$data = array_merge( array(
    'options' => array(),
        ), (array) $data );
?>

<div data-bind="template: {name:'tpl-types-modal-radio'}"></div>

<!--TYPES MODAL RADIO-->
<script id="tpl-types-modal-radio" type="text/html">

<input id="radio-display-db" type="radio" name="display" value="db" data-bind="checked: radio_mode" />
<label for="radio-display-db"><?php _e( 'Display the value of this field from the database', 'wpcf' ); ?></label>
<input id="radio-display-val" type="radio" name="display" value="value" data-bind="checked: radio_mode" />
<label for="radio-display-val"><?php _e( 'Show one of these values:', 'wpcf' ); ?></label>

<div id="radio-states" data-bind="visible: radio_mode() == 'value'">
    <?php foreach ( $data['options'] as $id => $option ): ?>
    <label for="radio-<?php echo $id; ?>"><?php echo $option['title']; ?></label>
    <input id="radio-<?php echo $id; ?>" type="text" name="options[<?php echo $id; ?>]" value="<?php echo $option['value']; ?>" />
    <?php endforeach; ?>
</div>

</script><!--END TYPES MODAL RADIO-->