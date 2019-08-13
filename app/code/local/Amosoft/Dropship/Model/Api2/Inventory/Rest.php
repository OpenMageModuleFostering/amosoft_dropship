<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship

 */
abstract class Amosoft_Dropship_Model_Api2_Inventory_Rest extends Amosoft_Dropship_Model_Api2_Inventory
{
    /**
     * Current loaded product
     *
     * @var Mage_Catalog_Model_Product
     */
    protected $_product;

    /**
     * Retrieve product data
     *
     * @return array
     */
    protected function _retrieve()
    {
        $product = $this->_getProduct();

        $this->_prepareProductForResponse($product);
        return $product->getData();
    }

    /**
     * Product create only available for admin
     *
     * @param array $data
     */
    protected function _create(array $data)
    {
    	$this->_critical(self::RESOURCE_METHOD_NOT_ALLOWED);
    }
    /**
     * Product create only available for admin
     *
     * @param array $data
     */
    protected function _multicreate(array $data)
    {
    	
    	//return array('name'=>'shashank');
        //$this->_critical(self::RESOURCE_METHOD_NOT_ALLOWED);
    }


}
