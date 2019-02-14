<?php

class Mageapps_Icanpay_Model_Resource_Icanpay extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('mageapps_icanpay/icanpay', 'icanpay_id');
    }
}