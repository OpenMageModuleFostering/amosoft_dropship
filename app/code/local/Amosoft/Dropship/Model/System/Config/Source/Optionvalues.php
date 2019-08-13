<?php


/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Model_System_Config_Source_Optionvalues {
    
     
    public function toOptionArray()
    {
        
        $optionsArray = $this->getOptionValue();
        if(is_array($optionsArray)){
        array_unshift($optionsArray,array('value' => '', 'label' => Mage::helper('dropship')->__('--Please Select--')));
        array_push($optionsArray,array('value' => 'addnew', 'label' => Mage::helper('dropship')->__('Add new code')));
        }
        else
        {
           $optionsArray = array(array('value' => '', 'label' => Mage::helper('dropship')->__('--Please Select--')),
               array('value' => 'addnew', 'label' => Mage::helper('dropship')->__('Add new code'))); 
        }
//        echo '<pre>';
//        print_r($optionsArray);
//        die('save ememm');
        return  $optionsArray;
        
    }
    
    public function getOptionValue()
    {
        $integration = Mage::getStoreConfig('amosoft_integration/integration/supplier_attribute');
        $amosoftCollection = Mage::getModel('dropship/supplier')->getCollection();
        $attributeArray = array();
        if($integration != null && $integration)
        {
        $attributeDetails = Mage::getSingleton("eav/config")->getAttribute("catalog_product", $integration);
        $options = $attributeDetails->getSource()->getAllOptions(false);
        foreach ($options as $option) {
            
            $attributeArray[] = array('value'=>$option["label"],'label' => Mage::helper('dropship')->__(strtolower($option["label"])));
        }
        }else
        {
          $amosoftCollection ->addFieldToFilter(array('is_update', 'verified'), array('1', '0'))->addFieldToFilter('status','')
          ->addFieldToSelect(array('company_id', 'magento_vendor_code'));
          $comapnyIds = $amosoftCollection->getData();
        if (count($comapnyIds) > 0) {
            foreach ($comapnyIds as $key=>$value) {
                $attributeArray[] = array('value'=>$value['magento_vendor_code'],'label' => Mage::helper('dropship')->__(strtolower($value['magento_vendor_code'])));
            }
        }
        }
        
        return $attributeArray;
        
    }
}

