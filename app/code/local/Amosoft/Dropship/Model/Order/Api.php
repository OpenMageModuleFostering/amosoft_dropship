<?php
/**
 * Order API rewrite to add three Amosoft fields in salesOrderItemEntity Array of salesorderinfo api
 * and to update Amosoft item status
 * @category   Amosoft
 * @package    Amosoft_Dropship
 */
class Amosoft_Dropship_Model_Order_Api extends Mage_Sales_Model_Order_Api_V2
{
	protected $_itemStatusTansmitting = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_TRANSMITTING;
	
	/**
	 * Retrieve list of orders. Filtration could be applied
	 *
	 * @param null|object|array $filters
	 * @return array
	 */
	public function items($filters = null)
	{
		$orders = array();
		$itemTemp = array();
		//TODO: add full name logic
		$billingAliasName = 'billing_o_a';
		$shippingAliasName = 'shipping_o_a';
	
		/** @var $orderCollection Mage_Sales_Model_Mysql4_Order_Collection */
		$orderCollection = Mage::getModel("sales/order")->getCollection();
		$billingFirstnameField = "$billingAliasName.firstname";
		$billingLastnameField = "$billingAliasName.lastname";
		$shippingFirstnameField = "$shippingAliasName.firstname";
		$shippingLastnameField = "$shippingAliasName.lastname";
		$orderCollection->addAttributeToSelect('*')
		->addAddressFields()
		->addExpressionFieldToSelect('billing_firstname', "{{billing_firstname}}",
				array('billing_firstname' => $billingFirstnameField))
				->addExpressionFieldToSelect('billing_lastname', "{{billing_lastname}}",
						array('billing_lastname' => $billingLastnameField))
						->addExpressionFieldToSelect('shipping_firstname', "{{shipping_firstname}}",
								array('shipping_firstname' => $shippingFirstnameField))
								->addExpressionFieldToSelect('shipping_lastname', "{{shipping_lastname}}",
										array('shipping_lastname' => $shippingLastnameField))
										->addExpressionFieldToSelect('billing_name', "CONCAT({{billing_firstname}}, ' ', {{billing_lastname}})",
												array('billing_firstname' => $billingFirstnameField, 'billing_lastname' => $billingLastnameField))
												->addExpressionFieldToSelect('shipping_name', 'CONCAT({{shipping_firstname}}, " ", {{shipping_lastname}})',
														array('shipping_firstname' => $shippingFirstnameField, 'shipping_lastname' => $shippingLastnameField)
												);
	
		/** @var $apiHelper Mage_Api_Helper_Data */
		/*$apiHelper = Mage::helper('api');
		$filters = $apiHelper->parseFilters($filters, $this->_attributesMap['order']);
		try {
			foreach ($filters as $field => $value) {
				$orderCollection->addFieldToFilter($field, $value);
			}
		} catch (Mage_Core_Exception $e) {
			$this->_fault('filters_invalid', $e->getMessage());
		}*/
		
		/* Patch apply to display item list with 
		 * vendor_cost,amosoft_vendor_code,amosoft_vendor_sku,magento_sku 
		*/
		foreach ($orderCollection as $order) {
			
			$results = $this->addItemDetails($order);
			if(count($results) > 0){
			/* new code */
			foreach($results as $result)
			{
				if($result['amosoft_vendor_code'] == $filters)
					$itemTemp['item_details'][] = $result;
			}

			//$a = !array_search('MagVendID1', $itemTemp['item_details']);
			//return $a;
			//unset($itemTemp['item_details'][$a]);

			$itemTemp['dropship_item'] = $this->isDropshipItemReady($order);
			$orders[] = array_merge($this->_getAttributes($order, 'order'),$itemTemp);
			unset($itemTemp);
		}else
		{
			$orders[] = $this->_getAttributes($order, 'order');
		}
		}
		
		return $orders;
	}
	protected function isDropshipItemReady($order)
	{
		$result = false;
		$ItemCollection = Mage::getModel('dropship/orderitems')->getCollection()->addFieldToFilter('amosoft_item_status',$this->_itemStatusTansmitting)->addFieldToFilter('item_order_id',$order->getEntityId());
		if($ItemCollection->count() > 0)
			$result = true;
		return $result;
	}
	
