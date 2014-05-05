<?php
/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/common/tags/Views-1.6-Types-1.5.6/toolset-forms/classes/class.checkbox.php $
 * $LastChangedDate: 2014-04-29 12:07:22 +0000 (Tue, 29 Apr 2014) $
 * $LastChangedRevision: 21855 $
 * $LastChangedBy: marcin $
 *
 */
require_once 'class.field_factory.php';

/**
 * Description of class
 *
 * @author Srdjan
 */
class WPToolset_Field_Checkbox extends FieldFactory
{
    public function metaform()
    {
        $value = $this->getValue();
        $data = $this->getData();

        if ( !empty( $value ) || $value == '0' ) {
            $data['default_value'] = $value;
        }
        /**
         * setup default value
         */
        $default_value = null;
        if (
            array_key_exists( 'default_value', $data )
            && (
                array_key_exists( 'checked', $data )
                && $data['checked']
            )
        ) {
            $default_value = (bool) $data['default_value'];
        }
        $form = array();
        $form[] = array(
            '#type' => 'checkbox',
            '#value' => $value,
            '#default_value' => $default_value,
            '#name' => $this->getName(),
            '#title' => $this->getTitle(),
            '#validate' => $this->getValidationData(),
            '#after' => '<input type="hidden" name="_wptoolset_checkbox[' . $this->getId() . ']" value="1" />',
            '#checked' => array_key_exists( 'checked', $data ) ? $data['checked']:null,
        );
        return $form;
    }
}
