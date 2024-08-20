<?php
/**
 * ePassND1000のアクセス権限設定マッピング一覧ファイル
 *
 * @author  張新星 * @since   2007-11-21
 * @version 1.0
*/

// 指定されたファイルを読み込む
$G_APPPATH_M = explode("mdl",__FILE__);
require_once $G_APPPATH_M[0]."/lib/log.php";
require_once $G_APPPATH_M[0]."/lib/db.php";

class Login_M
{
	/*
	*データベースの実例	*/
	var $objDb = null;

	
	/**
	* 関数名:__construct
	* コンストラクタ
*
	*/
	function __construct()
	{
		try{
				$this->objDb = new DB_Pgsql();
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例");
		}catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->GetMessage());
			throw $e;
		}
	}
	
/**
* 関数名: Login_M
* コンストラクタ.
* 
*/
	function Login_M()
	{
		try{
				$this->__construct();
			}
		catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->GetMessage());
			throw $e;
		}
	}
	
/**
*関数名: checkUser
*
*
*/
	function checkUser($UserId)
	{
		try
		{
			$UserId=strtolower($UserId);
			$sql = " SELECT count(userid) ";
			$sql.= " FROM sv_user";
			$sql.= " WHERE userid = '".$UserId."'";
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"sql");
			$userList = $this->objDb->ExecuteCommand($sql);
			if($userList[0] !=0)
			{
				$sql1=" SELECT access";
				$sql1.=" FROM sv_user";
				$sql1.=" WHERE userid ='".$UserId."'";
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"sql1");
				$access = $this->objDb->ExecuteCommand($sql1);
				if($access[0] == 0)
				{
					return false;
				}
				else
				{
					return true;
				}
			}
			else
			{
				return false;
			}
	
		}
		catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	 *関数名: checkGroup
	*
	*/
	function checkGroup($UserId)
		{
			try
			{
			$UserId=strtolower($UserId);
			$sql = " SELECT count(userid)";
			$sql.=" FROM sv_usergroup as a,sv_group as b";
			$sql.=" WHERE a.grpid = b.id";
			$sql.=" AND a.userid = '".$UserId."'";
			$sql.=" AND b.name = 'Administrators'";
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"sql");
		
			$userCount = $this->objDb->ExecuteCommand($sql);
			if($userCount[0]!= 0)
			{
			return true;
			}
				else
			{
		return false;
		}
			}
		catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	*関数名: checkPwd
	*
	*/
		function checkPwd($UserId,$Pwd)
			{
			try{
			
			$sql =" SELECT pwdid";
			$sql.=" FROM sv_user";
			$sql.=" WHERE userid = '".$UserId."'";
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"sql");
			$pwdId = $this->objDb->ExecuteCommand($sql);
		
				$sql1 =" SELECT pwd";
				$sql1.=" FROM sv_password";
				$sql1.=" WHERE id = '".$pwdId[0]."'";
				
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"sql1");
				$pwdList = $this->objDb->ExecuteCommand($sql1);
				
					if($pwdList[0] == $Pwd)
					{
					$sql2 =" SELECT access";
					$sql2.=" FROM sv_password";
					$sql2.=" WHERE id = '".$pwdId[0]."'";
					$access1 = $this->objDb->ExecuteCommand($sql2);
						if($access1[0] == 1)
					{
					return true;
					}
						else
					{
				return false;
				}
				}
					else
				{
			return false;
			}
		
			
			}
		catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e; 
		}
	}
		
	/**
	*関数名: checkDate1
	*
	*/
	function checkDate1($UserId)
	{
		try
		{
			$UserId=strtolower($UserId);
			$sql = " SELECT startdate,expire";
			$sql.= " FROM sv_user";
			$sql.="  WHERE userid = '".$UserId."'";
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"sql");
			$userDate = $this->objDb->ExecuteCommand($sql);
			return $userDate;
			
		}
		catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
			
	/**
	*関数名: checkDate2
	*
	*/			
	function checkDate2($UserId)
	{			
		try
		{	
			$sql1 =" SELECT sv_password.startdate,sv_password.expire";
			$sql1.=" FROM sv_password ,sv_user ";
			$sql1.=" WHERE sv_password.id = sv_user.pwdid";
			$sql1.=" AND sv_user.userid = '".$UserId."'";
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"sql1");
			$pwdDate = $this->objDb->ExecuteCommand($sql1);
			return $pwdDate;
		}
		catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
}
?>