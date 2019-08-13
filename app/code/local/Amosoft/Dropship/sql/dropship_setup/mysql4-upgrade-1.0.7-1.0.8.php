<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
$installer = $this;
//apply patch for insert on duplicate
$csvTempData = $installer->getTable('dropship/csvtmpdata');
$installer->getConnection()->addKey($csvTempData,'csv_vendor_sku_unique','csv_vendor_sku','unique');
$installer->endSetup();
