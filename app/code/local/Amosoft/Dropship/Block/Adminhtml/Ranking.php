<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Block_Adminhtml_Ranking extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_ranking';
    $this->_blockGroup = 'dropship';
    $this->_headerText = Mage::helper('dropship')->__('Vendor/Supplier Management');
    $this->_addButtonLabel = Mage::helper('dropship')->__('Add Vendor Ranking');
   
    
    $this->addButton('show_history',array(
    		'label'     => 'Show History',
    		'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/showhistory') .'\')',
    		'class'     => 'save amosoft-button',
    ));
     $this->addButton('save_ranking_table',array(
    		'label'     => 'save ranking table',
    		'onclick'   => 'saveRankingTable()',
    		'class'     => 'save amosoft-button',
    )); 
    
    $this->addButton('save_ranking',array(
    		'label'     => 'Save Ranking',
    		'class'     => 'save amosoft-button',
    		'onclick'   => 'saveRankingTable()',
    		
    ));
     parent::__construct();
     $this->setTemplate('amosoft/vendor_ranking.phtml');
   	$this->removeButton('add');
   	$this->removeButton('save_ranking_table');
    
  }
  public function getVendorCollection($type = 'no'){

  	$arrVendor = array();
  	$tempReslt = Mage::getModel('dropship/ranking')->getVendorCollection($type);
  	$result['gridData'] = Mage::helper('core')->jsonEncode($tempReslt);
  	if(!empty($tempReslt)){
  	foreach($tempReslt as $value){
  		$arrVendor[] = array('name'=>$value['name'],'code'=>$value['code']);
  	}
  	}
  	$result['arrayData'] = Mage::helper('core')->jsonEncode($arrVendor);
  	return $result;
  }
  public function getAttributeCode()
  {
	$helper = Mage::helper('dropship');
	$attributeCode = array(array('link'=>'','name'=>$helper::AMOSOFT_PRODUCT_LINK_NONE),array('link'=>$helper::AMOSOFT_PRODUCT_LINK_CODE_UPC,'name'=>$helper::AMOSOFT_PRODUCT_LINK_UPC),array('link'=>$helper::AMOSOFT_PRODUCT_LINK_CODE_MNP,'name'=>$helper::AMOSOFT_PRODUCT_LINK_MNP),array('link'=>$helper::AMOSOFT_PRODUCT_LINK_CODE_SKU,'name'=>$helper::AMOSOFT_PRODUCT_LINK_SKU));
	return Mage::helper('core')->jsonEncode($attributeCode);
  }

}
