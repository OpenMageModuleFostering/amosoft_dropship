<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
 $coreConfigData = array(
  array(
        'scope'         => 'default',
        'scope_id'    => '0',
        'path'       => 'amosoft_sourcing/cron_settings/backorder_time',
        'value'     => '-1,0',
        
    ),
	array(
        'scope'         => 'default',
        'scope_id'    => '0',
        'path'       => 'amosoft_sourcing/cron_settings_upload/time',
        'value'     => '23,59',
        
    ),
	array(
        'scope'      => 'default',
        'scope_id'   => '0',
        'path'       => 'crontab/jobs/amosoft_backorder/schedule/cron_expr',
		'value'     => '0 * * * *',  
    ),
	array(
        'scope'      => 'default',
        'scope_id'   => '0',
        'path'       => 'crontab/jobs/amosoft_uploadvendor/schedule/cron_expr',
		'value'     => '59 23 * * *',  
    ),
	array(
        'scope'      => 'default',
        'scope_id'   => '0',
        'path'       => 'crontab/jobs/amosoft_uploadvendor/run/model',
		'value'    => 'dropship/observer::ftpParseCsv',  
    )

);

/**
 * Insert default blocks
 */
foreach ($coreConfigData as $data) {
	Mage::getModel('core/config_data')->setData($data)->save();
} 