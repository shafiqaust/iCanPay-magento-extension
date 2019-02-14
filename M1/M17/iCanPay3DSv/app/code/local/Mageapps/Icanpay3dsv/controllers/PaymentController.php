<?php

class Mageapps_Icanpay3dsv_PaymentController extends Mage_Core_Controller_Front_Action 
{
	
	protected $_order = null;

	public function getOrder()
	{
	    if ($this->_order == null) {
	        $session = Mage::getSingleton('checkout/session');
	        $this->_order = Mage::getModel('sales/order');
	        $this->_order->loadByIncrementId($session->getLastRealOrderId());
	    }
	    return $this->_order;
	}
	

	public function redirectAction() 
	{
		
		
		$this->_order = $this->getOrder();

		$encryptedInformation = $this->_order->getPayment()->getAdditionalData();
		$cardDcryption = Mage::helper('mageapps_icanpay3dsv')->mc_decrypt($encryptedInformation);

		$cvc_code = $cardDcryption['cc_id'];
        $credit_card_number = $cardDcryption['cc_number'];
        $exp_month = $this->_order->getPayment()->getCcExpMonth();
        $exp_year = $this->_order->getPayment()->getCcExpYear();

		$country3Code = Mage::getModel('directory/country')
            ->getCollection()
            ->addFieldToFilter('iso2_code', $this->_order->getShippingAddress()->getCountry())
            ->getFirstItem()
            ->getIso3Code();
        $country = $country3Code;
        $orderid = $this->_order->getData('increment_id');

        $customer_id = $this->_order->getCustomerId();
        $customer = Mage::getModel('customer/customer')->load($customer_id);

        $apiData = Mage::helper('mageapps_icanpay3dsv')->getGateWayCredentials();
        $sec_key = $apiData['sec_key'];
        $params = array(
            'authenticate_id'	=>$apiData['authenticate_id'],
            'authenticate_pw'	=>$apiData['authenticate_pw'], 
            'orderid'			=>$orderid, 
            'transaction_type'	=>'a',
            'amount'			=>$this->_order->getPayment()->getBaseAmountOrdered(),
            'currency'			=>'USD', 
            'dob' 				=>'1982-01-22',
            'ccn'				=>$credit_card_number, 
            'exp_month'			=>$exp_month,
            'exp_year'			=>substr($exp_year,2),
            'cvc_code'			=>$cvc_code,
            'firstname'			=>$customer->getFirstname(),
            'lastname'			=>$customer->getLastname(), 
            'email'				=>$customer->getEmail(),
            'street'			=>$this->_order->getShippingAddress()->getStreet()[0], 
            'city'				=>$this->_order->getShippingAddress()->getCity(),
            'zip'				=>$this->_order->getShippingAddress()->getPostcode(),
            'state'				=>substr($this->_order->getShippingAddress()->getRegionCode(),0,2),
            'country'			=>$country,
            'phone'				=>$this->_order->getShippingAddress()->getTelephone(),
            'signature' 		=>'iCanPay',
            'fail_url' 			=>Mage::getUrl('icanpay3dsv/payment/cancel', array('_secure' => true)),
			'notify_url' 		=>Mage::getUrl('icanpay3dsv/payment/notify', array('_secure' => true)),
			'success_url' 		=>Mage::getUrl('icanpay3dsv/payment/response', array('_secure' => true)),
         );

            
        $api_type = '3DSV';
        try{
        	$pay = Mage::getModel('mageapps_icanpay3dsv/api',array(
	            'sec_key' 	=> $sec_key,
	            'params'	=> $params,
	            'api_type'	=> $api_type
	        ));
	        $response = $pay->payment();
	        $data = json_decode($response);

	        if ($data->status == 1) 
	        {
				$status = $data->status;
				$redirect_url = $data->redirect_url; 


				$this->_redirectUrl($redirect_url);
			}
			else
			{
				$status = $data->status; 
			}
        }catch(Exception $e){
        	
        	$this->_redirect('checkout/onepage/index', array('_secure'=>true));
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
	            $iCanPayModel->setAmount(round( $order->getData('base_grand_total'), 2 ));
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
			
				Mage::getSingleton('checkout/session')->unsQuoteId();
				
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=>true));
			}
			else {
				// There is a problem in the response we got
				$this->cancelAction();
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));
			}
		}
		else
			Mage_Core_Controller_Varien_Action::_redirect('');
	}
	
	// The cancel action is triggered when an order is to be cancelled
	public function cancelAction() 
	{
        if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
            if($order->getId()) {
				// Flag the order as 'cancelled' and save it
				$order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
			}
        }
        Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));
	}

	public function notifyAction()
	{
		$url = Mage::getUrl('checkout/onepage/success', array('_secure' => true));
		$this->_redirectUrl($url);
	}
}