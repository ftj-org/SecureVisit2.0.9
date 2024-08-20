<?php
/**
 * ログ管理_ログの読み込み管理

 *
 * @author 劉偉華.
 * @since  2007-09-24
 * @version 1.0
 */

$G_APPPATH_M = explode("mdl",__FILE__);
require_once $G_APPPATH_M[0]."/lib/log.php";
require_once $G_APPPATH_M[0]."/lib/db.php";

class LogToDB_M
{
	/**
	 * データベースの実例.
	 */
	var $objDb = null;
	
	/**
	* 関数名: __construct
	* コンストラクタ.
	* 
	*/
	function __construct()
	{		
		try
		{
			$this->objDb = new DB_Pgsql();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例化");
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	
	/**
	* 関数名: LogToDB_M
	* コンストラクタ.
	* 
	*/
	function LogToDB_M()
	{				
		try
		{
			$this->__construct();
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}				
	}
	
	/**
	* 関数名: readLogToTemp
	* 属性の情報を表示する.
	* 
	* @param		string		$strFPath			ソースファイルのパス.
	*
	* @param		string		$strAuthsLog		認証の成功のログ
	*
	* @param		string		$strAuthfLog		認証の失敗のログ
	*
	* @param		string		$strAccessLog		アクセスのログ
	*
	* @param		string		$strTPath			テンポラリファイルのパス
	*
	* @return		boolean		$isTrue				属性の情報.
	*/
	function readLogToTemp($strAuthsLog,$strAuthfLog,$strAccessLog,$strFPath,$strTPath)
	{
		try
		{	
			$isTrue=false;
			
			@file_put_contents($strTPath.$strAuthsLog,file_get_contents($strFPath.$strAuthsLog));
			@file_put_contents($strFPath.$strAuthsLog,"");

			@file_put_contents($strTPath.$strAuthfLog,file_get_contents($strFPath.$strAuthfLog));	
			@file_put_contents($strFPath.$strAuthfLog,"");	

			@file_put_contents($strTPath.$strAccessLog,file_get_contents($strFPath.$strAccessLog));	
			@file_put_contents($strFPath.$strAccessLog,"");
	
			$isTrue=true;
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;	
		}
		return $isTrue;
	}
	
	/**
	* 関数名: readLogToDataBase
	* 属性の情報を表示する.
	* 
	* @param		string		$strFPath			ソースファイルのパス.
	*
	* @param		string		$strAuthsLog		認証の成功のログ
	*
	* @param		string		$strAuthfLog		認証の失敗のログ
	*
	* @param		string		$strAccessLog		アクセスのログ
	*
	* @param		string		$strTPath			テンポラリファイルのパス
	*
	* @return		boolean		$isTrue				属性の情報.
	*/
	function readLogToDataBase($strAuthsLog,$strAuthfLog,$strAccessLog,$strTPath,$strFPath)
	{
		$isTrue=true;
		try
		{	
			//トラザクションの開始.
			//$this->objDb->begin();
			
			//日付を取得する.
			$strsql= " SELECT value";
			$strsql.=" FROM sv_logconfig";
			$strsql.=" WHERE name ='monthnum'";
			
			try{
				$strValue= $this->objDb->ExecuteCommand($strsql);
			}catch(exception $e)
			{
			}
			
			//日付を取得する.
			$strsql2= " SELECT value";
			$strsql2.=" FROM sv_logconfig";
			$strsql2.=" WHERE name ='filter'";
			try{
				$strValue2= $this->objDb->ExecuteCommand($strsql2);
			}catch(exception $e)
			{
			}
			
			//指定の時間のデータを削除する
			$strDelSqlA= " DELETE";
			$strDelSqlA.=" FROM sv_accesslog";
			$strDelSqlA.=" WHERE datetime<(select current_timestamp - interval '".$strValue[0]." month')";
			try{
				$this->objDb->ExecuteNonQuery($strDelSqlA);
			}catch(exception $e)
			{
			}
			
			$strDelSqlSF= " DELETE";
			$strDelSqlSF.=" FROM sv_authlog";
			$strDelSqlSF.=" WHERE datetime<(select current_timestamp - interval  '".$strValue[0]." month')";
			try{
				$this->objDb->ExecuteNonQuery($strDelSqlSF);
			}catch(exception $e)
			{
			}

			$strDelSqlSF= " DELETE";
			$strDelSqlSF.=" FROM sv_adminlog";
			$strDelSqlSF.=" WHERE cdate<(select current_timestamp - interval  '".$strValue[0]." month')";
			try{
				$this->objDb->ExecuteNonQuery($strDelSqlSF);
			}catch(exception $e)
			{
			}
			
			//認証の成功のログを取得するかつ取得したログはデータベースに格納する。

			$authsConn=@fopen($strFPath.$strAuthsLog,"r");
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$authsConn )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}

			$lastClentIp = "0.0.0.0";
			while($strRow = @fgetcsv($authsConn,4096))	
			{	
				$nCnt=0;
				$arrRow = explode(" - ", $strRow[$nCnt]);
				$arrExplodeRow=explode(" ",$arrRow[0]);
				if($arrExplodeRow[1]=="")
				{
					$strDateString=$arrExplodeRow[0]." ".$arrExplodeRow[2]." ".$arrExplodeRow[3];
					$strUserID=$arrExplodeRow[6];
				}
				else
				{
					$strDateString=$arrExplodeRow[0]." ".$arrExplodeRow[1]." ".$arrExplodeRow[2];
					$strUserID=$arrExplodeRow[5];
				}
				$strtotime=strtotime($strDateString);
				$dateResult=date("Y-m-d H:i:s",$strtotime);
				if(stripos($strRow[0],"last message repeated") !== false )
				{
					$aCountSize = sizeof($arrExplodeRow)-1;
					$arrRow[6] = $arrExplodeRow[$aCountSize-4]." ".$arrExplodeRow[$aCountSize-3]." ".$arrExplodeRow[$aCountSize-2]." ".$arrExplodeRow[$aCountSize-1]." ".$arrExplodeRow[$aCountSize];
					$strUserID = "";
					$arrRow[1] = $lastClentIp;
					$arrRow[2] = "";
					$arrRow[3] = "";
					$arrRow[4] = "";
					$arrRow[5] = "";
					$arrRow[7] = "";
				}
				if(trim($arrRow[1]) == "") $arrRow[1]='0.0.0.0';
				$lastClentIp = $arrRow[1];
				$strInsSqlS="INSERT INTO sv_authlog(userid,clientip,tokenid,tokentype,authmethod,hostname,url,status,detail,datetime) VALUES('".trim($strUserID)."','".trim($arrRow[1])."','".trim($arrRow[2])."','".trim($arrRow[3])."','".trim($arrRow[4])."','".trim($arrRow[5])."','".trim($arrRow[6])."','success','".trim($arrRow[7])."','".trim($dateResult)."' )";
				try
				{
					$this->objDb->ExecuteNonQuery($strInsSqlS);
				}
				catch(Exception $e)
				{
					$isTrue=false;
				}
			}	
			$isTrue=$strRow;
			@fclose($authsConn);
			@unlink($strFPath.$strAuthsLog);
			
			//認証の失敗のログを取得するかつ取得したログはデータベースに格納する。

			$authsConn=@fopen($strFPath.$strAuthfLog,"r");
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$authsConn )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}
			
