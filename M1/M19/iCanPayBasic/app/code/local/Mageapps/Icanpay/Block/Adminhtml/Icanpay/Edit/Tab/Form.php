<?php

class Mageapps_Icanpay_Block_Adminhtml_Icanpay_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('mageapps_icanpay_form', array('legend'=>Mage::helper('mageapps_icanpay')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('mageapps_icanpay')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('mageapps_icanpay')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('mageapps_icanpay')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('mageapps_icanpay')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('mageapps_icanpay')->__('Disabled'),
              ),
          ),
      ));
     
      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('mageapps_icanpay')->__('Content'),
          'title'     => Mage::helper('mageapps_icanpay')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getIcanpayData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getIcanpayData());
          Mage::getSingleton('adminhtml/session')->setIcanpayData(null);
      } elseif ( Mage::registry('mageapps_icanpay_data') ) {
          $form->setValues(Mage::registry('mageapps_icanpay_data')->getData());
      }
      return parent::_prepareForm();
  }
}