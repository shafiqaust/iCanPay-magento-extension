<?php

namespace Payment\Icanpay\Model\Source;

class TransactionType
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'authorize',
                'label' => __('Authorize Only')
            ),
            array(
                'value' => 'authorize_capture',
                'label' => __('Sale (Authorize & Capture)')
            ),
        );
    }
}
