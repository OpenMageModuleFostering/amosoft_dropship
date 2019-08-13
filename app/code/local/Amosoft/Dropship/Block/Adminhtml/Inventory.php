<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Block_Adminhtml_Inventory extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_inventory';
    $this->_blockGroup = 'dropship';
    $this->_headerText = Mage::helper('dropship')->__('Vendor/Supplier Details');
    parent::__construct();
    $this->removeButton('add');
  }
}
