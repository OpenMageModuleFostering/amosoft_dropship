<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
 
class Amosoft_Dropship_Adminhtml_SourcingController extends Mage_Adminhtml_Controller_Action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('dropship/order_sourcing')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Order Management'), Mage::helper('adminhtml')->__('Order Management'));
		
		return $this;
	}   
 
	public function indexAction() {
		$this->_initAction()->renderLayout();
	}
        
	/**
     * sourcing grid
     */
    
	public function sourcinggridAction() {
		$this->_initAction()->_title($this->__('Order Management'));
		$this->getLayout()->createBlock('dropship/adminhtml_sourcing_grid')->toHtml();
		//$this->loadLayout()->renderLayout();
		$this->renderLayout();
		
	}     
	public function editAction() {
		$id     = $this->getRequest()->getParam('amosoft_item_id');
		$model  = Mage::getModel('dropship/orderitems')->load($id);

		if ($model->getId()) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);                              
			}
            Mage::register('sourcing_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('dropship/order_sourcing');
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Order Sourcing'), Mage::helper('adminhtml')->__('Order Sourcing'));
			$this->_addContent($this->getLayout()->createBlock('dropship/adminhtml_sourcing_edit'))
				->_addLeft($this->getLayout()->createBlock('dropship/adminhtml_sourcing_edit_tabs'));
			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Sourcing does not exist'));
			$this->_redirect('*/*/sourcinggrid');
		}
	}      
	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {
			$vendorCode = $data['amosoft_vendor_code'];
			$sku = $data['sku'];
	  		$model = Mage::getModel('dropship/orderitems');	
			$model->load($this->getRequest()->getParam('amosoft_item_id'));
			$order = Mage::getModel('sales/order')->load($model->getItemOrderId());
			$orderStatus = $order->getStatus();				
			if($this->getRequest()->getParam('cancel') == 'item'){
				$itemStatusHistory = Mage::helper('dropship')->getSerialisedData($model->load($this->getRequest()->getParam('amosoft_item_id')), 'Cancelled', $orderStatus);	
				$model->setAmosoftItemStatus('Cancelled');
				$model->setUpdatedBy('User');
				$model->setItemStatusHistory($itemStatusHistory);
				try{
					$model->save();
					Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('dropship')->__('Item %s Cancelled Successfully ',$model->getSku()));
					
				}catch(Exception $e){
					Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				}
				$this->_redirect('*/*/sourcinggrid');
				return;	
			}
			if ($id = $this->getRequest()->getParam('amosoft_item_id')) {//the parameter name may be different
				$model->load($id);
			}
			
			if(!$data['amosoft_vendor_code']){
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Under Processing By Amosoft Can Not Update %s Sku',$model->getSku()));
				$this->_redirect('*/*/sourcinggrid');
				return;
			}
			$itemStatusHistory = Mage::helper('dropship')->getSerialisedData($model->load($this->getRequest()->getParam('amosoft_item_id')), 'Transmitting', $orderStatus);	
			$arrData = $model->updateSourcingByUser(array('amosoft_vendor_code'=>$vendorCode,'product_sku'=>$sku, 'qty'=>$this->getRequest()->getParam('qty'), 'item_status_history'=>$itemStatusHistory));
			$model->addData($arrData);			
			try {
				$model->save();
               	Mage::getSingleton('adminhtml/session')->setFormData(false);
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('dropship')->__('Sourcing Updated For %s',$model->getSku()));		

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('amosoft_item_id' => $model->getId()));
					return;
				}
				$this->_redirect('*/*/sourcinggrid');
				return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('amosoft_item_id' => $model->getId(), '_current'=>true)); 
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Unable to save sourcing'));
        $this->_redirect('*/*/sourcinggrid');
	}
	
	public function saveSoapApiAction()
	{
		$paramsArray = $this->getRequest()->getParams();
		if(!empty($paramsArray['user_id']) && !empty($paramsArray['api_key'])){
			Mage::getModel('api/user')->load($paramsArray['user_id'])->setApiKey($paramsArray['api_key'])->save();
			if(is_null(Mage::getSingleton('adminhtml/session')->getNotification()))
			Mage::getModel('dropship/amosoft')->send(null,array());
			$result['message'] = 'password save successfully';		
		}else{
			$result['message'] = 'can not save password';
		}	
			$result['success'] = 1;
		$result = Mage::helper('core')->jsonEncode($result);
		Mage::app()->getResponse()->setBody($result);
	}
        

	/**
	 * Export vendor in csv format
	 */
	public function exportCsvAction()
	{
		$fileName   = 'sourcing.csv';
		$content    = $this->getLayout()->createBlock('dropship/adminhtml_sourcing_grid')->getCsvFile();
		$this->_prepareDownloadResponse($fileName, $content);
	}
	
	/**
	 * Export vendor in Excel format
	 */
	public function exportXmlAction()
	{
		$fileName   = 'sourcing.xml';
		$content    = $this->getLayout()->createBlock('dropship/adminhtml_sourcing_grid')->getExcelFile($fileName);
		$this->_prepareDownloadResponse($fileName, $content);
	}
	
	public function viewOrderItemHistoryAction()
	{
		$this->_title($this->__('dropship'))->_title($this->__('View History'));
			
		$this->_initAction();
		$itemId = $this->getRequest()->getParam('amosoft_item_id');
		$orderItems = Mage::getModel( 'dropship/orderitems' )->load($itemId, 'item_id');
		
		$this->_title(sprintf("Item Sku %s", $orderItems->getSku()));
		
		$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

		$this->_addContent($this->getLayout()->createBlock('dropship/adminhtml_sourcing_history_view'))
			->_addLeft($this->getLayout()->createBlock('dropship/adminhtml_sourcing_history_view_tabs'));

		$this->renderLayout();
        
	}
	
	/**
	 * Acl check for admin
	 *
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('dropship/order_sourcing');
	}
	
	
}
