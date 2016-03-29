<?php

use Think\Model;

class MoneyModel extends Model {
    /*
     * 得到用户头像
     */

    public function getUserimg($nj = '', $sex = '', $size = 'mid') {
        if ($sex == '男') {
            $sex = 'm';
        } else {
            $sex = 'f';
        }

        if (empty($nj)) {
            if (empty($sex)) {
                return 'style_img/default_img/tyf.jpg';
            }
            return 'style_img/default_img/ty' . $sex . '.jpg';
        }
        if (empty($sex) || $sex == 'on') {
            return 'style_img/default_img/tyf.jpg';
        }
        if ($nj >= 10) {
            $nj = 'gz';
        } else if ($nj < 3) {
            $nj = 3;
        }
        return 'http://pay.canpoint.net' . 'style_img/default_img/' . $nj . $sex . '.jpg';
    }

    public function getCanpointUser($guid) {
        //1.
        if (!is_numeric($guid) || strlen($guid) != 9) {
            return null;
        }

        //2.
        $tableindex = substr($guid, -2);
        $sql = "select u_nickname,u_nianji, avatar_file_url, u_fpoint,u_rpoint,u_qdou  from " . C("DB_CANPOINTUSER") . ".qp_user_$tableindex where u_guid=$guid limit 1";

        $ds = $this->query($sql);
        if (count($ds) == 1) {
            if (empty($ds[0]['avatar_file_url'])) {
                $ds[0]['avatar_file_url'] = $this->getUserimg($ds[0]['u_nianji'], $ds[0]['u_sex']);
            }

            if ($ds[0]['u_sex'] == '男') {
                $ds[0]['avatar_sex'] = '';
            } else if ($ds[0]['u_sex'] == '女') {
                $ds[0]['avatar_sex'] = 'u_woman_icon';
            } else {
                $ds[0]['avatar_sex'] = 'u_woman_icon';
            }
        }
        return empty($ds) ? null : $ds[0];
    }

    /*
     * 购物车数量
     * @param $guid
     * @return int
     */

    public function cartNums($guid) {
        $data = M('zi_keorder');
        $cartnums = $data->where("user_id ='$guid' and ke_stat='1'")->count();
        return $cartnums;
        //return $data->getLastSql();
    }

    /*
     * 全品币—我的里程碑
     * 
     */

    public function rpoint_milepost($guid, $flag) {
        //$re = array("N", "N", "N", "N", "N", "N");
        $re = array(
            '0' => array('yn' => "N", 'tip' => "初窥堂奥：第一次充值即可点亮此里程碑"),
            '1' => array('yn' => "N", 'tip' => "略有小成：累计充值满200元即可点亮此里程碑"),
            '2' => array('yn' => "N", 'tip' => "渐入佳境：累计充值满500元即可点亮此里程碑"),
            '3' => array('yn' => "N", 'tip' => "炉火纯青：累计充值满1000元即可点亮此里程碑"),
            '4' => array('yn' => "N", 'tip' => "渐入佳境：累计充值满5000元即可点亮此里程碑"),
            '5' => array('yn' => "N", 'tip' => "渐入佳境：累计充值满10000元即可点亮此里程碑"),
        );
        #初窥堂奥：第一次充值即可点亮此里程碑
        $count = M("ka_jihuolog")->where("k_userid=$guid and k_type=$flag")->count();
        if ($count) {
            $re['0']['yn'] = "Y";
        }

        $two = M("ka_jihuolog")->where("k_userid=$guid and k_type=3")->sum("k_mianzi");

        #略有小成：累计充值满200元即可点亮此里程碑
        if ($two >= 200) {
            $re['1']['yn'] = "Y";
        }

        #渐入佳境：累计充值满500元即可点亮此里程碑
        if ($two >= 500) {
            $re['2']['yn'] = "Y";
        }

        #炉火纯青：累计充值满1000元即可点亮此里程碑
        if ($two >= 1000) {
            $re['3']['yn'] = "Y";
        }

        #渐入佳境：累计充值满5000元即可点亮此里程碑
        if ($two >= 5000) {
            $re['4']['yn'] = "Y";
        }

        #渐入佳境：累计充值满10000元即可点亮此里程碑
        if ($two >= 10000) {
            $re['5']['yn'] = "Y";
        }

        return $re;
    }

    /*
     * 全品豆—我的里程碑
     */

    public function qdou_milepost($guid) {
        $tableindex = substr($guid, -2);
        $table = C("DB_CANPOINTUSER") . ".user_$tableindex";
        $study = M($table);
        $qdou = $study->where("u_guid=$guid")->getField("u_qdou");
        $re = array(
            '0' => array('yn' => "N", 'tip' => "初学者：获得的全品豆累计达到 1000个即可点亮此里程碑"),
            '1' => array('yn' => "N", 'tip' => "游学者：获得的全品豆累计达到 2000个即可点亮此里程碑"),
            '2' => array('yn' => "N", 'tip' => "俊才：获得的全品豆累计达到 5000个即可点亮此里程碑"),
            '3' => array('yn' => "N", 'tip' => "智者：获得的全品豆累计达到 10000个即可点亮此里程碑"),
            '4' => array('yn' => "N", 'tip' => "尊者：获得的全品豆累计达到 50000个即可点亮此里程碑"),
            '5' => array('yn' => "N", 'tip' => "大智者：获得的全品豆累计达到 100000个即可点亮此里程碑"),
        );

        if ($qdou >= 1000) {
            $re[0]['yn'] = "Y";
        }
        if ($qdou >= 2000) {
            $re[1]['yn'] = "Y";
        }
        if ($qdou >= 5000) {
            $re[2]['yn'] = "Y";
        }
        if ($qdou >= 10000) {
            $re[3]['yn'] = "Y";
        }
        if ($qdou >= 50000) {
            $re[4]['yn'] = "Y";
        }
        if ($qdou >= 100000) {
            $re[5]['yn'] = "Y";
        }
        //return $study->getLastSql();
        return $re;
    }

    /*
     * 用户信息
     * @param $guid
     * @return array
     */

    public function getUserByGuid($guid,$field) {
        $tableindex = substr($guid, -2);
        $table = C("DB_CANPOINTUSER") . ".user_$tableindex";
        $study = M($table);
        $info = $study->where("u_guid=$guid")->field($field)->find();
        return $info;
    }

}
