<?php
use Think\Model;
/*
 * 生成关键字
*/
class CateModel extends Model
{

     protected $dbName = 'canpoint';
    function get_cate_key()
    {
        $cate = M('zi_category', 'canpoint.qp_');
        $cate_arr = $cate->field("cate_type,cate_00,cate_01,cate_02,cate_03,cate_04,cate_05,cate_06,cate_07")->select();
        return $cate_arr;
    }

    function add_cate_key($key)
    {
        // 判断全是中文直接插入
//         if (! eregi("/^[\x{4e00}-\x{9fa5}]+/u", $key))
//         {            
//             $this->add_keyword($key);
//         } else
//         {
            // print_r($key);
            
            // 提取中文
            
            $reg = '/[\x{4e00}-\x{9fa5}]+/u';
            preg_match_all($reg, $key, $result);
            if (is_array($result))
            {
                $result=array_unique($result[0]);
                foreach ($result as $v)
                {
                    $this->add_keyword($v);
                }
            }
            
            // print_r(is_array($result));
//         }
    }
    var $key_word;

    protected function add_keyword($str)
    {
        if(strlen($str)>3)//判断一个汉字的不处理
        {            
            if(!in_array($str,$this->key_word,true))
            {
               
                $this->key_word[]=$str;
                $data['key_word'] = $str;
                $data['key_pinyin'] = pinyin($str);
                $data['key_first_pinyin'] = pinyin($str, true);
                
                $logFile = $_SERVER ['DOCUMENT_ROOT'] . "/Application/Runtime/Logs/key_word.txt" ;
                $msg = $data['key_word']."  ".$data['key_pinyin']."  ".$data['key_first_pinyin'];
                $msg = $msg . "\r\n";
                file_put_contents ( $logFile, $msg, FILE_APPEND );
            }
           
        }
    }
}
?>