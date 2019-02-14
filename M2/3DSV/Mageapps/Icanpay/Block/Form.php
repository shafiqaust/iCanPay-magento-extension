<?php


namespace Mageapps\Icanpay\Block;

class Form extends \Magento\Payment\Block\Form
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Type\Onepage
     */
    protected $checkoutTypeOnepage;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentConfig;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Type\Onepage $checkoutTypeOnepage,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutTypeOnepage = $checkoutTypeOnepage;
        $this->paymentConfig = $paymentConfig;
        $this->eventManager = $eventManager;
    }
    protected function _construct()
    {
        parent::_construct();
	if ($this->storeManager->getStore()->isAdmin()) {
		$this->setTemplate('icanpay/creditcard.phtml');
		return;
	}

    $this->setTemplate('icanpay/creditcard.phtml');
    }
  
    public function setMethodInfo()
    {
        $payment = $this->checkoutTypeOnepage
            ->getQuote()
            ->getPayment();
        $this->setMethod($payment->getMethodInstance());

        return $this;
    }

    public function getMethod()
    {
        $method = $this->getData('method');

        if (!($method instanceof \Magento\Payment\Model\Method\AbstractMethod)) {
            throw new \Magento\Framework\Exception\LocalizedException($this->__('Cannot retrieve the payment method model object.'));
        }
        return $method;
    }

    /**
     * Retrieve payment method code
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->getMethod()->getCode();
    }

    /**
     * Retrieve field value data from payment info object
     *
     * @param   string $field
     * @return  mixed
     */
    public function getInfoData($field)
    {
        return $this->htmlEscape($this->getMethod()->getInfoInstance()->getData($field));
    }
 
    /**
     * Retrieve payment configuration object
     *
     * @return \Magento\Payment\Model\Config
     */
    protected function _getConfig()
    {
        return $this->paymentConfig;
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }


    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if (is_null($months)) {
            $months[0] =  $this->__('Month');
            $months = array_merge($months, $this->_getConfig()->getMonths());
            $this->setData('cc_months', $months);
        }
        return $months;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (is_null($years)) {
            $years = $this->_getConfig()->getYears();
            $years = array(0=>$this->__('Year'))+$years;
            $this->setData('cc_years', $years);
        }
        return $years;
    }

    /**
     * Retrive has verification configuration
     *
     * @return boolean
     */
    public function hasVerification()
    {
        if ($this->getMethod()) {
            $configData = $this->getMethod()->getConfigData('useccv');
            if(is_null($configData)){
                return true;
            }
            return (bool) $configData;
        }
        return true;
    }

    public function hasVerificationBackend()
    {
	if ($this->getMethod()) {
            $configData = $this->getMethod()->getConfigData('useccv_backend');
            if(is_null($configData)){
                return true;
            }
            return (bool) $configData;
        }
        return true;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->eventManager->dispatch('payment_form_block_to_html_before', array(
            'block'     => $this
        ));
        return parent::_toHtml();
    }


} 

?>
