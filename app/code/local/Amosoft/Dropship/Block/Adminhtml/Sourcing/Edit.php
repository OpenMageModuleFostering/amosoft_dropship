<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
 
class Amosoft_Dropship_Block_Adminhtml_Sourcing_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        
                 
        $this->_objectId = 'amosoft_item_id';
        $this->_blockGroup = 'dropship';
        $this->_controller = 'adminhtml_sourcing';
        parent::__construct();
        
        $this->_updateButton('save', 'label', Mage::helper('dropship')->__('Save Sourcing'));
       	$this->_updateButton('delete', 'label', Mage::helper('dropship')->__('Delete Supplier'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save amosoft-button',
        ), -100);
        if(Mage::registry('sourcing_data')->getData('updated_by') != 'Amosoft'){
        
        	$this->_addButton('cancelitem', array(
        			'label'     => Mage::helper('adminhtml')->__('Cancel Item'),
        			'onclick'   => 'cancelItem()',
        			'class'     => 'delete',
        	), -100);
        }
        $this->_removeButton('delete');
        $this->_removeButton('reset');
        $this->_formScripts[] = "
                
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
            function cancelItem(){
        		$('amosoft_vendor_code').className = '';
                editForm.submit($('edit_form').action+'cancel/item/');
            }    
            ";
        
        
    }

    public function getBackUrl()
    {
    	return $this->getUrl('*/*/sourcinggrid');
    }
    
    public function getHeaderText()
    {
        if( Mage::registry('sourcing_data') && Mage::registry('sourcing_data')->getAmosoftItemId() ) {
            return Mage::helper('dropship')->__("Edit Item Sourcing Supplier", $this->htmlEscape(Mage::registry('sourcing_data')->getTitle()));
        } else {
            return Mage::helper('dropship')->__('Select Sourcing Supplier');
        }
    }

}