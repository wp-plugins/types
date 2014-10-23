<?php
require_once 'class.file.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 * $HeadURL$
 * $LastChangedDate$
 * $LastChangedRevision$
 * $LastChangedBy$
 *
 */
class WPToolset_Field_Image extends WPToolset_Field_File
{
    public function metaform()
    {
        $validation = $this->getValidationData();
        $validation = self::addTypeValidation($validation);
        $this->setValidationData($validation);
        return parent::metaform();        
    }

    public static function addTypeValidation($validation) {
        $validation['extension'] = array(
            'args' => array(
                'extension',
                'jpg|jpeg|gif|png|bmp|webp',
            ),
            'message' => __( 'You can add only images.', 'wpv-views' ),
        );
        return $validation;
    }    
}
