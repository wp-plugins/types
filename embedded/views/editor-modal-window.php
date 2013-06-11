<?php
/*
 * Editor modal window.
 * Used to display shortcode options and trigger inserting.
 */

if ( !defined( 'ABSPATH' ) ) {
    die( 'Security check' );
}

if ( !isset( $data ) ) {
    $data = array();
}

$data = array_merge( array(
    'title' => sprintf( __( 'Insert %s', 'wpcf' ),
            ucfirst( $data['field']['type'] ) ),
    'tabs' => array(),
    'supports' => array(),
    'user_form' => '',
    'parents' => array(),
    'post_types' => get_post_types( array('show_ui' => true) ),
    'style' => '',
    'css' => '',
        ), (array) $data );
?>

<!-- TYPES MODAL WINDOW -->
<div id="types-editor-modal">
    <div class="media-modal wp-core-ui">
        <a class="media-modal-close" href="#" title="<?php esc_attr_e( 'Close' ); ?>"><span class="media-modal-icon"></span></a>
        <div class="media-modal-content">
            <div class="media-frame-menu">
                <div class="media-menu">
                    <?php foreach( $data['tabs'] as $tab ): ?>
                    <a class="media-menu-item js-raw-disable" href="#"><?php echo $tab['menu_title']; ?></a>
                    <?php endforeach; ?>
                    <?php if (in_array( 'style', $data['supports'] )): ?>
                    <a class="media-menu-item js-raw-disable" href="#"><?php _e( 'Styling', 'wpcf' ); ?></a>
                    <?php endif; ?>
                    <?php if ( !empty( $data['user_form'] ) ): ?>
                    <a class="media-menu-item" href="#"><?php _e( 'User', 'wpcf' ); ?></a>
                    <?php endif; ?>
                    <?php if ( $data['context'] == 'postmeta' ): ?>
                    <a class="media-menu-item" href="#"><?php _e( 'Post ID', 'wpcf' ); ?></a>
                    <?php endif; ?>
                    <div class="separator"></div>
                    <input type="checkbox" id="types-modal-raw" name="raw_mode" value="1" data-bind="checked: raw, click: rawDisableAll" />&nbsp;<label for="types-modal-raw"><?php _e( 'RAW field output', 'wpcf' ); ?><span class="types-modal-help-icon"></span></label>
                </div>
            </div>
            <div class="media-frame-title">
                <h1><?php echo $data['title']; ?></h1>
            </div>
            <div class="media-frame-router">
                <div class="media-router">
                    <!--<a class="media-menu-item" href="#">Upload Files</a>
                    <a class="media-menu-item active" href="#">Media Library</a>-->
                </div>
            </div>
            <div class="media-frame-content">
                <div class="message updated" data-bind="visible: raw()"><p><?php _e( 'RAW more is selected. The field value will be displayed, without any formatting.', 'wpcf' ); ?></p></div>
                <?php foreach( $data['tabs'] as $tab ): ?>
                    <div class="tab js-raw-disable">
                        <h2><?php echo $tab['title']; ?></h2>
                        <?php echo $tab['content']; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (in_array( 'style', $data['supports'] )): ?>
                <div class="tab js-raw-disable" data-bind="template: {name:'tpl-types-editor-modal-styling'}"></div>
                <?php endif; ?>
                <?php if ( !empty( $data['user_form'] ) ): ?>
                <div class="tab">
                    <h2><?php _e( 'Display the field for this user', 'wpcf' ); ?></h2>
                    <?php echo $data['user_form']; ?>
                </div>
                <?php endif; ?>
                <?php if ( $data['context'] == 'postmeta' ): ?>
                <div class="tab" data-bind="template: {name:'tpl-types-editor-modal-post_id'}"></div>
                <?php endif; ?>
            </div>
            <div class="media-frame-toolbar">
                <div class="media-toolbar-secondary"></div>
                <div class="media-toolbar-primary">
                    <a class="button media-button button-primary button-large media-button-insert" href="#"><?php _e( 'Insert shortcode', 'wpcf' ); ?></a>
                </div>
            </div>
        </div>
    </div>
    <div class="media-modal-backdrop"></div>
</div>

<!-- STYLE FORM -->
<script id="tpl-types-editor-modal-styling" type="text/html">

<h2><?php _e( 'Style and CSS', 'wpcf' ); ?></h2>
<label for="types-modal-style"><?php _e( 'Style', 'wpcf' ); ?></label>
<input type="text" name="style" value="<?php echo $data['style']; ?>" id="types-modal-style" />
<label for="types-modal-css"><?php _e( 'CSS', 'wpcf' ); ?></label>
<input type="text" name="css" value="<?php echo $data['css']; ?>" id="types-modal-css" />

</script><!-- END STYLE FORM -->

<!-- POST ID FORM -->
<script id="tpl-types-editor-modal-post_id" type="text/html">

<h2><?php _e( 'Display this field for this post', 'wpcf' ); ?></h2>
<input type="radio" id="post-id-current" name="post_id" value="current" data-bind="checked: mode" />
<label for="post-id-current"><?php _e( 'The current post being displayed either directly or in a View loop', 'wpcf' ); ?></label>

<input type="radio" id="post-id-parent" name="post_id" value="parent" data-bind="checked: mode" />
<label for="post-id-parent"><?php _e( 'The parent of the current post (Wordpress parent)', 'wpcf' ); ?></label>

<?php if ( !empty( $data['parents'] ) ): ?>

<input type="radio" id="post-id-related" name="post_id" value="related" data-bind="checked: mode" />
<label for="post-id-related"><?php _e( 'A related post to the current post', 'wpcf' ); ?></label>
<div data-bind="visible: mode() == 'related'" >
    <?php foreach ( $data['parents'] as $post ): ?>
    <input type="radio" name="related_post" id="post-id-<?php echo $post->ID; ?>" value="<?php echo $post->post_type; ?>" />
    <label for="post-id-<?php echo $post->ID; ?>"><?php echo $post->post_type; ?></label>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php if ( empty( $data['parents'] ) ): ?>

<input type="radio" id="post-id-related" name="post_id" value="related" data-bind="checked: mode" />
<label for="post-id-related"><?php _e( 'A related post to the current post', 'wpcf' ); ?></label>
<div data-bind="visible: mode() == 'related'" >
    <label for="post-id-related-post-type"><?php _e( 'Post type', 'wpcf' ); ?></label>
    <select id="post-id-related-post-type" name="related_post">
        <?php foreach ( $data['post_types'] as $post_type ): ?>
        <option value="<?php echo $post_type; ?>"><?php echo $post_type; ?></option>
        <?php endforeach; ?>
    </select>
</div>

<?php endif; ?>

<input type="radio" id="post-id" name="post_id" value="post_id" data-bind="checked: mode" />
<label for="post-id"><?php _e( 'A specify post', 'wpcf' ); ?></label>

<div data-bind="visible: mode() == 'post_id'">
    <label for="post-id-post_id"><?php _e( 'Post ID', 'wpcf' ); ?></label>
    <input type="text" id="post-id-post_id" name="specific_post_id" />
</div>

</script><!-- END POST ID FORM -->