			$lastClentIp = "0.0.0.0";
			while($strRow = @fgetcsv($authsConn,4096))	
			{	
				$nCnt=0;
				$arrRow = explode(" - ", $strRow[$nCnt]);
				$arrExplodeRow=explode(" ",$arrRow[0]);
				if($arrExplodeRow[1]=="")
				{
					$strDateString=$arrExplodeRow[0]." ".$arrExplodeRow[2]." ".$arrExplodeRow[3];
					$strUserID=$arrExplodeRow[6];
				}
				else
				{
					$strDateString=$arrExplodeRow[0]." ".$arrExplodeRow[1]." ".$arrExplodeRow[2];
					$strUserID=$arrExplodeRow[5];
				}
				$strtotime=strtotime($strDateString);
				$dateResult=date("Y-m-d H:i:s",$strtotime);
				if(stripos($strRow[0],"last message repeated") !== false )
				{
					$aCountSize = sizeof($arrExplodeRow)-1;
					$arrRow[6] = $arrExplodeRow[$aCountSize-4]." ".$arrExplodeRow[$aCountSize-3]." ".$arrExplodeRow[$aCountSize-2]." ".$arrExplodeRow[$aCountSize-1]." ".$arrExplodeRow[$aCountSize];
					$strUserID = "";
					$arrRow[1] = $lastClentIp;
					$arrRow[2] = "";
					$arrRow[3] = "";
					$arrRow[4] = "";
					$arrRow[5] = "";
					$arrRow[7] = "";
					$arrRow[8] = "";
				}
				if(trim($arrRow[1]) == "") $arrRow[1]='0.0.0.0';
				$lastClentIp = $arrRow[1];
				$strInsSqlF="INSERT INTO sv_authlog(userid,clientip,tokenid,tokentype,authmethod,hostname,url,status,detail,datetime) VALUES('".trim($strUserID)."','".trim($arrRow[1])."','".trim($arrRow[2])."','".trim($arrRow[3])."','".trim($arrRow[4])."','".trim($arrRow[5])."','".trim($arrRow[6])."','".trim($arrRow[7])."','".trim($arrRow[8])."','".trim($dateResult)."' )";
				try
				{
					$this->objDb->ExecuteNonQuery($strInsSqlF);
				}catch(Exception $e)
				{
					$isTrue=false;
				}
			}	
			$isTrue=$strRow;
			@fclose($authsConn);
			@unlink($strFPath.$strAuthfLog);
			
