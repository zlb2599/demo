<?php
/***********************************************************************************
 * Copyright (c) 2005-2014
 * All rights reserved.
 *
 * Author:jinxiaojia
 * Editor:$LastChangedBy: jinxiaojia $
 * Email:jinxiaojia@aliyun.com
 * Version:$Id: $Keytocourse.class.php 57 2014-11-18 05:06:49Z jinxiaojia $
 * Description:
 ***********************************************************************************/
?>
<?php

use Think\Model;
/*
 * 生成表单model
 */
class CourseModel extends Model
{
     protected $dbName = 'canpoint';

    /**
     * 生成表单
     *
     * @author jinxiaojia@aliyun.com
     * @since 2014-12-11
     * @return bool
     */
    function key_to_so_course()
    {
        // 1.从分类表中获取课ID列表
        $course_lists = M('zi_category')->where(' ke_id >0 AND cate_dep=5 ')->order('ke_id')->select();
        // 2.列表
        $kes = $this->get_kes();
//          var_dump($kes);
        $nianji = $this->get_nianji();
        $xueduan = $this->get_xuduan();
        $type = $this->get_leixing();
        $xueke = $this->get_xueke();
        // 3.封装
        
        // 用label来控制 叠加
        $label = 0;
        $data = array();
       
        foreach ($course_lists as $v)
        {
            
            if ($label != $v['ke_id'])
            {
                //当标记和上一次不是一个课时再写入文件
                var_dump($data);
                if ($data)
                {
                    $logFile = $_SERVER['DOCUMENT_ROOT'] . "/Application/Runtime/Logs/key_course.txt";
                    $msg = implode('|', $data);
                    $msg = $msg . "\r\n";
                    file_put_contents($logFile, $msg, FILE_APPEND);
                }
                
                $data['c_courseid'] = $v['ke_id'];
                $data['c_title'] = trim($v['cate_07']);
                $data['c_img'] = $kes[$v['ke_id']]['x_url'];
                $data['c_context'] = str_replace("\r\n","",preg_replace("/<(.*?)>/", "", $kes[$v['ke_id']]['content']));
                $data['c_grade_id'] = $v['cate_00'];
                $data['c_grade_name'] = $nianji[$v['cate_00']];
                $data['c_earningstages_id'] = $v['cate_01'];
                $data['c_earningstages_name'] = $xueduan[$v['cate_01']];
                $data['c_course_id'] = $v['cate_02'];
                $data['c_course_name'] = $xueke[$v['cate_02']];
                $data['c_teacher_cid'] = $kes[$v['ke_id']]['teacher_id'];
                $data['c_teacher_name'] = $kes[$v['ke_id']]['js_01'];
                $data['c_status'] = 1;
                $data['c_course_type'] = $type[$v['cate_type']];
                $data['c_course_edition'] = $v['cate_03'];
                $data['c_course_cata'] = $v['cate_04'];
                $data['c_course_unit'] = $v['cate_05'];
                $data['c_course_calss'] = $v['cate_06'];
                $label = $v['ke_id'];
               
                
            }
            else
            {
                $data['c_grade_name'] .= ',' . $nianji[$v['cate_00']];
                $data['c_earningstages_name'] .= ',' . $xueduan[$v['cate_01']];
                $data['c_course_name'] .= ',' . $xueke[$v['cate_02']];
                $data['c_course_type'] .= ',' . $type[$v['cate_type']];
                $data['c_course_edition'] .= ',' . $v['cate_03'];
                $data['c_course_cata'] .= ',' . $v['cate_04'];
                $data['c_course_unit'] .= ',' . $v['cate_05'];
                $data['c_course_calss'] .= ',' . $v['cate_06'];
            }
        }
    }
    
