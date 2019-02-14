<?php 
class Mageapps_Icanpay_Model_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'authorize_capture',
                'label' => Mage::helper('mageapps_icanpay')->__('Authorize and Capture')
            ),
        );
    }
}