	protected function addItemDetails($order){
		$result = array();
		$ItemCollection = Mage::getModel('dropship/orderitems')->getCollection()->addFieldToFilter('item_order_id',$order->getEntityId());
		if($ItemCollection->count() > 0){
			unset($result);
			foreach($ItemCollection as $item)
			{
				$result[] = array('item_sku'=>$item->getSku(),'amosoft_vendor_sku' => $item->getAmosoftVendorSku(),'amosoft_vendor_code' => $item->getAmosoftVendorCode(),'amosoft_vendor_cost' => $item->getVendorCost(),'amosoft_vendor_ship_cost' => $item->getShippingCost(),'amosoft_item_status'=>$item->getAmosoftItemStatus(), 'order_item_id'=>$item->getItemId());
			} 
		}
		
		return $result;
	}
   /**
     * Retrieve full order information
     *
     * @param string $orderIncrementId
     * @return array
     */
    public function info($orderIncrementId)
    {
        $order = $this->_initOrder($orderIncrementId);

        if ($order->getGiftMessageId() > 0) {
            $order->setGiftMessage(
                Mage::getSingleton('giftmessage/message')->load($order->getGiftMessageId())->getMessage()
            );
        }

        $result = $this->_getAttributes($order, 'order');

        $result['shipping_address'] = $this->_getAttributes($order->getShippingAddress(), 'order_address');
        $result['billing_address']  = $this->_getAttributes($order->getBillingAddress(), 'order_address');
        $result['items'] = array();
		
		//$id    = $order->getId();
		foreach ($order->getAllItems() as $item) {
			$result['items'][] = $this->getProductAmosoftDetails($item, $order);  
        }

        $result['payment'] = $this->_getAttributes($order->getPayment(), 'order_payment');

        $result['status_history'] = array();

        foreach ($order->getAllStatusHistory() as $history) {
            $result['status_history'][] = $this->_getAttributes($history, 'order_status_history');
        }

        return $result;
    }
	/**
	* This function will add details to order items
	* @param array $item, int $id
	* @return array $productItems
	*/
	protected function getProductAmosoftDetails($item, $order){
		if ($item->getGiftMessageId() > 0) {
			$item->setGiftMessage(
				Mage::getSingleton('giftmessage/message')->load($item->getGiftMessageId())->getMessage()
			);
		}
		$productItems = array();
		$productItems = $this->_getAttributes($item, 'order_item');		
		$amosoftItems = Mage::getModel('dropship/orderitems')->getCollection()
				->addFieldToSelect(array('sku', 'amosoft_vendor_sku', 'vendor_cost', 'shipping_cost', 'amosoft_item_status', 'amosoft_vendor_code', 'item_id'))
					->addFieldToFilter('item_order_id',array('eq'=>$order->getId()))
					->addFieldToFilter('item_id', array('eq'=>$productItems['item_id']));
					//->addFieldToFilter('amosoft_item_status', array('eq'=>$this->_itemStatusTansmitting));
		$amosoftItems->getSelect()->join(array('salesOrder'=>Mage::getSingleton('core/resource')->getTableName('sales/order')),
  			'salesOrder.entity_id = main_table.item_order_id', array('state'));
			//->where('salesOrder.state = ?','processing');
		if($amosoftItems->getSize() > 0){
				$productItems['amosoft_item_status'] = $amosoftItems->getFirstItem()->getAmosoftItemStatus();
				$productItems['amosoft_vendor_sku'] = $amosoftItems->getFirstItem()->getAmosoftVendorSku();
				$productItems['amosoft_vendor_cost'] = $amosoftItems->getFirstItem()->getVendorCost();
				$productItems['amosoft_vendor_ship_cost'] = $amosoftItems->getFirstItem()->getShippingCost();
				$productItems['vendor_sale_total'] = $productItems['amosoft_vendor_cost'] + $productItems['amosoft_vendor_ship_cost'];
				$productItems['amosoft_vendor_code'] = $amosoftItems->getFirstItem()->getAmosoftVendorCode(); 	
		} 	
		return 	$productItems;		
	}

	
	 /**
     * Change Amosoft order item status
     *
     * @param string $orderIncrementId, array $sku, string $status
     * @return bool
     */
	public function updateItemStatus($orderIncrementId, $sku, $status){
		
		$order = $this->_initOrder($orderIncrementId);
		$result = false;
		try{		
			$items = $order->getAllItems();
			foreach ($items as $item){
				if( $item->getSku() == $sku){
					$itemIdArr[] = $item->getItemId();
				}
			} 
			if(!$status){
				$status = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_SENT_TO_SUPPLIER;
			}
			if($itemIdArr && in_array(ucfirst($status),Mage::helper('dropship')->getItemStatuses())){
				foreach($itemIdArr as $itemId){
					$result = $this->saveAmosoftStatus($itemId, $status);
				}
			}else{
				$result = false;
			}
		}catch (Mage_Core_Exception $e) {
            $this->_fault('status_not_changed', $e->getMessage());
        }
		return $result;
	}
	
