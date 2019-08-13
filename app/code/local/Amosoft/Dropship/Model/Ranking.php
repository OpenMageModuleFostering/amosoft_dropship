<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Model_Ranking extends Mage_Core_Model_Abstract
{
    protected function _construct(){

       $this->_init("dropship/ranking");
    }
    public function rearrangeRank($value, $rank){		
		if($rank > 0 && $rank < $value->getRanking() ){
			$rankData = Mage::getModel('dropship/ranking')->load($value->getId());
			$rankData->setRanking($rank);
			$rankData->save();
			$rank++;	
		}	
    }    
    public function getVendorCollection($type)
    {
    	$orderBy = ($type == 'yes') ? 'ranking asc':'id asc';
    	$vendorCollection =  $this->getCollection()->addFieldToFilter('is_dropship',$type)->addFieldToFilter('is_active','yes');
    	$vendorCollection->getSelect()->order($orderBy); 	
    	$arrVendor = array();
    	if($vendorCollection->count() > 0 ){
    		foreach ($vendorCollection as $vendor) {
    			$arrVendor[] = array('name'=>$vendor->getAmosoftVendorName(),'code'=>$vendor->getAmosoftVendorCode(),'link'=>is_null($vendor->getLinkingAttribute()) ? '' : $vendor->getLinkingAttribute());   			
    		}
    	}
    	return $arrVendor;   	
    }
        
    public function addPreDefineVendorList(){
    	
    	$preDefinedArr = array(array('MagVendID1','Supplier 1',1),array('MagVendID2','Supplier 2',2),array('MagVendID3','Supplier 3',3),array('MagVendID4','Supplier 4',4),array('MagVendID5','Supplier 5',5),array('MagVendID6','Supplier 6',6),array('MagVendID7','Supplier 7',7),array('MagVendID8','Supplier 8',8),array('MagVendID9','Supplier 9',9),array('MagVendID10','Supplier 10',10),array('MagVendID11','Supplier 11',11),array('MagVendID12','Supplier 12',12),array('MagVendID13','Supplier 13',13),array('MagVendID14','Supplier 14',14),array('MagVendID15','Supplier 15',15));
    	
    	$arrRequired = array();
    	foreach ($preDefinedArr as $data){
    		$arrRequired[] = array('amosoft_vendor_code'=>$data[0] ,'amosoft_vendor_name'=>$data[1],'ranking'=>$data[2]);
    	}
    	foreach($arrRequired as $value){
    		$this->saveVendorDetails($value);
    	}	
    }
	
	protected function saveVendorDetails($value){
		$vendorDetail = Mage::getModel('dropship/ranking')->load($value['amosoft_vendor_code'],'amosoft_vendor_code');    		
		if(!$vendorDetail->getId()){
			$vendorDetail->setAmosoftVendorCode($value['amosoft_vendor_code']);
			$vendorDetail->setAmosoftVendorName($value['amosoft_vendor_name']);
			$vendorDetail->setRanking($value['ranking']);
            $vendorDetail->setIsDropship('no');
			$vendorDetail->setUpdatedAt(now());
			$vendorDetail->setCreatedAt(now());
			$vendorDetail->save();
		}
	}
}
	 