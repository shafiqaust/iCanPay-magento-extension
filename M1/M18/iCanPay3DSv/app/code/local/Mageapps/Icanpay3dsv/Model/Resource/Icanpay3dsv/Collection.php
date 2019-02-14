<?php

class Mageapps_Icanpay3dsv_Model_Resource_Icanpay3dsv_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('mageapps_icanpay3dsv/icanpay3dsv');
    }
}