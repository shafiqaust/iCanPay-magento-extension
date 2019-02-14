<?php
namespace Payment\Icanpay\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

 
class Data extends AbstractHelper
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    )
    {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /*
     * @return bool
     */
    public function isEnabled($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->isSetFlag(
            'payment/payment_icanpay/active',
            $scope
        );
    }

    /*
     * @return string
     */
    public function getTitle($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'payment/payment_icanpay/title',
            $scope
        );
    }

    /*
     * @return string
     */
    public function getApiCredentials($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        $authenticate_id = $this->scopeConfig->getValue(
            'payment/payment_icanpay/authenticate_id',
            $scope
        );
        $authenticate_pw = $this->scopeConfig->getValue(
            'payment/payment_icanpay/authenticate_pw',
            $scope
        );
        $sec_key = $this->scopeConfig->getValue(
            'payment/payment_icanpay/sec_key',
            $scope
        );
        $aApiKeys = ['authenticate_id' => $authenticate_id,'authenticate_pw'=>$authenticate_pw,'sec_key'=>$sec_key];
        
        
        return $aApiKeys;
    }

    
}