			$strCountsql = "SELECT  count(datetime) FROM sv_authlog";
			try{
				$Count=$this->objDb->ExecuteCommand($strCountsql);
			}
			catch(exception $e)
			{
			}
			
			if($Count[0] > 500000)
			{
				$value=$Count[0]-500000;
				$sqldel="DELETE FROM sv_authlog WHERE datetime in (SELECT datetime FROM sv_authlog ORDER BY datetime ASC LIMIT ".$value.")";
				try
				{
					$this->objDb->ExecuteNonQuery($sqldel);
				}
				catch(exception $e)
				{
					$isTrue=false;
				}
		    }
			
			//アクセスののログを取得するかつ取得したログはデータベースに格納する。

			$authsConn=@fopen($strFPath.$strAccessLog,"r");
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$authsConn )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}
			$lastClentIp = "0.0.0.0";
			while($strRow = @fgetcsv($authsConn,8192,"\r"))	
			{
				if( preg_match($strValue2[0],$strRow[0]) ) continue;
				$nCnt=0;
				$strRow[$nCnt]=str_replace(',',';',$strRow[$nCnt]);
				$arrRow = explode(" - ", $strRow[$nCnt]);
			        $nginxlog = 1;
	
				//$arrExplodeRow=explode(" ",$arrRow[0]);
				//if($arrExplodeRow[1]=="")
				//{
				//	$strDateString=$arrExplodeRow[0]." ".$arrExplodeRow[2]." ".$arrExplodeRow[3];
				//	$strUserID=$arrExplodeRow[6];
				//}
				//else
				//{
				//	$strDateString=$arrExplodeRow[0]." ".$arrExplodeRow[1]." ".$arrExplodeRow[2];
				//	$strUserID=$arrExplodeRow[5];
				//}

			        $strUserID = "";
                                $strDateString = "";
                                $arrExplodeSpace = explode(" ", $arrRow[0]);
                                $arrExplodeRow = explode("/", $arrExplodeSpace[0]);
                                if ($arrExplodeRow[1]=="") {
                                    $nginxlog = 0;
                                    $arrExplodeRow=explode(" ",$arrRow[0]);
                                    if($arrExplodeRow[1]==""){
                                      $strDateString=$arrExplodeRow[0]." ".$arrExplodeRow[2]." ".$arrExplodeRow[3];
                                      $strUserID=$arrExplodeRow[6];
                                    } else {
                                      $strDateString=$arrExplodeRow[0]." ".$arrExplodeRow[1]." ".$arrExplodeRow[2];
                                      $strUserID=$arrExplodeRow[5];
                                    }
                                } else {
                                   $arrExplodeTime = explode(":",$arrExplodeRow[2]);
                                   $strDateString = $arrExplodeRow[1]." ".$arrExplodeRow[0]." ".$arrExplodeTime[1].":".$arrExplodeTime[2].":".$arrExplodeTime[3];
				}

                                $strtotime=strtotime($strDateString);
				$dateResult=date("Y-m-d H:i:s",$strtotime);
				if(stripos($strRow[0],"last message repeated") !== false )
				{
                                     if ($nginxlog == 1) {
                                        $arrRow[5] = "";
                                     } else {
					$aCountSize = sizeof($arrExplodeRow)-1;
					$arrRow[5] = $arrExplodeRow[$aCountSize-4]." ".$arrExplodeRow[$aCountSize-3]." ".$arrExplodeRow[$aCountSize-2]." ".$arrExplodeRow[$aCountSize-1]." ".$arrExplodeRow[$aCountSize];
				     }
                                        $strUserID = "";
					$arrRow[7] = $arrRow[5];
					$arrRow[2] = $lastClentIp;
					$arrRow[3] = "";
					$arrRow[4] = "";
					$arrRow[6] = "";
					$arrRow[8] = "";
					$arrRow[9] = "";
					$arrRow[1] = "";
				}
				if(trim($arrRow[2]) == "") $arrRow[2]='0.0.0.0';
				$lastClentIp = $arrRow[2];
				
				$strInsSqlA="INSERT INTO sv_accesslog(userid,groupname,clientip,tokenid,tokentype,requrl,hostname,url,detail,datetime,status) VALUES('".trim($strUserID)."','".trim($arrRow[1])."','".trim($arrRow[2])."','".trim($arrRow[3])."','".trim($arrRow[4])."','".trim($arrRow[5])."','".trim($arrRow[6])."','".trim($arrRow[7])."','".trim($arrRow[8])."','".trim($dateResult)."','".trim($arrRow[9])."')";
				try
				{
					$this->objDb->ExecuteNonQuery($strInsSqlA);
				}catch(exception $e)
				{
					$isTrue=false;
				}
				//トラザクションの終了.
			}	
			$isTrue=$strRow;
			@fclose($authsConn);
			@unlink($strFPath.$strAccessLog);
			$strCountsql = "SELECT  count(datetime) FROM sv_accesslog";
			try
			{
				$Count=$this->objDb->ExecuteCommand($strCountsql);
			}
			catch(exception $e)
			{
			}
			if($Count[0] > 500000)
			{
				$badvalue=$Count[0]-500000;
				$sqldel="DELETE FROM sv_accesslog WHERE datetime in (SELECT datetime FROM sv_accesslog ORDER BY datetime ASC LIMIT ".$badvalue.")";
				try
				{
					$this->objDb->ExecuteNonQuery($sqldel);
				}
				catch(exception $e)
				{
					$isTrue=false;
				}
		    }

			$strCountsql = "SELECT  count(cdate) FROM sv_adminlog";
			try
			{
				$Count=$this->objDb->ExecuteCommand($strCountsql);
			}
			catch(exception $e)
			{
			}
			if($Count[0] > 500000)
			{
				$badvalue=$Count[0]-500000;
				$sqldel="DELETE FROM sv_adminlog WHERE cdate in (SELECT cdate FROM sv_adminlog ORDER BY cdate ASC LIMIT ".$badvalue.")";
				try
				{
					$this->objDb->ExecuteNonQuery($sqldel);
				}
				catch(exception $e)
				{
					$isTrue=false;
				}
		    }
			//$this->objDb->commit();
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			//ロールバック
		}
		//$isTrue=true;
		return $isTrue;
	}
}
?>