    /**
     * 生成表单
     *
     * @author jinxiaojia@aliyun.com
     * @since 2014-12-25
     * @return bool
     */
    function key_to_so_course_gz()
    {
        // 1.从分类表中获取课ID列表
        $course_lists = M('zi_ke')->where(' cate_01=3 ')->order('id')->select();
        // 2.列表
        $kes = $this->get_kes();
        //          var_dump($kes);
        $nianji = $this->get_nianji();
        $xueduan = $this->get_xuduan();
        $type = $this->get_leixing();
        $xueke = $this->get_xueke();
        // 3.封装
    
        // 用label来控制 叠加
        $label = 0;
        $data = array();
         
        foreach ($course_lists as $v)
        {
    
            if ($label != $v['id'])
            {
                //当标记和上一次不是一个课时再写入文件
                var_dump($data);
                if ($data)
                {
                    $logFile = $_SERVER['DOCUMENT_ROOT'] . "/Application/Runtime/Logs/key_course_gz.txt";
                    $msg = implode('|', $data);
                    $msg = $msg . "\r\n";
                    file_put_contents($logFile, $msg, FILE_APPEND);
                }
    
                $data['c_courseid'] = $v['id'];
                $data['c_title'] = trim($v['title']);
                $data['c_img'] = $v['x_url'];
                $data['c_context'] = str_replace("\r\n","",preg_replace("/<(.*?)>/", "", $kes[$v['id']]['content']));
                $data['c_grade_id'] = $v['cate_00'];
                $data['c_grade_name'] = $nianji[$v['cate_00']];
                $data['c_earningstages_id'] = $v['cate_01'];
                $data['c_earningstages_name'] = $xueduan[$v['cate_01']];
                $data['c_course_id'] = $v['cate_02'];
                $data['c_course_name'] = $xueke[$v['cate_02']];
                $data['c_teacher_cid'] = $kes[$v['id']]['teacher_id'];
                $data['c_teacher_name'] = $kes[$v['id']]['js_01'];
                $data['c_status'] = 1;
                $data['c_course_type'] = $type[$v['cate_type']];
                $data['c_course_edition'] = $v['cate_03'];
                $data['c_course_cata'] = $v['cate_04'];
                $data['c_course_unit'] = $v['cate_05'];
                $data['c_course_calss'] = $v['cate_06'].$v['cate_07'];
                $label = $v['id'];
                 
    
            }
            else
            {
                $data['c_grade_name'] .= ',' . $nianji[$v['cate_00']];
                $data['c_earningstages_name'] .= ',' . $xueduan[$v['cate_01']];
                $data['c_course_name'] .= ',' . $xueke[$v['cate_02']];
                $data['c_course_type'] .= ',' . $type[$v['cate_type']];
                $data['c_course_edition'] .= ',' . $v['cate_03'];
                $data['c_course_cata'] .= ',' . $v['cate_04'];
                $data['c_course_unit'] .= ',' . $v['cate_05'];
                $data['c_course_calss'] .= ',' . $v['cate_06'];
            }
        }
    }
    
