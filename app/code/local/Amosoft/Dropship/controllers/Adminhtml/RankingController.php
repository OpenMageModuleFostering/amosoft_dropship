<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
class Amosoft_Dropship_Adminhtml_RankingController extends Mage_Adminhtml_Controller_Action {

	protected function _initAction() {
		$this->loadLayout ()->_setActiveMenu ( 'dropship/vendor_ranking' )->_addBreadcrumb ( Mage::helper ( 'adminhtml' )->__ ( 'Vendor Management' ), Mage::helper ( 'adminhtml' )->__ ( 'Vendor Management' ) );		
		return $this;
	}
	
	public function indexAction() {
		$layout = $this->_initAction()->_title($this->__('Vendor Management'));
		$layout->getLayout()->getBlock('head')->setCanLoadExtJs(false);
		$layout->renderLayout();
	}
	
	public function popupAction() {
		$this->loadLayout ();
		$this->renderLayout ();
	}
	
	/**
	 * Ranking grid
	 */
	public function gridAction() {
		$this->getResponse ()->setBody ( $this->getLayout ()->createBlock ( 'dropship/adminhtml_ranking_grid' )->toHtml () );
	}
		
	public function showhistoryAction() {
		$this->loadLayout ();
		$this->renderLayout ();
	}
	
	public function addNewVendorAction() {		
		$isSuccess = false;
		$data = $this->getRequest ()->getPost ();
		$arrVendor = array();
		$vendorRankCollection =  Mage::getModel ( 'dropship/ranking' );
		$genrateVendorCode = $vendorRankCollection->getCollection()->addFieldToFilter('amosoft_vendor_code',array('like'=>'%MagVendID%'));
		
		foreach($genrateVendorCode as $vendorCode)
		{
			if(preg_match('!\d+!',  $vendorCode->getAmosoftVendorCode(), $matches)){
				$arrVendor[] = (int) $matches[0];
			}
			
		}
		$suffix = ((int) max($arrVendor) + 1);
		$code = 'MagVendID'.$suffix;
		$vendorRankCollection->setAmosoftVendorCode($code);
		$vendorRankCollection->setRanking($data['rank']);
		$vendorRankCollection->setAmosoftVendorName($data['name']);
		$vendorRankCollection->setAmosoftVendorType('user');
		$vendorRankCollection->setCreatedAt(now());
		$vendorRankCollection->setUpdatedAt(now());
		try{
		$vendorRankCollection->save();
		$isSuccess = true;
		}catch ( Exception $e ) {
				Mage::getSingleton ( 'adminhtml/session' )->addError( $e->getMessage () );
				$isSuccess = false;
			}
		$result = array('success'=>$isSuccess,'message'=>$code);		
		$result = Mage::helper('core')->jsonEncode($result);
		Mage::app()->getResponse()->setBody($result);		
		return;
	}
	
	
	public function saverankingAction() {
		$vendorName = array();		
		$data = $this->getRequest ()->getPost ();
		
		$tableName = $data['partent_save_table_input'];
		$dropShip = json_decode((urldecode($data['dropship_data'])),true); 
		$nonDropShip = json_decode((urldecode($data['nondropship_data'])),true);
		$vendorName = json_decode((urldecode($data['vendorname_data'])),true);
		$vendorProductLink = json_decode((urldecode($data['vendorproductlink_data'])),true);
		$modelRanking = Mage::getModel ( 'dropship/rankinglog' )->load($tableName,'label');		
		if (!$tableName || $modelRanking->getId()) {
			Mage::getSingleton ( 'adminhtml/session' )->addError ( Mage::helper ( 'dropship' )->__ ( 'Ranking Table Name Is Empty Or Already Exists' ) );
			$this->_redirect ( '*/*/' );
			return;
		}
		
		foreach($dropShip as $key=>$val){
			if(!empty($dropShip)){
				$this->_saveVendorRanking($key, $val, true);
			}
		}
		foreach($nonDropShip as $k=>$v){
			if(!empty($nonDropShip)){
				$this->_saveVendorRanking($k, $v, false);
			}
		}
		foreach($vendorName as $key=>$val){
			if(!empty($vendorName)){
				$this->_updateVendorName($val);
			}
		}
		foreach ($vendorProductLink as $value) {
			$this->saveProductLinking($value);
		}
		$result = $this->_saveTableRanking(trim($tableName));
		
		Mage::getSingleton ( 'adminhtml/session' )->addSuccess ( Mage::helper ( 'dropship' )->__ ( 'Vendor ranking saved successfully' ) );
		$this->_redirect ( '*/*/' );
		return;
	}
	protected function _saveVendorRanking($key, $val, $rank = false) {
		try {
			$model = Mage::getModel ( 'dropship/ranking' )->load ( $val['code'], 'amosoft_vendor_code' );
			$model->setUpdatedAt(now());
			
			if($rank)
			$model->setIsDropship ('yes');
			else
			$model->setIsDropship ('no');
			
			$model->setRanking ( ($rank) ? $key+1:0 );
			$model->save ();			
		} catch ( Exception $e ) {
			Mage::getSingleton ( 'adminhtml/session' )->addError ( $e->getMessage () );
		}
	}
	
