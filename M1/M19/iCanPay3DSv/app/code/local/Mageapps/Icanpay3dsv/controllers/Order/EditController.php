<?php

include_once('Mage/Adminhtml/controllers/Sales/Order/EditController.php');
class Mageapps_Icanpay3dsv_Order_EditController extends Mage_Adminhtml_Sales_Order_EditController
{ 
    /**
     * Saving quote and create order
     */
    public function callApi($order, $amount,$type)
    {


        

        $encryptedInformation = $order->getPayment()->getAdditionalData();
        $cardDcryption = Mage::helper('mageapps_icanpay3dsv')->mc_decrypt($encryptedInformation);

        $cvc_code = $cardDcryption['cc_id'];
        $credit_card_number = $cardDcryption['cc_number'];
        $exp_month = $order->getPayment()->getCcExpMonth();
        $exp_year = $order->getPayment()->getCcExpYear();

        $country3Code = Mage::getModel('directory/country')
            ->getCollection()
            ->addFieldToFilter('iso2_code', $order->getShippingAddress()->getCountry())
            ->getFirstItem()
            ->getIso3Code();
        $country = $country3Code;
        $orderid = $order->getData('increment_id');

        $customer_id = $order->getCustomerId();
        $customer = Mage::getModel('customer/customer')->load($customer_id);

        $apiData = Mage::helper('mageapps_icanpay3dsv')->getGateWayCredentials();
        $sec_key = $apiData['sec_key'];
        $params = array(
            'authenticate_id'   =>$apiData['authenticate_id'],
            'authenticate_pw'   =>$apiData['authenticate_pw'], 
            'orderid'           =>$orderid, 
            'transaction_type'  =>'a',
            'amount'            =>$order->getPayment()->getBaseAmountOrdered(),
            'currency'          =>'USD', 
            'dob'               =>'1982-01-22',
            'ccn'               =>$credit_card_number, 
            'exp_month'         =>$exp_month,
            'exp_year'          =>substr($exp_year,2),
            'cvc_code'          =>$cvc_code,
            'firstname'         =>$customer->getFirstname(),
            'lastname'          =>$customer->getLastname(), 
            'email'             =>$customer->getEmail(),
            'street'            =>$order->getShippingAddress()->getStreet()[0], 
            'city'              =>$order->getShippingAddress()->getCity(),
            'zip'               =>$order->getShippingAddress()->getPostcode(),
            'state'             =>substr($order->getShippingAddress()->getRegionCode(),0,2),
            'country'           =>$country,
            'phone'             =>$order->getShippingAddress()->getTelephone(),
            'signature'         =>'iCanPay',
            'fail_url'          =>Mage::helper('adminhtml')->getUrl('*/sales_order_create/cancelorder',array("_secure"=>true)),
            'notify_url'        =>Mage::helper('adminhtml')->getUrl('*/sales_order/index',array("_secure"=>true)),
            'success_url'       =>Mage::helper('adminhtml')->getUrl('*/sales_order_create/response',array("_secure"=>true)),
         );

        
        $api_type = '3DSV';
        $pay = Mage::getModel('mageapps_icanpay3dsv/api',array(
            'sec_key'   => $sec_key,
            'params'    => $params,
            'api_type'  => $api_type
        ));
        
        $response = $pay->payment();
        $data = json_decode($response);

        if ($data->status == 1) 
        {
            $status = $data->status;
            $redirect_url = $data->redirect_url; 
            
            header('Location: '.(string)$redirect_url);
            exit;
        }
        else
        {
            Mage::throwException('Unable to Save order');
            $this->_redirect('*/sales_order/index');
        }

        return $this;
        
        
    }
    public function responseAction() 
    {

        $responseOid = Mage::app()->getRequest()->getParam('oid');
        if($responseOid) 
        {   
            
            $validated = true;
            $orderId = $responseOid; // Generally sent by gateway
            
            if($validated) 
            {
                // Payment was successful, so update the order's state, send order email and move to the success page
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderId);


                $iCanPayModel = Mage::getModel('mageapps_icanpay3dsv/icanpay3dsv');
                $iCanPayModel->setApiType('3DSV');
                $iCanPayModel->setOrderId($orderId);
                $iCanPayModel->setAmount(round($order->getData('base_grand_total'), 2 ));
                $iCanPayModel->setTransactionId(null);
                $iCanPayModel->setOrderCustomerId($order->getCustomerId());
                $iCanPayModel->save();

                $order->getPayment()->setTransactionId(null);
                $order->getPayment()->setIsTransactionClosed(1);
                $order->getPayment()->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,array(
                    'amount'=>round( $order->getData('base_grand_total'), 2 ),
                    'currency'=>'USD',
                    'orderid' => $order->getData('increment_id'),
                    'descriptor' => 'descriptor',
                    'transactionid' => NULL
                )); 


                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');
                
                $order->sendNewOrderEmail();
                $order->setEmailSent(true);
                
                $order->save();
            
                $this->_getSession()->clear();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
                if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
                    $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
                } else {
                    $this->_redirect('*/sales_order/index');
                }
            }
            else {
                // There is a problem in the response we got
                $this->cancelAction();
                
            }
        }
        else
            $this->_redirect('*/*/');
    }
    
    // The cancel action is triggered when an order is to be cancelled
    public function cancelorderAction() 
    {
        $responseOid = Mage::app()->getRequest()->getParam('oid');
        
            $order = Mage::getModel('sales/order')->loadByIncrementId($responseOid);
            if($order->getId()) {
                // Flag the order as 'cancelled' and save it
                $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
            }
        
        $this->_getSession()->addError('Order saving error duto to gateway has declined the payment.');
        $this->_redirect('*/*/');
    }

    public function saveAction()
    {

        
    	
        /*if(isset($_POST['edit_order_number']) && $_POST['edit_order_number'] != "")
		{
			
		    	$postOrder = $this->getRequest()->getPost('order');
				
				$quote = $this->_getSession()->getQuote();
				$oldOrder = $this->_getSession()->getOrder();
				$preTotal = $oldOrder->getGrandTotal();
				$oldOrderId = $oldOrder->getId();
				$order = Mage::getModel('ordereditor/order')->load($oldOrderId);
				$orderAllItems = $order->getAllItems();
				foreach($orderAllItems as $delteItem)
				{
					$delteItem->delete();
				}

				$convertor = Mage::getModel('sales/convert_quote');
				 $price = 0 ;
				foreach ($quote->getAllItems() as $item) 
				{
				
					$options = array();
					$productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
			
					if ($productOptions) {
						$productOptions['info_buyRequest']['options'] = $this->prepareEditedOptionsForRequest($item);
						$options = $productOptions;						
					}
					$addOptions = $item->getOptionByCode('additional_options');
					if ($addOptions) {
						$options['additional_options'] = unserialize($addOptions->getValue());
					}
					$item->setProductOrderOptions($options);
					$orderItem = $convertor->itemToOrderItem($item);
					if ($item->getParentItem()) {
						$orderItem->setParentItem($oldOrder->getItemByQuoteItemId($item->getParentItem()->getId()));
					}
					$oldOrder->addItem($orderItem);
					$orderItem->save();
				}
				$address = $quote->getShippingAddress();
				$taxAmount = $address->getTaxAmount();
				$rates = $address->getShippingRatesCollection();
				foreach ($rates as $_rate)
				{
					
					$orderData = $this->getRequest()->getPost('order');
					
						if($orderData['shipping_method'] == $_rate->getCode())
						{
							$oldOrder->setShippingMethod($orderData['shipping_method']);
							$shippingDescription = $_rate->getCarrierTitle().' - '.$_rate->getMethodTitle();
							$oldOrder->setShippingDescription($shippingDescription);
							$oldOrder->setShippingAmount($_rate->getPrice());
							$oldOrder->setShippingInclTax($_rate->getPrice());
							$oldOrder->setBaseShippingInclTax($_rate->getPrice());	
							$shippingPrice = $_rate->getPrice();
						}
				}

				$oldOrder->setData('coupon_code',$quote->getData('coupon_code'));
				$oldOrder->setData('store_id',$quote->getData('store_id'));
				$oldOrder->setData('subtotal',$quote->getData('subtotal'));
				
				$subTotal = $quote->getData('subtotal');
				$baseSubTotal = $quote->getData('base_subtotal_with_discount');
				$discountAmount = $subTotal - $baseSubTotal;
				$discountAmount = '-'.$discountAmount;
				
				$oldOrder->setData('discount_amount',$discountAmount);
				$oldOrder->setData('base_discount_amount',$quote->getData('subtotal'));
				$oldOrder->setData('discount_description',$quote->getData('coupon_code'));
				
				
				$oldOrder->setData('base_subtotal',$quote->getData('base_subtotal'));
				$oldOrder->setData('grand_total',$quote->getData('grand_total'));
				$oldOrder->setData('base_grand_total',$quote->getData('base_grand_total'));
				$oldOrder->setData('store_id',$quote->getData('store_id'));
				$oldOrder->setData('base_tax_amount',$taxAmount);
				$oldOrder->setData('tax_amount',$taxAmount);
				$quote->getPayment()->getMethod();
				$sameAsBilling = $quote->getShippingAddress()->getSameAsBilling();
				$postBillingAddress = $postOrder['billing_address'];
				$bb = $oldOrder->getBillingAddress();
				
				$bb->setData('prefix',$postBillingAddress['prefix']);
				$bb->setData('firstname',$postBillingAddress['firstname']);
				$bb->setData('middlename',$postBillingAddress['middlename']);
				$bb->setData('lastname',$postBillingAddress['lastname']);
				$bb->setData('suffix',$postBillingAddress['suffix']);
				$bb->setData('company',$postBillingAddress['company']);
				
				$bb->setData('street',implode(" ",$postBillingAddress['street']));
				
				$bb->setData('city',$postBillingAddress['city']);
				$bb->setData('country_id',$postBillingAddress['country_id']);
				
				if(isset($postBillingAddress['region']) && $postBillingAddress['region'] != "")
				{
					$bb->setData('region',$postBillingAddress['region']);
				}
				if(isset($postBillingAddress['region_id']) && $postBillingAddress['region_id'] != "")
				{
					$bb->setData('region_id',$postBillingAddress['region_id']);			
				}

				$bb->setData('postcode',$postBillingAddress['postcode']);
				$bb->setData('telephone',$postBillingAddress['telephone']);
				$bb->setData('fax',$postBillingAddress['fax']);
				if(isset($postBillingAddress['vat_id']) && $postBillingAddress['vat_id'] != "")
				{
					$bb->setData('vat_id',$postBillingAddress['vat_id']);
				}
								
				$oldOrder->setBillingAddress($bb);
				$sameShip = $oldOrder->getShippingAddress();

				if($sameAsBilling == 1 && isset($sameShip) && is_array($sameShip))
				{
				
					$sameShip = $oldOrder->getShippingAddress();
				
					$sameShip->setData('prefix',$postBillingAddress['prefix']);
					$sameShip->setData('firstname',$postBillingAddress['firstname']);
					$sameShip->setData('middlename',$postBillingAddress['middlename']);
					$sameShip->setData('lastname',$postBillingAddress['lastname']);
					$sameShip->setData('suffix',$postBillingAddress['suffix']);
					$sameShip->setData('company',$postBillingAddress['company']);
					
					$sameShip->setData('street',implode(" ",$postBillingAddress['street']));
					
					$sameShip->setData('city',$postBillingAddress['city']);
					$sameShip->setData('country_id',$postBillingAddress['country_id']);
					if(isset($postBillingAddress['region']) && $postBillingAddress['region'] != "")
					{
						$sameShip->setData('region',$postBillingAddress['region']);
					}
					if(isset($postBillingAddress['region_id']) && $postBillingAddress['region_id'] != "")
					{
						$sameShip->setData('region_id',$postBillingAddress['region_id']);
					}
					
					$sameShip->setData('postcode',$postBillingAddress['postcode']);
					$sameShip->setData('telephone',$postBillingAddress['telephone']);
					$sameShip->setData('fax',$postBillingAddress['fax']);
					if(isset($postBillingAddress['vat_id']) && $postBillingAddress['vat_id'] != "")
					{
						$sameShip->setData('vat_id',$postBillingAddress['vat_id']);
					}	
				
					$oldOrder->setShippingAddress($sameShip);
				}
				if(isset($postOrder['shipping_address']) && is_array($postOrder['shipping_address']))
				{
					$shipAdd = $oldOrder->getShippingAddress();
					$postShippingAddress = $postOrder['shipping_address'];
					
					$shipAdd->setData('prefix',$postShippingAddress['prefix']);
					$shipAdd->setData('firstname',$postShippingAddress['firstname']);
					$shipAdd->setData('middlename',$postShippingAddress['middlename']);
					$shipAdd->setData('lastname',$postShippingAddress['lastname']);
					$shipAdd->setData('suffix',$postShippingAddress['suffix']);
					$shipAdd->setData('company',$postShippingAddress['company']);
					
					$shipAdd->setData('street',implode(" ",$postShippingAddress['street']));
					
					$shipAdd->setData('city',$postShippingAddress['city']);
					$shipAdd->setData('country_id',$postShippingAddress['country_id']);
					if(isset($postShippingAddress['region']) && $postShippingAddress['region'] != "")
					{
						$shipAdd->setData('region',$postShippingAddress['region']);
					}
					if(isset($postShippingAddress['region_id']) && $postShippingAddress['region_id'] != "")
					{
						$shipAdd->setData('region_id',$postShippingAddress['region_id']);
					}
					
					$shipAdd->setData('postcode',$postShippingAddress['postcode']);
					$shipAdd->setData('telephone',$postShippingAddress['telephone']);
					$shipAdd->setData('fax',$postShippingAddress['fax']);
					if(isset($postShippingAddress['vat_id']) && $postShippingAddress['vat_id'] != "")
					{
						$shipAdd->setData('vat_id',$postShippingAddress['vat_id']);
					}
								
					$oldOrder->setShippingAddress($shipAdd);
				}
			
				$comment = $postOrder['comment'];
				if(isset($comment) && is_array($comment))
				{
					$customer_note = $comment['customer_note'];
					if(isset($customer_note) && $customer_note != "")
					{
						$oldOrder->setCustomerNote($customer_note);
						$oldOrder->addStatusToHistory($oldOrder->getStatus(),$customer_note, false);
					}
				}
			
				$account = $postOrder['account'];
				if(isset($account) && is_array($account))
				{
						$email = $account['email'];
						if(isset($email) && $email != "")
						{
							$oldOrder->setCustomerEmail($email);
						}
						
						
						if(isset($account['group_id']) && $account['group_id'] != "")
						{
							$group_id = $account['group_id'];
							$oldOrder->setCustomerGroupId($group_id);
						}
						
						
				}
				if($postPaymentMethod = $this->getRequest()->getPost('payment'))
				{
					
					

					$payment = $oldOrder->getPayment();
					$payment->setMethod($postPaymentMethod['method']);
					$payment->save();
		            $baseGrandTotal = $oldOrder->getBaseGrandTotal();
		            $oldOrder->setBaseGrandTotal($baseGrandTotal);
		            $grandTotal = $oldOrder->getGrandTotal();
		            $oldOrder->setGrandTotal($grandTotal);
				}
				$oldOrder->save();

				$_3DSV = Mage::helper('mageapps_icanpay')->is_3DSV();
		        if($_3DSV)
		        {
		            $oldOrder = $oldOrder->getPayment()->getOrder();
		            $amount = $oldOrder->getPayment()->getBaseAmountOrdered();
		            $this->callApi($oldOrder,$amount,'authorize');
		        }
				
					
				


		}
		else
		{*/
					try {
			            $this->_processActionData('save');
			            $paymentData = $this->getRequest()->getPost('payment');
			            $postOrder = $this->getRequest()->getPost('order');
			            

			            if ($paymentData) {
			                $paymentData['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_INTERNAL
			                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
			                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
			                    | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
			                    | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
			                $this->_getOrderCreateModel()->setPaymentData($paymentData);
			                $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
			            }

			            $order = $this->_getOrderCreateModel()
			                ->setIsValidate(true)
			                ->importPostData($this->getRequest()->getPost('order'))
			                ->createOrder();

			            
			            //$order->save();


			            /*Payment Gateway Calculation*/

			            $_3DSV = Mage::helper('mageapps_icanpay3dsv')->is_3DSV();
			            if($_3DSV)
			            {
			                $order = $order->getPayment()->getOrder();
			                $amount = $order->getPayment()->getBaseAmountOrdered();
			                $this->callApi($order,$amount,'authorize');
			            }
		            /*End Payment Gateway Calculation*/


			            $this->_getSession()->clear();
			            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
			            if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
			                $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
			            } else {
			                $this->_redirect('*/sales_order/index');
			            }
			        } catch (Mage_Payment_Model_Info_Exception $e) {
			            $this->_getOrderCreateModel()->saveQuote();
			            $message = $e->getMessage();
			            if( !empty($message) ) {
			                $this->_getSession()->addError($message);
			            }
			            $this->_redirect('*/*/');
			        } catch (Mage_Core_Exception $e){
			            $message = $e->getMessage();
			            if( !empty($message) ) {
			                $this->_getSession()->addError($message);
			            }
			            $this->_redirect('*/*/');
			        }
			        catch (Exception $e){
			            $this->_getSession()->addException($e, $this->__('Order saving error: %s', $e->getMessage()));
			            $this->_redirect('*/*/');
			        }
		/*}*/



        
        
    }
    /**
     * Prepare options array for info buy request
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    protected function prepareEditedOptionsForRequest($item)
    {
        $newInfoOptions = array();
        if ($optionIds = $item->getOptionByCode('option_ids')) {
            foreach (explode(',', $optionIds->getValue()) as $optionId) {
                $option = $item->getProduct()->getOptionById($optionId);
                $optionValue = $item->getOptionByCode('option_'.$optionId)->getValue();

                $group = Mage::getSingleton('catalog/product_option')->groupFactory($option->getType())
                    ->setOption($option)
                    ->setQuoteItem($item);

                $newInfoOptions[$optionId] = $group->prepareOptionValueForRequest($optionValue);
            }
        }
        return $newInfoOptions;
    }
}
