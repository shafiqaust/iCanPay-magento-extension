<?php

class Mageapps_Icanpay3dsv_Model_Resource_Icanpay3dsv extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('mageapps_icanpay3dsv/icanpay3dsv', 'icanpay_id');
    }
}