<?php
class Mageapps_Icanpay_Block_Icanpay extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getIcanpay()     
     { 
        if (!$this->hasData('mageapps_icanpay')) {
            $this->setData('mageapps_icanpay', Mage::registry('mageapps_icanpay'));
        }
        return $this->getData('mageapps_icanpay');
        
    }
}