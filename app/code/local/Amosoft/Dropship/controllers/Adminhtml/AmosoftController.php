<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */
 
class Amosoft_Dropship_Adminhtml_AmosoftController extends Mage_Adminhtml_Controller_Action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('dropship/suppliers')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Vendor Manager'), Mage::helper('adminhtml')->__('Vendor Manager'));
		
		return $this;
	}   
 
	public function indexAction() {
		$this->_initAction()->renderLayout();
	}

	public function getapidetailsAction() {
		$this->loadLayout();
		$this->getLayout()->getBlock('amosoftnotification')->setConfigValue(array(
						'scope'         => 'default',
						'scope_id'    => '0',
						'path'       => 'amosoft_integration/integration/notificationstatus',
						'value'     => '0',
				));
		Mage::app()->getCacheInstance()->cleanType('config');		
		Mage::getSingleton('adminhtml/session')->setNotification(false);
		$this->_redirectReferer();
	}
	    
	public function sourcinggridAction() {
		
		$this->getLayout()->createBlock('dropship/adminhtml_sourcing_grid')->toHtml();
		$this->loadLayout()->renderLayout();
		
	}
	
    public function gridAction() {
        $this->getResponse()->setBody(
		$this->getLayout()->createBlock('dropship/adminhtml_amosoft_grid')->toHtml());
    }    
	public function editAction() {
		$id     = $this->getRequest()->getParam('vendor_id');
		$model  = Mage::getModel('dropship/supplier')->load($id);

		if ($model->getVendorId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
                                
			}
			Mage::register('amosoft_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('dropship/suppliers');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Vendor Manager'), Mage::helper('adminhtml')->__('Vendor Manager'));
			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('dropship/adminhtml_amosoft_edit'))
				->_addLeft($this->getLayout()->createBlock('dropship/adminhtml_amosoft_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Vendor does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function newAction()
	{
		$this->loadLayout();
		$this->_setActiveMenu('dropship/suppliers');
		$this->_addContent($this->getLayout()->createBlock('dropship/adminhtml_amosoft_edit'))
				->_addLeft($this->getLayout()->createBlock('dropship/adminhtml_amosoft_edit_tabs'));
		$this->renderLayout();

	}
        
    public function saveAction() 
	{
		if ($data = $this->getRequest()->getPost()) {		
	  		$model = Mage::getModel('dropship/supplier');		
			if ($id = $this->getRequest()->getParam('vendor_id')) {//the parameter name may be different
				$model->load($id);
			}
			$companyid = $this->getRequest()->getParam('company_id');
			$message = '';
			$result  = array();
			if(empty($companyid) || strcmp($model->getCompanyId(),$companyid) == 0 )
				$validate = 0;
			else{
				$result = $model->validateCompany($companyid,$data);
				$validate = $result['validate'];
				$data = $result['data'];
				if(!$validate){
					$model->load($data['id']);
				}			
			}   
			$message = ($result['message']) ? 'Vendor Recovered Successfully -'.$data['company_id'] : 'Vendor was successfully saved';
			$model->addData($data);			
			try {
				if(!empty($data['addnewoption'])){
					$model->setData('magento_vendor_code',strtolower($data['addnewoption']));
				}
				//validate compny id as unique
				if($validate == 1){
					Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Duplicate Company ID'));
					Mage::getSingleton('adminhtml/session')->setFormData($data);
				    $this->_redirect('*/*/edit', array('vendor_id' => $model->getVendorId()));
					return;
				}
				$model->save();
				
				if(!empty($data['addnewoption'])){
					Mage::getModel('dropship/amosoft')->createOptionValueOnSave($model->getMagentoVendorCode());
				}
				Mage::getSingleton('adminhtml/session')->setFormData(false);
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('dropship')->__($message));		

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('vendor_id' => $model->getVendorId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('vendor_id'=>$model->getVendorId(), '_current'=>true)); 
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Unable to find Vendor to save'));
        $this->_redirect('*/*/');
	}
        
	public function deleteAction()
	{
        if ($id = $this->getRequest()->getParam('vendor_id')) {
            try {
                $model = Mage::getModel('dropship/supplier');
                $model->load($id);
       			$model->setData('status','deleted');
                $model->save();
				$collection = Mage::getModel('dropship/ranking')->getCollection()->addFieldToFilter('is_dropship','yes');
				$collection->getSelect()->order('ranking asc');
				$rank = Mage::getModel('dropship/ranking')->load($id)->getRanking(); 
				foreach($collection as $value){
					Mage::getModel('dropship/ranking')->rearrangeRank($value, $rank);
				}
                Mage::getModel('dropship/ranking')->load($id,'amosoft_vendor_id')->setRanking('')->setIsDropship('no')->setIsActive('no')->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('dropship')->__('The Vendor has been deleted.'));
                $this->_redirect('*/*/');
                return;

            } catch (Exception $e) {
                
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                
                $this->_redirect('*/*/edit', array('vendor_id' => $id));
                return;
            }
        }
        
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__('Unable to find a Vendor to delete.'));
	
        $this->_redirect('*/*/');
    }

 	
	/**
     * Export vendor in csv format
     */
    public function exportCsvAction()
    {
        $fileName   = 'supplier.csv';
        $content    = $this->getLayout()->createBlock('dropship/adminhtml_amosoft_grid')->getCsvFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export vendor in Excel format
     */
    public function exportXmlAction()
    {
        $fileName   = 'supplier.xml';
        $content    = $this->getLayout()->createBlock('dropship/adminhtml_amosoft_grid')->getExcelFile($fileName);
        $this->_prepareDownloadResponse($fileName, $content);
    }
        
	public function validateajaxrequestAction()
	{
		$paramsArray = $this->getRequest()->getParams();
		$validation = Mage::getModel('dropship/amosoft');
		$result = $validation->validation($paramsArray['groups']['integration']['fields']);
		$result = Mage::helper('core')->jsonEncode($result);
		Mage::app()->getResponse()->setBody($result);		
	}
	
	/**
	 * Change dropship order item status through Ajax request
	 */
	public function changeStatusAjaxAction()
	{
		$data = $this->getRequest()->getPost();
		//print_r($data); exit;
		if($data){
			if($data['amosoft_item_status']!=""){
				$order = Mage::getModel('sales/order')->load($data['order_id']);
				$orderStatus = $order->getStatus();
				$OrderItemInstance = Mage::getModel('dropship/orderitems')->getCollection()->addFieldToFilter('item_id', $data['amosoft_item_id']);
				try{
					if($OrderItemInstance->count() > 0){			
						foreach($OrderItemInstance as $item){
							$itemStatusHistory = Mage::helper('dropship')->getSerialisedData($item, $data['amosoft_item_status'], $orderStatus);
							$item->setAmosoftItemStatus($data['amosoft_item_status']);
							$item->setItemStatusHistory($itemStatusHistory);
							$item->setUpdatedBy('User');
							$item->setUpdatedAt(Mage::getModel('core/date')->gmtDate());
							$item->save();
							if($data['amosoft_item_status']==$item->getAmosoftItemStatus()){
								$data['msg'] = $item->getSku().' status successfully changed to '.$data['amosoft_item_status'];
								Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('dropship')->__($data['msg']));
							}else{
								$data['msg'] = $item->getSku().' status unable to change to '.$data['amosoft_item_status'];
								Mage::getSingleton('adminhtml/session')->addError(Mage::helper('dropship')->__($data['msg']));
							}
							
						}
					}			
					if($data['amosoft_item_status'] == 'Transmitting'){
						Mage::getModel('dropship/amosoft')->setupNotification();
					}			
					$result = Mage::helper('core')->jsonEncode($data);
					echo $result; exit;
					Mage::app()->getResponse()->setBody($result);
				}catch(Exception $e){			
					Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				}
			}
		}else{
			$data['msg'] = 'Unable to perform the required operation';
		}	
	}
	
	/**
	 * Acl check for admin
	 *
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('dropship/inventory');
	}
	
}
