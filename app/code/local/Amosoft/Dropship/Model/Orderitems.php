<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Model_Orderitems extends Mage_Core_Model_Abstract
{
    protected function _construct(){
       $this->_init("dropship/orderitems");
    }
	
	public function prepareOrderItemData($item){		
		$productSku = $item->getSku();
		$ruletype = Mage::getStoreConfig( 'amosoft_sourcing/rank/ranktype' );
		
		if ($ruletype == 'default')
			$orderBy = 'ranking ASC';
		else
			$orderBy = 'cost ASC';
		
		$collectionVendor = Mage::getModel( 'dropship/inventory' )->getCollection()->addFieldToFilter ( 'product_sku', $productSku );
		$collectionVendor->getSelect()->joinleft( array ('amosoftRanking' => Mage::getSingleton( 'core/resource' )->getTableName( 'dropship/ranking' )), 'amosoftRanking.amosoft_vendor_code = main_table.amosoft_vendor_code', array ('*') )->where('amosoftRanking.is_dropship = "yes" and amosoftRanking.is_active = "yes"');
		$collectionVendor->getSelect()->order( $orderBy );
		return $collectionVendor;
	}
	
	
	public function isVendorCollectionAvailable()
	{	
		if (Mage::getModel( 'dropship/inventory' )->getCollection()->count() > 0 ) 
			return true;
		else 
			return false;
	}
		
	public function setItemData($orderItemInstance,$status,$item,$vendorCode,$vendorCost,$vendorSku,$itemStatusHistory)
	{
		$orderItemInstance->setItemId( $item->getItemId () );
		$orderItemInstance->setAmosoftItemStatus( $status );
		$orderItemInstance->setAmosoftVendorCode( $vendorCode );
		$orderItemInstance->setAmosoftVendorSku( $vendorSku );
		$orderItemInstance->setVendorCost( $vendorCost );
		$orderItemInstance->setItemOrderId($item->getOrderId () );
		$orderItemInstance->setSku( $item->getSku() );
		$orderItemInstance->setUpdatedBy('Cron');
		$orderItemInstance->setUpdatedAt(now());
		$orderItemInstance->setCreatedAt( Mage::getModel('core/date')->gmtDate());
		$orderItemInstance->setItemStatusHistory($itemStatusHistory);		
		try {
			$orderItemInstance->save();
		} catch ( Exception $e ) {
			Mage::helper('dropship')->genrateLog(0,null,null,'Section :Error In Setting order item data: '.$e->getMessage().' sku : '.$item->getSku().','.$item->getOrderId ());
			echo $e->getMessage ();
		}	
	}
	public function updateAmosoftVendorInvenory($vendorCode,$productSku,$qtyInvoiced) 
	{
		$inventory = Mage::getModel ( 'dropship/inventory' )->getCollection()
					->addFieldToFilter('amosoft_vendor_code',$vendorCode)->addFieldToFilter('product_sku',$productSku);		
		$filedData = $inventory->getFirstItem()->getData();
		$InventoryStock = $filedData['stock'];
		$finalStock = $InventoryStock - $qtyInvoiced;
		$inventory->getFirstItem()->setStock( ($finalStock > 0) ? $finalStock : 0 );	
		try {
			$inventory->getFirstItem()->save();
			Mage::getModel('dropship/inventory')->_saveInventoryLog('update',array('amosoft_vendor_name'=>$filedData['amosoft_vendor_name'],'updated_by'=>'system','product_sku'=>$productSku,'amosoft_vendor_code'=>$vendorCode,'cost'=>$filedData['cost'],'stock'=>($finalStock > 0) ? $finalStock : 0));
		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
	}
	
	public function updateSourcingByUser($request)
	{		
		$arrData = array();
		$inventoryModel = Mage::getModel('dropship/inventory')->getCollection()->addFieldToFilter('amosoft_vendor_code',$request['amosoft_vendor_code'])->addFieldToFilter('product_sku',$request['product_sku']);
		$arrData['amosoft_vendor_code'] = $request['amosoft_vendor_code'];
		$arrData['amosoft_item_status'] = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_TRANSMITTING;
		$arrData['updated_by'] = 'User';
		$arrData['amosoft_vendor_sku'] = $inventoryModel->getFirstItem()->getAmosoftVendorSku();
		$arrData['vendor_cost'] = $request['qty']*$inventoryModel->getFirstItem()->getCost();
		$arrData['item_status_history'] = $request['item_status_history'];
		return $arrData;
	}
	
	public function updateOrderStatus($orderId,$itemId)
	{	
		$arrData = array();
		$orderStatus = $this->_changeAllItemStatus($orderId,$itemId);
		if(is_null($orderStatus))
		return;
		$orderCollection = Mage::getModel('sales/order')->Load($orderId);
		$orderCollection->setStatus(Mage::getStoreConfig($orderStatus));       
		$orderCollection->addStatusToHistory(Mage::getStoreConfig($orderStatus), 'Order status and sourcing changed by user', false);
		try{
			$orderCollection->save();
		}catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
		return $arrData;
	}
	
	protected function _changeAllItemStatus($orderId){
		$orderItemCollection = $this->getCollection()->addFieldToFilter('item_order_id',$orderId);
		$orderStatus = null;
		$arrItemStatus = array();
		if($orderItemCollection->count() > 0){			
			if($orderItemCollection->count() > 1)
			{
				foreach($orderItemCollection as $item){		
					$arrItemStatus[] = $item->getAmosoftItemStatus();				
				}
			}else{
				$arrItemStatus[] = 'Transmitting';
			}			
		}
		$arrUnique = array_unique($arrItemStatus);
		if(count($arrUnique) == 1){
			switch($arrUnique[0]){
				case 'Backorder' :
						$orderStatus = 'amosoft_sourcing/order/backorder';
				break;
				case 'Transmitting' :
						$orderStatus = 'amosoft_sourcing/order/awaiting_transmission';
				break;
				case 'No Dropship' :
						$orderStatus = 'amosoft_sourcing/order/sourcing_complete';
				break;
			}
		}elseif(count($arrUnique) > 1){
			if(in_array('Backorder',$arrUnique))
				$orderStatus = 'amosoft_sourcing/order/backorder';
			else
				$orderStatus = 'amosoft_sourcing/order/awaiting_transmission';		
		}			
		return $orderStatus;
	}
	
	public function setSourcingOrderStatus($data)
	{		
		$itemStatus = array();
		$itemCollection = $this->getCollection()->addFieldToFilter('item_order_id',$data['item_order_id']);		
		foreach($itemCollection as $items){		
			if($items->getItemId() != $data['item_id'])
			$itemStatus[] = $items->getAmosoftItemStatus();
		}		
		$uniqueArray = array_unique($itemStatus);
		$orderCollection = Mage::getModel('sales/order')->Load($data['item_order_id']);
		switch($itemStatus)
		{
			case (count($uniqueArray) == 1 && $uniqueArray[0] == 'Cancelled' ) ://case : when all item status are cancelled
				$orderCollection->setStatus('canceled');       
				$orderCollection->setState('canceled');
				$orderCollection->addStatusToHistory('canceled', 'Order status changed to canceled', false);
				
				break;
			case in_array('Backorder',$itemStatus) :
					$orderCollection->setStatus(Mage::getStoreConfig(Amosoft_Dropship_Model_Observer::XML_PATH_AMOSOFT_ORDER_BACKORDERED));       
					$orderCollection->setState('processing');
					$orderCollection->addStatusToHistory(Mage::getStoreConfig(Amosoft_Dropship_Model_Observer::XML_PATH_AMOSOFT_ORDER_BACKORDERED), 'Order status changed to backorder', false);
					break;
			case (in_array('Transmitting', $uniqueArray)) :
				$orderCollection->setStatus(Mage::getStoreConfig(Amosoft_Dropship_Model_Observer::XML_PATH_AMOSOFT_ORDER_AWAITING_TRANSMISSION));       
				$orderCollection->setState('processing');
				$orderCollection->addStatusToHistory(Mage::getStoreConfig(Amosoft_Dropship_Model_Observer::XML_PATH_AMOSOFT_ORDER_AWAITING_TRANSMISSION), 'Order status changed to transmitting', false);
				break;			
					
			default : 
				$orderCollection->setStatus(Mage::getStoreConfig(Amosoft_Dropship_Model_Observer::XML_PATH_AMOSOFT_ORDER_SOURCING_COMPLETE));       
				$orderCollection->setState('processing');
				$orderCollection->addStatusToHistory(Mage::getStoreConfig(Amosoft_Dropship_Model_Observer::XML_PATH_AMOSOFT_ORDER_SOURCING_COMPLETE), 'Order status changed to sourcing complete', false);
				break;								
		}		
		try{
			$orderCollection->save();
			return true;
		}catch (Exception $e) {
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			return false;
        }
	}	
}
	 