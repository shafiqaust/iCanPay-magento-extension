<?php

define('IV_SIZE', mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));

class Mageapps_Icanpay3dsv_Model_Api extends Mage_Core_Model_Abstract
{

    private $_API_URL;
    private $_3DSv_API_URL;
    private $_SECRET_KEY;
    private $_PARAMS;
    private $_API_TYPE;

    public function __construct(array $arguments) 
    {
        

        if (($this->_mcrypt_exists = function_exists('mcrypt_encrypt')) === FALSE) {
            Mage::throwException('The Encrypt library requires the Mcrypt extension.');
        }

        $this->_PARAMS = $arguments['params'];
        $this->_SECRET_KEY = $arguments['sec_key'];
        $this->_API_URL = 'https://pay.icanpay.cn.com/pay/authorize_payment';
        $this->_3DSv_API_URL = 'https://pay.icanpay.cn.com/pay/authorize3dsv_payment';
        $this->_API_TYPE = $arguments['api_type'];

        $this->validatePayload();
    }

    public function payment() {
        $payload = array(
            "ccn" => $this->_PARAMS['ccn'],
            "expire" => $this->_PARAMS['exp_month'] . '/' . $this->_PARAMS['exp_year'],
            "cvc" => $this->_PARAMS['cvc_code'],
            "firstname" => $this->_PARAMS['firstname'],
            "lastname" => $this->_PARAMS['lastname']
        );
        $encripted_card_info = $this->encrypt($payload);
        $this->_PARAMS['card_info'] = $encripted_card_info;

        unset($this->_PARAMS['ccn']);
        unset($this->_PARAMS['cvc_code']);
        unset($this->_PARAMS['firstname']);
        unset($this->_PARAMS['lastname']);
        unset($this->_PARAMS['exp_year']);
        unset($this->_PARAMS['exp_month']);
        $this->_PARAMS['customerip'] = $_SERVER['REMOTE_ADDR'];
        if ($this->_API_TYPE == '3DSV') {
            $this->_PARAMS['success_url'] = urlencode($this->_PARAMS['success_url']);
            $this->_PARAMS['fail_url'] = urlencode($this->_PARAMS['fail_url']);
            $this->_PARAMS['notify_url'] = urlencode($this->_PARAMS['notify_url']);
        }

        $signature = "";
        ksort($this->_PARAMS);

        foreach ($this->_PARAMS as $key => $val) {
            if ($key != "signature") {
                $signature .= $val;
            }
        }
        $signature = $signature . $this->_SECRET_KEY;
        $signature = strtolower(sha1($signature));
        $this->_PARAMS['signature'] = $signature;        
        if ($this->_API_TYPE == 'API') {
            $this->_PARAMS['tr_mode'] = 'API';            
            $response = $this->post_request();
            parse_str($response, $output);
            return json_encode($output);
        } elseif ($this->_API_TYPE == '3DSV') {
            $this->_PARAMS['tr_mode'] = 'API3DSv';            
            $requestDataJson = json_encode($this->_PARAMS);
            $base64_encode = base64_encode($requestDataJson);
            $final_param = array('request' => $base64_encode);
            $url_args = http_build_query($final_param);

            $response = array(
                'status' => 1,
                'redirect_url' => $this->_3DSv_API_URL . '?' . $url_args
            );
            return json_encode($response);
        }
    }

    private function encrypt($payload = array()) {        
        $string = preg_replace("/[^A-Za-z0-9 ]/", '', $this->_SECRET_KEY);
        $sKey = substr($string, 0, 16);
        
        $iv = mcrypt_create_iv(IV_SIZE, MCRYPT_DEV_URANDOM);
        $crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $sKey, $this->array_implode_with_keys($payload), MCRYPT_MODE_CBC, $iv);
        $combo = $iv . $crypt;
        $encryptdata = base64_encode($iv . $crypt);
        return $encryptdata;
    }

    

    private function validatePayload() {
        $payload = $this->_PARAMS;
        if (!is_array($payload)) {
            Mage::throwException('Data to be encripted must be in array format');
            //die('Data to be encripted must be in array format');

        }
        if ($this->_API_TYPE == 'API') {
            $required_parameter = array('authenticate_id', 'authenticate_pw', 'orderid', 'transaction_type', 'amount', 'currency', 'ccn', 'exp_month', 'exp_year', 'cvc_code', 'firstname', 'lastname', 'email', 'street', 'city', 'zip', 'state', 'country', 'phone');
        } elseif ($this->_API_TYPE == '3DSV') {
            $required_parameter = array('authenticate_id', 'authenticate_pw', 'orderid', 'transaction_type', 'amount', 'currency', 'ccn', 'exp_month', 'exp_year', 'cvc_code', 'firstname', 'lastname', 'email', 'street', 'city', 'zip', 'state', 'country', 'phone', 'dob', 'success_url', 'fail_url', 'notify_url');
        }

        $errmsg = '';
        foreach ($required_parameter as $key) {
            if (empty($payload[$key])) {
                $errmsg .= $key . ' must have a value,';
                Mage::throwException($errmsg);
            }
        }
        if($errmsg)
        {
            Mage::throwException($errmsg);
        }
        if ($payload['ccn']) {
            $ccn = preg_replace('/[^0-9]/', '', $payload['ccn']);
            if ((strlen($ccn) < 13) || (strlen($ccn) > 16)) {
                Mage::throwException($payload['ccn'] . ' is invalid card');
            }
        }
        if ($payload['cvc_code']) {
            $cvc_code = preg_replace('/[^0-9]/', '', $payload['cvc_code']);
            if ((strlen($cvc_code) < 3) || (strlen($cvc_code) > 4)) {
                
                Mage::throwException($payload['cvc_code'] . ' is invalid cvc code');
            }
        }
        if ($this->validDate($payload['exp_year'], $payload['exp_month']) == FALSE) {
            
            Mage::throwException('Expiry date must be valid');
        }
        return TRUE;
    }

    private function validDate($year, $month) {
        $year = '20' . $year;
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        if (!preg_match('/^20\d\d$/', $year)) {
            return FALSE;
        }
        if (!preg_match('/^(0[1-9]|1[0-2])$/', $month)) {
            return FALSE;
        }
        // past date
        if ($year < date('Y') || $year == date('Y') && $month < date('m')) {
            return FALSE;
        }
        return TRUE;
    }

    private function array_implode_with_keys($array) {
        $return = '';
        if (count($array) > 0) {
            foreach ($array as $key => $value) {
                $return .= $key . '||' . $value . '__';
            }
            $return = substr($return, 0, strlen($return) - 2);
        }
        return $return;
    }

    private function post_request() {
        $data_stream = http_build_query($this->_PARAMS);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_stream);
        curl_setopt($ch, CURLOPT_URL, $this->_API_URL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $result_str = curl_exec($ch);
        if (curl_errno($ch) != 0) {
            $result_str = 'curl_error=' . curl_errno($ch) . '&status=0';
        }
        curl_close($ch);
        return $result_str;
    }

}
