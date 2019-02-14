<?php

class Mageapps_Icanpay3dsv_Block_Adminhtml_Icanpay_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('mageapps_icanpay3dsvGrid');
      $this->setDefaultSort('mageapps_icanpay3dsv_id');
      $this->setDefaultDir('ASC');
      $this->setEmptyCellLabel(Mage::helper('mageapps_icanpay3dsv')->__('No records found.'));
      $this->setSaveParametersInSession(true);
      $this->setUseAjax(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('mageapps_icanpay3dsv/icanpay3dsv')->getCollection();
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('icanpay_id', array(
          'header'    => Mage::helper('mageapps_icanpay3dsv')->__('ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'icanpay_id',
      ));

      $this->addColumn('title', array(
          'header'    => Mage::helper('mageapps_icanpay3dsv')->__('Title'),
          'align'     =>'left',
          'index'     => 'title',
      ));

	  /*
      $this->addColumn('content', array(
			'header'    => Mage::helper('icanpay')->__('Item Content'),
			'width'     => '150px',
			'index'     => 'content',
      ));
	  */

      $this->addColumn('status', array(
          'header'    => Mage::helper('mageapps_icanpay3dsv')->__('Status'),
          'align'     => 'left',
          'width'     => '80px',
          'index'     => 'status',
          'type'      => 'options',
          'options'   => array(
              1 => 'Enabled',
              2 => 'Disabled',
          ),
      ));
	  
        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('mageapps_icanpay3dsv')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('mageapps_icanpay3dsv')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
		
		$this->addExportType('*/*/exportCsv', Mage::helper('mageapps_icanpay3dsv')->__('CSV'));
		$this->addExportType('*/*/exportXml', Mage::helper('mageapps_icanpay3dsv')->__('XML'));
	  
      return parent::_prepareColumns();
  }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('icanpay3dsv_id');
        $this->getMassactionBlock()->setFormFieldName('mageapps_icanpay3dsv');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('mageapps_icanpay3dsv')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('mageapps_icanpay3dsv')->__('Are you sure?')
        ));

        $statuses = Mage::getSingleton('mageapps_icanpay3dsv/status')->getOptionArray();

        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('mageapps_icanpay3dsv')->__('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('mageapps_icanpay3dsv')->__('Status'),
                         'values' => $statuses
                     )
             )
        ));
        return $this;
    }

  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }
  public function getGridUrl()
  {
    return $this->getUrl('*/*/grid', array('_current'=>true));
  }

}