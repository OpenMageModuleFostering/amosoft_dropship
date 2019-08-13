<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
$collectionSize = (Mage::getModel('dropship/inventory')->getCollection()->getSize() >= 1) ? 1 : '';
 $coreConfigData = array(
  array(
        'scope'         => 'default',
        'scope_id'    => '0',
        'path'       => 'amosoft/notification/vendor_setup',
        'value'     => $collectionSize,
        
    )

);

/**
 * Insert default blocks
 */
foreach ($coreConfigData as $data) {
	Mage::getModel('core/config_data')->setData($data)->save();
} 