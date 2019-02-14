<?php

class Mageapps_Icanpay_Model_Pay extends Mage_Payment_Model_Method_Ccsave
{

	protected $_isGateway = true;   //Is this payment method?
    protected $_canAuthorize = false;   //Can authorize online?
    protected $_canCapture = true;//Can capture funds online?
    protected $_canCancel = true;
    protected $_canVoid = true;   //Can void transactions online?
    protected $_canUseInternal = true;   //Can use this payment method in administration panel?
    protected $_canUseCheckout = true;   //Can show this payment method as an option on checkout payment page?
    protected $_canUseForMultishipping = true;   //Is this payment method suitable for multi-shipping checkout?
    protected $_canFetchTransactionInfo = false;
    protected $_canReviewPayment = true;
    protected $_isInitializeNeeded = false;
    protected $_canCancelOrder = true;
    protected $_canSaveCc = false;

    protected $_code = 'mageapps_icanpay';
    protected $_formBlockType = 'mageapps_icanpay/form_cc';
    protected $_infoBlockType = 'mageapps_icanpay/info_cc';


    public function assignData($data)
    {
       
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $this->getInfoInstance()->clearInstance();
        $info = $this->getInfoInstance()->cleanModelCache();

        


        $info->setAdditionalData(null);
        $info->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear());

