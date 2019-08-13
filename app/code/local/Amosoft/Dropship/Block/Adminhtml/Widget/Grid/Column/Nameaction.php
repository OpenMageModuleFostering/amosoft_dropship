<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Block_Adminhtml_Widget_Grid_Column_Nameaction extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
  
	/**
     * Renders column
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
    	$sku = $row->getData('product_sku');
    	$productObject = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku); 
    	if ($productObject) 
    		$out = $productObject->getName();
    	else
    		$out = '';
    	return $out;
    }
}
