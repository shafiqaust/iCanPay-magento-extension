<?php
class Mageapps_Icanpay3dsv_Block_Adminhtml_Icanpay extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_icanpay3dsv';
    $this->_blockGroup = 'mageapps_icanpay3dsv';
    $this->_headerText = Mage::helper('mageapps_icanpay3dsv')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('mageapps_icanpay3dsv')->__('Add Item');
    parent::__construct();
  }
}