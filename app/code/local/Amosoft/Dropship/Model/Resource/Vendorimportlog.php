<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Model_Resource_Vendorimportlog 
{
	protected $_tableVendorImportLog ;
	protected $conn;
	
	public function getDatabaseConnection() 
	{
		return Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_write' );
	}
    
	public function insertLog($amosoft_vendor_code = null,$updated_by = null,$success = 0,$failure = 0,$ftp_error =null,$ftp_error_desc = null)
	{
	$this->_tableVendorImportLog = Mage::getSingleton ( 'core/resource' )->getTableName ( 'dropship/vendor_import_log' );
	 $this->conn = $this->getDatabaseConnection();	
     $this->conn->beginTransaction ();
	 $created_at = now();
     $insert = 'INSERT INTO '.$this->_tableVendorImportLog.'(amosoft_vendor_code,updated_by,success,failure,ftp_error,ftp_error_desc,created_at) VALUES ("'.$amosoft_vendor_code.'","'.$updated_by.'",'.$success.','.$failure.',"'.$ftp_error.'","'.$ftp_error_desc.'","'.$created_at.'")';
     $this->conn->query($insert);
		try {
				$this->conn->commit ();
			} catch ( Exception $e ) {
				$this->conn->rollBack ();
				Mage::log($e->getMessage(), Zend_Log::ERR);
				Mage::logException($e);
				echo $e->getMessage();
			
			}
   }
}