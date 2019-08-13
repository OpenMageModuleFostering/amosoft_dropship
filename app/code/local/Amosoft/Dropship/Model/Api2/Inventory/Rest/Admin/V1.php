<?php
/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship

 */
class Amosoft_Dropship_Model_Api2_Inventory_Rest_Admin_V1 extends Amosoft_Dropship_Model_Api2_Inventory_Rest
{
	protected function _create(array $data)
	{
		if(Mage::helper('dropship')->isProcessRunning('bulk_assign')){
			$message = 'Bulk product setup is currently running hence cannot run REST import';
			echo $message;
			//Mage::log($message, null, 'amosoft_log_report.log');
			die;
		}
		$requestData = array_chunk($data['vendordata'], 1, true);
		try {
			foreach($requestData as $chunkData)
			{
				$processedData['vendordata'] = $chunkData;
				$result[] = Mage::getModel('dropship/inventory')->prepareInventoryTable($processedData);
			}
			 
			foreach($result as $row){
				foreach($row as $vendor=>$msg){
					echo $msg.' '.$vendor.'<br>';
				}
			}
			die();
		} catch (Exception $e) {
			die($e->getMessage());
		}	
	}
}
