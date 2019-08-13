<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Block_Adminhtml_System_Config_Helplink extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$url = Mage::helper('dropship')->getConfigObject('apiconfig/helpurl/link');
		$html = parent::_getElementHtml($element);
		$html .= "<a href='{$url}' target='_blank' title='amosoft'>Visit dropship Knowledge Base</a>";
		return $html;
	}
}
