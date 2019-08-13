<?php
/**
 * @category   Amosoft
 * @package    Amosoft_Dropship
 */
class Amosoft_Dropship_Model_Order_Dropship_Api extends Mage_Api_Model_Resource_Abstract
{

	public function getAmosoftInvoices($ids) {
 		
 		$allInvoices['flag'] = 'false';
 		$i = 0;
 		if(!empty($ids)){
	 		foreach($ids as $key => $increment_id){
	 			$invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($increment_id);
				$order = $invoice->getOrder();

				$OrderIncrementid = $order->getIncrementId();
				$order_created_date = $order->getCreatedAt();
				if ($order->hasInvoices()) {
			        $invoices = $invoice->getData();
			        $allInvoices['flag'] = 'true';
			        $allInvoices['status'] = '1';
			        $allInvoices['invoices'][] = $invoices;
			        $allInvoices['invoices'][$i]['OrderIncrementid'] = $OrderIncrementid;
			    	$allInvoices['invoices'][$i]['order_created_at'] = $order_created_date;
			    	$i++;
				}
				else{
					$allInvoices['status'] = '0';
					$allInvoices['message'] = "No new invoice found";
				}
			}
		}
		else{
			$allInvoices['status'] = '0';
			$allInvoices['message'] = "No new invoice found";
		}
		return $allInvoices;
  	}

