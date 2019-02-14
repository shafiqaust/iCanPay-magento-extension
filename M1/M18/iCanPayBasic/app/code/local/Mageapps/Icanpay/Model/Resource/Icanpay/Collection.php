<?php

class Mageapps_Icanpay_Model_Resource_Icanpay_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('mageapps_icanpay/icanpay');
    }
}