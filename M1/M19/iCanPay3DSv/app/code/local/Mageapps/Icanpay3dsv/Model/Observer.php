<?php
class Mageapps_Icanpay3dsv_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function disableMethod(Varien_Event_Observer $observer)
    {
        $moduleName = "Mageapps_Icanpay3dsv";
        if ('mageapps_icanpay3dsv' == $observer->getMethodInstance()->getCode()) {
            if (!Mage::getStoreConfigFlag('advanced/modules_disable_output/' . $moduleName)) {
                //nothing here, as module is ENABLE
            } else {
                $observer->getResult()->isAvailable = false;
            }
        }
    }
}