	function key_to_so_course2()
    {
        // 1.从分类表中获取课ID列表
        $course_lists = M('zi_category')->where(' ke_id >0 AND cate_dep=5 ')->order('ke_id')->select();
        // 2.列表
        $kes = $this->get_kes();
//          var_dump($kes);
        $nianji = $this->get_nianji();
        $xueduan = $this->get_xuduan();
        $type = $this->get_leixing();
        $xueke = $this->get_xueke();
        // 3.封装
        
        // 用label来控制 叠加
        $label = 0;
        $data = array();
       
        foreach ($course_lists as $v)
        {
            
            if ($label != $v['ke_id'])
            {
                //当标记和上一次不是一个课时再写入文件
                var_dump($data);
                if ($data)
                {
                    $logFile = $_SERVER['DOCUMENT_ROOT'] . "/Application/Runtime/Logs/key_course2.txt";
                    $msg = implode('|', $data);
                    $msg = $msg . "\r\n";
                    file_put_contents($logFile, $msg, FILE_APPEND);
                }
                
                $data['c_courseid'] = $v['ke_id'];
                $data['c_title'] = trim($v['cate_07']);
                $data['c_img'] = $kes[$v['ke_id']]['x_url'];
                $data['c_context'] = str_replace( "\r\n","",preg_replace("/<(.*?)>/", "", $kes[$v['ke_id']]['content']));
                $data['c_grade_id'] = $v['cate_00'];
                $data['c_grade_name'] = $nianji[$v['cate_00']];
                $data['c_earningstages_id'] = $v['cate_01'];
                $data['c_earningstages_name'] = $xueduan[$v['cate_01']];
                $data['c_course_id'] = $v['cate_02'];
                $data['c_course_name'] = $xueke[$v['cate_02']];
                $data['c_teacher_cid'] = $kes[$v['ke_id']]['teacher_id'];
                $data['c_teacher_name'] = $kes[$v['ke_id']]['js_01'];
                $data['c_status'] = 1;
                $data['c_course_type'] = $type[$v['cate_type']];
                $data['c_course_edition'] = $v['cate_03'];
                $data['c_course_cata'] = $v['cate_04'];
                $data['c_course_unit'] = $v['cate_05'];
                $data['c_course_calss'] = $v['cate_06'];
                $label = $v['ke_id'];
               
                
            }
            else
            {
                $data['c_grade_name'] .= ',' . $nianji[$v['cate_00']];
                $data['c_earningstages_name'] .= ',' . $xueduan[$v['cate_01']];
                $data['c_course_name'] .= ',' . $xueke[$v['cate_02']];
                $data['c_course_type'] .= ',' . $type[$v['cate_type']];
                $data['c_course_edition'] .= ',' . $v['cate_03'];
                $data['c_course_cata'] .= ',' . $v['cate_04'];
                $data['c_course_unit'] .= ',' . $v['cate_05'];
                $data['c_course_calss'] .= ',' . $v['cate_06'];
            }
        }
    }
    
    
    

    /**
     * 获取课列表
     *
     * @author jinxiaojia@aliyun.com
     * @since 2014-12-11
     * @return array
     */
    protected function get_kes()
    {
        $arr = M('zi_ke')->field("id,content,x_url,js_01,js_02")->select();
        
        $teachers = $this->get_tecchers();
        
        foreach ($arr as $k => $v)
        {
            $arr[$k]['teacher_id'] = $teachers[$v['js_02']];
        }
        
        foreach ($arr as $k => $v)
        {
            
            $re_all[$v['id']] = $v;
        }
        return $re_all;
    }

    /**
     * 获取老师列表
     *
     * @author jinxiaojia@aliyun.com
     * @since 2014-12-11
     * @return array
     */
    protected function get_tecchers()
    {
        $arr = M('u_techer')->field("id,u_id")->select();
        foreach ($arr as $v)
        {
            $re_all[$v['u_id']] = $v['id'];
        }
        return $re_all;
    }

    /**
     * 获取年级
     *
     * @author jinxiaojia@aliyun.com
     * @since 2014-12-11
     * @return array
     */
    protected function get_nianji()
    {
        $arr = M('zi_nianji')->field("id,nianji")->select();
        foreach ($arr as $v)
        {
            $re_all[$v['id']] = $v['nianji'];
        }
        return $re_all;
    }

    /**
     * 获取学段
     *
     * @author jinxiaojia@aliyun.com
     * @since 2014-12-11
     * @return array
     *
     */
    protected function get_xuduan()
    {
        return array(
            "1" => "小学",
            "2" => "初中",
            "3" => "高中"
        );
    }

    /**
     * 获取学科
     * $param
     *
     * @author jinxiaojia@aliyun.com
     * @since 2014-12-11
     * @return array
     *
     */
    protected function get_xueke()
    {
        $arr = M('zi_xueduan')->field("id,x_ke")->select();
        foreach ($arr as $v)
        {
            $re_all[$v['id']] = $v['x_ke'];
        }
        return $re_all;
    }

    /**
     * 获取类型
     *
     * @author jinxiaojia@aliyun.com
     * @since 2014-12-11
     * @return array
     *
     */
    protected function get_leixing()
    {
        return array(
            "1" => "教材同步",
            "2" => "专题",
            "3" => "考试",
            "4" => "单元复习",
            "5" => "期中复习",
            "6" => "期末复习"
        );
    }
}

?>
