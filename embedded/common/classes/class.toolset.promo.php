<?php

class Toolset_Promotion
{
    private $version = '1.0';

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function admin_enqueue_scripts()
    {
        wp_register_style(__CLASS__, plugins_url('/res/css/toolset-modal.css', dirname(__FILE__)), false, $this->version);
    }

    public static function add()
    {
        wp_enqueue_style(__CLASS__);
        add_thickbox();
?>
<div id="js-buy-toolset-embedded-message" style="display:none;">
    <div class="toolset-modal">
        <h2><span class="icon-toolset-logo"></span>Want to edit Views, CRED forms and Layouts? Get the full <em>Toolset</em> package!</h2>
        <div class="content">
            <!--
            <p>THEME_NAME uses the Embedded Toolset plugins. This means that many parts of your theme are available for you to edit, customize and extend.</p>
            -->
            <p class="full">The full <em>Toolset</em> package allows you to develop and customize themes without touching PHP. You will be able to:</p>

            <div class="icons">
                <ul>
                    <li class="template">Create templates</li>
                    <li class="layout">Design page layouts using drag-and-drop</li>
                    <li class="toolset-search">Build parametric searches</li>
                </ul>
                <ul>
                    <li class="list">Display lists of content</li>
                    <li class="form">Create front-end content editing forms</li>
                    <li class="more">and more…</li>
                </ul>
            </div>

            <p class="description">Once you buy the full Toolset, you will be able to edit Views, CRED forms and Layouts in your site, as well as build new ones.</p>

            <!--
            <p>All Toolset packages are intended for site developers. You will be able to create unlimited sites for yourself and for your clients.</p>
            -->

            <a href="#" class="button"><em>Toolset</em> Package Options</a> <a href="#" class="learn">Learn more about <em>Toolset</em></a>
        </div>
    </div>
</div>
<?php
        $url = add_query_arg(
            array(
                'inlineId' => 'js-buy-toolset-embedded-message',
                'width' => 550,
                'height' => 600,
                'modal' => 'true',
            ),
            '#TB_inline'
        );
        printf(' <a href="%s" class="thickbox">open message</a>', $url);
    }

}
