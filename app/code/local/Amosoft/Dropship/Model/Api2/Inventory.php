<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship

 */
class Amosoft_Dropship_Model_Api2_Inventory extends Mage_Api2_Model_Resource
{
	public function getAvailableAttributes($userType, $operation)
    {
    	return array (
			'vendordata' => 'vendordata'
		);
    	
    }

}
