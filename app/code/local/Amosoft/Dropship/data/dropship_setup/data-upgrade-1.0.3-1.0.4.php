<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

$updateddata = array(
        'scope'         => 'default',
        'scope_id'    => '0',
        'path'       => 'amosoft_integration/integration/magento_base_url',
        'value'     => Mage::getBaseUrl()
        
    );
    
 try {
 
 Mage::getModel('core/config_data')->load('amosoft_integration/integration/magento_base_url','path')->setData($updateddata)->save(); 
 }
catch (Exception $e) {
    echo $e->getMessage();
    return;
}

/* update order state */

/* $orderStatuses = array('lb_ready_to_source'=>'Ready to Source');
 Mage::getModel('dropship/amosoft')->updateOrderState($orderStatuses); */



/* If compiler is enabled than we need to run the compilation  */

/*if (defined('COMPILER_INCLUDE_PATH')) {
	try {
		Mage::getModel('compiler/process')->run();
		Mage::getSingleton('adminhtml/session')->addSuccess(
		Mage::helper('compiler')->__('The compilation has completed.')
		);
	} catch (Mage_Core_Exception $e) {
		Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
	} catch (Exception $e) {
		Mage::getSingleton('adminhtml/session')->addError(
		Mage::helper('compiler')->__('Compilation error')
		);
	}
}
*/
/* Load default values of Sourcing Schedule Cron Jobs*/
$coreConfigData = array(
    array(
        'scope'     => 'default',
        'scope_id'  => '0',
        'path'      => 'amosoft_sourcing/cron_settings/sourcing_time',
        'value'     => '-2,10',
        
    ),
    array(
        'scope'         => 'default',
        'scope_id'    => '0',
        'path'       => 'amosoft_sourcing/cron_settings/backorder_time',
        'value'     => '-2,15',
        
    ),
    array(
        'scope'      => 'default',
        'scope_id'   => '0',
        'path'       => 'crontab/jobs/amosoft_dropship/schedule/cron_expr',
        'value'      => '*/10 * * * *',
        
    ),
	array(
        'scope'     => 'default',
        'scope_id'  => '0',
        'path'      => 'crontab/jobs/amosoft_dropship/run/model',
		'value'     => 'dropship/observer::amosoftSourcing', 
    ),
	array(
        'scope'      => 'default',
        'scope_id'   => '0',
        'path'       => 'crontab/jobs/amosoft_backorder/schedule/cron_expr',
		'value'     => '*/15 * * * *',  
    ),
	array(
        'scope'      => 'default',
        'scope_id'   => '0',
        'path'       => 'crontab/jobs/amosoft_backorder/run/model',
		'value'    => 'dropship/observer::amosoftBackorder',  
    )
	
	
	
    );

/**
 * Insert default blocks
 */
foreach ($coreConfigData as $data) {
    if(!Mage::getModel('core/config_data')->load($data['path'],'path')->getData()){
		 Mage::getModel('core/config_data')->setData($data)->save();
	}
} 

