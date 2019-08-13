<?php
/**
 * Amosoft
 *

 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Model_System_Config_Source_Vendorlist
{
    public function toOptionArray($addEmpty = true)
    {
    	$options =array();
    	$collectionVendor = Mage::getModel ( 'dropship/inventory' )->getCollection ();
    	
    	
    	$collectionVendor->getSelect ()->joinleft ( array (
    			'amosoftRanking' => Mage::getSingleton ( 'core/resource' )->getTableName ( 'dropship/ranking' )
    	), 'amosoftRanking.amosoft_vendor_code = main_table.amosoft_vendor_code', array (
    			'*'
    	) )->where('amosoftRanking.is_dropship = ?','yes');
    	
    	$collectionVendor->getSelect ()->group('main_table.amosoft_vendor_code');
    	if($collectionVendor->count() > 0){
    	foreach ($collectionVendor as $vendor) {
    		$options[] = array(
    				'label' => $vendor->getAmosoftVendorName(),
    				'value' => $vendor->getAmosoftVendorCode()
    		);
    	}
    	}
    	array_unshift($options,array('value' => '', 'label' => Mage::helper('dropship')->__('--Please Select--')),array('value' => 'none', 'label' => Mage::helper('dropship')->__('None')));
 		return $options;
    }
    
    public function vendorList($addEmpty = true,$sku)
    {
    	$vendorModel = Mage::getModel('dropship/ranking')->getCollection();
    	$vendorModel->getSelect ()->where('main_table.amosoft_vendor_code not in (select amosoft_vendor_code from '.Mage::getSingleton ( 'core/resource' )->getTableName ( 'dropship/inventory' ).' where product_sku = ?)',$sku);
    	$options = array();
    	if($vendorModel->count() > 0 ){
    	foreach ($vendorModel as $vendor) {
    		$options[] = array(
    				'label' => $vendor->getAmosoftVendorCode().'--'.$vendor->getAmosoftVendorName(),
    				'value' => $vendor->getAmosoftVendorCode()
    		);
    	}
    	}
    	array_unshift($options,array('value' => '', 'label' => Mage::helper('dropship')->__('--Please Select--'))
    	);
    	return $options;
    }
    
    public function vendorListSourcing($addEmpty = true,$sku)
    {
    	$options =array();
    	$collectionVendor = Mage::getModel ( 'dropship/inventory' )->getCollection ()->addFieldToFilter('product_sku',$sku);
    	$collectionVendor->getSelect ()->joinleft ( array (
    			'amosoftRanking' => Mage::getSingleton ( 'core/resource' )->getTableName ( 'dropship/ranking' )
    	), 'amosoftRanking.amosoft_vendor_code = main_table.amosoft_vendor_code', array (
    			'*'
    	) )->where('amosoftRanking.is_dropship = ?','yes');
    	 
    	$collectionVendor->getSelect ()->group('main_table.amosoft_vendor_code');
    	
    	if($collectionVendor->count() > 0){
    	foreach ($collectionVendor as $vendor) {
    		$options[] = array(
    				'label' => $vendor->getAmosoftVendorName(),
    				'value' => $vendor->getAmosoftVendorCode()
    		);
    	}
    	}
    	array_unshift($options,array('value' => '', 'label' => Mage::helper('dropship')->__('--Please Select--')));
 		return $options;
    }
    
    public function getAllVendor($addEmpty = true)
    {
    	$options =array();
    	$collectionVendor = Mage::getModel ( 'dropship/ranking' )->getCollection ();
    	
    	if($collectionVendor->count() > 0){
    		foreach ($collectionVendor as $vendor) {
    			$options[] = array(
    					'label' => $vendor->getAmosoftVendorCode().'--'.$vendor->getAmosoftVendorName(),
    					'value' => $vendor->getAmosoftVendorCode()
    			);
    		}
    	}
    	array_unshift($options,array('value' => '', 'label' => Mage::helper('dropship')->__('--Please Select--')));
    	return $options;
    }
}
