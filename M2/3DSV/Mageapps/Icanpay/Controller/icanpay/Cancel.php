<?php
namespace Mageapps\Icanpay\Controller\Icanpay;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;

class Cancel extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        $this->messageManager->addErrorMessage("Payment cancelled");
        $this->_redirect('checkout/cart');
    }
}