        return $this;
    }


    public function validate()
    {

        
        $addData = $this->getInfoInstance()->getAdditionalData();
        if (!empty($addData)) {
            return true;
        }
        /*
       * calling parent validate function
       */
        parent::validate();

        $info = $this->getInfoInstance();
        $errorMsg = false;
        $availableTypes = explode(',', $this->getConfigData('cctypes'));

        $ccNumber = $info->getCcNumber();

        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);

        $ccType = '';

        if (in_array($info->getCcType(), $availableTypes)) {
            if ($this->validateCcNum($ccNumber)
                // Other credit card type number validation
                || ($this->OtherCcType($info->getCcType()) && $this->validateCcNumOther($ccNumber))
            ) {
                $ccType = 'OT';
                $discoverNetworkRegexp = '/^(30[0-5]\d{13}|3095\d{12}|35(2[8-9]\d{12}|[3-8]\d{13})|36\d{12}'
                    . '|3[8-9]\d{14}|6011(0\d{11}|[2-4]\d{11}|74\d{10}|7[7-9]\d{10}|8[6-9]\d{10}|9\d{11})'
                    . '|62(2(12[6-9]\d{10}|1[3-9]\d{11}|[2-8]\d{12}|9[0-1]\d{11}|92[0-5]\d{10})|[4-6]\d{13}'
                    . '|8[2-8]\d{12})|6(4[4-9]\d{13}|5\d{14}))$/';
                $ccTypeRegExpList = array(

                    // Solo only
                    'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/',
                    // Visa
                    'VI' => '/^4[0-9]{12}([0-9]{3})?$/',
                    // Master Card
                    'MC' => '/^(5[1-5][0-9]{14}|2(22[1-9][0-9]{12}|2[3-9][0-9]{13}|[3-6][0-9]{14}|7[0-1][0-9]{13}|720[0-9]{12}))$/',
                    // American Express
                    'AE' => '/^3[47][0-9]{13}$/',
                    // Discover Network
                    'DI' => $discoverNetworkRegexp,
                    // Dinners Club (Belongs to Discover Network)
                    'DICL' => $discoverNetworkRegexp,
                    // JCB (Belongs to Discover Network)
                    'JCB' => $discoverNetworkRegexp,

                    // Maestro & Switch
                    'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)'
                        . '|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)'
                        . '|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))'
                        . '|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))'
                        . '|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/'
                );

                $specifiedCCType = $info->getCcType();
                if (array_key_exists($specifiedCCType, $ccTypeRegExpList)) {
                    $ccTypeRegExp = $ccTypeRegExpList[$specifiedCCType];
                    if (!preg_match($ccTypeRegExp, $ccNumber)) {
                        $errorMsg = Mage::helper('payment')->__('Credit card number mismatch with credit card type.');
                    }
                }
            } else {
                $errorMsg = Mage::helper('payment')->__('Invalid Credit Card Number');
            }
        } else {
            $errorMsg = Mage::helper('payment')->__('Credit card type is not allowed for this payment method.');
        }

        //validate credit card verification number
        if ($errorMsg === false && $this->hasVerification()) {
            $verifcationRegEx = $this->getVerificationRegEx();
            $regExp = isset($verifcationRegEx[$info->getCcType()]) ? $verifcationRegEx[$info->getCcType()] : '';
            if (!$info->getCcCid() || !$regExp || !preg_match($regExp, $info->getCcCid())) {
                $errorMsg = Mage::helper('payment')->__('Please enter a valid credit card verification number.');
            }
        }

        if ($ccType != 'SS' && !$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            $errorMsg = Mage::helper('payment')->__('Incorrect credit card expiration date.');
        }

        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }

        //This must be after all validation conditions
        if ($this->getIsCentinelValidationEnabled()) {
            $this->getCentinelValidator()->validate($this->getCentinelValidationData());
        }

        return $this;
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        


        $order = $payment->getOrder();
        $result = $this->callApi($payment,$amount,'authorize');


        if ($result->status == 1) 
        {
            // Payment has been made successfully
            // assign response variables to local variables for further use $transactionid = $data->transactionid;
            $status = $result->status;
            $errorcode = $result->errorcode;
            $errorMsg = $result->errormessage;
            $amount = $result->amount;
            $currency = $result->currency;
            $orderid = $result->orderid;
            $descriptor = $result->descriptor;  
            $transactionid = $result->transactionid;

            $payment->setTransactionId($transactionid);
            $payment->setIsTransactionClosed(0);
            $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,array(
                'amount'        =>$amount,
                'currency'      =>$currency,
                'orderid'       => $orderid,
                'descriptor'    => $descriptor,
                'transactionid' => $transactionid
            ));
        }
        else
        {
            $errorCode = $result->errorcode;
            $errorMsg = $result->errormessage;
            $status = $result->status;
            Mage::throwException($errorMsg);
            

        }

        if($errorMsg){
            Mage::throwException($errorMsg);
            
        }
 
        return $this;
    }
 
	//right now this function has sample code only, you need put code here as per your api.
	private function callApi(Varien_Object $payment, $amount,$type)
    {

        $order = $payment->getOrder();
        $info = $this->getInfoInstance();

        $cvc_code = $info->getCcCid();
        $exp_month = $info->getCcExpMonth();
        $exp_year = $info->getCcExpYear();
        $credit_card_number =  $info->getCcNumber();
        
        

        $country3Code = Mage::getModel('directory/country')
            ->getCollection()
            ->addFieldToFilter('iso2_code', $order->getShippingAddress()->getCountry())
            ->getFirstItem()
            ->getIso3Code();



        $country = $country3Code;

        
        $orderid = $order->getData('increment_id');

        $customer_id = $order->getCustomerId();
        $customer = Mage::getModel('customer/customer')->load($customer_id);

        $apiData = Mage::helper('mageapps_icanpay')->getGateWayCredentials();
        $sec_key = $apiData['sec_key'];
        $params = array(
            'authenticate_id'   =>$apiData['authenticate_id'],
            'authenticate_pw'   =>$apiData['authenticate_pw'], 
            'orderid'           => $orderid, 
            'transaction_type'  =>'a',
            'amount'            => $amount,
            'currency'          =>'USD', 
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
            'phone'             =>$order->getShippingAddress()->getTelephone()
            );
            
            $api_type = 'API';
        
            $pay = Mage::getModel('mageapps_icanpay/api',array(
                'sec_key'   => $sec_key,
                'params'    => $params,
                'api_type'  => $api_type
            ));
            
            $response = $pay->payment();
            $data = json_decode($response);

            return $data;
       
        
        
	}
	    /** For capture **/
	    public function capture(Varien_Object $payment, $amount)
	    {
            

            
            $order = $payment->getOrder();
            $result = $this->callApi($payment,$amount,'authorize');

	        if ($result->status == 1) 
            {
                // Payment has been made successfully
                // assign response variables to local variables for further use $transactionid = $data->transactionid;
                $status = $result->status;
                $errorcode = $result->errorcode;
                $errorMsg = $result->errormessage;
                $amount = $result->amount;
                $currency = $result->currency;
                $orderid = $result->orderid;
                $descriptor = $result->descriptor;  
                $transactionid = $result->transactionid;

                $payment->setTransactionId($transactionid);
                $payment->setIsTransactionClosed(1);
                $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,array(
                    'amount'=>$amount,
                    'currency'=>$currency,
                    'orderid' => $orderid,
                    'descriptor' => $descriptor,
                    'transactionid' => $transactionid
                )); //use this in case you want to add some extra information



                $iCanPayModel = Mage::getModel('mageapps_icanpay/icanpay');
                $iCanPayModel->setOrderId($order->getData('increment_id'));
                $iCanPayModel->setAmount($amount);
                $iCanPayModel->setTransactionId($transactionid);
                $iCanPayModel->setOrderCustomerId($order->getCustomerId());
                $iCanPayModel->setApiType('API');
                $iCanPayModel->save();
            }
            else
            {
                $errorCode = $result->errorcode;
                $errorMsg = $result->errormessage;
                $status = $result->status;
                
                Mage::throwException($errorMsg);
            }

            if($errorMsg){
                 
                Mage::throwException($errorMsg);
            }
     
            return $this;
	    }
        

        public function getConfigPaymentAction() {
            return 'authorize_capture';
        } 
	    /*public function refund(Varien_Object $payment, $amount)
        {
            $order = $payment->getOrder();
            $result = $this->callApi($payment,$amount,'refund');
            if($result === false) {
                $errorCode = 'Invalid Data';
                $errorMsg = $this->_getHelper()->__('Error Processing the request');
                Mage::throwException($errorMsg);
            }
            return $this;
     
        }
        */
        
}