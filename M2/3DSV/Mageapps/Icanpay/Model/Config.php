<?php
namespace Mageapps\Icanpay\Model;

class Config
{
    protected static $_methods;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }
    public function getActiveMethods($store=null)
    {
        $methods = array();
        $config = $this->scopeConfig->getValue('creditcard', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
        foreach ($config as $code => $methodConfig) {
            if (Mage::getStoreConfigFlag('creditcard/'.$code.'/active', $store)) {
                $methods[$code] = $this->_getMethod($code, $methodConfig);
            }
        }
        return $methods;
    }

    public function getAllMethods($store=null)
    {
        $methods = array();
        $config = $this->scopeConfig->getValue('payment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store);
        foreach ($config as $code => $methodConfig) {
            $methods[$code] = $this->_getMethod($code, $methodConfig);
        }
        return $methods;
    }

    protected function _getMethod($code, $config, $store=null)
    {
        if (isset(self::$_methods[$code])) {
            return self::$_methods[$code];
        }
        $modelName = $config['model'];
        $method = Mage::getModel($modelName);
        $method->setId($code)->setStore($store);
        self::$_methods[$code] = $method;
        return self::$_methods[$code];
    }

    public function getAccountTypes()
    {
        $types = array('CHECKING' => 'Checking', 'BUSINESSCHECKING' => 'Business checking', 'SAVINGS' => 'Savings');
        return $types;
    }    
}
