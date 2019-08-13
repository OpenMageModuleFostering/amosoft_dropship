<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Model_Observer {
	
	const CRON_STRING_PATH_SOURCING = 'crontab/jobs/amosoft_dropship/schedule/cron_expr';
	const CRON_STRING_PATH_BACKORDER = 'crontab/jobs/amosoft_backorder/schedule/cron_expr';
	const XML_PATH_AMOSOFT_ORDER_BEGIN_SOURCING_STATUS   = 'Reprocess';
	const XML_PATH_AMOSOFT_ORDER_BACKORDERED   = 'Backorder';
	const XML_PATH_AMOSOFT_EMAIL_SHIPMENT   = 'amosoft_sourcing/rank/email_shipment';
	const XML_PATH_INVENTORY_NOTIFICATION_EMAIL  = 'amosoft_sourcing/inventory_notification/email';
	const XML_PATH_INVENTORY_NOTIFICATION_EMAIL_ENABLED  = 'amosoft_sourcing/inventory_notification/enabled';
	const XML_PATH_INVENTORY_NOTIFICATION_DAYS  = 'amosoft_sourcing/inventory_notification/days';
	const XML_PATH_LOGICSOURCING_SOURCING_TYPE  = 'amosoft_sourcing/rank/sourcing_type';
	protected $_orderStatus;
	protected $_itemData = array();
	
	public static function getWorkingDir()
	{
		return Mage::getBaseDir();
	}
	
	
	public function insertProcessOrder($object)
	{
		
		$this->_orderStatus = $object->getOrder()->getStatus();
			foreach ($object->getOrder ()->getAllItems() as $item){
				if(in_array($item->getProductType(),array('simple','grouped')) ){
					$started = 0;
					$ended = 1;
					$logMsg = 'Item inserted @'.Mage::getSingleton ( 'core/resource' )->getTableName ( 'dropship/orderitems' ). ' sku : '.$item->getSku().','.$object->getOrder()->getIncrementId();
					Mage::helper('dropship')->genrateLog(++$started,'Order Item Inserted Started',null,$logMsg);
						$this->getOrderSourcing($item, $object);
					Mage::helper('dropship')->genrateLog(++$ended,null,'Order Item Inserted Ended',null);
				}
			}
		}
	
	protected function getOrderSourcing($item, $object){
		$orderSourcingInstance = Mage::getModel( 'dropship/orderitems' );
		Mage::getModel('dropship/amosoft')->prepareNotification($orderSourcingInstance,$object->getOrder()->getEntityId());
		$orderStatus = $object->getOrder()->getStatus();
    	$itemStatusHistory = Mage::helper('dropship')->getSerialisedData($orderSourcingInstance, 'Sourcing', $orderStatus);

    	$selected_ship_method = $object->getOrder()->getShippingMethod();
    	if($selected_ship_method == "suppliershippingcostperproduct_suppliershippingcostperproduct"){
    		
    		$shipCost = Mage::helper('dropship')->getVendorShippingCost($item);
    		$shippingPrice = $item->getQtyOrdered() * $shipCost;
			$orderSourcingInstance->setShippingCost( $shippingPrice );
		}
		$orderSourcingInstance->setSku( $item->getSku() );
		$orderSourcingInstance->setItemId( $item->getItemId() );
		$orderSourcingInstance->setItemOrderId( $object->getOrder()->getEntityId() );
		$orderSourcingInstance->setAmosoftItemStatus('Sourcing');
		$orderSourcingInstance->setUpdatedBy('Cron');
		$orderSourcingInstance->setUpdatedAt(now());
		$orderSourcingInstance->setItemStatusHistory($itemStatusHistory);		
		try {
			$orderSourcingInstance->save();
		} catch ( Execption $e ) {
			Mage::helper('dropship')->genrateLog(0,null,null,'Section : order item inserted Error: '.$e->getMessage().' sku : '.$item->getSku());
			echo $e->getMessage();
		}
		
		//As item get saved in amosoft_sales_orders_items we run our sourcing logic 
		if(Mage::getStoreConfigFlag(self::XML_PATH_LOGICSOURCING_SOURCING_TYPE)){
			$this->assignToVendor($item);
			Mage::getResourceModel('dropship/orderitems')->saveOrderItems($this->_itemData,$object->getOrder());
			$this->_itemData = array();
		}
	}

	public function amosoftSourcing() {
		$sourcingObj = Mage::getModel('dropship/ordersourcing');
		if(!Mage::getStoreConfig(self::CRON_STRING_PATH_SOURCING)) {
			Mage::helper('dropship')->genrateLog(0,'Sourcing started','Sourcing started','Sourcing can not be started as cron time not set');
			return;
		}
		if($sourcingObj->checkRunningStatus('sourcing')){
			
			Mage::helper('dropship')->genrateLog(0,'Sourcing started','Sourcing started','Sourcing can not be started as process already running');
			return;
		}
		Mage::helper('dropship')->genrateLog(1,'Sourcing Started for '.Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_REPROCESS.' Item status',null,null);
		$sourcingObj->sourcingStarted(Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_SOURCING);
		$this->setAmosoftVendorRanking (Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_REPROCESS,true);
		$this->addCronStatus('amosoft_sourcing/cron_settings/dispaly_sourcing_updated_time', Mage::helper('core')->formatDate(now(), 'medium', true));
		$sourcingObj->sourcingCompleted(Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_SOURCING);
		Mage::helper('dropship')->genrateLog(2,null,'Sourcing Ended for ' .Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_REPROCESS.' Item status',null);
		return; 
	}
	
	public function amosoftBackorder()
	{
		$sourcingObj = Mage::getModel('dropship/ordersourcing');
		if(!Mage::getStoreConfig(self::CRON_STRING_PATH_BACKORDER)) {
			Mage::helper('dropship')->genrateLog(0,'Backorder sourcing started','Backorder sourcing ended','Backorder Sourcing can not be started as cron time not set');
			return;
        }
        if($sourcingObj->checkRunningStatus(Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_BACKORDER)){
        	Mage::helper('dropship')->genrateLog(0,'Backorder sourcing started','Backorder sourcing ended','Backorder Sourcing can not be started process already running');
        	return;
        }
        Mage::helper('dropship')->genrateLog(1,'Backorder Sourcing Started for '.Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_BACKORDER . ' item status',null,null);
        $sourcingObj->sourcingStarted(Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_BACKORDER);
		$this->setAmosoftVendorRanking (Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_BACKORDER);
		$this->addCronStatus('amosoft_sourcing/cron_settings/display_backorder_updated_time', Mage::helper('core')->formatDate(now(), 'medium', true));
		$sourcingObj->sourcingCompleted(Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_BACKORDER);
		Mage::helper('dropship')->genrateLog(1,'Backorder Sourcing Ended for '.Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_BACKORDER .' item status',null,null);
		return;
	}
		
		
	/* 
	* Save last cron status
	* @param string $statusPath
	* @param string $status
	*/
	protected function addCronStatus($statusPath, $status) {
		$config = new Mage_Core_Model_Config();
		$config->saveConfig($statusPath, $status, 'default', 0);
		return;
	}
	
	protected function setAmosoftVendorRanking($crontype,$isCronSourcing = false)
	{
		$reprocess = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_REPROCESS;
		$OrderInstances = Mage::getModel('dropship/ordersourcing');
		$collection = $OrderInstances->prepareItemCollection($crontype,$isCronSourcing);
		if(count($collection) > 0 ){
		foreach ( $collection as $orderID => $orderCollectionData ) {
			$orderCollection = Mage::getModel('sales/order')->Load($orderID);
			//Patch : skip sourcing process if order is deleted
			if (! $orderCollection->getEntityId ()) {
				Mage::helper ( 'dropship' )->genrateLog ( 0, null, null, 'Order not exists for => order_id: ' . $orderID . ' hence cannot continue' );
				continue;
			}
			$this->_orderStatus = $orderCollection->getStatus();
			foreach ($orderCollectionData as $orderData ){
			Mage::helper('dropship')->genrateLog(0,null,null,'<---->Item Processing Started : '.$orderData->getSku());
			if ($crontype == Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_REPROCESS) {
				$assigned = $this->assignToVendor(Mage::getModel('sales/order_item')->Load($orderData->getItemId()));
			}else
			{	
				$orderItems = Mage::getModel( 'dropship/orderitems' )->load($orderData->getItemId(), 'item_id');
				$itemStatusHistory = Mage::helper('dropship')->getSerialisedData($orderItems, $reprocess, $this->_orderStatus);
				$this->_itemData[$orderData->getItemId()] = array('amosoft_item_status'=>$reprocess,'item_status_history'=>$itemStatusHistory);
			}
			Mage::helper('dropship')->genrateLog(0,null,null,'####### Item Processing ended : '.$orderData->getSku());
			 			 	
			}	
			Mage::getResourceModel('dropship/orderitems')->saveOrderItems($this->_itemData,$orderCollection,$crontype);
			$this->_itemData = array();
		}
		}else {
		Mage::helper('dropship')->genrateLog(0,null,null,'Order collection is empty for => Cron_type: '.$crontype.' hence cannot continue');
			return;
		}
	}
	protected function assignToVendor($item) {
		$productSku = $item->getSku();
		$itemStatusComplete = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_TRANSMITTING;
		$itemStatusBackorder = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_BACKORDER;
		$itemStatusNoDropShip = Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_NO_DROPSHIP;
		$qtyInvoiced = $item->getQtyOrdered();
		$itemId = $item->getItemId();
		$isDefaultVendor = false;
		$vendorCode = '';
		$inventoryStock = '';
		$defaultVendor = (Mage::getStoreConfig('amosoft_sourcing/rank/defaultbackorder') == 'none') ? '' : Mage::getStoreConfig('amosoft_sourcing/rank/defaultbackorder');
		$orderItemInstance = Mage::getModel( 'dropship/orderitems' );
		$orderItemInstance->load( $itemId, 'item_id' );
		$collectionVendor = $orderItemInstance->prepareOrderItemData($item);
		$arrDefaultVendorDetails = array();
		$vendorCost = 0;
		
		if ($collectionVendor->count() > 0) {			
			if($collectionVendor->count() >= 1){			
				foreach ($collectionVendor as $vendorData) {					
					//assign default vendor details
					if(!empty($defaultVendor) && $vendorData->getAmosoftVendorCode() == $defaultVendor )
					{
						$arrDefaultVendorDetails =  array('amosoft_vendor_code'=>$vendorData->getAmosoftVendorCode(),'stock'=>$vendorData->getStock(),'cost'=>$vendorData->getCost(),'amosoft_vendor_sku'=>$vendorData->getAmosoftVendorSku(),'product_sku'=>$vendorData->getProductSku());
					}					
					//if item is in backordered 
					if($vendorData->getStock() < $qtyInvoiced ){		
						$arrVendorDetail[] = array('amosoft_vendor_code'=>$vendorData->getAmosoftVendorCode(),'stock'=>$vendorData->getStock(),'cost'=>$vendorData->getCost(),'amosoft_vendor_sku'=>$vendorData->getAmosoftVendorSku(),'product_sku'=>$vendorData->getProductSku());
						$vendorCode = $arrVendorDetail[0]['amosoft_vendor_code'];
						$inventoryStock = $arrVendorDetail[0]['stock'];
						$vendorCost = $arrVendorDetail[0]['cost'];
						$vendorSku = $arrVendorDetail[0]['amosoft_vendor_sku'];
						$productSku = $arrVendorDetail[0]['product_sku'];
						$arrVendorAvailable[] = $vendorData->getAmosoftVendorCode();
						$isDefaultVendor = true;
					}else{
						$vendorCode = $vendorData->getAmosoftVendorCode();
						$inventoryStock = $vendorData->getStock();
						$vendorCost = $vendorData->getCost();
						$vendorSku = $vendorData->getAmosoftVendorSku();
						$productSku = $vendorData->getProductSku();
						$arrVendorAvailable[] = $vendorData->getAmosoftVendorCode();
						$isDefaultVendor = false;
						break;
					}
				}
				$arrVendorAvailable[] = $vendorData->getAmosoftVendorCode();
			}else {	
				$arrFirstVendor = $collectionVendor->getFirstItem()->getData();
				$vendorCode = $arrFirstVendor ['amosoft_vendor_code'];
				$inventoryStock = $arrFirstVendor ['stock'];
				$vendorCost = $arrFirstVendor ['cost'];
				$vendorSku = $arrFirstVendor ['amosoft_vendor_sku'];
				$productSku = $arrFirstVendor ['product_sku'];
				if($inventoryStock < $qtyInvoiced)
					$isDefaultVendor = true;
				else 
					$isDefaultVendor = false;
				
				$arrVendorAvailable[] = $arrFirstVendor ['amosoft_vendor_code'];
			}			
		} 
		
		if(!empty($vendorCode)){
			if ($vendorCode && $inventoryStock >= $qtyInvoiced) {		
				$itemStatusHistory = Mage::helper('dropship')->getSerialisedData($orderItemInstance, $itemStatusComplete, $this->_orderStatus);
				Mage::helper('dropship')->genrateLog(0,null,null,'@@@@@@@@ Sourcing Details==> stock('.$inventoryStock.') >= qtyinvoiced('.$qtyInvoiced.'),vendor_code ->'.$vendorCode.', item-status->'.$itemStatusComplete);
				Mage::getModel('dropship/amosoft')->setupNotification();
				$this->_itemData [$item->getItemId()] = array (
						'updateInventory' => true,
						'qtyInvoiced' =>$qtyInvoiced,
						'updated_at' => now(),
						'sku' => $item->getSku(),
						'updated_by' => 'Cron',
						'amosoft_item_status' => $itemStatusComplete,
						'amosoft_vendor_code' => $vendorCode,
						'vendor_cost' => $vendorCost * $qtyInvoiced,
						'amosoft_vendor_sku' => $vendorSku,
						'item_status_history' => $itemStatusHistory 
				);
				return $itemStatusComplete;
			}
			if ($isDefaultVendor && $inventoryStock <= $qtyInvoiced && !empty($defaultVendor) && in_array($defaultVendor,$arrVendorAvailable)) {	
				$itemStatusHistory = Mage::helper('dropship')->getSerialisedData($orderItemInstance, $itemStatusComplete, $this->_orderStatus);		
				Mage::helper('dropship')->genrateLog(0,null,null,'@@@@@@@@ Sourcing Details Default vendor set ==>stock('.$inventoryStock.') >= qtyinvoiced('.$qtyInvoiced.'),vendor_code ->'.$vendorCode.', item-status->Transmitting');
				$this->_itemData [$item->getItemId()] = array (
						'updateInventory' => false,
						'updated_at' => now(),
						'sku' => $item->getSku(),
						'updated_by' => 'Cron',
						'amosoft_item_status' => $itemStatusComplete,
						'amosoft_vendor_code' => $defaultVendor,
						'vendor_cost' => $arrDefaultVendorDetails ['cost'] * $qtyInvoiced,
						'amosoft_vendor_sku' => $arrDefaultVendorDetails ['amosoft_vendor_sku'],
						'item_status_history' => $itemStatusHistory 
				);
				return $itemStatusComplete;			
			}
			if ($vendorCode && $inventoryStock <= $qtyInvoiced) {				
				$itemStatusHistory =Mage::helper('dropship')->getSerialisedData($orderItemInstance, $itemStatusBackorder, $this->_orderStatus);
				Mage::helper('dropship')->genrateLog(0,null,null,'@@@@@@@@ Sourcing Details==>stock('.$inventoryStock.') <= qtyinvoiced('.$qtyInvoiced.'),vendor_code ->'.$vendorCode.', item-status->'.$itemStatusBackorder);
				$this->_itemData [$item->getItemId()] = array (
						'updateInventory' => false,
						'updated_at' => now(),
						'sku' => $item->getSku(),
						'updated_by' => 'Cron',
						'amosoft_item_status' => $itemStatusBackorder,
						'amosoft_vendor_code' => $vendorCode,
						'vendor_cost' => $vendorCost * $qtyInvoiced,
						'amosoft_vendor_sku' => $vendorSku,
						'item_status_history' => $itemStatusHistory 
				);
				return $itemStatusBackorder;				
			}		
		}else{
			$itemStatusHistory = Mage::helper('dropship')->getSerialisedData($orderItemInstance,$itemStatusNoDropShip, $this->_orderStatus);	
			Mage::helper('dropship')->genrateLog(0,null,null,'@@@@@@@@ Sourcing Details==> No vendor Set ,vendor_code ->'.$vendorCode.', item-status->No Dropship');
			$this->_itemData [$item->getItemId()] = array (
					'updateInventory' => false,
					'updated_at' => now(),
					'sku' => $item->getSku(),
					'updated_by' => 'Cron',
					'amosoft_item_status' => $itemStatusNoDropShip,
					'amosoft_vendor_code' => $vendorCode,
					'vendor_cost' => $vendorCost * $qtyInvoiced,
					'amosoft_vendor_sku' => '',
					'item_status_history' => $itemStatusHistory 
			);
			return $itemStatusNoDropShip;			
		}		
	}
	
	 /**
     * Flag to stop observer executing more than once
     *
     * @var static bool
     */
    //static protected $_singletonFlag = false;
 
    /**
     * This method will run when the product is saved from the Magento Admin
     * Use this function to update the product model, process the 
     * data or anything you like
     *
     * @param Varien_Event_Observer $observer
     */
	
	public function saveProductTabData(Varien_Event_Observer $observer)
   	{       
        $product = $observer->getEvent()->getProduct();
        if(!empty($product['vendor_update']) || !empty($product['vendor_new'])){
            try {
                /**
                 * Perform any actions you want here
                 *
                 */
                $customFieldValue =  $this->_getRequest()->getPost('product');
                $result = Mage::getModel('dropship/inventory')->saveTabVendorData($customFieldValue);
                
                /**
                 * Uncomment the line below to save the product
                 *
                 */
                //if(!$result)
                	//Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Vendor Data Cannot be saved'));
                
                //$product->save();
                
                
                $this->_inventoryUpdate($result,$customFieldValue['sku']);
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
         }
         return;
   	}
   	
   	protected function _inventoryUpdate($result,$sku)
   	{
   		if(!$result['result']){
   			return;
   		}
   			
   		$finalStock = $result['inventory'];
        $finalStock = Mage::helper('dropship')->getIsQtyDecimal($sku, $finalStock);
   		$conn = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_write' );
   		 
   		$tableNameStatus = Mage::getSingleton ( 'core/resource' )->getTableName ( 'cataloginventory/stock_status' );
   		$tableNameItem = Mage::getSingleton ( 'core/resource' )->getTableName ( 'cataloginventory/stock_item' );
   		$tableNameItemIdx = Mage::getSingleton ( 'core/resource' )->getTableName ( 'cataloginventory/stock_status_indexer_idx' );
   		 
   		$stockStatus = $finalStock ? 1 : 0;
   		$productId = Mage::getModel('catalog/product')->getIdBySku($sku);
   		if($productId){ 
			$updateStatus = 'update '.$tableNameStatus.' SET qty = '.$finalStock.',stock_status = '.$stockStatus.' where product_id = '.$productId;
			$updateItem = 'update '.$tableNameItem.' SET qty = '.$finalStock.',is_in_stock = '.$stockStatus.' where product_id = '.$productId;
			$updateItemIdx =  'update '.$tableNameItemIdx.' SET qty = '.$finalStock.',stock_status = '.$stockStatus.' where product_id = '.$productId;
			$conn->beginTransaction ();
			$conn->query ($updateStatus);
			$conn->query ($updateItem);
			$conn->query ($updateItemIdx);			 
			try {
				$conn->commit ();
			} catch ( Exception $e ) {
				$conn->rollBack ();
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			} 		 
   		}  		 
   	}
      
    /**
     * Retrieve the product model
     *
     * @return Mage_Catalog_Model_Product $product
     */
    public function getProduct()
    {
        return Mage::registry('product');
    }
     
    /**
     * Shortcut to getRequest
     *
     */
    protected function _getRequest()
    {
        return Mage::app()->getRequest();
    }
    
    public function preDispatch(Varien_Event_Observer $observer)
    {    
		return;
    }
    
    protected function _isValidForShipmentEmail($shipment)
    {
		// send shipment email only when email shipment is enabled from module
		if(Mage::getStoreConfig(self::XML_PATH_AMOSOFT_EMAIL_SHIPMENT)){
        $trackingNumbers = array();
        foreach ($shipment->getAllTracks() as $track) {
            $trackingNumbers[] = $track->getNumber();
        };
        // send shipment email only when carrier tracking info is added
        if (count($trackingNumbers) > 0) {
            $lastValueOfArray = end($trackingNumbers);
            $lastValueOfArray = trim($lastValueOfArray);    
                if(!empty($lastValueOfArray))
                    return true;
                else
                    return false;
        } else {
            return false;
        }
    }
    }
     
    public function salesOrderShipmentSaveBefore(Varien_Event_Observer $observer)
    {       
		if(!Mage::getStoreConfig(self::XML_PATH_AMOSOFT_EMAIL_SHIPMENT)){
			return $this;
		}
        if (Mage::registry('salesOrderShipmentSaveBeforeTriggered')) {
            return $this;
        } 
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment = $observer->getEvent()->getShipment();
        if ($shipment) {
            if ($this->_isValidForShipmentEmail($shipment)) {
                $shipment->setEmailSent(true);
                Mage::register('salesOrderShipmentSaveBeforeTriggered', true);
            }
        }
        return $this;
    }
     
    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
		if(!Mage::getStoreConfig(self::XML_PATH_AMOSOFT_EMAIL_SHIPMENT)){
			return $this;
		}
        if (Mage::registry('salesOrderShipmentSaveAfterTriggered')) {
            return $this;
        }
        
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment = $observer->getEvent()->getShipment();
        if ($shipment) {
            if ($this->_isValidForShipmentEmail($shipment)) {
                $shipment->sendEmail();
                Mage::register('salesOrderShipmentSaveAfterTriggered', true);
            }
        }
        return $this;
    }
	
	/*
	 * This function is used to delete the sku from vendor inventory 
	 *	when the same sku is deleted from catalog product
	 */
	public function catalogProductDeleteAfter(Varien_Event_Observer $observer)
	{
		$deletedProductSku = $observer->getEvent()->getProduct()->getSku();
		$orderItem = Mage::getModel ('dropship/inventory')->getCollection()->addFieldToFilter('product_sku', $deletedProductSku);
		if($orderItem->getSize() > 0){
			foreach($orderItem as $data){
				try {
					Mage::getModel ('dropship/inventory')->load($data->getId())->delete();
				} catch (Exception $e) {    
					Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				}		
			}
		}
		return 	$this;		
	}
	//@function : notify cutomer for oudated product inventory through email,initiated by cron
	public function notifyForProductUpdateInventory(){
		if (!Mage::getStoreConfigFlag (self::XML_PATH_INVENTORY_NOTIFICATION_EMAIL_ENABLED) || !Mage::getStoreConfigFlag (self::XML_PATH_INVENTORY_NOTIFICATION_DAYS) || !Mage::getStoreConfigFlag (self::XML_PATH_INVENTORY_NOTIFICATION_EMAIL)) {
			return $this;
		}
		$itemObject;
		$fileInfo = array();
		$ioAdapter = new Varien_Io_File();
		$open_monitor_from = Date('Y-m-d h:i:s', strtotime('-'.Mage::getStoreConfig(self::XML_PATH_INVENTORY_NOTIFICATION_DAYS).' day'));
		$open_monitor_to = Mage::getModel('core/date')->gmtDate();
		$itemObject = Mage::getModel('dropship/inventory')->getCollection()->addFieldTofilter('updated_at', array('from' => $open_monitor_from,'to' => $open_monitor_to));
		if($itemObject->getSize() <= 0){
			Mage::log('cannot send outdated product inventory email collection is empty for form :'.$open_monitor_from.' to :'.$open_monitor_to, null, 'notification_error.log');
			return $this;
		}
		$fileInfo = Mage::getModel('dropship/csvparser')->getCsvFile($itemObject);
		$mailData['days'] = Mage::getStoreConfig(self::XML_PATH_INVENTORY_NOTIFICATION_DAYS);
		$mailData['subject'] = 'dropship list of outdated product inventory';
		$postObject = new Varien_Object();
		$postObject->setData($mailData);
		$email = trim(Mage::getStoreConfig(self::XML_PATH_INVENTORY_NOTIFICATION_EMAIL));
		$templateId = 'amosoft_outdated_product_inventory';
		$isMailSent = Mage::helper('dropship')->sendMail($postObject,$email,$templateId,$fileInfo['value']);
		$ioAdapter->rm($fileInfo['value']);
		return $this;
	}
}
