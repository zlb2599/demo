<?php
/***********************************************************************************
 * Copyright (c) 2005-2014
 * All rights reserved.
 *
 * Author:gaohaifeng
 * Editor:$LastChangedBy: haifeng $
 * Email:haifeng@hnusoft.com
 * Version:$Id: IndexAction.class.php 57 2014-11-18 05:06:49Z haifeng $
 * Description:
 ***********************************************************************************/
?>
<?php
use Think\Model;
/*
 * 搜索model
 */
 class SearchModel extends Model
 {
      protected $dbName = 'canpoint';
 	protected $tableName = 'so_keywd';
 	/**
     * 得到联想关键字
     * Author：haifeng
     * date：2014-11-19
     */
 	public function getSearchKeyWord( $keywd )
 	{
 		//1.课程
 		$sql = 'select k_kwd, k_word, k_type, k_count from ' . $this->tablePrefix . $this->tableName . ' where k_count>0 and k_type=1 and k_kwd like \'' . $keywd . '%\' group by k_word order by k_count desc  limit 10';
 		$ds = $this->query($sql);
 		
 		//2. 老师
 		$sql = 'select k_kwd, k_word, k_type, k_count from ' . $this->tablePrefix . $this->tableName . ' where k_count>0 and k_type=2 and k_kwd like \'' . $keywd . '%\' group by k_word order by k_count desc  limit 10';
 		$ds2 = $this->query($sql);
 		$result = array_merge($ds, $ds2);
 		for( $i=0; $i<count($result);$i++ )
 		{
 			$result[$i]['k_word2'] = canpointCommon::cutString($result[$i]['k_word'], 12);
 		}
 		return $result;
 	}
 	
 	
	public function dealKey()
 	{
 		$sql = 'select * from canpoint_live.qp_zi_keytmp';
 		canpointCommon::logDegub( $sql );
 		$ds = $this->query($sql);
 		//print_r( $ds );
 		
 		for( $i=0; $i<count($ds);$i++ )
 		{
 			echo "insert into qp_so_keywd( k_kwd, k_word, k_type, k_count, k_sort) values( '" . $ds[$i]['key_word'] . "', '" . $ds[$i]['key_word'] . "', 1, 100 ,1 );" . "\r\n";
 			echo "insert into qp_so_keywd( k_kwd, k_word, k_type, k_count, k_sort) values( '" . $ds[$i]['key_pinyin'] . "', '" . $ds[$i]['key_word'] . "', 1, 100 ,1 );" . "\r\n";
 			echo "insert into qp_so_keywd( k_kwd, k_word, k_type, k_count, k_sort) values( '" . $ds[$i]['key_first_pinyin'] . "', '" . $ds[$i]['key_word'] . "', 1, 100 ,1 );" . "\r\n";
 			//echo $ds[$i]['key_word'] . chr(9) . "1500\r\n";
 		}
 		
 		return $ds;
 	}
 	
 	
 	
 	/**
     * 得到关键字列表，课程关键字
     * Author：haifeng
     * date：2014-12-27
     */
 	public function getKeyword()
 	{
 		//$sql = 'select id, k_word from qp_so_keywd where k_type=1 limit 10';
 		$sql = 'select id, k_word from qp_so_keywd where k_type=1 and k_count=-1 limit 10';
 		canpointCommon::logDegub( $sql );
 		$ds = $this->query($sql);
 		return $ds;
 	}
 	
 	/*
 	 * 刷新关键字搜索结果数量
 	 */
 	public function setKeywordcount( $id, $count)
 	{
 		$sql = 'update qp_so_keywd set k_count=' . $count . ' where id=' . $id;
 		$this->execute($sql);
 	}
 	
 	
 	
 	
 }