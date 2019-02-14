<?php
class Mageapps_Icanpay_Block_Adminhtml_Icanpay extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_icanpay';
    $this->_blockGroup = 'mageapps_icanpay';
    $this->_headerText = Mage::helper('mageapps_icanpay')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('mageapps_icanpay')->__('Add Item');
    parent::__construct();
  }
}