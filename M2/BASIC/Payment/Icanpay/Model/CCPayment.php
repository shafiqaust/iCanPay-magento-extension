<?php
 
namespace Payment\Icanpay\Model;


use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;
//use Magento\Quote\Model\Quote\Payment;
use Payment\Icanpay\Model\Api;


class CCPayment extends \Magento\Payment\Model\Method\Cc
{
    

    const CODE = 'payment_icanpay';

    const REQUEST_METHOD_CC     = 'CREDIT';
    const REQUEST_METHOD_ECHECK = 'ACH';

    const REQUEST_TYPE_AUTH_CAPTURE = 'SALE';
    const REQUEST_TYPE_AUTH_ONLY    = 'AUTH';
    const REQUEST_TYPE_CAPTURE_ONLY = 'CAPTURE';
    const REQUEST_TYPE_CREDIT       = 'REFUND';
    const REQUEST_TYPE_VOID         = 'VOID';
    const REQUEST_TYPE_PRIOR_AUTH_CAPTURE = 'PRIOR_AUTH_CAPTURE';

    const ECHECK_ACCT_TYPE_CHECKING = 'CHECKING';
    const ECHECK_ACCT_TYPE_BUSINESS = 'BUSINESSCHECKING';
    const ECHECK_ACCT_TYPE_SAVINGS  = 'SAVINGS';

    const ECHECK_TRANS_TYPE_CCD = 'CCD';
    const ECHECK_TRANS_TYPE_PPD = 'PPD';
    const ECHECK_TRANS_TYPE_TEL = 'TEL';
    const ECHECK_TRANS_TYPE_WEB = 'WEB';

    const RESPONSE_DELIM_CHAR = ',';

    const RESPONSE_CODE_APPROVED = 'APPROVED';
    const RESPONSE_CODE_DECLINED = 'DECLINED';
    const RESPONSE_CODE_ERROR    = 'ERROR';
    const RESPONSE_CODE_MISSING  = 'MISSING';
    const RESPONSE_CODE_HELD     = 4;

	protected $responseHeaders;
	protected $tempVar;

    protected $_code  = 'payment_icanpay';
	
	protected static $_dupe = true;
	protected static $_underscoreCache = array();

    protected $_stripeApi = false;

    protected $_countryFactory;

    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = array('USD');

    /**
     * Availability options
     */
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc 		= false;

    protected $_allowCurrencyCode = array('USD');

    /**
     * Fields that should be replaced in debug with '***'
     *
     * @var array
     */
    //protected $_debugReplacePrivateDataKeys = array('');

    /**
     * @var \Magento\Authorizenet\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $checkoutCartHelper;

    /**
     * Request factory
     *
     * @var \Magento\Authorizenet\Model\RequestFactory
     */
    protected $requestFactory;

    /**
     * Response factory
     *
     * @var \Magento\Authorizenet\Model\ResponseFactory
     */
    protected $responseFactory;

    /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    protected $scopeConfig;


    protected $_invoiceService;


    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Checkout\Helper\Cart $checkoutCartHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Session\Generic $generic,
        \Payment\Icanpay\Model\Request\Factory $requestFactory,
        \Payment\Icanpay\Model\Response\Factory $responseFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\HTTP\ZendClientFactory $zendClientFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        //$this->dataHelper = $dataHelper;
        $this->checkoutCartHelper = $checkoutCartHelper;
        $this->checkoutSession = $checkoutSession;
        $this->generic = $generic;
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->zendClientFactory = $zendClientFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_invoiceService = $invoiceService;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );



        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
    }

