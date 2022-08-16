<?php
defined( 'ABSPATH' ) || exit;

class WAA_DataCrypt{
    private $appid;
	private $sessionKey;

	/**
	 * 构造函数
	 * @param $sessionKey string 用户在小程序登录后获取的会话密钥
	 * @param $appid string 小程序的appid
	 */
	public function __construct( $appid, $sessionKey, $type)
	{
		$this->sessionKey = $sessionKey;
		$this->appid = $appid;
		$this->type = $type;
	}


	/**
	 * 检验数据的真实性，并且获取解密后的明文.
	 * @param $encryptedData string 加密的用户数据
	 * @param $iv string 与用户数据一同返回的初始向量
	 * @param $data string 解密后的原文
     *
	 * @return int 成功0，失败返回对应的错误码
	 */
	public function decryptData( $encryptedData, $iv, &$data )
	{
		if($this->type === 'swan') return $this->swan_decrypt($encryptedData, $iv, $data);
		if($this->type === 'alipay') return $this->alipay_decrypt($encryptedData, $data);
		return $this->decrypt( $encryptedData, $iv, $data );
	}

	public function decrypt( $encryptedData, $iv, &$data )
	{
		if (strlen($this->sessionKey) != 24) {
			return ErrorCode::$IllegalAesKey;
		}
		$aesKey=base64_decode($this->sessionKey);

        
		if (strlen($iv) != 24) {
			return ErrorCode::$IllegalIv;
		}
		$aesIV=base64_decode($iv);

		$aesCipher=base64_decode($encryptedData);

		$result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

		$dataObj=json_decode( $result );
		if( $dataObj  == NULL )
		{
			return ErrorCode::$IllegalBuffer;
		}
		if( $dataObj->watermark->appid != $this->appid )
		{
			return ErrorCode::$IllegalBuffer;
		}
		$data = $result;
		return ErrorCode::$OK;
	}

	public function alipay_decrypt( $encryptedData, &$data )
	{
		if (strlen($this->sessionKey) != 24) {
			return ErrorCode::$IllegalAesKey;
		}
		$aesKey=base64_decode($this->sessionKey);

        
		$aesCipher=base64_decode($encryptedData);

		$result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, OPENSSL_RAW_DATA);

		$dataObj=json_decode( $result );
		if( $dataObj  == NULL )
		{
			return ErrorCode::$IllegalBuffer;
		}
		$data = $result;
		return ErrorCode::$OK;
	}

	function swan_decrypt($encryptedData, $iv, &$data) {
	    $session_key = base64_decode($this->sessionKey);
	    $iv = base64_decode($iv);
	    $ciphertext = base64_decode($encryptedData);

	    $plaintext = false;
	    if (function_exists("openssl_decrypt")) {
	        $plaintext = openssl_decrypt($ciphertext, "AES-192-CBC", $session_key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
	    } else {
	        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, null, MCRYPT_MODE_CBC, null);
	        mcrypt_generic_init($td, $session_key, $iv);
	        $plaintext = mdecrypt_generic($td, $ciphertext);
	        mcrypt_generic_deinit($td);
	        mcrypt_module_close($td);
	    }
	    if ($plaintext == false) {
	        return false;
	    }

	    // trim pkcs#7 padding
	    $pad = ord(substr($plaintext, -1));
	    $pad = ($pad < 1 || $pad > 32) ? 0 : $pad;
	    $plaintext = substr($plaintext, 0, strlen($plaintext) - $pad);

	    // trim header
	    $plaintext = substr($plaintext, 16);
	    // get content length
	    $unpack = unpack("Nlen/", substr($plaintext, 0, 4));
	    // get content
	    $content = substr($plaintext, 4, $unpack['len']);
	    // get app_key
	    $app_key_decode = substr($plaintext, $unpack['len'] + 4);

	    $data = $content;

	    return $this->appid == $app_key_decode ? 0 : -1;
	}
}


class ErrorCode{
	public static $OK = 0;
	public static $IllegalAesKey = -41001;
	public static $IllegalIv = -41002;
	public static $IllegalBuffer = -41003;
	public static $DecodeBase64Error = -41004;
}
