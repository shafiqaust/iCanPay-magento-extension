<?php
namespace Payment\Icanpay\Model\Mysql4\CCPayment;


class Debug extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct(
            $context,
            $connectionName
        );
    }

    protected function _construct()
    {
        $this->_init('icanpay/ccpayment_debug', 'debug_id');
    }
}
