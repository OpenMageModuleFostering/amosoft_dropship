<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Block_Adminhtml_Amosoft extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_amosoft';
    $this->_blockGroup = 'dropship';
    $this->_headerText = Mage::helper('dropship')->__('Vendor/Supplier Manager');
    $this->_addButtonLabel = Mage::helper('dropship')->__('Add Vendor/Supplier');
    parent::__construct();
  }
}
