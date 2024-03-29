<?php

/**
* Amosoft
*
* @category    Local
* @package     Amosoft_Dropship
*/

class Amosoft_Dropship_Helper_Data extends Mage_Core_Helper_Abstract
{

	const AMOSOFT_ITEM_STATUS_TRANSMITTING   = 'Transmitting';
	const AMOSOFT_ITEM_STATUS_SOURCING   = 'Sourcing';
	const AMOSOFT_ITEM_STATUS_REPROCESS   = 'Reprocess';
	const AMOSOFT_ITEM_STATUS_BACKORDER   = 'Backorder';
	const AMOSOFT_ITEM_STATUS_SENT_TO_SUPPLIER   = 'Sent to Supplier';
	const AMOSOFT_ITEM_STATUS_CANCELLED   = 'Cancelled';
	const AMOSOFT_ITEM_STATUS_NO_DROPSHIP   = 'No Dropship';
	const AMOSOFT_ITEM_STATUS_COMPLETED   = 'Completed';
	const AMOSOFT_PRODUCT_LINK_UPC   = 'UPC';
	const AMOSOFT_PRODUCT_LINK_MNP   = 'Manufacturer Part Number';
	const AMOSOFT_PRODUCT_LINK_SKU   = 'Magento Sku';
	const AMOSOFT_PRODUCT_LINK_NONE   = 'None';
	const AMOSOFT_PRODUCT_LINK_CODE_UPC   = 'amosoft_upc';
	const AMOSOFT_PRODUCT_LINK_CODE_MNP   = 'amosoft_mnp';
	const AMOSOFT_PRODUCT_LINK_CODE_SKU   = 'sku';
	protected $_maxtime = 60; 	// time in minutes
	protected $_shipping_cost;

	public function getConfigObject($nodeName = null)
	{
		return trim(Mage::getConfig()->getNode($nodeName)->__toString());
	}

	public function getItemStatuses()
	{
		return array('Sourcing','Reprocess','Backorder','Transmitting','Sent to Supplier','Cancelled','No Dropship','Completed');
	}

	public function getIsQtyDecimal($product_sku,$inventory)
	{
		if($product_sku){
	      $is_qty_decimal = $this->checkIsQtyDecimal($product_sku);
	      if($is_qty_decimal == 1)
	      {
		$inventory = $inventory;
	      }
	      else
	      {
		$inventory = floor($inventory);
	      }

		}
	return $inventory;
	}

	public function checkIsQtyDecimal($product_sku){

		$is_qty_decimal = 0;
	    $product_collection = Mage::getResourceModel('catalog/product_collection');
	    $product_collection->getSelect()->join(Mage::getConfig()->getTablePrefix().'cataloginventory_stock_item', 'e.entity_id ='.Mage::getConfig()->getTablePrefix().'cataloginventory_stock_item.product_id');
	    $product_collection->getSelect()->where('e.sku = ' ."'" .$product_sku."'");
	    $product_collection_count =  count($product_collection);
	    if($product_collection_count > 0)
	    {
	      $is_qty_decimal = $product_collection->getFirstItem ()->getData('is_qty_decimal');

	    }

	return $is_qty_decimal;
	}

	public function getDatabaseConnection() {
		return Mage::getSingleton ( 'core/resource' );
	}

	public function getTableName($name)
	{
		return $this->getDatabaseConnection()->getTableName ( $name );
	}

	public function isProcessRunning($type){

	$isProcessRunning = false;
	if($this->selectTmpTableData($type))
	{
		if(!$this->isProcessHault($type))
		{
			$isProcessRunning = true;
		}
	}

	return $isProcessRunning;
	}

	public function startProcess($type){

	if(!$this->selectTmpTableData($type))
	{
		$this->insertTmpTableData($type);
	}
	 return;
	}


	public function finishProcess($type){
		$this->deleteTmpTableData($type);
	}