	protected function _updateVendorName($val) {
		try {
			$model = Mage::getModel ( 'dropship/ranking' )->load ( $val['code'], 'amosoft_vendor_code' );
			if($model->getAmosoftVendorCode())
			$model->setAmosoftVendorName ($val['name'])->save();
			Mage::getModel ( 'dropship/inventory' )->upDateVendorName($val);
		} catch ( Exception $e ) {
			Mage::getSingleton ( 'adminhtml/session' )->addError ( $e->getMessage () );
		}	
	}
	
	protected function _saveTableRanking($tableName) {	
		$serializedArray = array ();
		$model = Mage::getModel ( 'dropship/ranking' );
		$modelRanking = Mage::getModel ( 'dropship/rankinglog' );
		$collection = $model->getCollection ();
		$collection->getSelect()->order('ranking asc');
		if($collection->count() > 0){
			foreach ( $collection as $value ) {
				$serializedArray [] = array (
						$value->getAmosoftVendorName (),
						$value->getAmosoftVendorCode (),
						$this->getAttributeCode($value->getLinkingAttribute()),
						$value->getRanking (),
						$value->getIsDropship()
				);
			}
			$modelRanking->setRankingData ( serialize ( $serializedArray ) );
			$modelRanking->setLabel ( trim ( $tableName ) );
			$modelRanking->setCreatedAt(now());				
			try {
				$modelRanking->save ();				
			} catch ( Exception $e ) {
				Mage::getSingleton ( 'adminhtml/session' )->addError ( $e->getMessage () );
			}
		}
		return;
	}
	protected function getAttributeCode($code)
	{
		$helper = Mage::helper('dropship');
		switch ($code) {
			case $helper::AMOSOFT_PRODUCT_LINK_CODE_UPC:
				return $helper::AMOSOFT_PRODUCT_LINK_UPC;
			break;
			case $helper::AMOSOFT_PRODUCT_LINK_CODE_MNP:
				return $helper::AMOSOFT_PRODUCT_LINK_MNP;
				break;
			case $helper::AMOSOFT_PRODUCT_LINK_CODE_SKU:
				return $helper::AMOSOFT_PRODUCT_LINK_SKU;
				break;
			default:
				return $helper::AMOSOFT_PRODUCT_LINK_NONE;
		}
	}
	protected function saveProductLinking($data)
	{
		if(empty($data))
			return ;
		try {
			$model = Mage::getModel ( 'dropship/ranking' )->load ( $data['code'], 'amosoft_vendor_code' );
			$model->setUpdatedAt(now());
			$model->setLinkingAttribute($data['attr']);
			$model->save ();
		} catch ( Exception $e ) {
			Mage::getSingleton ( 'adminhtml/session' )->addError ( $e->getMessage () );
		}
	}
	
	/**
	 * Acl check for admin
	 *
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return (Mage::getSingleton('admin/session')->isAllowed('dropship/suppliers') || Mage::getSingleton('admin/session')->isAllowed('dropship/vendor_ranking'));
	}
}
