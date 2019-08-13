<?php


/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Model_System_Config_Source_Attributecodes extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
    	$vendorModel = Mage::getModel('dropship/ranking')->getCollection();
    	$options = array();
    	if($vendorModel->count() > 0 ){
    		foreach ($vendorModel as $vendor) {
    			$options[] = array(
    					'label' => $vendor->getAmosoftVendorName(),
    					'value' => $vendor->getAmosoftVendorCode()
    			);
    		}
    	}
    	array_unshift($options,array('value' => '', 'label' => Mage::helper('dropship')->__('--Please Select--'))
    	);
    
        if (!$this->_options) {
        	$this->_options = $options;
            
        }
        
        return $this->_options;
    }
}


