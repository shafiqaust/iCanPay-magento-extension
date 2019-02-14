<?php
class Mageapps_Icanpay3dsv_Block_Info_Cc extends Mage_Payment_Block_Info_Cc
{

    /*protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('icanpay/payment/info/cc.phtml');
    }*/

   
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = array();

        $additionalData = $this->getInfo()->getAdditionalData();

        
            if ($ccType = $this->getCcTypeName()) 
            {
                $data[Mage::helper('payment')->__('Credit Card Type')] = $ccType;
            }
            if ($this->getInfo()->getCcLast4()) 
            {
                $data[Mage::helper('payment')->__('Credit Card Number')] = sprintf('xxxx-%s', $this->getInfo()->getCcLast4());
            }
            if (!$this->getIsSecureMode()) 
            {
                if ($ccSsIssue = $this->getInfo()->getCcSsIssue()) {
                    $data[Mage::helper('payment')->__('Switch/Solo/Maestro Issue Number')] = $ccSsIssue;
                }
                $year = $this->getInfo()->getCcSsStartYear();
                $month = $this->getInfo()->getCcSsStartMonth();
                if ($year && $month) {
                    $data[Mage::helper('payment')->__('Switch/Solo/Maestro Start Date')] = $this->_formatCardDate($year, $month);
                }
            }
        

        return $transport->setData(array_merge($data, $transport->getData()));
    }
    
}