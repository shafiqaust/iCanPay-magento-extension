<?php

namespace Payment\Icanpay\Model\Source;

class TransactionMode
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'TEST',
                'label' => __('Test')
            ),
            array(
                'value' => 'LIVE',
                'label' => __('Live')
            ),
        );
    }
}
