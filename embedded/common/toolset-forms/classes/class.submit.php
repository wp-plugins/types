<?php
/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/common/trunk/toolset-forms/classes/class.submit.php $
 * $LastChangedDate: 2014-10-29 12:24:27 +0100 (śro) $
 * $LastChangedRevision: 28329 $
 * $LastChangedBy: marcin $
 *
 */
require_once 'class.textfield.php';

class WPToolset_Field_Submit extends WPToolset_Field_Textfield
{

    public function metaform()
    {
        $attributes = $this->getAttr();

        $metaform = array();
        $metaform[] = array(
            '#type' => 'submit',
            '#title' => $this->getTitle(),
            '#description' => $this->getDescription(),
            '#name' => $this->getName(),
            '#value' => $this->getValue(),
            '#validate' => $this->getValidationData(),
            '#attributes' => array(
                'class' => '',
            ),
        );
        if (array_key_exists( 'class', $attributes )) {
            $metaform[0]['#attributes']['class'] = $attributes['class'];
        }
        if ( array_key_exists( 'use_bootstrap', $this->_data ) && $this->_data['use_bootstrap'] ) {
            $metaform[0]['#attributes']['class'] .= ' btn btn-primary';
        }
        $this->set_metaform($metaform);
        return $metaform;
    }

}
