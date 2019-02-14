<?php
namespace Mageapps\Icanpay\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
 
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	const KEY = 'd0a7e7997b6d5fcd55f4b5c32611b87cd923e88837b63bf2941ef819dc8ca282';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    protected $scopeConfig;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
    	ScopeConfigInterface $scopeConfig,
        Context $context,
        EncryptorInterface $encryptor
    )
    {
    	$this->scopeConfig = $scopeConfig;
    	$this->encryptor = $encryptor;
        parent::__construct($context,$scopeConfig);
        
    }

    /*
     * @return bool
     */
    public function isEnabled($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->isSetFlag(
            'payment/mageapps_icanpay/active',
            $scope
        );
    }

    /*
     * @return string
     */
    public function getTitle($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'payment/mageapps_icanpay/title',
            $scope
        );
    }

    /*
     * @return string
     */
    public function getApiCredentials($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        $authenticate_id = $this->scopeConfig->getValue(
            'payment/mageapps_icanpay/authenticate_id',
            $scope
        );
        $authenticate_pw = $this->scopeConfig->getValue(
            'payment/mageapps_icanpay/authenticate_pw',
            $scope
        );
        $sec_key = $this->scopeConfig->getValue(
            'payment/mageapps_icanpay/sec_key',
            $scope
        );
        $aApiKeys = ['authenticate_id' => $authenticate_id,'authenticate_pw'=>$authenticate_pw,'sec_key'=>$sec_key];
        
        
        return $aApiKeys;
    }



	/*public function getGateWayCredentials()
	{
		return array(
				'sec_key' => '56e8441e880267.81606026',
				'authenticate_id' => '220c1f070d72bf2adbb85db0c5429325',
				'authenticate_pw' =>'b79eaa516e744e56b89f7f724c631afe'
			);
	}*/


	public function mc_encrypt($encrypt)
	{
		$key = self::KEY;
	    $encrypt = serialize($encrypt);
	    /*$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
	    $key = pack('H*', $key);
	    $mac = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));
	    $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt.$mac, MCRYPT_MODE_CBC, $iv);
	    $encoded = base64_encode($passcrypt).'|'.base64_encode($iv);
    	return $encoded;*/
    	return $encrypt;
	}

	public function mc_decrypt($decrypt)
	{
		/*$key = self::KEY;
	    $decrypt = explode('|', $decrypt.'|');
	    $decoded = base64_decode($decrypt[0]);
	    $iv = base64_decode($decrypt[1]);

	    if(strlen($iv)!==mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC))
	    { 
	    	return false; 
	    }
	    $key = pack('H*', $key);
	    $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv));
	    $mac = substr($decrypted, -64);
	    $decrypted = substr($decrypted, 0, -64);
	    $calcmac = hash_hmac('sha256', $decrypted, substr(bin2hex($key), -32));
	    if($calcmac!==$mac)
	    { 
	    	return false; 
	    }
	    $decrypted = unserialize($decrypted);
	    return $decrypted;*/
	    return unserialize($decrypt);
	}
}
