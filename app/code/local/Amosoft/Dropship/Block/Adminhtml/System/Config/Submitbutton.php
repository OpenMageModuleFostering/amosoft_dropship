<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Block_Adminhtml_System_Config_Submitbutton
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
    }

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
    	$element->setReadonly('readonly');
    	return parent::_getElementHtml($element);
    }

}
