<?php 
class Mageapps_Icanpay3dsv_Model_Source_Type
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '1',
                'label' => Mage::helper('mageapps_icanpay3dsv')->__('Credit Card Payment')
            ),
            
        );
    }
}
