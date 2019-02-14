<?php

class Mageapps_Icanpay_Model_Icanpay extends Mage_Core_Model_Abstract
{
	public function _construct() {
        $this->_init('mageapps_icanpay/icanpay');
        parent::_construct();      
    }
}