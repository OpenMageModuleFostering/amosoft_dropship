<?php
/**
 * Adminhtml sourcing history view panel
 *
 * @category    Amosoft
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Block_Adminhtml_Sourcing_History_View_Form extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('amosoft/item_order_history.phtml');
    }
	
	/*
	 * To get the order item status history from serialized to unserialized form
	 * 
	 * @return array
     */	 
	public function getHistory()
	{
		$ItemId = Mage::app()->getRequest()->getParam('amosoft_item_id');
		$itemStatusHistory = Mage::getModel ( 'dropship/orderitems' )->load($ItemId, 'item_id')->getItemStatusHistory();
		return unserialize($itemStatusHistory);
	}
	
	
}	