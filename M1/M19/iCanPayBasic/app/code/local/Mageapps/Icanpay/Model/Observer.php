<?php
class Mageapps_Icanpay_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function disableMethod(Varien_Event_Observer $observer)
    {
        $moduleName = "Mageapps_Icanpay";
        if ('mageapps_icanpay' == $observer->getMethodInstance()->getCode()) {
            if (!Mage::getStoreConfigFlag('advanced/modules_disable_output/' . $moduleName)) {
                //nothing here, as module is ENABLE
            } else {
                $observer->getResult()->isAvailable = false;
            }
        }
    }
    public function implementOrderStatus($event){
        $order = $event->getOrder();
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);        
        $order->save();

    }
}