	/**
	* This function is used to save order item status
	* @param int $itemId, string $status
	* @return bool
	*/
	protected function saveAmosoftStatus($itemId, $status){
		$amosoftStatus = Mage::getModel('dropship/orderitems')->load($itemId, 'item_id');
		$orderCollection = Mage::getModel('sales/order')->load($amosoftStatus->getItemOrderId());
		$orderStatus = $orderCollection->getStatus();
		$itemStatusHistory = Mage::helper('dropship')->getSerialisedData($amosoftStatus, ucfirst($status), $orderStatus);
		if($amosoftStatus->getId()){			
			$amosoftStatus->setAmosoftItemStatus(ucfirst($status))
					->setUpdatedBy('amosoft')
					->setItemStatusHistory($itemStatusHistory)
					->setUpdatedAt(Mage::getModel('core/date')->gmtDate())
					->save();	
			Mage::helper('dropship')->genrateLog(0,'API Item Update started','API Item Update ended','Item Status updated by Amosoft API item-status->'.$status.' ,sku->'.$amosoftStatus->getSku().' ,orderId->'.$amosoftStatus->getItemOrderId());
			return true;
		}	
	}
	
	
	/**
	 * Retrive orders by Item status 
	 *
	 * @param string $orderIncrementId, string $orderStatus, int $limit
	 * @return bool
	 */
	public function getAmosoftOrderByItemStatus($store_id,$orderItemStatus){
		
		if (!$store_id) {
			$this->_fault('invaild_store');
		}
		$orderItemStatus = (!empty($orderItemStatus)) ? $orderItemStatus : 'Transmitting';
		$orderItemsdDetails = array();
		try{
			
				$orderCollection = Mage::getModel('dropship/orderitems')->getCollection();
				$orderCollection->addFieldToFilter('amosoft_item_status',$orderItemStatus);
				$orderCollection->getSelect()->join(array('salesOrder'=>Mage::getSingleton('core/resource')->getTableName('sales/order')),
  			'salesOrder.entity_id = main_table.item_order_id', array('increment_id','store_id'))->where('store_id = ?', (int)$store_id);
				$orderCollection->getSelect()->group('item_order_id');
				
				if($orderCollection->getSize() > 0){
					$result['ResultCount'] = $orderCollection->count();
					foreach($orderCollection as $order)
					{
					$result['orderDetails'][] = array('increment_id'=>$order->getIncrementId());
					}
				}else
				$result['error_message'] = 'Result not found'; 
			
			
		}catch (Mage_Core_Exception $e) {
			$this->_fault('data_invalid', $e->getMessage());
		}
	
		
		return $result;
	}
	
	
	
	public function setAmosoftOrderItemStatus($orderIncrementId,$itemStatus){
	
		$order = $this->_initOrder($orderIncrementId);
		$itemId = array();
		$itemOrderId = $order->getEntityId();
		$result = false;
		$orderCollection = Mage::getModel('dropship/orderitems')->getCollection();
		$orderCollection->addFieldToFilter('amosoft_item_status','Transmitting');
		$orderCollection->addFieldToFilter('item_order_id',$itemOrderId);
		
		try{		
		
			if($orderCollection->getSize() > 0){
			foreach($orderCollection as $itemDetails)
			{
				$itemId[] = $itemDetails->getItemId();
			}
			
		}
		if(empty($itemStatus)){
			$itemStatus = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_SENT_TO_SUPPLIER;
		}
			
			if(!empty($itemId) && in_array(ucfirst($itemStatus),Mage::helper('dropship')->getItemStatuses())){
				foreach($itemId as $itemId){
					$result = $this->saveAmosoftStatus($itemId, $itemStatus);						
				}
			}else{
				$result = false;
			}
		}catch (Mage_Core_Exception $e) {
            $this->_fault('status_not_changed', $e->getMessage());
        }
		return ($result) ? array('success_message'=>'Item Status Upated Successfully') : array('error_message'=>'Error In Updating Item Status');
	}

} 