  	/* Load invoices using order*/
 	public function getAmosoftInvoices2($ids) {
 		
 		$allInvoices['flag'] = 'false';
 		$i = 0;
 		foreach($ids as $key => $increment_id){

			$order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);
			$OrderIncrementid = $order->getIncrementId();
			$order_created_date = $order->getCreatedAt();
			if ($order->hasgetAmosoftInvoices2Invoices()) {
			    foreach ($order->getInvoiceCollection() as $inv) {
			        $invoices = $inv->getData();
			        $allInvoices['flag'] = 'true';
			        $allInvoices['status'] = '1';
			        $allInvoices['invoices'][] = $invoices;
			        $allInvoices['invoices'][$i]['OrderIncrementid'] = $OrderIncrementid;
			    	$allInvoices['invoices'][$i]['order_created_at'] = $order_created_date;
			    	$i++;
			    }
			}
			else{
				$allInvoices['status'] = '0';
				$allInvoices['message'] = "No new invoice found";
			}
		}
		return $allInvoices;

  	}

  	public function getAmosoftShipments($ids) {

  		$allShipments['flag'] = 'false';
  		$i = 0;
  		if(!empty($ids)){
	  		foreach($ids as $key => $increment_id){
	  			$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($increment_id);
	  			$order = $shipment->getOrder();

				$OrderIncrementid = $order->getIncrementId();
				$order_created_date = $order->getCreatedAt();
				$shipmentCollection = $order->getShipmentsCollection();
				if($shipmentCollection){
					$shipments = $shipment->getData();
					$allShipments['flag'] = 'true';
					$allShipments['status'] = '1';
					$allShipments['shipments'][] = $shipments;
					$allShipments['shipments'][$i]['OrderIncrementid'] = $OrderIncrementid;
					$allShipments['shipments'][$i]['order_created_at'] = $order_created_date;
					$i++;
				}
				else{
					$allShipments['status'] = '0';
					$allShipments['message'] = "No new shipment found";
				}
			}
		}
		else{
			$allShipments['status'] = '0';
			$allShipments['message'] = "No new shipment found";
		}
		return $allShipments;
  	}

  	/* Load invoices using order*/
  	public function getAmosoftShipments2($ids) {

  		$allShipments['flag'] = 'false';
  		$i = 0;
  		foreach($ids as $key => $increment_id){

			$order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);
			$OrderIncrementid = $order->getIncrementId();
			$order_created_date = $order->getCreatedAt();

			$shipmentCollection = $order->getShipmentsCollection();
			if($shipmentCollection){
				foreach($shipmentCollection as $shipment){
					$shipments = $shipment->getData();
					$allShipments['flag'] = 'true';
					$allShipments['status'] = '1';
					$allShipments['shipments'][] = $shipments;
					$allShipments['shipments'][$i]['OrderIncrementid'] = $OrderIncrementid;
					$allShipments['shipments'][$i]['order_created_at'] = $order_created_date;
					$i++;
				}
			}
			else{
				$allShipments['status'] = '0';
				$allShipments['message'] = "No new shipment found";
			}
		}
		return $allShipments;
  	}

  	public function createAmosoftShipment($orderId, $comment, $importData) {

		$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        $orderShip = $order->prepareShipment(); // can take sku => qty array
        $orderShip->register();
        $orderShip->sendEmail();

        $tracker = Mage::getModel( 'sales/order_shipment_track' );
        $tracker->setShipment( $orderShip );
        $tracker->setData( 'title', $importData['title'] );
        $tracker->setData( 'number', $importData['number'] );
        $tracker->setData( 'carrier_code', $importData['carrier_code'] );
        $tracker->setData( 'order_id', $orderId );

        $orderShip->addTrack($tracker);
        $orderShip->save();

        $order->setData('state', "complete");
        $order->setStatus("complete");
        $history = $order->addStatusHistoryComment($comment, false);
        $history->setIsCustomerNotified(false);
        $order->save();
        return "complete";
	}

	/**
     * Collect invoice tax amount
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return Mage_Sales_Model_Order_Invoice_Total_Tax
     */
    public function addShippingTaxCharges($invoice_id, $shipping_cost)
    {
	    $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoice_id);
	    $totalTax       = 0;
        $baseTotalTax   = 0;
        $totalHiddenTax      = 0;
        $baseTotalHiddenTax  = 0;

        $order = $invoice->getOrder();

        /** @var $item Mage_Sales_Model_Order_Invoice_Item */
        foreach ($invoice->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            $orderItemQty = $orderItem->getQtyOrdered();

            if (($orderItem->getTaxAmount() || $orderItem->getHiddenTaxAmount()) && $orderItemQty) {
                if ($item->getOrderItem()->isDummy()) {
                    continue;
                }

                /**
                 * Resolve rounding problems
                 */
                $tax            = $orderItem->getTaxAmount() - $orderItem->getTaxInvoiced();
                $baseTax        = $orderItem->getBaseTaxAmount() - $orderItem->getBaseTaxInvoiced();
                $hiddenTax      = $orderItem->getHiddenTaxAmount() - $orderItem->getHiddenTaxInvoiced();
                $baseHiddenTax  = $orderItem->getBaseHiddenTaxAmount() - $orderItem->getBaseHiddenTaxInvoiced();
                if (!$item->isLast()) {
                    $availableQty  = $orderItemQty - $orderItem->getQtyInvoiced();
                    $tax           = $invoice->roundPrice($tax / $availableQty * $item->getQty());
                    $baseTax       = $invoice->roundPrice($baseTax / $availableQty * $item->getQty(), 'base');
                    $hiddenTax     = $invoice->roundPrice($hiddenTax / $availableQty * $item->getQty());
                    $baseHiddenTax = $invoice->roundPrice($baseHiddenTax / $availableQty * $item->getQty(), 'base');
                }

                $item->setTaxAmount($tax);
                $item->setBaseTaxAmount($baseTax);
                $item->setHiddenTaxAmount($hiddenTax);
                $item->setBaseHiddenTaxAmount($baseHiddenTax);

                $totalTax += $tax;
                $baseTotalTax += $baseTax;
                $totalHiddenTax += $hiddenTax;
                $baseTotalHiddenTax += $baseHiddenTax;
            }
        }

        $totalTax           += $order->getShippingTaxAmount();
        $baseTotalTax       += $order->getBaseShippingTaxAmount();
        $totalHiddenTax     += $order->getShippingHiddenTaxAmount();
        $baseTotalHiddenTax += $order->getBaseShippingHiddenTaxAmount();

        $invoice->setShippingTaxAmount($order->getShippingTaxAmount());
        $invoice->setBaseShippingTaxAmount($order->getBaseShippingTaxAmount());
        $invoice->setShippingHiddenTaxAmount($order->getShippingHiddenTaxAmount());
        $invoice->setBaseShippingHiddenTaxAmount($order->getBaseShippingHiddenTaxAmount());
        
        $allowedTax = $order->getTaxAmount() - $order->getTaxInvoiced();
        $allowedBaseTax = $order->getBaseTaxAmount() - $order->getBaseTaxInvoiced();;
        $allowedHiddenTax     = $order->getHiddenTaxAmount() + $order->getShippingHiddenTaxAmount()
            - $order->getHiddenTaxInvoiced() - $order->getShippingHiddenTaxInvoiced();
        $allowedBaseHiddenTax = $order->getBaseHiddenTaxAmount() + $order->getBaseShippingHiddenTaxAmount()
            - $order->getBaseHiddenTaxInvoiced() - $order->getBaseShippingHiddenTaxInvoiced();

        if ($invoice->isLast()) {
            $totalTax           = $allowedTax;
            $baseTotalTax       = $allowedBaseTax;
            $totalHiddenTax     = $allowedHiddenTax;
            $baseTotalHiddenTax = $allowedBaseHiddenTax;
        } else {
            $totalTax           = min($allowedTax, $totalTax);
            $baseTotalTax       = min($allowedBaseTax, $baseTotalTax);
            $totalHiddenTax     = min($allowedHiddenTax, $totalHiddenTax);
            $baseTotalHiddenTax = min($allowedBaseHiddenTax, $baseTotalHiddenTax);
        }

        $invoice->setTaxAmount($totalTax);
        $invoice->setBaseTaxAmount($baseTotalTax);
        $invoice->setHiddenTaxAmount($totalHiddenTax);
        $invoice->setBaseHiddenTaxAmount($baseTotalHiddenTax);
        $invoice->setShippingAmount($shipping_cost);
        $invoice->setBaseShippingAmount($shipping_cost);
        $invoice->setGrandTotal($invoice->getGrandTotal() + $shipping_cost + $totalTax + $totalHiddenTax);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $shipping_cost + $baseTotalTax + $baseTotalHiddenTax);
        //$invoice->save();
        return 'shipping_cost_added';   
    }

    /**
     * Add custom supplier shipping charges to each invoice
     *
     * @param invoice id, shipping cost
     * @return response
     */
    public function addShippingCharges($invoice_id, $shipping_cost)
    {
    	$invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoice_id);
        $order = $invoice->getOrder();

        $invoice->setShippingAmount(0);
        $invoice->setBaseShippingAmount(0);

        $orderShippingAmount        = $shipping_cost;
        $baseOrderShippingAmount    = $shipping_cost;
        $shippingInclTax            = $shipping_cost;
        $baseShippingInclTax        = $shipping_cost;

        if ($orderShippingAmount) {
            $invoice->setShippingAmount($orderShippingAmount);
            $invoice->setBaseShippingAmount($baseOrderShippingAmount);
            $invoice->setShippingInclTax($shippingInclTax);
            $invoice->setBaseShippingInclTax($baseShippingInclTax);
            
            $grandTotal = $invoice->getGrandTotal();
            $baseGrandTotal = $invoice->getBaseGrandTotal();
            
            /*else{
                $grandTotal = $invoice->getGrandTotal() - $invoice->getOrder()->getShippingAmount();
                $baseGrandTotal = $invoice->getBaseGrandTotal() - $invoice->getOrder()->getShippingAmount();
            }*/

            $invoice->setGrandTotal($grandTotal+$orderShippingAmount);
            $invoice->setBaseGrandTotal($baseGrandTotal+$baseOrderShippingAmount);

            $order->setTotalPaid($order->getTotalPaid()+$orderShippingAmount);  //update total order due

            //$order->setBaseTotalPaid($order->getBaseTotalPaid()+$baseGrandTotal);
            //Mage::helper('dropship')->setCustomShipping($shipping_cost);
            //$invoice->setCustomShipping($shipping_cost);

            $invoice->save();
            $order->save();
        }
        return 'shipping_cost_added';
    }

    public function getAmosoftSupplierInventory($supplier_code) {
        
        $dataCollection = Mage::getModel('dropship/inventory');
        $collection = null;
        $allInventory['flag'] = 'false';
        $collection = $dataCollection->getCollection()->addFieldToFilter('amosoft_vendor_code',$supplier_code);       
        if($collection->getSize() == 0){
            $errorMsg = 'Can not import supplier inventory';
            $allInventory['status'] = '1';
            //$allInventory['message'] = $errorMsg;
            $allInventory['data'] = $collection;
        }
        else{
            $allInventory['status'] = '1';
            $allInventory['flag'] = 'true';
            //$allInventory['message'] = "Inventory sync successful";
            $allInventory['data'] = $collection->getData();
        }
        return $allInventory;
    }

    public function updateAmosoftInventory($id, $request) {

        $model = Mage::getModel('dropship/inventory')->load($id)->addData($request);   
        try {  
            //save the data  
            $model->setId($id)->save();  
            //show success msg  
            return "record updated successfully.";  
        } catch (Exception $e){  
            return $e->getMessage();  
        }
    }

    public function addSupplierToProduct($request) {

        $model = Mage::getModel('dropship/inventory')->addData($request);   
        try {  
            //save the data  
            $model->save();  
            //show success msg  
            return "record updated successfully.";  
        } catch (Exception $e){  
            return $e->getMessage();  
        }
    }
    
}