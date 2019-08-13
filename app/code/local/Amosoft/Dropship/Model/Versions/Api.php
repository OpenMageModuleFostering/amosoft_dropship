<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Model_Versions_Api extends Mage_Api_Model_Resource_Abstract
{
	public function getdropshipversion() {
	  $magento_edition = Mage::getEdition();                      
	  $dropship_version = Mage::getStoreConfig('amosoft_integration/integration/ds360_version',Mage::app()->getStore());	  
	  $magento_version = Mage::getStoreConfig('amosoft_integration/integration/magento_version',Mage::app()->getStore());	  
	  $moduleName = 'Amosoft_Dropship'; 
	  $moduleVersion = Mage::getConfig()->getNode('modules/' . $moduleName . '/version');	  
	  $versions = array();
	  $versions['magentoVer'] = $magento_edition ." ".$magento_version;
	  $versions['dropshipVer'] = $dropship_version;
	  $versions['dropshipDbScriptVer'] = $moduleVersion; 
	  return $versions; 
	}

} 
