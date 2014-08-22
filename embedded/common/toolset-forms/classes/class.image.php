<?php
require_once 'class.file.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/common/tags/august-release/toolset-forms/classes/class.image.php $
 * $LastChangedDate: 2014-07-29 23:56:51 +0800 (Tue, 29 Jul 2014) $
 * $LastChangedRevision: 25431 $
 * $LastChangedBy: marcin $
 *
 */
class WPToolset_Field_Image extends WPToolset_Field_File
{
    public function metaform()
    {
        $form = parent::metaform();
        return self::setForm($form);
    }

    public static function setForm($form)
    {
        if ( !isset( $form[0] ) || !is_array($form[0] ) ) {
            return $form;
        }
        if ( !array_key_exists( '#validate', $form[0] ) ) {
            $form[0]['#validate'] = array();
        }
        if ( !array_key_exists( 'extension', $form[0]['#validate'] ) ) {
            $form[0]['#validate']['extension'] = array(
                'args' => array(
                    'extension',
                    'jpg|jpeg|gif|png',
                ),
                'message' => __( 'You can add only images.', 'wpv-views' ),
            );
        }
        return $form;
    }
}
