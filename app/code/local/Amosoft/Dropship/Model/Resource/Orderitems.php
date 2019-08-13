<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Model_Resource_Orderitems extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("dropship/orderitems", "id");
    }
    public function saveOrderItems($itemData,$orderObj,$crontype)
    {
    	try {
    		$adapter = $this->_getWriteAdapter();
    		$adapter->beginTransaction();
    		foreach ($itemData as $key => $item) {
    			$condition = array(
    					'item_id = ?' => (int) $key,
    			);
    			unset($item['updateInventory']);
    			unset($item['qtyInvoiced']);
    			$adapter->update($this->getMainTable(),$item,$condition);
    		}
    		$adapter->commit();
    		foreach ($itemData as $key => $item) {
    			if($item ['updateInventory'])
    				Mage::getModel ( 'dropship/orderitems' )->updateAmosoftVendorInvenory ( $item['amosoft_vendor_code'],$item['sku'], $item['qtyInvoiced']);
    			if ($item['amosoft_item_status'] == Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_TRANSMITTING)
    				Mage::getModel('dropship/amosoft')->setupNotification();;
    			$this->saveOrderItemsComments($item,$orderObj);
    		}
    	} catch (Mage_Core_Exception $e) {
    		$adapter->rollBack();
    		throw $e;
    	} catch (Exception $e){
    		$adapter->rollBack();
    		Mage::logException($e);
    		Mage::helper('dropship')->genrateLog(0,null,null,'Section :Error In saving order item data: '.$e->getMessage().' for orderid : '.$orderObj->getEntityId());
    		Mage::getModel('dropship/ordersourcing')->sourcingCompleted($crontype);
    	}
    }
    
    protected function saveOrderItemsComments($itemData,$orderObj){
    	try {

    		$orderObj->addStatusHistoryComment($itemData['sku'].': Item status changed to '.$itemData['amosoft_item_status']);
    		$orderObj->save();
    	} catch (Exception $e) {
    		throw $e;
    	}
    	
    }
}