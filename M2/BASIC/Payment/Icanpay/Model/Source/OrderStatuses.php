<?php

 
namespace Payment\Icanpay\Model\Source;

class OrderStatuses
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => __('processing'),
                'label' => 'Processing'
            ),
            array(
                'value' => __('pending'),
                'label' => 'Pending'
            ),
            array(
                'value' => __('complete'),
                'label' => 'Complete'
            ),
            array(
                'value' => __('payment_review'),
                'label' => 'Payment Review'
            ),
            array(
                'value' => __('holded'),
                'label' => 'On Hold'
            ),
        );
    }
}
