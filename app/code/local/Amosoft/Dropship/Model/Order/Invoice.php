<?php
class Amosoft_Dropship_Model_Order_Invoice extends Mage_Sales_Model_Order_Invoice
{

	public function __construct(){
		parent::__construct();
	}

	/**
     * Invoice totals collecting
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function collectTotals()
    { 	
    	$order = $this->getOrder();
	
        foreach($this->getConfig()->getTotalModels() as $model) {
	        if($order['shipping_method'] == "suppliershippingcostperproduct_suppliershippingcostperproduct"){
	        	if($model->getData('code') != 'shipping'){
	        		//Mage::log(Mage::helper('dropship')->getCustomShipping(), null, "ship.log", 1);
	        		$model->collect($this);
	        	}
	        }
	        else{
	        	$model->collect($this);
	        }
        }
        return $this;
    }
}