	protected function insertTmpTableData($type){
		$write = $this->getDatabaseConnection()->getConnection ( 'core_write' );
		$updatedAt = Mage::getModel('core/date')->gmtDate();
		$createAt = Mage::getModel('core/date')->gmtDate();
		$tmpTableName = $this->getTableName ( 'dropship/tmpdata' );
		$insert = 'insert into '.$tmpTableName.' (tmpdata,created_at,updated_at) values("'.$type.'","'.$createAt.'","'.$updatedAt.'")';
		$write->beginTransaction ();
		$write->query($insert);
	try {
			$write->commit();
		} catch ( Exception $e ) {
			$write->rollBack ();
		}

	}

	protected function updateTmpTableData($type){

		$write = $this->getDatabaseConnection()->getConnection ( 'core_write' );
		$updatedAt = Mage::getModel('core/date')->gmtDate();
		$tmpTableName = $this->getTableName ( 'dropship/tmpdata' );
		$update = 'update '.$tmpTableName.' set updated_at  = '.$updatedAt.' where tmpdata = "'.$type.'"' ;
		$write->beginTransaction ();
		$write->query($update);
		try {
			$write->commit();
		} catch ( Exception $e ) {
			$write->rollBack ();
		}
	}

	protected function deleteTmpTableData($type){

	$write = $this->getDatabaseConnection()->getConnection ( 'core_write' );
	$tmpTableName = $this->getTableName ( 'dropship/tmpdata' );
	$delete = 'delete from '.$tmpTableName.' where tmpdata = "'.$type.'"' ;
	$write->beginTransaction ();
	$write->query($delete);
	try {
		$write->commit();

	} catch ( Exception $e ) {
		$write->rollBack ();
	}
	}

	public function selectTmpTableData($type){

		$read = $this->getDatabaseConnection()->getConnection ( 'core_read' );
		$tmpTableName = $this->getTableName ( 'dropship/tmpdata' );
		$select = 'Select * from '.$tmpTableName.' where tmpdata = "'.$type.'" ORDER BY id DESC limit 1';
		$result = $read->fetchAll($select);

		if(count($result) >= 1)
		$this->_lastUpdateTime = $result[0]['updated_at'];
		return count($result) <= 0 ? false : true;
	}

