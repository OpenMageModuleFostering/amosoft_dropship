<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship

 */
class Amosoft_Dropship_Model_Api2_Product extends Mage_Api2_Model_Resource
{
	public function getAvailableAttributes($userType, $operation)
    {
    	return array (
			'productdata' => 'productdata'
		);	
    }

}
