<?php
/**
 * Adminhtml order items grid overwrite for Adding Amosoft order item details
 *
 * @category   Amosoft
 * @package    Amosoft_Dropship
 */
class Amosoft_Dropship_Block_Adminhtml_Sales_Order_View_Items extends Mage_Adminhtml_Block_Sales_Order_View_Items
{
    /**
     * Retrieve order items collection
     *
     * @return unknown
     */
    public function getItemsCollection()
    {
		$id = $this->getOrder()->getId();
		$collection = Mage::getResourceModel('sales/order_item_collection');
		$collection->getSelect()->joinLeft( array('abc'=> Mage::getSingleton('core/resource')->getTableName('dropship/orderitems')), "main_table.item_id = abc.item_id",array("abc.amosoft_item_status", "abc.amosoft_vendor_sku","abc.amosoft_vendor_code"))->where("main_table.order_id =".$id);
		$collection->getSelect()->joinLeft( array('nr'=> Mage::getSingleton('core/resource')->getTableName('dropship/ranking')), "abc.amosoft_vendor_code = nr.amosoft_vendor_code",array("nr.amosoft_vendor_name"));
		return $collection;
    }
}