/**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        /*if ($quote && (
            $quote->getBaseGrandTotal() < $this->_minAmount
            || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }
        if (!$this->getConfigData('account_id')) {
            return false;
        }*/
        
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $active = $this->scopeConfig->getValue('payment/payment_icanpay/active',$storeScope);

        if (!$active) {
            return false;
        }



        return parent::isAvailable($quote);
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->getAcceptedCurrencyCodes())) {
            return false;
        }
        return true;
    }

    /**
     * Return array of currency codes supplied by Payment Gateway
     *
     * @return array
     */
    public function getAcceptedCurrencyCodes()
    {
        if (!$this->hasData('_accepted_currency')) {
            $acceptedCurrencyCodes = $this->_allowCurrencyCode;
            $acceptedCurrencyCodes[] = $this->getConfigData('currency');
            $this->setData('_accepted_currency', $acceptedCurrencyCodes);
        }
        return $this->_getData('_accepted_currency');
    }

    /**
     * Send authorize request to gateway
    */
	
    

    /**
     * Send capture request to gateway
     */
    public function callApi(\Magento\Payment\Model\InfoInterface $payment,$amount)
    {
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $countryFactory = $objectManager->get('Magento\Directory\Model\CountryFactory');
        $country = $countryFactory->create()->loadByCode($billing->getCountryId())->getData();

        //$apiKeys = $this->iCanPayHelper->getApiCredentials();

        //$sec_key = $apiKeys['sec_key'];

        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $sec_key = $this->scopeConfig->getValue('payment/payment_icanpay/sec_key',$storeScope);
        $authenticate_id = $this->scopeConfig->getValue('payment/payment_icanpay/authenticate_id',$storeScope);
        $authenticate_pw = $this->scopeConfig->getValue('payment/payment_icanpay/authenticate_pw',$storeScope);




        
        $CcExpYear = str_split($payment->getCcExpYear(), 2);
        //$sec_key = '56e8441e880267.81606026';

        $params = array(
            'authenticate_id'   =>$authenticate_id,
            'authenticate_pw'   =>$authenticate_pw,
            'orderid'           => $order->getIncrementId(), 
            'transaction_type'  =>'a',
            'amount'            => $amount,
            'currency'          =>'USD', 
            'ccn'               =>$payment->getCcNumber(), 
            'exp_month'         =>$payment->getCcExpMonth(),
            'exp_year'          =>$CcExpYear[1],
            'cvc_code'          =>$payment->getCcCid(),
            'firstname'         =>$billing->getFirstname(),
            'lastname'          =>$billing->getLastname(), 
            'email'             =>$order->getCustomerEmail(),
            'street'            =>$billing->getStreetLine(1), 
            'city'              =>$billing->getCity(),
            'zip'               =>$billing->getPostcode(),
            'state'             =>substr($billing->getRegionCode(),0,2),
            'country'           =>$country['iso3_code'],
            'phone'             =>$billing->getTelephone(),
            'random_ip'         =>$_SERVER['REMOTE_ADDR'],
            'signature'         =>'iCanPay',
            );
            
        $api_type = 'API';



        $pay = new Api($sec_key, $params, $api_type);
        $response = $pay->payment();
        $data = json_decode($response);

        return $data;
    }
    /*public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
       
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }
        return $this;


    }*/

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $payment->setAmount($amount);
        $order = $payment->getOrder();
    
        if ($payment->getCcTransId()) {
            $payment->setTransactionType(self::REQUEST_TYPE_CAPTURE_ONLY);
        } else {
            $payment->setTransactionType(self::REQUEST_TYPE_AUTH_CAPTURE);
        }

       
        $result = $this->callApi($payment, $amount);
        

        if ($result->status == 1) 
        {
            $payment->setCcType($payment->getCcType());
            $payment->setPaymentType(self::REQUEST_METHOD_CC);
            $payment->setCcLast4(substr($payment->getCcNumber(), -4));
            $payment->setCcTransId($result->transactionid);
            $payment->setLastTransId($result->transactionid);
            $payment->setTransactionId($result->transactionid);
            $payment->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$payment->getAdditionalInformation()]);


            $rawDetails = array(
                    'amount'=>$amount,
                    'currency'=>'USD',
                    'orderid' => $order->getIncrementId(),
                    'descriptor' => $result->descriptor,
                    'transactionid' => $result->transactionid
                );
            
            $trans = $objectManager->get('Magento\Sales\Model\Order\Payment\Transaction\Builder');
            $transaction = $trans->setPayment($payment)->setOrder($order)->setTransactionId($result->transactionid)->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => $rawDetails]
            )->setFailSafe(true)->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            
            $payment->setParentTransactionId(null);
            $payment->setIsTransactionClosed(0);
            $order->save();
            $transaction->save();



            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            $invoice->register();


            $transactionInvoice = $objectManager->create('Magento\Framework\DB\Transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transactionInvoice->save();


            /*$objectManager->create('Magento\Sales\Model\Order\Email\Sender\InvoiceSender')->send($invoice);
            $order->addStatusHistoryComment(
                __('Notified customer about invoice #%1.', $invoice->getId())
            )->setIsCustomerNotified(true)->save();*/
            
        }
        else
        {
            
            $errorCode = $result->errorcode;
            $errorMsg = $result->errormessage;
            $status = $result->status;
            $order->cancel()->save();   

            throw new \Magento\Framework\Exception\LocalizedException(__('The transaction has been declined due to '.$errorMsg));
        }
        return $this;

       
        
        
    }
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	    $payment->setAmount($amount);
        $order = $payment->getOrder();
	
        if ($payment->getCcTransId()) {
            $payment->setTransactionType(self::REQUEST_TYPE_CAPTURE_ONLY);
        } else {
            $payment->setTransactionType(self::REQUEST_TYPE_AUTH_CAPTURE);
        }

       
        $result = $this->callApi($payment, $amount);
        

        if ($result->status == 1) 
        {
            $payment->setCcType($payment->getCcType());
            $payment->setPaymentType(self::REQUEST_METHOD_CC);
            $payment->setCcLast4(substr($payment->getCcNumber(), -4));
            $payment->setCcTransId($result->transactionid);
            $payment->setLastTransId($result->transactionid);
            $payment->setTransactionId($result->transactionid);
            $payment->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$payment->getAdditionalInformation()]);


            $rawDetails = array(
                    'amount'=>$amount,
                    'currency'=>'USD',
                    'orderid' => $order->getIncrementId(),
                    'descriptor' => $result->descriptor,
                    'transactionid' => $result->transactionid
                );
            
            $trans = $objectManager->get('Magento\Sales\Model\Order\Payment\Transaction\Builder');
            $transaction = $trans->setPayment($payment)->setOrder($order)->setTransactionId($result->transactionid)->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => $rawDetails]
            )->setFailSafe(true)->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            
            $payment->setParentTransactionId(null);
            $payment->setIsTransactionClosed(0);
            $order->save();
            $transaction->save();

            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            $invoice->register();


            $transactionInvoice = $objectManager->create('Magento\Framework\DB\Transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transactionInvoice->save();


            /*$objectManager->create('Magento\Sales\Model\Order\Email\Sender\InvoiceSender')->send($invoice);
            $order->addStatusHistoryComment(
                __('Notified customer about invoice #%1.', $invoice->getId())
            )->setIsCustomerNotified(true)->save();*/

            
        }
        else
        {
            
            $errorCode = $result->errorcode;
            $errorMsg = $result->errormessage;
            $status = $result->status;
            $order->cancel()->save();   

            throw new \Magento\Framework\Exception\LocalizedException(__('The transaction has been declined due to '.$errorMsg));
        }
        
        return $this;
        
    }
	

    /**
     * Void the payment through gateway
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        /*if ($payment->getParentTransactionId()) {
			$order = $payment->getOrder();
            $payment->setTransactionType(self::REQUEST_TYPE_CREDIT);
			$payment->setAmount($amount);
			$payment->setRrno($payment->getParentTransactionId());
            $request = $this->_buildRequest($payment);
            $result = $this->_postRequest($request);
            if ($result->getResult()==self::RESPONSE_CODE_APPROVED) {
                 $payment->setStatus(self::STATUS_APPROVED);
				 $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true)->save();
                 return $this;
            }
            $payment->setStatus(self::STATUS_ERROR);
            throw new \Magento\Framework\Exception\LocalizedException(__($result->getMessage()));
        }
        $payment->setStatus(self::STATUS_ERROR);
        throw new \Magento\Framework\Exception\LocalizedException(__('Invalid transaction ID.'));*/

        return $this;
    }

    /**
     * refund the amount with transaction id
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /*if ($payment->getRefundTransactionId() && $amount > 0) {
            $payment->setTransactionType(self::REQUEST_TYPE_CREDIT);
			$payment->setRrno($payment->getRefundTransactionId());
			$payment->setAmount($amount);
            $request = $this->_buildRequest($payment);
            $request->setRrno($payment->getRefundTransactionId());
            $result = $this->_postRequest($request);
            if ($result->getResult()==self::RESPONSE_CODE_APPROVED) {
                $payment->setStatus(self::STATUS_SUCCESS);
                return $this;
            }
			if ($result->getResult()==self::RESPONSE_CODE_DECLINED) {
                throw new \Magento\Framework\Exception\LocalizedException($this->_wrapGatewayError('DECLINED'));
            }
			if ($result->getResult()==self::RESPONSE_CODE_ERROR) {
                throw new \Magento\Framework\Exception\LocalizedException($this->_wrapGatewayError('ERROR'));
            }			
            throw new \Magento\Framework\Exception\LocalizedException($this->_wrapGatewayError($result->getRrno()));
        }
        throw new \Magento\Framework\Exception\LocalizedException(__('Error in refunding the payment.'));*/

        return $this;
    }

	
    public function validate()
    {


        $info = $this->getInfoInstance();

        $errorMsg = false;
        $availableTypes = explode(',', $this->getConfigData('cctypes'));

        $ccNumber = $info->getCcNumber();


        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);

        $ccType = '';

        if (in_array($info->getCcType(), $availableTypes)) 
        {
            if ($this->validateCcNum(
                $ccNumber
            ) || $this->otherCcType(
                $info->getCcType()
            ) && $this->validateCcNumOther(
                // Other credit card type number validation
                $ccNumber
            )
            ) {
                $ccTypeRegExpList = [
                    //Solo, Switch or Maestro. International safe
                    'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/',
                    'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)' .
                        '|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)' .
                        '|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))' .
                        '|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))' .
                        '|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/',
                    // Visa
                    'VI' => '/^4[0-9]{12}([0-9]{3})?$/',
                    // Master Card
                    'MC' => '/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$/',
                    // American Express
                    'AE' => '/^3[47][0-9]{13}$/',
                    // Discover
                    'DI' => '/^(6011((0|9|[2-4])[0-9]{11,14}|(74|7[7-9]|8[6-9])[0-9]{10,13})|6(4[4-9][0-9]{13,16}|' .
                        '5[0-9]{14,17}))/',
                    'DN' => '/^3(0[0-5][0-9]{13,16}|095[0-9]{12,15}|(6|[8-9])[0-9]{14,17})/',
                    // UnionPay
                    'UN' => '/^622(1(2[6-9][0-9]{10,13}|[3-9][0-9]{11,14})|[3-8][0-9]{12,15}|9([[0-1][0-9]{11,14}|' .
                        '2[0-5][0-9]{10,13}))|62[4-6][0-9]{13,16}|628[2-8][0-9]{12,15}/',
                    // JCB
                    'JCB' => '/^35(2[8-9][0-9]{12,15}|[3-8][0-9]{13,16})/',
                    'MI' => '/^(5(0|[6-9])|63|67(?!59|6770|6774))\d*$/',
                    'MD' => '/^(6759(?!24|38|40|6[3-9]|70|76)|676770|676774)\d*$/',
                ];

                $ccNumAndTypeMatches = isset(
                    $ccTypeRegExpList[$info->getCcType()]
                ) && preg_match(
                    $ccTypeRegExpList[$info->getCcType()],
                    $ccNumber
                );
                $ccType = $ccNumAndTypeMatches ? $info->getCcType() : 'OT';


                if (!$ccNumAndTypeMatches && !$this->otherCcType($info->getCcType())) {
                    $errorMsg = __('The credit card number doesn\'t match the credit card type.');
                }
            } else {
                $errorMsg = __('Invalid Credit Card Number');
            }
        } 
        else 
        {
            $errorMsg = __('This credit card type is not allowed for this payment method.');
        }

        //validate credit card verification number
        if ($errorMsg === false && $this->hasVerification()) {
            $verifcationRegEx = $this->getVerificationRegEx();
            $regExp = isset($verifcationRegEx[$info->getCcType()]) ? $verifcationRegEx[$info->getCcType()] : '';
            if (!$info->getCcCid() || !$regExp || !preg_match($regExp, $info->getCcCid())) {
                $errorMsg = __('Please enter a valid credit card verification number.');
            }
        }

        if ($ccType != 'SS' && !$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            $errorMsg = __('Please enter a valid credit card expiration date.');
        }


           


        if ($errorMsg) {
            throw new \Magento\Framework\Exception\LocalizedException($errorMsg);
        }

        return $this;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        /*if (is_array($data)) {
            $this->getInfoInstance()->addData($data);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $this->getInfoInstance()->addData($data->getData());
        }*/
        /*$info = $this->getInfoInstance();
		//error_log($_POST["PAYMENT_ACCOUNT"]);
        $info->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear())
	    ->setAdditionalData($data->getBpToken());
        error_log('Order Response: assignData '.print_r($data, true), 3, "/Users/shafiq/zaman/magento21/var/log/icanpay.log");
        return $this;

        */
        $additionalData = $data->getData(\Magento\Quote\Api\Data\PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }
        /** @var DataObject $info */
        $info = $this->getInfoInstance();
        $info->addData(
            [
                'cc_type' => $additionalData->getCcType(),
                'cc_owner' => $additionalData->getCcOwner(),
                'cc_last_4' => substr($additionalData->getCcNumber(), -4),
                'cc_number' => $additionalData->getCcNumber(),
                'cc_cid' => $additionalData->getCcCid(),
                'cc_exp_month' => $additionalData->getCcExpMonth(),
                'cc_exp_year' => $additionalData->getCcExpYear(),
                'cc_ss_issue' => $additionalData->getCcSsIssue(),
                'cc_ss_start_month' => $additionalData->getCcSsStartMonth(),
                'cc_ss_start_year' => $additionalData->getCcSsStartYear()
            ]
        );

        //error_log('Order Response: assignData '.print_r($additionalData, true), 3, "/Users/shafiq/zaman/magento21/var/log/icanpay.log");


        return $this;
    }


    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        if ($this->_canSaveCc) {
            $info->setCcNumberEnc($info->encrypt('xxxx-'.$info->getCcLast4()));
        }
		if ($info->getAdditionalData()) {
			$info->setAdditionalData($info->getAdditionalData());
		}
        //$info->setCcCidEnc($info->encrypt($info->getCcCid()));
        $info->setCcNumber(null)
            ->setCcCid(null);
        return $this;

    }	
	
	public function hasVerificationBackend()
	{
        $configData = $this->getConfigData('useccv_backend');
        if(is_null($configData)){
            return true;
        }
        return (bool) $configData;
    }

}