	protected function isProcessHault($type){
		$now = strtotime(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
			$lastupdate = Mage::getModel('core/date')->timestamp(strtotime($this->_lastUpdateTime));

		$isProcessHault = true;
		$timePassed = ($now - $lastupdate)/60;

		if($timePassed <= $this->_maxtime){

			$isProcessHault = false;
		}else{
			
			$this->finishProcess($type);
		}
		return $isProcessHault;
	}

	/*
	 *
	 * $state 0,1,2 // 0= start without sepration text,1=Start logging section sepration,2=end logging section sepration
	 *
	 *
	 * */
	public function genrateLog($state,$startMessage = null,$endMessage =null,$message = null){
		if($state == 1){
			Mage::log('******'.$startMessage.'******', null, 'amosoft_debug.log');
		}
		if($state == 0 && !empty($startMessage) ){
			Mage::log('******'.$startMessage.'******', null, 'amosoft_debug.log');
		}
		if(!empty($message)){
		Mage::log($message, null, 'amosoft_debug.log');
		}
		if($state == 2){
			Mage::log('******'.$endMessage.'******', null, 'amosoft_debug.log');
		}
		if($state == 0 && !empty($endMessage) ){
			Mage::log('******'.$endMessage.'******', null, 'amosoft_debug.log');
		}
	}

	protected function getLogCollection($params){
		$conn = Mage::getModel('dropship/uploadvendor')->getDatabaseConnection();
		$tableVendorImportLog = Mage::getSingleton ( 'core/resource' )->getTableName ( 'dropship/vendor_import_log' );
		$select = $conn->select()->from($tableVendorImportLog)
								->where("created_at=?", $params['vdate']);
		$result = $conn->query($select);
		$rows = $result->fetch();
		return $rows;
	}

	/**
	 * Returns indexes of the fetched array as headers for CSV
	 *
	 * @return array
	 */
	protected function _getCsvHeaders()
	{
	$headers = array('Supplier Code','Supplier','Magento Sku','vendor_sku','cost','inventory','Failure Reason') ;
	return $headers;
	}

	/**
	 * Generates CSV file with error's list according to the collection in the $this->_list
	 * @return array
	 */
	public function generateErrorList($params,$isFtp = false)
	{
	    $ftpCollection = (!$isFtp) ? $this->getLogCollection($params) : $params;
		//$ftpError = explode('<li>', $this->_list['ftp_error_desc']);
		if (!empty($ftpCollection)) {
		    if (count($ftpCollection) > 0) {
		    if($ftpCollection['failure'] > 3000){
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Data size is too big for export'));
		    	return array('error' => true);
		    }
			$io = new Varien_Io_File();
			$path = Mage::getBaseDir('var') . DS . 'export' . DS;
			$name = md5(microtime());
			$file = $path . DS . $name . '.csv';
			$io->setAllowCreateFolders(true);
			$io->open(array('path' => $path));
			$io->streamOpen($file, 'w+');
			$io->streamLock(true);
			$io->streamWriteCsv($this->_getCsvHeaders());
			$rowData = (!is_null($ftpCollection['ftp_error_desc'])) ? $ftpCollection['ftp_error_desc'] : $ftpCollection['error_id'];
			$prepareValue = $this->prepareExportVaues($rowData, $ftpCollection['amosoft_vendor_code']); 
				if(is_array($prepareValue)){
				foreach($prepareValue as $value){
					$csv[] = $ftpCollection['amosoft_vendor_code'];
					$csv[] = $this->getSupplierName($ftpCollection['amosoft_vendor_code']);
					$csv[] = $value['magento_sku'];
					$csv[] = $value['vendor_sku'];
					$csv[] = $value['cost'];
					$csv[] = $value['qty'];
					$csv[] = $value['reason'];
					$io->streamWriteCsv($csv);
					unset($csv);
						}
				}else
				{
					$csv[] = $ftpCollection['amosoft_vendor_code'];
					$csv[] = $this->getSupplierName($ftpCollection['amosoft_vendor_code']);
					$csv[] = '';
					$csv[] = '';
					$csv[] ='';
					$csv[] = '';
					$csv[] = $ftpCollection['ftp_error_desc'];
					$io->streamWriteCsv($csv);
					unset($csv);
					}
			}
			return array(
			    'type'  => 'filename',
			    'value' => $file,
			    'rm'    => true,
			    'error'=> false	
			);
		}
	}
	public function prepareExportVaues($description,$vendorCode){
		$csvData = array();
		$decodedata = Mage::app()->getLayout()->createBlock('dropship/adminhtml_vendorproductuploadhistory')->prepareRowData($description);
		if(!is_array($decodedata) || empty($decodedata))
			return empty($decodedata) ? implode('',$decodedata) : $decodedata;
		foreach($decodedata as $data){
			$msgArray = Mage::app()->getLayout()->createBlock('dropship/adminhtml_vendorproductuploadhistory')->getMessageArray();
			$msg = $msgArray[$data['error_type']];
			if(is_array($data['value']) && !empty($data['value'])){
				$csvData[] = array('magento_sku'=>$data['value']['magento_sku'],'vendor_sku'=>$data['value']['vendor_sku'],'cost'=>$data['value']['cost'],'qty'=>$data['value']['qty'],'reason'=> $this->genrateHtml($data['value'],$msg,$vendorCode));
			}else{
				$csvData[] = array('magento_sku'=>'','vendor_sku'=>'','cost'=>'','qty'=>'','reason'=> (strstr($msg,'row_num')) ? str_replace('row_num',$data['value'],$msg) : str_replace('empty_file',$data['value'],$msg) );
			}
		}
		return $csvData;
	}
	public function genrateHtml($value,$msg,$vendorCode){
		$replace = Mage::app()->getLayout()->createBlock('dropship/adminhtml_vendorproductuploadhistory')->getReplaceValue();
		$string = $msg;
		$value['vendor_code'] = $vendorCode;
		foreach($replace as $val){
			if(strstr($string,$val))
				$string = str_replace($val,$value[$val],$string);
		}
		return $string;
	}

	/**
	 * Item status for orders
	 *
	 *	@return array
	 */
	public function getAmosoftOrderItemStatus()
	{
		return array('Reprocess', 'Backorder', 'Transmitting', 'Sent to Supplier', 'No Dropship');

	}

	/*
	 * Get serialised data for order item history
	 * @param object $orderItemInstance, string $itemStatus, string $orderStatus
	 * @return string
	 */
	public function getSerialisedData($orderItemInstance, $itemStatus, $orderStatus )
	{
		if($orderItemInstance->getItemStatusHistory()){
			$existingValues = unserialize($orderItemInstance->getItemStatusHistory());
			$data = array('date'=>now(), 'item_status'=>$itemStatus, 'order_status'=>$orderStatus);
			array_push($existingValues,$data);
			$serializeData = serialize($existingValues);
		}else{
			$data = array(0=>array('date'=>now(), 'item_status'=>$itemStatus, 'order_status'=>$orderStatus));
			$serializeData = serialize($data);
		}
		return $serializeData;
	}

	public function sendMail($templateObject,$email,$templateId,$attachment = null){
		
		$result = false;
		
		if(empty($templateId) || empty($email))
			return $result;
		$mailTemplate = Mage::getModel('core/email_template');
		if($attachment)
		{
			$content = file_get_contents($attachment);
			//$content = str_replace('></',">\n</",$content);
			// this is for to set the file format
			$at = new Zend_Mime_Part($content);
			$at->type = 'application/csv'; // if u have PDF then it would like -> 'application/pdf'
			$at->disposition = Zend_Mime::DISPOSITION_INLINE;
			$at->encoding = Zend_Mime::ENCODING_8BIT;
			$at->filename = 'outdated_productlist'.date('ymdHis').'.csv';
			$mailTemplate->getMail()->addAttachment($at);
			//$emailTemplate->_mail->addAttachment($at);
		}
		/* @var $mailTemplate Mage_Core_Model_Email_Template */
		$mailTemplate->setDesignConfig(array('area' => 'backend'));
		if($templateObject->getBcc())
		{
			$mailTemplate->addBcc($templateObject->getBcc());
		}
		//$mailTemplate->setTemplateSubject($subject);
		$name = explode('@',$email);
		$mailTemplate->sendTransactional(
					$templateId,
					'general',
					$email,
					$name[0],
					array('templatevar' => $templateObject)
			);
		(!$mailTemplate->getSentSuccess()) ? $result = false : $result = true;
		return $result;
	}

	/**
	 * Turn on read uncommitted mode
	 */
	public function turnOnReadUncommittedMode()
	{
		$this->getDatabaseConnection()->getConnection('read')->query("SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED");
	}
	/**
	 * Turn on read committed mode
	 */
	public function turnOnReadCommittedMode()
	{
		$this->getDatabaseConnection()->getConnection('read')->query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
	}
	public function isJson($data){
		$result = false;
		$data = trim($data,'"');
		$data = trim($data,'\'');
		$data = stripslashes($data);
		$decodedata = json_decode($data);
		$result = (json_last_error() == JSON_ERROR_NONE) ? true : false;
		return $result;
	}
	public function getSupplierName($vendorCode){
		$vendorRankModel = Mage::getSingleton('dropship/ranking')->load($vendorCode,'amosoft_vendor_code');
		return $vendorRankModel->getAmosoftVendorName();
	}

	public function getVendorShippingCost($product){

		$productSku = $product->getSku();
		//echo $productSku; exit; 
		$itemId = $product->getItemId();
		$isDefaultVendor = false;
		$vendorCode = '';
		$inventoryStock = '';
		$defaultVendor = (Mage::getStoreConfig('amosoft_sourcing/rank/defaultbackorder') == 'none') ? '' : Mage::getStoreConfig('amosoft_sourcing/rank/defaultbackorder');
		$orderItemInstance = Mage::getModel( 'dropship/orderitems' );
		$orderItemInstance->load( $itemId, 'item_id' );
		$collectionVendor = $orderItemInstance->prepareOrderItemData($product);
		$arrDefaultVendorDetails = array();
		$vendorCost = 0;
		
		if ($collectionVendor->count() > 0) {
			if($collectionVendor->count() >= 1){
				foreach ($collectionVendor as $vendorData) {
					//assign default vendor details
					if(!empty($defaultVendor) && $vendorData->getAmosoftVendorCode() == $defaultVendor )
					{
						$arrDefaultVendorDetails =  array('amosoft_vendor_code'=>$vendorData->getAmosoftVendorCode(),'stock'=>$vendorData->getStock(),'cost'=>$vendorData->getCost(),'shipping_cost'=>$vendorData->getShippingCost(),'amosoft_vendor_sku'=>$vendorData->getAmosoftVendorSku(),'product_sku'=>$vendorData->getProductSku());
					}					
					//if item is in backordered 
					if($vendorData->getStock() < $qtyInvoiced ){
						$arrVendorDetail[] = array('amosoft_vendor_code'=>$vendorData->getAmosoftVendorCode(),'stock'=>$vendorData->getStock(),'cost'=>$vendorData->getCost(),'amosoft_vendor_sku'=>$vendorData->getAmosoftVendorSku(),'product_sku'=>$vendorData->getProductSku());
						$vendorCode = $arrVendorDetail[0]['amosoft_vendor_code'];
						$inventoryStock = $arrVendorDetail[0]['stock'];
						$vendorCost = $arrVendorDetail[0]['cost'];
						$vendorShippingCost = $arrVendorDetail[0]['shipping_cost'];
						$vendorSku = $arrVendorDetail[0]['amosoft_vendor_sku'];
						$productSku = $arrVendorDetail[0]['product_sku'];
						$arrVendorAvailable[] = $vendorData->getAmosoftVendorCode();
						$isDefaultVendor = true;
					}else{
						$vendorCode = $vendorData->getAmosoftVendorCode();
						$inventoryStock = $vendorData->getStock();
						$vendorCost = $vendorData->getCost();
						$vendorShippingCost = $vendorData->getShippingCost();
						//echo $vendorShippingCost; exit;
						$vendorSku = $vendorData->getAmosoftVendorSku();
						$productSku = $vendorData->getProductSku();
						$arrVendorAvailable[] = $vendorData->getAmosoftVendorCode();
						$isDefaultVendor = false;
						break;
					}
				}
				$arrVendorAvailable[] = $vendorData->getAmosoftVendorCode();
			}else {	
				$arrFirstVendor = $collectionVendor->getFirstItem()->getData();
				$vendorCode = $arrFirstVendor['amosoft_vendor_code'];
				$inventoryStock = $arrFirstVendor['stock'];
				$vendorShippingCost = $arrFirstVendor['shipping_cost'];
				$vendorCost = $arrFirstVendor['cost'];
				$vendorSku = $arrFirstVendor['amosoft_vendor_sku'];
				$productSku = $arrFirstVendor['product_sku'];
				if($inventoryStock < $qtyInvoiced)
					$isDefaultVendor = true;
				else 
					$isDefaultVendor = false;
				
				$arrVendorAvailable[] = $arrFirstVendor['amosoft_vendor_code'];
			}
			return $vendorShippingCost;			
		}
	}

	public function setCustomShipping($_shipping_cost){
		$this->_shipping_cost = $_shipping_cost;
		//return $this->_shipping_cost;
    }

    public function getCustomShipping(){
		return $this->_shipping_cost;
    }
}
