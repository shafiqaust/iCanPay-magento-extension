<?php
include_once('Mage/Adminhtml/controllers/Sales/Order/CreateController.php');
class Mageapps_Icanpay3dsv_Order_CreateController extends Mage_Adminhtml_Sales_Order_CreateController
{



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

            

            /*Payment Gateway Calculation*/
            
                $order = $order->getPayment()->getOrder();
                $amount = $order->getPayment()->getBaseAmountOrdered();
                $this->callApi($order,$amount,'authorize');
            
            

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
    }
}