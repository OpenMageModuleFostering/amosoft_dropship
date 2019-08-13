<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
 
class Amosoft_Dropship_Block_Adminhtml_Sourcing_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{


  public function __construct()
  {
      parent::__construct();
      $this->setId('amosoft_tabs');
      $this->setDestElementId('edit_form');
     $this->setTitle(Mage::helper('dropship')->__('Item Sourcing Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('dropship')->__('Item Sourcing Information'),
          'title'     => Mage::helper('dropship')->__('Item Sourcing Information'),
          'content'   => $this->getLayout()->createBlock('dropship/adminhtml_sourcing_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}