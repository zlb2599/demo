<?php

use Think\Model;

/** * ***************************************************************************
 * 用户操作模型
 * @author dushasha <1845825214@qq.com>
 * @copyright (c) 2014, dushasha.
 * @since version 1.0
 * @createdtime  2014-11-26
 * @link http://e.canpoint.net 全品学堂
 * ******************************************************************************* */
class StudyModel extends Model {

    protected $dbName = 'canpoint';
    //操作表名
    protected $tableName = 'u_study';

    /* ---------------------------
     * 根据用户uid密码获取信息
     * @return array
      ---------------------------- */

    public function get_by_uid_pass($uid, $passwd) {
        if (strstr($uid, '@')) {
            $t = $this->where("u_email='" . $uid . "' and u_pass='" . $passwd . "'")->select();
        } else {
            if (strlen($uid) == 11 && preg_match("/^1[0-9]{10}$/", $uid)) {
                $t = $this->where("u_hphone='" . $uid . "' and u_pass='" . $passwd . "'")->select();
                if (!$t) {
                    $t = $this->where("u_id='" . $uid . "' and u_pass='" . $passwd . "'")->select();
                }
            } else {
                $t = $this->where("u_id='" . $uid . "' and u_pass='" . $passwd . "'")->select();
            }
        }
        return $t;
       // return $this->getLastSql();

//        if(is_numeric($uid)){
//            $t = $this->where("(u_hphone='" . $uid . "') and u_pass='" . $passwd . "'")->select();
//        }else if(strstr($uid, '@')){
//            $t = $this->where("(u_email='" . $uid . "') and u_pass='" . $passwd . "'")->select();
//        }else{
//            $t = $this->where("(u_id='".$uid."') and u_pass='" . $passwd . "'")->select();
//        }
        // $t = $this->where("(u_id='" . $uid . "' or u_email='" . $uid . "' or  u_hphone='" . $uid . "') and u_pass='" . $passwd . "'")->select();
    }

    /* ---------------------------
     * 用户uid统计总数量
     * @return int
      ---------------------------- */

    public function get_count_by_uid($uid) {
        $count = $this->where("u_id='" . $uid . "'")->count();
        return $count;
    }

    /* ---------------------------
     * 邮箱email统计总数量
     * @return int
      ---------------------------- */

    public function get_count_by_email($email) {
        $count = $this->where("u_email='" . $email . "'")->count();
        return $count;
    }

    /* ---------------------------
     * 根据用户名uid和邮箱email统计总数量
     * @return int
      ---------------------------- */

    public function get_count_by_uid_email($uid, $email) {
        $count = $this->where("u_id!='" . $uid . "' and u_email='" . $email . "'")->count();
        return $count;
    }

    /* ---------------------------
     * 根据手机号tel统计总数量
     * @return int
      ---------------------------- */

    public function get_count_by_tel($tel) {
        $count = $this->where("u_hphone='" . $tel . "'")->count();
        return $count;
    }

    /* ---------------------------
     * 根据uid获取信息
     * @return array
      ---------------------------- */

    public function get_by_uid($u_id) {
        $arr = $this->where("u_id='" . $u_id . "'")->find();
        return $arr;
    }

    /* ---------------------------
     * 根据id获取信息
     * @return array
      ---------------------------- */

    public function get_by_id($id) {
        $arr = $this->where("id='" . $id . "'")->find();
        return $arr;
    }

    /* ---------------------------
     * 根据id获取签到天数u_sign
     * @return int
      ---------------------------- */

    public function get_sign_by_id($id) {
        $usign = $this->where("id='" . $id . "'")->getField('u_sign');
        return $usign;
    }

}

?>
