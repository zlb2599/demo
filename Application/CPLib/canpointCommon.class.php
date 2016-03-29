<?php
/***********************************************************************************
 * Copyright (c) 2005-2011
 * All rights reserved.
 *
 * File:
 * Author:gaohaifeng
 * Editor:
 * Email:haifeng@hnusoft.com
 * Tel:
 * Version:
 ***********************************************************************************/
?>
<?php
use Think\Crypt\Driver\Des;
class canpointCommon
{
	/*
	 * logDegub
	 */
	public static function logDegub($msg, $ext = "")
	{
		$logFile = $_SERVER ['DOCUMENT_ROOT'] . "/Application/Runtime/Logs/Debug-" . date ( 'Y-m-d' ) . '.txt' . $ext;
		//$msg = date ( '[Y-m-d H:i:s]' ) . '	' . $msg . '	{in file:' . $_SERVER ['REQUEST_URI'] . "}\r\n";
		$msg = date ( '[Y-m-d H:i:s]' ) . '	' . $msg . "\r\n";
		file_put_contents ( $logFile, $msg, FILE_APPEND );
	}
	
	/*
	 * logError
	 */
	public static function logError($msg)
	{
		$logFile = $_SERVER ['DOCUMENT_ROOT'] . "/Application/Runtime/Logs/Error-" . date ( 'Y-m-d' ) . '.txt';
		//$msg = date ( '[Y-m-d H:i:s]' ) . '	' . $msg . '	{in file:' . $_SERVER ['REQUEST_URI'] . "}\r\n";
		$msg = date ( '[Y-m-d H:i:s]' ) . '	' . $msg . "\r\n";
		file_put_contents ( $logFile, $msg, FILE_APPEND );
	}
	
	/*
	 * logWarning
	 */
	public static function logWarning($msg)
	{
		$logFile = $_SERVER ['DOCUMENT_ROOT'] . "/Application/Runtime/Logs/Warning-" . date ( 'Y-m-d' ) . '.txt';
		//$msg = date ( '[Y-m-d H:i:s]' ) . '	' . $msg . '	{in file:' . $_SERVER ['REQUEST_URI'] . "}\r\n";
		$msg = date ( '[Y-m-d H:i:s]' ) . '	' . $msg . "\r\n";
		file_put_contents ( $logFile, $msg, FILE_APPEND );
	}
	
	public static function surl_encode($url)
	{
		$url = urlencode($url);
		$url = strtolower( $url );
		$url = str_replace( '%3a', '%2e3a', $url);
		$url = str_replace( '%2f', '%2e2f', $url);
		return $url;
	}
	
	public static function surl_decode($url)
	{
		$url = strtolower( $url );
		$url = str_replace( '%2e3a', '%3a', $url);
		$url = str_replace( '%2e2f', '%2f', $url);
		$url = urldecode($url);
		$url = str_replace( '.3a', ':', $url);
		$url = str_replace( '.2f', '/', $url);
		$url = strtolower( $url );
		return $url;
	}
	
