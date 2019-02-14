<?php
namespace Mageapps\Icanpay\Controller\Icanpay;


class Response extends \Magento\Framework\App\Action\Action
{
    
    protected $urlBuilder;
    protected $checkoutSession;
    protected $orderFactory;
    protected $orderSender;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        
        $this->urlBuilder = $context->getUrl();
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        
         parent::__construct($context);
    }
    public function execute()
    {
    	$incrementId = (int) $this->getRequest()->getParam('oid');
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        $quoteId = $order->getQuoteId();


        $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);

        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $invoice = $objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();


        /*$transactionInvoice = $objectManager->create('Magento\Framework\DB\Transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        $transactionInvoice->save();*/


        $this->_redirect('checkout/onepage/success');
    }
}
