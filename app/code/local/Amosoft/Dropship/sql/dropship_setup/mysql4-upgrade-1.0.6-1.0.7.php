<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
$installer = $this;
$importLog = $installer->getTable('dropship/vendor_import_log');
$ranking = $installer->getTable('dropship/ranking');
$installer->startSetup();
$installer->getConnection()->addColumn($importLog, 'error_id', array(
    'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'identity'  => true,
	'nullable' => false,
     'unsigned'  => true,
	 'primary'   => true,
    'comment' => 'Add error id row'
)
);
$installer->getConnection()->addKey($importLog, 'error_id', 'error_id');
$installer->getConnection()->addColumn($ranking, 'linking_attribute', array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' =>255,
		'nullable' => true,
		'default' => null,
		'comment' => 'linking attribute'));
$installer->run(
  "DROP TABLE IF EXISTS {$installer->getTable('dropship/csvtmpdata')};
  CREATE TABLE IF NOT EXISTS {$installer->getTable('dropship/csvtmpdata')} (
  `row_id` INT(11) NOT NULL AUTO_INCREMENT ,
  `vendor_code` varchar(50) NOT NULL,
  `csv_vendor_sku` varchar(50) NOT NULL,
  `csv_stock` varchar(11) NOT NULL,
  `csv_price` varchar(11) NOT NULL,
 `is_processed` int(1) NOT NULL DEFAULT '0',
  KEY `row_id` (`row_id`),
  KEY `vendor_code` (`vendor_code`),
  KEY `csv_vendor_sku` (`csv_vendor_sku`),
  KEY `csv_stock` (`csv_stock`),
  KEY `csv_price` (`csv_price`),
  KEY `is_processed` (`is_processed`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
CREATE TABLE IF NOT EXISTS {$installer->getTable('dropship/vendor_import_log_desc')} (
`id` int(11) NOT NULL AUTO_INCREMENT,
`error_id` int(10) unsigned NOT NULL,
`description` varchar(255) CHARACTER SET utf8 NOT NULL,
PRIMARY KEY (`id`),
KEY `error_id` (`error_id`),
FOREIGN KEY (`error_id`) REFERENCES {$installer->getTable('dropship/vendor_import_log')} (`error_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");


$installer->endSetup();
