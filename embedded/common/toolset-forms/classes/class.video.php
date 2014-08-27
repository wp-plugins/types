<?php
require_once 'class.file.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/common/tags/Views-1.6.3-CRED-1.3.1-Types-1.6.1/toolset-forms/classes/class.video.php $
 * $LastChangedDate: 2014-08-22 18:23:29 +0800 (Fri, 22 Aug 2014) $
 * $LastChangedRevision: 26350 $
 * $LastChangedBy: francesco $
 *
 */
class WPToolset_Field_Video extends WPToolset_Field_File
{
    protected $_settings = array('min_wp_version' => '3.6');

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
                '3gp|aaf|asf|avchd|avi|cam|dat|dsh|fla|flr|flv|m1v|m2v|m4v|mng|mp4|mxf|nsv|ogg|rm|roq|smi|sol|svi|swf|wmv|wrap|mkv|mov|mpe|mpeg|mpg',
            ),
            'message' => __( 'You can add only video.', 'wpv-views' ),
        );
        return $validation;
    }    
}
