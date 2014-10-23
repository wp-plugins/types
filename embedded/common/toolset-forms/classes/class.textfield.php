<?php
/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/common/trunk/toolset-forms/classes/class.textfield.php $
 * $LastChangedDate: 2014-09-23 15:35:22 +0200 (Tue, 23 Sep 2014) $
 * $LastChangedRevision: 27379 $
 * $LastChangedBy: marcin $
 *
 */
require_once "class.field_factory.php";
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author Franko
 */
class WPToolset_Field_Textfield extends FieldFactory
{
    public function metaform()
    {
        $attributes =  $this->getAttr();

        $metaform = array();
        $metaform[] = array(
            '#type' => 'textfield',
            '#title' => $this->getTitle(),
            '#description' => $this->getDescription(),
            '#name' => $this->getName(),
            '#value' => $this->getValue(),
            '#validate' => $this->getValidationData(),
            '#repetitive' => $this->isRepetitive(),
            '#attributes' => $attributes,
            'wpml_action' => $this->getWPMLAction(),
        );
        return $metaform;
    }

}
