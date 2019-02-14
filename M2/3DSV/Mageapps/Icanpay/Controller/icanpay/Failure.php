<?php
namespace Mageapps\Icanpay\Controller\Icanpay;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;

class Failure extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        $this->messageManager->addErrorMessage("Failed to process payment, please try again");
        $this->_redirect('checkout/cart');
    }
}
