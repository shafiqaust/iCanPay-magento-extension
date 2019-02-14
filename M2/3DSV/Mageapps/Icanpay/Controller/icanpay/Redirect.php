<?php
namespace Mageapps\Icanpay\Controller\Icanpay;

use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use Mageapps\Icanpay\Model\Api;


class Redirect extends \Magento\Framework\App\Action\Action
{

    protected $_modelSession;
    protected $_viewLayoutFactory;
    protected $urlBuilder;
    protected $checkoutSession;
    protected $orderFactory;
    
    protected $orderSender;
    protected $helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        LayoutFactory $viewLayoutFactory,
        PaymentHelper $paymentHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Mageapps\Icanpay\Helper\Data $helper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        $this->_viewLayoutFactory = $viewLayoutFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->urlBuilder = $context->getUrl();
        $this->checkoutSession = $checkoutSession;
        
        $this->orderFactory = $orderFactory;
        $this->helper = $helper;
        $this->orderSender = $orderSender;

        
         parent::__construct($context);
    }
    public function execute()
    {
        
        $incrementId = $this->checkoutSession->getLastRealOrderId();
        

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        $quoteId = $order->getQuoteId();

       
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('quote_payment'); 
        $sql = 'Select * FROM `' . $tableName. '` WHERE `quote_id` = '.$quoteId;
        $result = $connection->fetchAll($sql); // gives associated array, table fields as key in array.

        $paymentData = $result[0]['additional_data'];

        
        $response = $this->callApi($order,$paymentData);


        
        if ($response->status == 1) 
        {
            $status = $response->status;
            $redirect_url = $response->redirect_url; 


            //$this->_redirect($redirect_url);
            header('location:'.(string)$redirect_url);
            exit;
        }
        else
        {
            $status = $response->status; 
            $this->messageManager->addErrorMessage("Failed to process payment, please try again");
            $order->cancel()->save();   
            $this->_redirect('checkout/cart');
        }

        return $this;

    }

    public function callApi($order,$paymentData)
    {
        
        $payment = $order->getPayment();
        
        $billing = $order->getBillingAddress();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $countryFactory = $objectManager->get('Magento\Directory\Model\CountryFactory');
        $country = $countryFactory->create()->loadByCode($billing->getCountryId())->getData();

        $cardInfo = $this->helper->mc_decrypt($paymentData);
        
        $CcExpYear = str_split($payment->getCcExpYear(), 2);





        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $apiKeys = $this->helper->getApiCredentials();

        $sec_key = $apiKeys['sec_key'];
        $authenticate_id = $apiKeys['authenticate_id'];
        $authenticate_pw = $apiKeys['authenticate_pw'];


        $params = array(
            
            'authenticate_id'   =>$authenticate_id,
            'authenticate_pw'   =>$authenticate_pw,
            'orderid'           => $order->getIncrementId(), 
            'transaction_type'  =>'a',
            'amount'            => $order->getGrandTotal(),
            'currency'          =>'USD', 
            'customerip'        => $_SERVER['REMOTE_ADDR'],
            'dob'               =>'1982-01-22',
            'ccn'               =>$cardInfo['cc_number'], 
            'cvc_code'          =>$cardInfo['cc_id'],
            'exp_month'         =>$payment->getCcExpMonth(),
            'exp_year'          =>$CcExpYear[1],
            'firstname'         =>$billing->getFirstname(),
            'lastname'          =>$billing->getLastname(), 
            'email'             =>$order->getCustomerEmail(),
            'street'            =>$billing->getStreetLine(1), 
            'city'              =>$billing->getCity(),
            'zip'               =>$billing->getPostcode(),
            'state'             =>substr($billing->getRegionCode(),0,2),
            'country'           =>$country['iso3_code'],
            'phone'             =>$billing->getTelephone(),
            'signature'         =>'iCanPay',
            'fail_url'          =>$this->urlBuilder->getUrl('icanpay/icanpay/failure', ['_secure' => true]),
            'notify_url'        =>$this->urlBuilder->getUrl('icanpay/icanpay/cancel', ['_secure' => true]),
            'success_url'       =>$this->urlBuilder->getUrl('icanpay/icanpay/response', ['_secure' => true]),
            );
            
          
        $api_type = '3DSV';

        $pay = new Api($sec_key, $params, $api_type);
        
        $response = $pay->payment();
        $data = json_decode($response);

        return $data;
    }
}
