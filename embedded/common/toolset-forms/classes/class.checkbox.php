<?php
/**
 *
 * $HeadURL$
 * $LastChangedDate$
 * $LastChangedRevision$
 * $LastChangedBy$
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
        global $post;

        $value = $this->getValue();
        $data = $this->getData();
        /**
         * turn off autocheck for saved posts
         */
        if ( 'auto-draft' != $post->post_status && empty( $data['value'] )) {
            $data['checked'] = false;

        }
        $form = array();
        $form[] = array(
            '#type' => 'checkbox',
            '#value' => $value,
            '#default_value' => $data['default_value'],
            '#name' => $this->getName(),
            '#title' => $this->getTitle(),
            '#validate' => $this->getValidationData(),
            '#after' => '<input type="hidden" name="_wptoolset_checkbox[' . $this->getId() . ']" value="1" />',
            '#checked' => array_key_exists( 'checked', $data ) ? $data['checked']:null,
        );
        return $form;
    }
}
