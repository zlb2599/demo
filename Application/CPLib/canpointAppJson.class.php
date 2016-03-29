<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

class canpointAppJson {

    protected static $data;
    protected static $effectedTaskCount = 0;
    protected static $succeed = 1;
    protected static $PageArr = array(0, 0, 0);
    protected static $u_error = array();

    public static function ResultShell() {
        header("content-type:text/html;charset=utf-8");
        self::$effectedTaskCount = 1;
        if (!empty(self::$data))
            $arr["data"] = self::$data;
        else
            $arr["data"] = array();
        // $arr["data"]=self::$data;

        $arr["task"] = self::$effectedTaskCount;
        if (!empty(self::$u_error)) {
            $arr["status"] = self::$u_error;
        } else {
            self::ResultStatus();
            $arr["status"] = self::$u_error;
        }
        $arr["page"] = self::PageInfo();
        return json_encode($arr);
// // 	//消息主类
//      public IActionResult data{ get; set; }
// 		//系统任务类，用于支持任务的系统校验，可为空
// 		public TaskResult task{ get; set; }
// 		//状态类
//      public ResultStatus status{ get; set; }	
    }

    // protected  function TaskResult()
    //{
    //return self::$effectedTaskCount;
    //每次action是否影响任务，没有时是0；
    // public int effectedTaskCount { get; set; }
    //}
    //错误信息

    protected function ResultStatus($_succeed = 1, $_error_code = "", $_message = "", $_sessionid = "") {
        if ($_succeed == 1) {
            $arr["succeed"] = $_succeed;
        } else {
            $arr["succeed"] = 0;
            $arr["error_code"] = $_error_code;
            $arr["message"] = $_message;
            $arr["sessionid"] = $_sessionid;
        }
        self::$u_error = $arr;
//   	//成功标记 成功为1
//      public int succeed{ get; set; }
// 		//错误号 ，体系商定，成功是o
// 		public int error_code { get; set; }
// 		//消息，传输错误或异常等消息
//      public string message { get; set; }
// 		//更新会话id时用，下一次通信需要使用的会话，一般使用GUID
// 		//不建议每次通信都更新，因为会增加服务器压力
// 		public string sessionid{ get; set; }
    }

    //分页

    protected function PageInfo() {
        $arr["total"] = self::$PageArr["total"];
        $arr["count"] = self::$PageArr["count"];
        $arr["more"] = self::$PageArr["more"];
        return $arr;
// 	    //全部记录数
//         public int total{ get; set; }
// 		//本页数量
//         public int count{ get; set; }
// 		//是否有下一页
//         public int more{ get; set; }
    }

    /**
     * 
     * @param string $setdata 数据主体
     * @return string
     */
    static function setData($setdata = "") {
        self::$data = $setdata;
        return self::ResultShell();
    }

    /**
     * 
     * @param unknown $_error_code 错误编号
     * @param unknown $_message  错误具体信息
     * @param string $_sessionid GUID
     * @return string
     */
    static function setError($_error_code, $_message, $_sessionid = "") {
        self::ResultStatus(0, $_error_code, $_message, $_sessionid);
        return self::ResultShell();
    }

    /**
     * 
     * @param number $total  全部记录数
     * @param number $count  本页数量
     * @param number $more   是否有下一页
     */
    static function setPage($total = 0, $count = 0, $more = 0) {
        self::$PageArr["total"] = $total;
        self::$PageArr["count"] = $count;
        self::$PageArr["more"] = $more;
    }

}