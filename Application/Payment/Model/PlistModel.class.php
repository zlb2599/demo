<?php

use Think\Model;

class PlistModel extends Model {

    function getlist_user($idstr, $guid) {
        $tableIndex = substr($guid, -2);

        return $this->table($this->dbName . "." . $this->tablePrefix . "user_" . $tableIndex)->where("u_guid in ({$idstr})")->getField('u_guid,u_hphone,u_email');
    }

}
