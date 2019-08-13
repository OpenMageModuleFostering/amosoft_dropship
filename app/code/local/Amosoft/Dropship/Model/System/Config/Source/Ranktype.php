<?php
/**
 * Amosoft
 *

 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Model_System_Config_Source_Ranktype
{
    public function toOptionArray($addEmpty = true)
    {
        
 		return array(
            array('value' => '', 'label' => Mage::helper('dropship')->__('--Please Select--')),
 			array('value' => 'default', 'label' => Mage::helper('dropship')->__('Ranked Based')),
 			array('value' => 'cost', 'label' => Mage::helper('dropship')->__('Cost Based'))
 			
 				);
    }
}
