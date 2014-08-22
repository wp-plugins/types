<?php
/**
 * Description of class
 *
 * @author Srdjan
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/common/tags/august-release/toolset-forms/classes/class.checkboxes.php $
 * $LastChangedDate: 2014-08-06 00:42:25 +0800 (Wed, 06 Aug 2014) $
 * $LastChangedRevision: 25659 $
 * $LastChangedBy: juan $
 *
 */

require_once 'class.field_factory.php';

class WPToolset_Field_Checkboxes extends FieldFactory
{
    public function metaform()
    {
        global $post;
        $value = $this->getValue();
        $data = $this->getData();
		$name = $this->getName();
        $form = array();
        $_options = array();
        if (isset($data['options'])) {
            foreach ( $data['options'] as $option_key => $option ) {
                
                $checked = isset( $option['checked'] ) ? $option['checked'] : !empty( $value[$option_key] );
                
                if (isset($post) && 'auto-draft' == $post->post_status && array_key_exists( 'checked', $option ) && $option['checked']) {
                    $checked = true;
                }
                
                // Comment out broken code. This tries to set the previous state after validation fails
                //$_values=$this->getValue();
                //if (!$checked&&isset($value)&&!empty($value)&&is_array($value)&&in_array($option['value'],$value)) {
                //    $checked=true;
                //}
                
                $_options[$option_key] = array(
                    '#value' => $option['value'],
                    '#title' => $option['title'],
                    '#type' => 'checkbox',
                    '#default_value' => $checked,
                    '#name' => $option['name']."[]",
                    //'#inline' => true,
                );
				
				if ( !is_admin() ) {// TODO maybe add a doing_ajax() check too, what if we want to load a form using AJAX?
					$_options[$option_key]['#before'] = '<li class="wpt-form-item wpt-form-item-checkbox">';
					$_options[$option_key]['#after'] = '</li>';
					$_options[$option_key]['#pattern'] = '<BEFORE><PREFIX><ELEMENT><LABEL><ERROR><SUFFIX><DESCRIPTION><AFTER>';
				}
            }
        }
        $metaform = array(
            '#type' => 'checkboxes',
            '#options' => $_options,
        );
        if ( is_admin() ) {
            $metaform['#title'] = $this->getTitle();
            $metaform['#after'] = '<input type="hidden" name="_wptoolset_checkbox[' . $this->getId() . ']" value="1" />';
        } else {
			$metaform['#before'] = '<ul class="wpt-form-set wpt-form-set-checkboxes wpt-form-set-checkboxes-' . $name . '">';
			$metaform['#after'] = '</ul>';
		}
        return array($metaform);
    }
}
