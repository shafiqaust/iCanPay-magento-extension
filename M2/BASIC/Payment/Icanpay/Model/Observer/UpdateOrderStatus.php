<?php


namespace Payment\Icanpay\Model\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

/**
 * Response object
 */
class UpdateOrderStatus implements ObserverInterface {
	public function execute(Observer $observer) {
		/*$order = $observer->getEvent()->getOrder();
		if ($order->getPayment()->getMethodInstance()->getCode() == 'payment_icanpay') {
			$order->setStatus($order->getPayment()->getMethodInstance()->getConfigData('order_status'));
			$order->addStatusHistoryComment('Order status changed to ' . $order->getPayment()->getMethodInstance()->getConfigData('order_status'), false);
			$order->save();
		}*/
	}
}