	/*
	 * 检测是否是纯数字，不含小数点
	 */
	public static function checkNumber($number)
	{
		if(preg_match("/^\d{6,12}$/", $number))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	
	/*
	 * 检测输入字符串是否合法
	 * $opermode:get post,cookie
	 */
	public static function checkInput( $array_for_check, $opermode )
	{
		try 
		{
			$getfilter="'|(and|or)\\b.+?(>|<|=|in|like)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?Select|Update.+?SET|Insert\\s+INTO.+?VALUES|(Select|Delete).+?FROM|(Create|Alter|Drop|TRUNCATE)\\s+(TABLE|DATABASE)" ;  
			$postfilter="\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?Select|Update.+?SET|Insert\\s+INTO.+?VALUES|(Select|Delete).+?FROM|(Create|Alter|Drop|TRUNCATE)\\s+(TABLE|DATABASE)" ;  
			$cookiefilter="\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?Select|Update.+?SET|Insert\\s+INTO.+?VALUES|(Select|Delete).+?FROM|(Create|Alter|Drop|TRUNCATE)\\s+(TABLE|DATABASE)" ;  
			
			$filter = '';
			if( $opermode == 'get')
			{
				$filter = $getfilter;
			}
			else if( $opermode == 'post' )
			{
				$filter = $postfilter;
			}
			else if( $opermode == 'cookie' )
			{
				$filter = $cookiefilter;
			}
			else 
			{
				return false;
			}
			
			$b = true;
			foreach( $array_for_check as $key=>$value)
			{
				if (preg_match("/".$filter."/is", $value )==1)
				{  
	    			$b = false;
	    			break;  
				} 
			}
			
			return $b;
		}
		catch ( Exception $e )
		{
			canpointCommon::logError ( 'ErrorRow:' . $e->getLine() . ' ErrorMsg:' . $e->getMessage () . ' ErrorFile:' . $e->getFile() );
		}
		
	}

	/*
	 * 清除html标签,清除标签，并进行转义
	 */
	public static function clear_html( $str )
	{   
		try 
		{
			if( true )
			{
				$str = preg_replace( "@<script(.*?)</script>@is", "", $str ); 
				$str = preg_replace( "@<iframe(.*?)</iframe>@is", "", $str ); 
				$str = preg_replace( "@<style(.*?)</style>@is", "", $str ); 
				$str = preg_replace( "@<(.*?)>@is", "", $str ); 
			}
			$str = htmlspecialchars( $str, ENT_QUOTES );
			return $str;
		}
		catch ( Exception $e )
		{
			canpointCommon::logError ( 'ErrorRow:' . $e->getLine() . ' ErrorMsg:' . $e->getMessage () . ' ErrorFile:' . $e->getFile() );
		}
	}
	
	//函数名: compress_html 
	//参数: $string 
	//返回值: 压缩后的$string 
	public static function compress_html($string) 
	{ 
	    $string = str_replace("\r\n", '', $string); //清除换行符 
	    $string = str_replace("\n", '', $string); //清除换行符 
	    $string = str_replace("\t", '', $string); //清除制表符 
	    $pattern = array ( 
	                    "/> *([^ ]*) *</", //去掉注释标记 
	                    "/[\s]+/", 
	                    "/<!--[^!]*-->/", 
	                    "/\" /", 
	                    "/ \"/", 
	                    "'/\*[^*]*\*/'" 
	                    ); 
	    $replace = array ( 
	                    ">\\1<", 
	                    " ", 
	                    "", 
	                    "\"", 
	                    "\"", 
	                    "" 
	                    ); 
	    return preg_replace($pattern, $replace, $string); 
	}
	
	//获取文件后缀名函数
	public static function fileext($filename)
	{
		return substr ( strrchr ( $filename, '.' ), 1 );
	}
	
	//生成随机文件名函数   
	public static function random($length)
	{
		$hash = '';
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
		$max = strlen ( $chars ) - 1;
		mt_srand ( ( double ) microtime () * 1000000 );
		for($i = 0; $i < $length; $i ++)
		{
			$hash .= $chars [mt_rand ( 0, $max )];
		}
		return $hash;
	}
	
	
	public static function randomnums($length)
	{
		$hash = '';
		$chars = '0123456789';
		$max = strlen ( $chars ) - 1;
		mt_srand ( ( double ) microtime () * 1000000 );
		for($i = 0; $i < $length; $i ++)
		{
			$hash .= $chars [mt_rand ( 0, $max )];
		}
		return $hash;
	}

	
	/*
	 * 得到IP
	 */
	public static function getClientIP()
	{
		if (@$_SERVER["HTTP_X_FORWARDED_FOR"]) 
		$ip = $_SERVER["HTTP_X_FORWARDED_FOR"]; 
		else if (@$_SERVER["HTTP_CLIENT_IP"]) 
		$ip = $_SERVER["HTTP_CLIENT_IP"]; 
		else if (@$_SERVER["REMOTE_ADDR"]) 
		$ip = $_SERVER["REMOTE_ADDR"]; 
		else if (@getenv("HTTP_X_FORWARDED_FOR"))
		$ip = getenv("HTTP_X_FORWARDED_FOR"); 
		else if (@getenv("HTTP_CLIENT_IP")) 
		$ip = getenv("HTTP_CLIENT_IP"); 
		else if (@getenv("REMOTE_ADDR")) 
		$ip = getenv("REMOTE_ADDR"); 
		else 
		$ip = "127.0.0.1"; 
		return $ip; 
	}
	
	
	/*
	 * 图片缩略大小
	 */
	public function resizeImage( $imgurl, $maxwidth, $maxheight)
	{
		try
		{
			$src_im = imagecreatefromjpeg($imgurl);
			//$srcW = ImageSX($src_im); //获得图像的宽   
			//$srcH = ImageSY($src_im);
			
			$pic_width = ImageSX($src_im);
			$pic_height = ImageSY($src_im);
			
			if(($maxwidth && $pic_width > $maxwidth) || ($maxheight && $pic_height > $maxheight))
			{
				if($maxwidth && $pic_width>$maxwidth)
				{
					$widthratio = $maxwidth/$pic_width;
					$resizewidth_tag = true;
				}
			
				if($maxheight && $pic_height>$maxheight)
				{
					$heightratio = $maxheight/$pic_height;
					$resizeheight_tag = true;
				}
			
				if($resizewidth_tag && $resizeheight_tag)
				{
					if($widthratio<$heightratio)
					$ratio = $widthratio;
					else
					$ratio = $heightratio;
				}
			
				if($resizewidth_tag && !$resizeheight_tag)
				$ratio = $widthratio;
				if($resizeheight_tag && !$resizewidth_tag)
				$ratio = $heightratio;
				
				$newwidth = $pic_width * $ratio;
				$newheight = $pic_height * $ratio;
				
				$imgsize = array( 'width'=>$newwidth, 'height'=>$newheight );
				return $imgsize;
			}
						
		}
		catch ( Exception $e )
		{
			canpointCommon::logError ( $e->getMessage () );
		}
	}
	
	
	public static function imageSize( $width, $height, $maxWidth, $maxHeight)
	{
		$num4 = 0;
		$num5 = 0;
		$num = $maxWidth;
		$num2 = $maxHeight;
		$num3 = $num / $num2;
		$num6 = $width;
		$num7 = $height;
		$num8=0;
		if (($num6 > $num) || ($num7 > $num2))
		{
			if (($num6 / $num7) > $num3)
			{
				$num8 = $num6 / $num;
				$num4 = intval( $num6 / $num8 );
				$num5 = intval( $num7 / $num8 );
			}
			else
			{
				$num8 = $num7 / $num2;
				$num4 = intval($num6 / $num8);
				$num5 = intval($num7 / $num8);
			}
		}
		else
		{
			$num4 = $width;
			$num5 = $height;
		}
		
		$imgsize = array( 'width'=>$num4, 'height'=>$num5 );
		return $imgsize;
	}
	
	
	public static function getStrLen($str)
	{
		return strlen(mb_convert_encoding($str, "gb2312", "utf-8"));
	}
	
	/*
	 * uft-8格式字符串截取，中英混合不会出现乱码
		大写字母按两个字符计算
	 */
	public static function cutString($str, $length, $suffix=true)
	{
		
		$returnstr = '';
		$i = 0;
		$n = 0;
		$str_length = strlen($str); //字符串的字节数
		while (($n < $length) and ($i <= $str_length))
		{
			$temp_str = substr($str,$i,1);
			$ascnum = Ord($temp_str);	//得到字符串中第$i位字符的ascii码
			if ($ascnum >= 224)		//如果ASCII位高与224，
			{
				$returnstr = $returnstr.substr($str,$i,3); //根据UTF-8编码规范，将3个连续的字符计为单个字符         
				$i = $i+3;			//实际Byte计为3
				$n++;				//字串长度计1
			}
			elseif ($ascnum >= 192)	//如果ASCII位高与192，
			{
				$returnstr = $returnstr.substr($str,$i,2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
				$i = $i+2;			//实际Byte计为2
				$n++;				//字串长度计1
			}
			elseif ($ascnum >= 65 && $ascnum <= 90) //如果是大写字母，
			{
				$returnstr = $returnstr.substr($str,$i,1);
				$i = $i+1;			//实际的Byte数仍计1个
				$n++;				//但考虑整体美观，大写字母计成一个高位字符
			}
			else					//其他情况下，包括小写字母和半角标点符号，
			{
				$returnstr = $returnstr.substr($str,$i,1);
				$i = $i+1;			//实际的Byte数计1个
				$n = $n+0.5;			//小写字母和半角标点等与半个高位字符宽...
			}
		}
		//2个字符按1个长度计算
		if (((strlen($str)+mb_strlen($str,"UTF8"))/4 > $length) && ($suffix)){
		//if (($str_length > $length) && ($suffix)){
			$returnstr = $returnstr . "..";//超过长度时在尾处加上省略号
		}
		return $returnstr;
	}
	
	
	
	/*
	 * 格式化数字
	 */
	public static function formantNumber($str)
	{
		$temp = number_format($str, 2, '.', '');
		if(substr( $temp, -3, 3) == '.00')
		{
			$temp = substr( $temp, 0, -3);
		}
		
		return $temp;
	}
	

		
	/**
	* 说明：过滤HTML字串
	* 参数：
	* $str : 要过滤的HTML字串
	* $tag : 过滤的标签类型
	* $keep_attribute :
	* 要保留的属性,此参数形式可为
	* href
	* href,target,alt
	* array('href','target','alt')
	* filter($str,'a','href,target,alt');
	filter($str,'p','align');
	filter($str,'font','color,alt'); 
	*/
	public static function filter(&$str,$tag,$keep_attribute) 
	{
	
		//检查要保留的属性的参数传递方式
		if(!is_array($keep_attribute)) 
		{
			//没有传递数组进来时判断参数是否包含,号
			if(strpos($keep_attribute,',')) 
			{
				//包含,号时,切分参数串为数组
				$keep_attribute = explode(',',$keep_attribute);
			}else 
			{
				//纯字串,构造数组
				$keep_attribute = array($keep_attribute);
			}
		}
		
		//echo("·过滤[$tag]标签,保留属性:".implode(',',$keep_attribute).'<br>');
		
		//取得所有要处理的标记
		$pattern = "/<$tag(.*)<\/$tag>/i";
		preg_match_all($pattern,$str,$out);
		
		//循环处理每个标记
		foreach($out[1] as $key => $val) 
		{
			//取得a标记中有几个=
			$cnt = preg_split('/ *=/i',$val);
			$cnt = count($cnt) -1;
			
			//构造匹配正则
			$pattern = '';
			for($i=1; $i<=$cnt; $i++) 
			{
				$pattern .= '( .*=.*)';
			}
			//完成正则表达式形成,如/(<a)( .*=.*)( .*=.*)(>.*<\/a>/i的样式
			$pattern = "/(<$tag)$pattern(>.*<\/$tag>)/i";
			
			//取得保留属性
			$replacement = canpointCommon::match($pattern,$out[0][$key],$keep_attribute);
			
			//替换
			$str = str_replace($out[0][$key],$replacement,$str);
		}
		
		
		
		//2-------------------
		//有时候是 <link xxxxxxx /> 这样的样式
		//取得所有要处理的标记
		$pattern = "/<$tag(.*)\/>/i";
		preg_match_all($pattern,$str,$out);
		
		//循环处理每个标记
		foreach($out[1] as $key => $val) 
		{
			//取得a标记中有几个=
			$cnt = preg_split('/ *=/i',$val);
			$cnt = count($cnt) -1;
			
			//构造匹配正则
			$pattern = '';
			for($i=1; $i<=$cnt; $i++) 
			{
				$pattern .= '( .*=.*)';
			}
			//完成正则表达式形成,如/(<a)( .*=.*)( .*=.*)(>.*<\/a>/i的样式
			$pattern = "/(<$tag)$pattern(>.*\/>)/i";
			
			//取得保留属性
			$replacement = canpointCommon::match($pattern,$out[0][$key],$keep_attribute);
			
			//替换
			$str = str_replace($out[0][$key],$replacement,$str);
		}
		
		return $str;
	}


	/**
	* 说明：构造标签,保留要保留的属性
	* 参数：$reg : pattern,preg_match的表达式
	* $str : string,html字串
	* $arr : array,要保留的属性
	* 返回：
	* 返回经保留处理后的标签,如
	* <A href='http://www.e.com' target=_blank alt=e e e>e.com</A>
	*/
	public static function match($reg,&$str,$arr) 
	{
	
		//match
		preg_match($reg,$str,$out);
		
		//取出保留的属性
		$keep_attribute = '';
		foreach($arr as $k1=>$v1) 
		{
			//定义的要保留的属性的数组
			foreach($out as $k2=>$v2) 
			{
				//匹配=后的数组
				$attribute = trim(substr($v2,0,strpos($v2,'=')));
				//=前面的
				if($v1 == $attribute) 
				{
					//要保留的属性和匹配的值的=前的部分相同
					$keep_attribute .= $v2;
					//保存此匹配部分的值
				}
			}
		
		}
		
		//构造返回值,结构如:<a href=xxx target=xxx class=xxx>aadd</a>
		$keep_attribute = $out[1].$keep_attribute.($out[count($out)-1]);
		//返回值
		Return $keep_attribute;
	} 
	
	
	/*
	 * 格式化段落
	 */
	public static function getHtmlTextToBrPaddingleft2Words($res, $css='ptext') 
	{
	
		$str_temp = nl2br( $res );
		$str_temp = str_replace( '<br />', '</p><p class="' . $css . '">', $str_temp);
		$str_temp = '<p class="' . $css . '">' . $str_temp . '</p>';
		//返回值
		Return $str_temp;
	} 
	
	
		/*
	 * 得到整数
	 */
	public static function getInt( $inputInt ) 
	{
		$int_temp = 0;
		$inputInt = trim( $inputInt );
		if( $inputInt == '' )
		{
			$int_temp = 0;
		}
		
		if( preg_match('/^\d*$/',$inputInt) )
		{
			$int_temp = (int)$inputInt;
		}
		else 
		{
			$int_temp = 0;
		}
		
		return $int_temp;
	} 
	
	

	/*
	 * 检测email的正确性
	 */
	public static function emailcheck($email)
	{
		$ret=false;
		if(strstr($email, '@') && strstr($email, '.'))
		{
			//if(eregi("^([_a-z0-9]+([._a-z0-9-]+)*)@([a-z0-9]{1,}(.[a-z0-9-]{1,})*.[a-z]{2,3})$", $email))
			if(preg_match('/^[a-z0-9_\-]+(\.[_a-z0-9\-]+)*@([_a-z0-9\-]+\.)+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)$/',$email) )
			{
				$ret=true;
			}
		}
		return $ret;
	}

	
		
	/*
	 * 得到操作系统类型
	 * linux
	 * windows
	 */
	public static function getOs()
	{
		if( PATH_SEPARATOR == ':')
		{
			return "linux";
		}
		else 
		{
			return "windows";
		}
	}
	
	
	
	
	/*
	 * 对数组按照某一列进行排序
	 */
	public static function array_sort($arr,$keys,$type='asc')
	{ 
		$keysvalue = $new_array = array();
		foreach ($arr as $k=>$v)
		{
			$keysvalue[$k] = $v[$keys];
		}
		if($type == 'asc')
		{
			asort($keysvalue);
		}else
		{
			arsort($keysvalue);
		}
			reset($keysvalue);
		foreach ($keysvalue as $k=>$v)
		{
			$new_array[$k] = $arr[$k];
		}
		return $new_array; 
	}
	
	
	
	
	/*
	 * 递归创建文件夹，如果已经有此文件夹，则返回true
	 */
	public static function create_folders($dir)
	{ 
		return is_dir($dir) or ( canpointCommon::create_folders( dirname($dir) ) and mkdir($dir, 0777) ); 
	}
	

	
	/*
	 * 加密数据
	 */
	public static function encrypt( $str )
	{
		return str_replace('+', '_', str_replace( '/', '-', trim( base64_encode( Des::encrypt( $str, 'canp') ) ) ) );
	}
	
	/*
	 * 解密数据
	 */
	public static function decrypt( $str )
	{
		return trim( Des::decrypt( base64_decode( str_replace( '_', '+', str_replace( '-', '/', $str) ) ), 'canp' ) );
	}
	
	


}

?>