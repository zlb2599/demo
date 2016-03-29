<?php
/***********************************************************************************
*	Copyright (c) 2005-2011
*	All rights reserved.
*
*	File:
*	Author:gaohaifeng
*	Editor:
*	Email:haifeng@hnusoft.com
*	Tel:
*	Version:
***********************************************************************************/
?>
<?php
class canpointDES
{
		var $key;
    	var $iv; //偏移量 
    
    	public function hnusoftDES( $key, $iv=0) 
    	{
			//key长度8例如:1234abcd
        	$this->key = $key;
        	if( $iv == 0 ) 
        	{
            		$this->iv = $key; //默认以$key 作为 iv
        	} 
        	else 
        	{
            		$this->iv = $iv; //mcrypt_create_iv ( mcrypt_get_block_size (MCRYPT_DES, MCRYPT_MODE_CBC), MCRYPT_DEV_RANDOM );
        	}
    	}
    
    	public function encrypt($str)
    	{
			//加密，返回大写十六进制字符串, MCRYPT_3DES, MCRYPT_DES
        	$size = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_ECB);
        	$str = $this->pkcs5Pad ( $str, $size );
        	return strtolower( bin2hex( mcrypt_cbc(MCRYPT_DES, $this->key, $str, MCRYPT_ENCRYPT, 0 ) ) );
    	}
    	
    	//2013-06-09， 这个加密出的结果和 flex, C 语言不一样，所以不用这个
    	public static function desEncrypt_old( $str )
    	{
    		$des = new canpointDES( 'canpointabc' );
    		return $des->encrypt( $str );    		
    	}
    	
    	//2013-06-09 开始用这个 
		public static function desEncrypt( $str )
    	{
    		$key="ghnusoft"; 
			$iv="ghnusoft";
			$encrypt=$str; //明文
			
			$tb=mcrypt_module_open(MCRYPT_3DES,'','ecb',''); //创建加密环境 256位 128/8 = 16 字节 表示IV的长度
			mcrypt_generic_init($tb,$key,$iv); //初始化加密算法
			$encrypt= canpointDES::PaddingPKCS7( $encrypt);//这个函数非常关键,其作用是对明文进行补位填充
			
			$cipher=mcrypt_generic($tb,$encrypt); //对数据执行加密
			$cipher=bin2hex( $cipher);//同意进行base64编码 
			mcrypt_generic_deinit($tb); //释放加密算法资源
			mcrypt_module_close($tb); //关闭加密环境  	

			return $cipher;
    	}
    	
    	
    	
    
    	private function decrypt($str) 
    	{
			//解密
        	$strBin = $this->hex2bin( strtolower( $str ) );
        	$str = mcrypt_cbc( MCRYPT_DES, $this->key, $strBin, MCRYPT_DECRYPT, 0 );
        	$str = $this->pkcs5Unpad( $str );
        	return $str;
    	}
    	
    	
    	//2013-06-09， 这个加密出的结果和 flex, C 语言不一样，所以不用这个
		public static function desDecrypt_old( $str )
    	{
    		$des = new canpointDES( 'ghnusoft' );
    		return $des->decrypt( $str );    		
    	}
    	
    	//2013-06-09 开始用这个 
		public static function desDecrypt( $str )
    	{
    		$key="canpointabc"; 
			$iv="canpointabc";
			$encrypt=$str; //密文
			
    		$tb=mcrypt_module_open(MCRYPT_3DES,'','ecb','');
			mcrypt_generic_init($tb,$key,$iv);
			$cipher= canpointDES::hex2bin( trim( $encrypt ));
			$pain=mdecrypt_generic($tb,$cipher);
			mcrypt_generic_deinit($tb);
			mcrypt_module_close($tb);
			
			return canpointDES::UnPaddingPKCS7( $pain );  	

    	}
    	
    	
    	/*
    	private function hex2bin($hexData) 
    	{
        	$binData = "";
        	for($i = 0; $i < strlen ( $hexData ); $i += 2) 
        	{
            		$binData .= chr ( hexdec ( substr ( $hexData, $i, 2 ) ) );
        	}
        	return $binData;
    	}
		*/
		public static function hex2bin($hexData) 
    	{
        	$binData = "";
        	for($i = 0; $i < strlen ( $hexData ); $i += 2) 
        	{
            		$binData .= chr ( hexdec ( substr ( $hexData, $i, 2 ) ) );
        	}
        	return $binData;
    	}

    	private function pkcs5Pad($text, $blocksize) 
    	{
        	$pad = $blocksize - (strlen ( $text ) % $blocksize);
        	return $text . str_repeat ( chr ( $pad ), $pad );
    	}
    
    	private function pkcs5Unpad($text) 
    	{
        	$pad = ord ( $text {strlen ( $text ) - 1} );
        	if ($pad > strlen ( $text ))
            	return false;
        	if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad)
            	return false;
        	return substr ( $text, 0, - 1 * $pad );
    	}
	
    	
    	
    	//2013-06-09	
		//补位填充函数
		public static function PaddingPKCS7 ($data)
		{
		     /* 获取加密算法的区块所需空间,MCRYPT_3DES表示加密算法,cbc表示加密模式,要和mcrypt_module_open(MCRYPT_3DES,'','cbc','')的一致*/
		    $block_size = mcrypt_get_block_size(MCRYPT_3DES, 'cbc');
		    //echo ' block_size = '.$block_size.' = ';
		    $padding_char = $block_size - (strlen($data) % $block_size); // 计算需要补位的空间 
		    $data .= str_repeat(chr($padding_char), $padding_char);        // 补位操作
		    return $data;
		}


		public static function UnPaddingPKCS7($text)
		{
			$pad = ord($text{strlen($text) - 1});
			if ($pad > strlen($text)) {
				return false;
			}
			if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
				return false;
			}
			return substr($text, 0, - 1 * $pad);
		}
    	
    	
}



?>