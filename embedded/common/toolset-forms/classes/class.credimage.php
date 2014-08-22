<?php
require_once 'class.credfile.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/common/tags/august-release/toolset-forms/classes/class.credimage.php $
 * $LastChangedDate: 2014-08-14 21:05:33 +0800 (Thu, 14 Aug 2014) $
 * $LastChangedRevision: 25980 $
 * $LastChangedBy: francesco $
 *
 */
class WPToolset_Field_Credimage extends WPToolset_Field_Credfile
{
    public function metaform()
    {
        //TODO: check if this getValidationData does not break PHP Validation _cakePHP required file.
        $validation = $this->getValidationData();
        $validation['extension'] = array(
                'args' => array(
                    'extension',
                    'jpg|jpeg|gif|png|bmp|webp',
                ),
                'message' => __( 'You can add only images.', 'wpv-views' ),
            );
        $this->setValidationData($validation);
        return parent::metaform();        
    }
}
