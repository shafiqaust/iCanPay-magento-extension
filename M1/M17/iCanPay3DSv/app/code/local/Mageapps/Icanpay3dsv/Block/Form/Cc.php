<?php

class Mageapps_Icanpay3dsv_Block_Form_Cc extends Mage_Payment_Block_Form_Cc
{
	protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('icanpay3dsv/payment/form/ccsave.phtml');
    }
}