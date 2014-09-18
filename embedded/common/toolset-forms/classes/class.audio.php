<?php
require_once 'class.file.php';

/**
 * Description of class
 *
 * @author Srdjan
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/common/tags/Views-1.6.4-CRED-1.3.2-Types-1.6.4-Acces-1.2.3/toolset-forms/classes/class.audio.php $
 * $LastChangedDate: 2014-08-22 18:23:29 +0800 (Fri, 22 Aug 2014) $
 * $LastChangedRevision: 26350 $
 * $LastChangedBy: francesco $
 *
 */
class WPToolset_Field_Audio extends WPToolset_Field_File
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
                '16svx|2sf|8svx|aac|aif|aifc|aiff|amr|ape|asf|ast|au|aup|band|brstm|bwf|cdda|cust|dsf|dwd|flac|gsf|gsm|gym|it|jam|la|ly|m4a|m4p|mid|minipsf|mng|mod|mp1|mp2|mp3|mp4|mpc|mscz|mt2|mus|niff|nsf|off|ofr|ofs|ogg|ots|pac|psf|psf2|psflib|ptb|qsf|ra|raw|rka|rm|rmj|s3m|shn|sib|sid|smp|spc|spx|ssf|swa|tta|txm|usf|vgm|voc|vox|vqf|wav|wma|wv|xm|ym',
            ),
            'message' => __( 'You can add only audio.', 'wpv-views' ),
        );
        return $validation;
    }
}
