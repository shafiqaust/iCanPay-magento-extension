<?php

class Mageapps_Icanpay_Block_Adminhtml_Icanpay_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'mageapps_icanpay';
        $this->_controller = 'adminhtml_icanpay';
        
        $this->_updateButton('save', 'label', Mage::helper('mageapps_icanpay')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('mageapps_icanpay')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('mageapps_icanpay_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'mageapps_icanpay_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'mageapps_icanpay_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('mageapps_icanpay_data') && Mage::registry('mageapps_icanpay_data')->getId() ) {
            return Mage::helper('mageapps_icanpay')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('mageapps_icanpay_data')->getTitle()));
        } else {
            return Mage::helper('mageapps_icanpay')->__('Add Item');
        }
    }
}