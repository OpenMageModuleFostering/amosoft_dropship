<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Block_Adminhtml_Sourcing_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    
	protected $_statusArray = array(
					      		Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_TRANSMITTING,
					      		Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_SOURCING,
					      		Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_REPROCESS,
					      		Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_BACKORDER,
					      		Amosoft_Dropship_Helper_Data::AMOSOFT_ITEM_STATUS_NO_DROPSHIP
      							);	
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $sku = Mage::registry('sourcing_data')->getData('sku');
      $this->setForm($form);
      $fieldset = $form->addFieldset('amosoft_form', array('legend'=>Mage::helper('dropship')->__('Item Sourcing Information')));
      
    if(in_array(Mage::registry('sourcing_data')->getData('amosoft_item_status'),$this->_statusArray)){	     
      $fieldset->addField('amosoft_vendor_code', 'select', array(
      		'label'     => Mage::helper('dropship')->__('Supplier'),
      		'class'     => 'required-entry validate-select',
      		'required'  => true,
      		'name'      => 'amosoft_vendor_code',
      		'values'    => Mage::getModel('dropship/system_config_source_vendorlist')->vendorListSourcing(true,$sku),
      		'default' => '',
      		'note' => 'Select your Dropship Supplier to source this item and bypass the dropship sourcing rule.'
      ));
	}
     $fieldset->addField('amosoft_item_status', 'text', array(
     		'label'     => Mage::helper('dropship')->__('Amosoft Item Status'),
     		'name'      => 'amosoft_item_status',
     		'note'=>'Read only filed',
     		'readonly'=> true
     ));
       
      
     $fieldset->addField('sku', 'text', array(
          'label'     => Mage::helper('dropship')->__('Sku'),
          'name'      => 'sku',
     		'note'=>'Read only filed',
     		'readonly'=> true
      )); 
     $fieldset->addField('item_order_id', 'hidden', array(
     		'name'      => 'item_order_id',
     ));
     
     $fieldset->addField('item_id', 'hidden', array(
     		'name'      => 'item_id',
     ));
	
      if ( Mage::getSingleton('adminhtml/session')->getSourcingData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getSourcingData());
          Mage::getSingleton('adminhtml/session')->setSourcingData(null);
      } elseif ( Mage::registry('sourcing_data') ) {
          $form->setValues(Mage::registry('sourcing_data')->getData());
      }
      return parent::_prepareForm();
  }
}