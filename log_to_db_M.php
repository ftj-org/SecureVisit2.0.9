<?php
/**
 * ログ管理_ログの読み込み管理
 *
 * @author 劉偉華.
 * @since  2007-09-24
 * @version 1.0
 */

require_once "../lib/log.php";
require_once "../lib/db.php";

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
			
			return $isTrue;
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;	
		}
		
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
	function readLogToDataBase($strAuthsLog,$strAuthfLog,$strAccessLog,$strFPath,$strTPath)
	{
		$isTrue=false;
		try
		{	
			//トラザクションの開始.
			$this->objDb->begin();
			
			//日付を取得する.
			$strsql= " SELECT value";
			$strsql.=" FROM sv_logconfig";
			$strsql.=" WHERE name ='monthnum'";
			
			$strValue= ord($this->objDb->ExecuteCommand($strsql));
			
			//指定の時間のデータを削除する
			$strDelSqlA= " delete";
			$strDelSqlA.=" FROM sv_accesslog";
			$strDelSqlA.=" WHERE datetime<(select current_timestamp - interval '".strValue." month')";
			
			$this->objDb->ExecuteCommand($strDelSqlA);
			
			$strDelSqlSF= " delete";
			$strDelSqlSF.=" FROM sv_authlog";
			$strDelSqlSF.=" WHERE datetime<(select current_timestamp - interval '".strValue." month')";
			
			$this->objDb->ExecuteCommand($strDelSqlSF);
			
			//認証の成功のログを取得するかつ取得したログはデータベースに格納する。
			$authsConn=@fopen($strFPath.$strAuthsLog,"r");
			while(!feof($authsConn))	
			{	
				$nCount=0;
				$strRow = @fgets($authsConn,4096);
				$arrRow=explode($strRow,"-");
			    $strInsSqlS="insert into sv_authlog values(";
				for($i=0;$i<sizeof($arrRow);$i++)
				{
					if($nCount==0)
					{
					}
					else
					{
						$strInsSql.=",";
					}
					$strInsSqlS.="'".$strRow[$i]."'";
				}
				$strInsSqlS.=")";
			}	
			$this->objDb->ExecuteCommand($strInsSqlS);
			@fclose($authsConn);
			@unlink($strFPath.$strAuthsLog);
			
			//認証の失敗のログを取得するかつ取得したログはデータベースに格納する。
			$authsConn=@fopen($strFPath.$strAuthfLog,"r");
			while(!feof($authsConn))	
			{	
				$nCount=0;
				$strRow = @fgets($authsConn,4096);
				$arrRow=explode($strRow,"-");
				$strInsSqlF="insert into sv_authlog values(";
				for($i=0;$i<sizeof($arrRow);$i++)
				{
					if($nCount==0)
					{
					}
					else
					{
						$strInsSqlF.=",";
					}
					$strInsSqlF.="'".$strRow[$i]."'";
				}
				$strInsSqlF.=")";
			}	
			$this->objDb->ExecuteCommand($strInsSqlF);
			@fclose($authsConn);
			@unlink($strFPath.$strAuthfLog);
			
			//アクセスののログを取得するかつ取得したログはデータベースに格納する。
			$authsConn=@fopen($strFPath.$strAccessLog,"r");
			while(!feof($authsConn))	
			{	
				$nCount=0;
				$strRow = @fgets($authsConn,4096);
				$arrRow=explode($strRow,"-");
				$strInsSqlA="insert into sv_authlog values(";
				for($i=0;$i<sizeof($arrRow);$i++)
				{
					if($nCount==0)
					{
					}
					else
					{
						$strInsSqlA.=",";
					}
					$strInsSqlA.="'".$strRow[$i]."'";
				}
				$strInsSqlA.=")";
			}	
			$this->objDb->ExecuteCommand($strInsSqlA);
			@fclose($authsConn);
			@unlink($strFPath.$strAccessLog);
			
			//トラザクションの終了.
			$this->objDb->commit();
			}
			catch( Exception $e )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
				//ロールバック
				$this->objDb->rollback();
				throw $e;	
			}
		$isTrue=true;
		return $isTrue;
		}
}
?>