<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
 
class Amosoft_Dropship_Block_Adminhtml_Inventory_Edit_Vendortabhistory extends Mage_Adminhtml_Block_Widget
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function canShowTab()
    {
        return true;
    }
    public function getTabLabel()
    {
        return $this->__('dropship Vendor History');
    }
    public function getTabTitle()
    {
        return $this->__('Vendor History');
    }
    public function isHidden()
    {
        return false;
    }
    public function getTabUrl()
    {
        return $this->getUrl('amosoft/adminhtml_inventory/vendorshistory', array('_current' => true));
    }
    public function getTabClass()
    {
        return 'ajax';
    }
} 