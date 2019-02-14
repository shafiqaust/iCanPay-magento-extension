<?php

class Mageapps_Icanpay3dsv_Block_Adminhtml_Icanpay_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('mageapps_icanpay3dsv_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('mageapps_icanpay3dsv')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('mageapps_icanpay3dsv')->__('Item Information'),
          'title'     => Mage::helper('mageapps_icanpay3dsv')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('mageapps_icanpay3dsv/adminhtml_icanpay_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}