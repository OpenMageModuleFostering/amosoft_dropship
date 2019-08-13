<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Model_Inventory extends Mage_Core_Model_Abstract
{
	protected $_productInventoryAdd = 0;
	protected $_productInventorySubtract = 0;
	protected $_productInventoryUpdate = 0;
	protected $_stockBeforeQtyDecimalCheck = ''; 
	protected $_iserror = true;
	protected $_errorMsg = '';
	protected function _construct()
	{
       $this->_init("dropship/inventory");
    }

    public function prepareInventoryTable($restReqest)
	{   	
    	$result = $this->prepareData($restReqest);
    	$this->updateProductStock();
    	return $result;    	
    }
    
    protected function updateProductStock()
	{    	
    	$dataCollection = Mage::getModel('dropship/inventory')->getCollection();
    	$stockData = array();
    	if($dataCollection->count() < 0){
    		return;
    	}
    	foreach($dataCollection as $stock){
    		if(array_key_exists($stock->getProductSku(),$stockData)){
    			 
    			$stockData[$stock->getProductSku()] = $stockData[$stock->getProductSku()] + $stock->getStock();
    		}else{
    			$stockData[$stock->getProductSku()] = $stock->getStock();
    		}
    			
    	}	
    	if(empty($stockData)){
    		return;
    	}
    	foreach ($stockData as $sku=>$qty) {    		
    		$this->saveStockData($sku, $qty);
    	}   	
    }
	
	protected function saveStockData($sku, $qty)
	{
		$productId = Mage::getModel('catalog/product')->getIdBySku($sku);
		if($productId){
    		$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
    		if (!$stockItem->getId()) {
    			$stockItem->setData('product_id', $productId);
    			$stockItem->setData('stock_id', 1);
    		}
    		if ($stockItem->getQty() != $qty) {
    			$stockItem->setData('qty', $qty);
    			$stockItem->setData('is_in_stock', $qty ? 1 : 0);
    			try {
    				$stockItem->save();
    			} catch (Exception $e) {
    				Mage::helper('dropship')->genrateLog(0,'mgento inventory update started','mgento inventory update ended','Section :Error In Setting/update magento inventory: '.$e->getMessage().' sku : '.$sku);
    				echo $e->getMessage();
    			}    			
    		}
    	}
	}

    
    protected function prepareData($restReqest)
	{
    	$result = array();
    	$buffer = trim(Mage::getStoreConfig('amosoft_sourcing/inventory/buffer'));
    	$vendorModel = Mage::getModel('dropship/ranking')->getCollection();
    	foreach ($restReqest as $value) {
    		foreach ($value as $key=>$val) {
				$result[$val['amosoft_vendor_code']] = $this->saveAmosoftInventory($val, $buffer);
			}
    	}		
		return 	$result;
    }
    
    protected function validateRowData($val)
	{
    	if(empty($val['product_sku']) && empty($val['amosoft_vendor_sku'])){
    		$this->_errorMsg = 'Please provide the Vendor SKU or Product SKU details';
    		$this->_iserror = false;
    	}elseif(empty($val['amosoft_vendor_code'])){
    		$this->_errorMsg = 'Error In Importing "amosoft_vendor_code" Cannot Be Empty';
    		$this->_iserror = false;
		}
    	return $this->_iserror;
		}

    protected function updateStock($val,$buffer)
        {
    	if(!empty($buffer))
    		($buffer > $val['stock']) ? $val['stock'] = 0 : $val['stock'] = $val['stock'] - $buffer;
    	return $val['stock'];
        } 
  
    
    protected function _prepareCollection($val)
        {
		$dataCollection = Mage::getModel('dropship/inventory');
    	$collection = null;
    	$amosoft_vendor_sku = $val['amosoft_vendor_sku'];
		if($amosoft_vendor_sku != ''){
			$collection = $dataCollection->getCollection()->addFieldToFilter('amosoft_vendor_code',$val['amosoft_vendor_code'])->addFieldToFilter('amosoft_vendor_sku',$amosoft_vendor_sku);          
    		if($collection->getSize() == 0 && $val['product_sku'] != ''){
                if(!(Mage::getModel('catalog/product')->getIdBySku($val['product_sku']))){
    				$this->_errorMsg = 'Can not import Vendor inventory for non-existing product';
    				return $collection;
                }
    			$collection = $dataCollection->getCollection()->addFieldToFilter('amosoft_vendor_code',$val['amosoft_vendor_code'])->addFieldToFilter('product_sku',$val['product_sku']);
			}      
		}
        else{
    		if($val['product_sku'] != ''){
			   if(!(Mage::getModel('catalog/product')->getIdBySku($val['product_sku']))){
    				$this->_errorMsg = 'Can not import Vendor inventory for non-existing product';
    				return $collection;
                }
				$collection = $dataCollection->getCollection()->addFieldToFilter('amosoft_vendor_code',$val['amosoft_vendor_code'])->addFieldToFilter('product_sku',$val['product_sku']);
            } else{
    			$this->_errorMsg = 'Can not import Vendor inventory for blank product sku';
    			return $collection;
    		}
        }        
    	return $collection;
			}
            
    protected function getLogMsg($ignoreData)
    {
			if(count($ignoreData)>0){
				if(in_array('stock', $ignoreData)) {
					$msg  = 'Cost Updated Successfully, Stock Ignored due to invalid data for';
					$type = 'Cost Updated , Qty Ignored';
				}
				if(in_array('cost', $ignoreData)){
						
					$msg  =  'Stock Updated Successfully, Cost Ignored due to invalid data for';
					$type = 'Qty Updated, Cost Ignored';
				}					
				if(count($ignoreData)==2){
					$msg = 'Cost & Stock Update Failed due to invalid data for';
					$type = 'ignore';
				}
			}else{
				$msg = 'Cost & Stock Updated Successfully for';
				$type = 'update';
            }            
    	return array('msg'=>$msg,'type'=>$type);
    }
    
	/** 
	 * This function is used to save Vendor inventory
	 * @param array $val
	 * @return array $msg
	 */
	protected function saveAmosoftInventory($rowVal, $buffer)
	{
		$val = array_map('trim',$rowVal);
		$originalStock = $val['stock'];
		$vendorObject =  Mage::getModel('dropship/ranking');
		$vendorCollection =  $vendorObject->load($val['amosoft_vendor_code'],'amosoft_vendor_code');
		if(!$this->validateRowData($val))
			{
				return $this->_errorMsg;
			}
		
		$ignoreData = array();
		(!is_numeric($val['cost']) || $val['cost'] < 0) ? $ignoreData[]= 'cost' : '';
		(!is_numeric($val['stock']) || $val['stock'] < 0) ? $ignoreData[]= 'stock' : '';
		(!is_numeric($val['stock']) || $val['stock'] < 0 || $val['stock'] == "") ? $stockFlag = false : $stockFlag = true; 
    	(!is_numeric($val['cost']) || $val['cost'] < 0 || $val['cost'] == "") ? $costFlag = false : $costFlag = true;
		$val['stock'] = $this->updateStock($val,$buffer);
		$collection = $this->_prepareCollection($val);
		if(is_null($collection)){
			return $this->_errorMsg;
		}
		$product_sku = $collection->getFirstItem()->getProductSku();
        /* LBN - 935 change */
        $val['stock'] = Mage::helper('dropship')->getIsQtyDecimal($product_sku,$val['stock']);
        if($collection->getSize() >= 2 && empty($val['product_sku']))
        {
            return  'Multiple records found. Please provide Product SKU';  
        }        
		if($collection->getSize() > 0){		
			$collection->getFirstItem ()->setUpdatedAt(now());
			($stockFlag == true) ? $collection->getFirstItem ()->setStock($val['stock']) : '';
			($costFlag == true) ? $collection->getFirstItem ()->setCost($val['cost']) : '';
			$arrayUpdate = array('updated_by'=>'system','product_sku'=>$product_sku,'amosoft_vendor_code'=>$val['amosoft_vendor_code'],'cost'=>$val['cost'],'stock'=>$originalStock);
			$logDetail = $this->getLogMsg($ignoreData);
			$this->_saveInventoryLog($logDetail['type'],$arrayUpdate);
			if(count($ignoreData)!=2){
				$collection->getFirstItem ()->save();
				$this->_updateVendorList($vendorCollection,$val,false);
			}
			return $logDetail['msg'];
		}else{
			return 'Vendor Sku "'.$val['amosoft_vendor_sku'].'" and Magento SKU "'.$val['product_sku'].'" combination does not exist for vendor ';
		}
	}
    
    public function saveTabVendorData($request)
	{   	
  	$update = isset($request['vendor_update']) ? $request['vendor_update'] : '';
    	$addNew = isset($request['vendor_new']) ? $request['vendor_new'] : '';
    	$sku = isset($request['sku']) ? $request['sku'] : '';
    	$result = true;   	
    	$error = $this->_validate($request);   	
    	if($error){    		
    		return $result = false;
    	} 	
    if(!empty($update)){	
    	foreach($update as $key => $data){
    		if($data['is_delete'] == 1){
    			$this->_deleteInvendorVendor($key);	
    		}else{
    			$result = $this->_updateInventoryVendor($key,$data);		
    		}
    	}
    	}
    	if(!empty($addNew)){
    		foreach($addNew as $key => $data){
				if($data['is_delete'] == 1){
					continue;
				}
				$this->_addNewInventoryVendor($data,$sku);    		
			}
    	}
    	$finalStock = 0;
    	$finalStock = $this->_productInventoryAdd + $this->_productInventoryUpdate;
    	return array('inventory'=>$finalStock,'result' => $result);
    }
    
    protected function _validate($request)
    {
    	$arrVendorCode = array();
    	$isUniqueCombination = false;
		$isEntrySame = false;	
    	$isError = true;
    	$errorArr = array();
    	if(!empty($request['vendor_new'])){
			foreach ($request['vendor_new'] as $key => $data){   			
				$arrVendorCode[] = $data['amosoft_vendor_code'];
				//patch for unique combination keys vendor_code and vendor_sku
				if($this->checkCodeSkuCombination($data['amosoft_vendor_code'],$data['amosoft_vendor_sku']) > 0 )
				{
					Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Duplicate entry found for %s , %s',$data['amosoft_vendor_code'],$data['amosoft_vendor_sku']));
					$errorArr[] = 'yes';
				}else{
					$errorArr[] = 'no';
				}
			}    	
			$isUnique = (array_unique($arrVendorCode) == $arrVendorCode);
			$isEntrySame = $isUnique ? false : true;
			if($isEntrySame)
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Duplicate Vendor Entry'));
			$isUniqueCombination = in_array('yes',$errorArr);
    	}	
    	//patch for unique combination keys vendor_code and vendor_sku
    	(!$isUniqueCombination && !$isEntrySame) ? $isError = false : $isError;   	
    	return $isError;
    }
     
    protected function checkCodeSkuCombination($vendorCode,$vendorSku)
    {
    	$collection = $this->getCollection()->addFieldTofilter('amosoft_vendor_code',$vendorCode)->addFieldTofilter('amosoft_vendor_sku',$vendorSku);
    	return $collection->count();
    }
    
    protected function _addNewInventoryVendor($request,$productSku){
    	$vendorCollection =  Mage::getModel('dropship/ranking')->load($request['amosoft_vendor_code'],'amosoft_vendor_code');
		$request['created_at'] = now();
    	$request['updated_at'] = now();
    	$request['product_sku'] = $productSku;
    	$request['updated_by'] = Mage::getSingleton('admin/session')->getUser()->getUsername();
    	$request['amosoft_vendor_name'] = $vendorCollection->getAmosoftVendorName();
		$request['amosoft_vendor_sku'] = trim($request['amosoft_vendor_sku']);
    	if(!empty($productSku)){                    
		   $qty = Mage::helper('dropship')->getIsQtyDecimal($productSku, $request['stock']);  
		}
		else{
		   $qty = $request['stock'];  
		}
    	$request['stock'] = $this->_updateBuffer($qty);
    	$this->setData($request);
    	try{
    		$this->save();
    		$this->_saveInventoryLog('add',$request);
    		$this->_productInventoryAdd = $this->_productInventoryAdd +  $request['stock'];
    		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('dropship')->__('%s Added Successfully ',$request['amosoft_vendor_name']));
    	}catch(Exception $e){
    		Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
    	}
    }
    
    protected function _updateInventoryVendor($id,$request){
        
        $model = $this->load($id);
        $vendorCode = $model->getAmosoftVendorCode();
        $vendorName = $model->getAmosoftVendorName();
        $DbValues['cost'] = $model->getCost();
        $DbValues['shipping_cost'] = $model->getShippingCost();
        $DbValues['stock'] = $model->getStock();
        $DbValues['amosoft_vendor_sku'] = $model->getAmosoftVendorSku();
        $productSku = $model->getProductSku();
        $request['amosoft_vendor_sku'] = trim($request['amosoft_vendor_sku']);
        $request['shipping_cost'] = trim($request['shipping_cost']);
        if(!empty($productSku)){                    
            $this->_stockBeforeQtyDecimalCheck =  $request['stock'];
           $request['stock'] = Mage::helper('dropship')->getIsQtyDecimal($productSku, $request['stock']);  
        }
        
        if($DbValues['amosoft_vendor_sku'] != $request['amosoft_vendor_sku']){
            //patch for unique combination keys vendor_code and vendor_sku
            if($this->checkCodeSkuCombination($vendorCode,$request['amosoft_vendor_sku']) > 0){
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Duplicate entry found for %s , %s',$vendorCode,$request['amosoft_vendor_sku']));
                return false;
            }
        }
        if($DbValues['cost'] != $request['cost'] || $DbValues['stock'] != $request['stock'] || $DbValues['amosoft_vendor_sku'] != $request['amosoft_vendor_sku'] || $DbValues['shipping_cost'] != $request['shipping_cost']){   
            if($DbValues['stock'] != $request['stock'])
            $request['stock'] = $this->_updateBuffer($request['stock'],$DbValues['stock']);         
            $request['updated_at'] = now();
            $model->addData($request);
            try{
                $model->save();         
                $this->_saveInventoryLog('update',array('updated_by'=>Mage::getSingleton('admin/session')->getUser()->getUsername(),'product_sku'=>$model->getProductSku(),'amosoft_vendor_code'=>$model->getAmosoftVendorCode(),'cost'=>$model->getCost(),'stock'=>$model->getStock()));
                $this->_productInventoryUpdate = $this->_productInventoryUpdate +  $request['stock'];
                
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('dropship')->__('%s Updated Successfully',$vendorName));
            }catch(Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }else{          
            $this->_productInventoryUpdate = $this->_productInventoryUpdate +  $request['stock'];
        }
        return true;
    }
    
    protected function _deleteInvendorVendor($vendorId){   	
    	$model = $this->load($vendorId);   	
    	$vendorCode = $model->getAmosoftVendorCode();
    	$vendorCollection =  Mage::getModel('dropship/ranking')->load($vendorCode,'amosoft_vendor_code');
    	$request = array('amosoft_vendor_name'=>$vendorCollection->getAmosoftVendorName(),'updated_by'=>Mage::getSingleton('admin/session')->getUser()->getUsername(),'product_sku'=>$model->getProductSku(),'amosoft_vendor_code'=>$model->getAmosoftVendorCode(),'cost'=>$model->getCost(),'stock'=>$model->getStock(),'updated_at' => now());
    	try{
			$model->delete();
			$this->_saveInventoryLog('delete',$request);
			$this->_productInventorySubtract = $this->_productInventorySubtract +  $request['stock'];
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('dropship')->__('%s Deleted Successfully ',$request['amosoft_vendor_name']));
    	}catch(Exception $e){
    		Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
    	}
    }
    
    public function _saveInventoryLog($type,$request){
    	$modelLog = Mage::getModel('dropship/inventorylog');
    	$request['activity'] = $type;
		if($type=='add')
		$request['created_at'] = now();
    	$request['updated_at'] = now();
		if(!isset($request['amosoft_vendor_name'])){
			$vendorRankModel = Mage::getModel('dropship/ranking')->load($request['amosoft_vendor_code'],'amosoft_vendor_code');
			$request['amosoft_vendor_name'] = $vendorRankModel->getAmosoftVendorName();
		}	
    	$modelLog->setData($request);
    	try{
    		$modelLog->save();   		
    	}catch(Exception $e){
    		Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
    	}
    }
    
    protected function _updateVendorList($object,$data = ''){
    	if(!empty($data)){
    		$object->setUpdatedAt(now());
    		$object->setAmosoftVendorType('enhanced');
    		if(!$object->getId()) $object->setIsDropship('no');
    		$object->setAmosoftVendorCode($data['amosoft_vendor_code']);
    		
    	}
		try{
    		$object->save();   		
    	}catch(Exception $e){
    		Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
    	}
    }
    
    protected function _updateBuffer($stock,$dbCost = null){
    	$buffer = Mage::getStoreConfig('amosoft_sourcing/inventory/buffer');
    	$finalStock = 0;
    	if(!empty($dbCost) && $this->_stockBeforeQtyDecimalCheck == $dbCost)
    	{
    		return $stock;
    	}
    	if(!empty($buffer)){
    		if($buffer > $stock){
    			$stock = 0;
    		}else{
    	
    			$finalStock = $stock - $buffer;
    		}
    	}else{
    		$finalStock = $stock;
    	}
    	return $finalStock;
    }
    /* method use to send email notification to amosoft 
     * that first vendor has been added to amosoft_vendor_inventory
     */
    protected function _afterSave()
    {
    	$colSize = $this->getCollection()->getSize();
    	$notifyVs = Mage::getStoreConfigFlag('amosoft/notification/vendor_setup');
    	if($colSize == 1 && !$notifyVs)
    	{
    		$this->sendVendorNotification();
    		Mage::getModel('dropship/amosoft')->saveNotificationValue(1,'amosoft/notification/vendor_setup');
    	}
    	parent::_afterSave();
    	return;
    }
    
   protected function sendVendorNotification(){
   	
   			try {
   				$fieldsetData['subject'] = 'DS360 Product Setup completed on Magento';
   				$postObject = new Varien_Object();
   				$postObject->setData($fieldsetData);
   				$templateId = 'amosoft_productsetup_notification';
   				$email = Mage::helper('dropship')->getConfigObject('apiconfig/email/toaddress');
   				$isMailSent = Mage::helper('dropship')->sendMail($postObject,$email,$templateId);
   				if (!$isMailSent) {
   					Mage::helper('dropship')->genrateLog(0,'Order notification started','Order notification ended','First product setup complete successfully but email sending failed');
   				}
   				return true;
   			} catch (Exception $e) {
   			return false;//$e->getMassage();
   		}
   	}
   	public function upDateVendorName($vendor){
   		if(empty($vendor['code'])  || empty($vendor['name']))
   		{
   			return;
   		}
   		$table =  Mage::getSingleton ( 'core/resource' )->getTableName ( 'dropship/inventory' );
   		$update = 'UPDATE '.$table.' SET amosoft_vendor_name = "'.$vendor['name'].'" WHERE amosoft_vendor_code = "'.$vendor['code'].'"';
   		$conObj = Mage::getSingleton ( 'core/resource' )->getConnection('core_write');
   		$conObj->beginTransaction();
   		$conObj->query($update);
   		try {
   			$conObj->commit ();
   		} catch ( Exception $e ) {
   			$conObj->rollBack ();
   			Mage::getSingleton ( 'adminhtml/session' )->addError($e->getMessage ());
   		}
   	}
}