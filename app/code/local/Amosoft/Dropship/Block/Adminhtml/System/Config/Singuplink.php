<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Block_Adminhtml_System_Config_Singuplink extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$url = 'https://portal.amosoft.com/';
		$html = parent::_getElementHtml($element);
		$html .= "<a href='{$url}' target='_blank' title='amosoft'>Sign up here</a>";
		return $html;
	}
}
