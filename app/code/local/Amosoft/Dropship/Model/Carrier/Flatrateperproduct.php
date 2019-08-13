<?php
/*
* Copyright (c) 2013 www.magebuzz.com
*/
class Amosoft_Dropship_Model_Carrier_Flatrateperproduct
extends Mage_Shipping_Model_Carrier_Abstract
implements Mage_Shipping_Model_Carrier_Interface {		 
	protected $_code = 'suppliershippingcostperproduct';
	public function collectRates(Mage_Shipping_Model_Rate_Request $request)
	{	
		$itemStatusComplete = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_TRANSMITTING;
		$itemStatusBackorder = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_BACKORDER;
		$itemStatusNoDropShip = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_NO_DROPSHIP;

		if (!$this->getConfigFlag('active')) {
			return false;
		}
		$shippingPrice =0;
		if ($request->getAllItems()) {
			foreach ($request->getAllItems() as $item) {
				
				$product = Mage::getModel('catalog/product')->load($item->getProductId());
				//print_r($product->getData()); exit;
				if($product->getTypeId()=='configurable' || $product->getTypeId()=='bundle') {
					continue;
				}
				 
				$shipCost = Mage::helper('dropship')->getVendorShippingCost($item);
				//print_r($shipCost); exit;
				if($shipCost==null || $shipCost==0){
					$shippingPrice += $item->getQty() * $this->getConfigData('price');				 		 
				}
				else{
					$shippingPrice += $item->getQty() * $shipCost;	
				}
			}
		}   				
		$result = Mage::getModel('shipping/rate_result');			
		if ($shippingPrice !== false) {
			$method = Mage::getModel('shipping/rate_result_method');
			$method->setCarrier('suppliershippingcostperproduct');
			$method->setCarrierTitle($this->getConfigData('title'));
			$method->setMethod('suppliershippingcostperproduct');
			$method->setMethodTitle($this->getConfigData('name'));	
			$method->setPrice($shippingPrice);
			$method->setCost($shippingPrice);
			$result->append($method);
		}
		return $result;
	}
	public function getAllowedMethods()
	{
		return array('suppliershippingcostperproduct'=>$this->getConfigData('name'));
	}
}










