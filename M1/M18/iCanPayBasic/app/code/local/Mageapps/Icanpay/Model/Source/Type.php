<?php 
class Mageapps_Icanpay_Model_Source_Type
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '1',
                'label' => Mage::helper('mageapps_icanpay')->__('Credit Card Payment')
            ),
            
        );
    }
}
