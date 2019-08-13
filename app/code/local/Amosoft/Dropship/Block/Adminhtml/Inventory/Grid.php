<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Block_Adminhtml_Inventory_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('amosoftgrid');
      $this->setDefaultSort('updated_at');
      $this->setDefaultDir('DESC');
	  $this->setUseAjax(true);
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $entityTypeId = Mage::getModel('eav/config')->getEntityType('catalog_product')->getEntityTypeId(); 
  	  $prodNameAttrId = Mage::getModel('eav/entity_attribute')->loadByCode($entityTypeId, 'name')->getAttributeId();
      $collection = Mage::getModel('dropship/inventory')->getCollection();
      $collection->getSelect()->join(array('amosoftRanking'=>Mage::getSingleton('core/resource')->getTableName('dropship/ranking')),'amosoftRanking.amosoft_vendor_code = main_table.amosoft_vendor_code', array('amosoft_vendor_name'));
      $collection->getSelect()->joinLeft(array('prod' => Mage::getSingleton('core/resource')->getTableName('catalog/product')),'prod.sku = main_table.product_sku',array('magento_pro_id'=>'entity_id'));
      $collection->getSelect()->joinLeft(array('cpev' => Mage::getSingleton('core/resource')->getTableName('catalog/product').'_varchar'),'cpev.entity_id=prod.entity_id AND cpev.attribute_id='.$prodNameAttrId.'',array('product_name' => 'value'));
      $collection->getSelect()->where('prod.entity_id IS NOT NULL');
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _getStore()
  {
  	$storeId = (int) $this->getRequest()->getParam('store', 0);
  	return Mage::app()->getStore($storeId);
  }
  
  protected function _prepareColumns()
  {
      $this->addColumn('amosoft_vendor_name', array(
          'header'    => Mage::helper('dropship')->__('Vendor'),
          'align'     =>'right',
          'width'     => '50px',
      	'filter_index' => 	'amosoftRanking.amosoft_vendor_name',
          'index'     => 'amosoft_vendor_name',
      ));

	  $this->addColumn('stock', array(
          'header'    => Mage::helper('dropship')->__('Vendor Inventory'),
          'index'     => 'stock',
           'type' =>    'number'
      ));
	  $store = $this->_getStore();
      	  $this->addColumn('cost', array(
          'header'    => Mage::helper('dropship')->__('Cost'),
          'index'     => 'cost',
          'type' => 'price',
      	  'currency_code' => $store->getBaseCurrency()->getCode(),
      ));

        $store = $this->_getStore();
          $this->addColumn('shipping_cost', array(
          'header'    => Mage::helper('dropship')->__('Shipping Cost'),
          'index'     => 'shipping_cost',
          'type' => 'price',
          'currency_code' => $store->getBaseCurrency()->getCode(),
      ));
 
      	  $this->addColumn('product_name', array(
      	  		'header'    => Mage::helper('dropship')->__('Product Name'),
      	  		'align'     =>'left',
      	  		'width'     => '80px',
      	  		'index'     => 'product_name',
      	  		'filter_index'=>'cpev.value', 
      	  ));
	 $this->addColumn('product_sku', array(
          'header'    => Mage::helper('dropship')->__('Product Sku'),
          'align'     =>'left',
          'width'     => '80px',
          'index'     => 'product_sku',
	 	   'renderer' => 'Amosoft_Dropship_Block_Adminhtml_Widget_Grid_Column_Skuaction'
      ));

	 $this->addColumn('amosoft_vendor_sku', array(
	 		'header'    => Mage::helper('dropship')->__('Vendor Sku'),
	 		'align'     =>'left',
	 		'width'     => '80px',
	 		'index'     => 'amosoft_vendor_sku',
	 		'renderer' => 'Amosoft_Dropship_Block_Adminhtml_Widget_Grid_Column_Skuaction'
	 ));
	  $this->addColumn('updated_at', array(
          'header'    => Mage::helper('dropship')->__('Last Sync'),
          'index'     => 'updated_at',
          'width'     => '80px',
          'default'   => '--',    
          'type'     => 'datetime',
	  	  'filter_index'=> 'main_table.updated_at'	
      ));
	  
	  // below code added for Jira ticket 734
	  $this->addColumn('action',
	  		array(
	  				'header'    =>  Mage::helper('dropship')->__('Action'),
	  				'width'     => '100',
	  				'type'      => 'action',
	  				'getter'    => 'getMagentoProId',
	  				'actions'   => array(
	  						array(
	  								'caption'   => Mage::helper('dropship')->__('Edit'),
	  								'url'       => array('base'=> 'adminhtml/catalog_product/edit/back/edit/tab/product_info_tabs_vendor_tab'),
	  								'field'     => 'id'
	  						)
	  				),
	  				'filter'    => false,
	  				'sortable'  => false,
	  				'index'     => 'stores',
	  				'is_system' => true,
	  		));
	  $this->addExportType('*/*/exportCsv', Mage::helper('dropship')->__('CSV'));
		$this->addExportType('*/*/exportXml', Mage::helper('dropship')->__('XML'));
	  
      return parent::_prepareColumns();
	}

	public function getRowUrl($row)
	{
      //return $this->getUrl('*/*/edit', array('vendor_id' => $row->getVendorId()));
	}
	public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

}