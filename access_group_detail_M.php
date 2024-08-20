<?php
/** 
 * ePassND1000のアクセス権限設定マッピング一覧ファイル
 *
 * @author  張新星 * @since   2007-09-24
 * @version 1.0
*/

// 指定されたファイルを読み込む
$G_APPPATH_M = explode("mdl",__FILE__);
require_once $G_APPPATH_M[0]."/lib/db.php";
require_once $G_APPPATH_M[0]."/lib/log.php";

class Access_group_detail_M
{ 
	/**
	* データの実例
	*/
	var $objDb = null;
	
	/**
	* 関数名:__construct
	* コンストラクタ
	*
	*/
	function __construct()
	{
		try{
			$this->objDb = new Db_Pgsql();
			ToLog::ToLogs(DEBUG,__FILE__,__LINE__,"実例");
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
			exit;
		}
	}
	
	/**
	* 関数名: Access_control_detail_M
	* コンストラクタ.
	* 
	*/
	function Access_group_detail_M()
	{
		try{
			$this->__construct();
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: readGroup
	* データの獲得
	* @param			$groupName			グループ名
	* @return Array   $List
	*/
	function readGroup($groupName)
	{
		try{
			$sql = " SELECT name,memo,startdate,expire,lastupdate";
			$sql.= " FROM sv_group";
			$sql.= " WHERE name = '".$groupName."'";

			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"sql");
			$list = $this->objDb->ExecuteCommand($sql);
			return $list;
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: updateGroup
	* データの獲得
	* @param	$arrGroup			グループの記録
	* @return Array   $List
	*/
	function updateGroup($arrGroup,$strLastupdate)
	{
	try{
			$this->objDb->begin();	
			$sqltime=" SELECT COUNT (name)";
			$sqltime.=" FROM sv_group";
			$sqltime.=" WHERE name = '".$arrGroup["name"]."'";
			$sqltime.=" AND lastupdate='".$strLastupdate."'";
			$restime = $this->objDb->ExecuteCommand($sqltime);
			if($restime[0]!=1)
			return "fail";
			$sql = " SELECT COUNT (name)";
			$sql .= " FROM sv_group";
			$sql .= " WHERE name = '".$arrGroup["name"]."'";
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$sql");
			$res = $this->objDb->ExecuteCommand($sql);
			//グループの存在場合
			if($res[0] != 0)
			{
				$strSql  = "UPDATE sv_group";
				$strSql .=" SET ";
				$strSql .= " memo ='".$this->objDb->sqlescapestring($arrGroup["memo"])."',";
				//利用開始はヌルではない場合
				if($arrGroup["startdate"]!=" ")
					$strSql .= "  startdate='".$arrGroup["startdate"]."',";
				//利用開始はヌルの場合
				else
					$strSql .= "  startdate='".date("Y/m/d H:i:s")."',";
				//有効期限はヌルではない場合
				if($arrGroup["expire"]!=" ")
					$strSql .= "  expire='".$arrGroup["expire"]."'";
				//有効期限はヌルの場合
				else
				$strSql .= "  expire='2099/12/31 23:59:59'";
				$strSql .=" WHERE";
				$strSql .=" name = '".$arrGroup["name"]."'" ;
				
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
				$this->objDb->ExecuteNonQuery($strSql);
				
				$sqlupdate="UPDATE sv_group";
				$sqlupdate.=" SET ";
				$sqlupdate.=" lastupdate = CURRENT_TIMESTAMP";
				$sqlupdate.=" WHERE name ='".$arrGroup["name"]."'";
				$this->objDb->ExecuteNonQuery($sqlupdate);
				
				$this->objDb->commit();
				return true;
			}
			else
			{
				$this->objDb->rollback();
				return false;
			}
			
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: addGroup
	* データの獲得
	* @param				$arrGroup			グループの記録
	* @returnArray   $List
	*/
	function addGroup($arrGroup)
	{
		try{	
			$sql = " SELECT COUNT(*) as group";
			$sql .= " FROM sv_group";
			$sql .= " WHERE name = '".$arrGroup["name"]."'";
			$res = $this->objDb->ExecuteCommand($sql);
			//グループは存在しない

			if($res[0] == 0)
			{
				$sql2  = " INSERT INTO sv_group";
				$sql2 .= " (name,";
				$sql2 .= " memo,";
				$sql2 .= " startdate,";
				$sql2 .= " expire)";
				$sql2 .= " VALUES(";
				$sql2 .= "'".$arrGroup["name"]."',";
				$sql2 .= "'".$this->objDb->sqlescapestring($arrGroup["memo"])."',";
				//利用開始はヌルではない場合
				if($arrGroup["startdate"]!=" ")
					$sql2 .= "'".$arrGroup["startdate"]."',";
				//利用開始はヌルの場合			
				else
					$sql2 .= "  '".date("Y/m/d H:i:s")."',";
					//有効期限はヌルではない場合
				if($arrGroup["expire"]!=" ")
						$sql2 .= " '".$arrGroup["expire"]."')";
						//有効期限はヌルの場合				
				else
					$sql2 .= "'2099/12/31 23:59:59')";
 				$this->objDb->ExecuteNonQuery($sql2);
				
				$sqlupdate="UPDATE sv_group";
				$sqlupdate.=" SET ";
				$sqlupdate.=" lastupdate = CURRENT_TIMESTAMP";
				$sqlupdate.=" WHERE name ='".$arrGroup["name"]."'";
				$this->objDb->ExecuteNonQuery($sqlupdate);
				
				return true;
			}
			//グループは存在する
			else
				return false;
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
}
?>
