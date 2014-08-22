<?php
require_once 'class.credfile.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/common/tags/august-release/toolset-forms/classes/class.credvideo.php $
 * $LastChangedDate: 2014-08-14 21:05:33 +0800 (Thu, 14 Aug 2014) $
 * $LastChangedRevision: 25980 $
 * $LastChangedBy: francesco $
 *
 */
class WPToolset_Field_Credvideo extends WPToolset_Field_Credfile
{
    protected $_settings = array('min_wp_version' => '3.6');

    public function metaform()
    {
        //TODO: check if this getValidationData does not break PHP Validation _cakePHP required file.
        $validation = $this->getValidationData();
        $validation['extension'] = array(
                'args' => array(
                    'extension',
                    '3gp|aaf|asf|avchd|avi|cam|dat|dsh|fla|flr|flv|m1v|m2v|m4v|mng|mp4|mxf|nsv|ogg|rm|roq|smi|sol|svi|swf|wmv|wrap|mkv|mov|mpe|mpeg|mpg',
                ),
                'message' => __( 'You can add only video.', 'wpv-views' ),
            );
        $this->setValidationData($validation);
        return parent::metaform();        
    }
}
