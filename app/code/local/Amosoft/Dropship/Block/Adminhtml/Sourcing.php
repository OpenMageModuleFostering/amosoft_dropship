<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Block_Adminhtml_Sourcing extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_sourcing';
    $this->_blockGroup = 'dropship';
    $this->_headerText = Mage::helper('dropship')->__('Order Management');
    $this->_addButtonLabel = Mage::helper('dropship')->__('Add Vendor');
    parent::__construct();
    $this->removeButton('add');
  }
  
  public function getAmosoftOrderItemsDetails($item){
  	
  	$ItemCollection = Mage::getModel('dropship/orderitems')->getCollection()->addFieldTofilter('item_order_id',$item->getOrderId())->addFieldTofilter('sku',$item->getSku());
  	$ItemCollection->getSelect()->joinLeft( array('nrank'=> Mage::getSingleton('core/resource')->getTableName('dropship/ranking')), "main_table.amosoft_vendor_code = nrank.amosoft_vendor_code",array("nrank.amosoft_vendor_name"));
  	return $ItemCollection->getFirstItem();
  }
}
