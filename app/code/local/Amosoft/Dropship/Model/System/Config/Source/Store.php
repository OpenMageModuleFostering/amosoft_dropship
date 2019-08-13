<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Model_System_Config_Source_Store 
{
    protected $_options;
    
    
    public function toOptionArray()
    {
    	$options = array();
        if (!$this->_options) {
            $this->_options = Mage::getResourceModel('core/store_collection')
                ->load();
            
            foreach ($this->_options as $val){
            	$options[] =array(
            			'label' => $val->getName().'-'.$val->getStoreId(),
            			'value' =>  $val->getStoreId()
            	);
            }
        }
        
        return $options;
    }
}

