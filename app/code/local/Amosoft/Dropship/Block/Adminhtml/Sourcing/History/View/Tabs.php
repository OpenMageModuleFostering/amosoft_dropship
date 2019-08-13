<?php
/**
 * Sourcing History view tabs
 *
 * @category   Amosoft
 * @package    Amosoft_Dropship
 */
class Amosoft_Dropship_Block_Adminhtml_Sourcing_History_View_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('adminhtml_sourcing_history_view');
      $this->setDestElementId('history_view');
      $this->setTitle(Mage::helper('dropship')->__('Order Item History'));
   }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('dropship')->__('Order Item History'),
          'title'     => Mage::helper('dropship')->__('Order Item History'),
      ));
     
      return parent::_beforeToHtml();
  }
   

}