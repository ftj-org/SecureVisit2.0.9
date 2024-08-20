<?php
/**
 * ログ管理_ログの読み出し管理


 *
 * @author 劉偉華.
 * @since  2007-09-24
 * @version 1.0
 */

$G_APPPATH_M = explode("mdl",__FILE__);
require_once $G_APPPATH_M[0]."/lib/log.php";
require_once $G_APPPATH_M[0]."/lib/db.php";

class DBToLogZip_M
{
	/**
	 * データベースの実例.
	 */
	var $objDb = null;

    var $startDate = null;
	
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
	* 関数名: DBToLogZip_M
	* コンストラクタ.
	* 
	*/
	function DBToLogZip_M()
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
	* 関数名: compress
	* 
	* @param		string		$srcName			ソースファイルの名前.
	*
	* @param		string		$dstName			テンポラリファイルの名前
	*
	*/
	function compress($srcName, $dstName)
	{
		@exec("cd ".G_LOCAL_LOG_PATH." && zip -D $dstName ./$srcName");
	}
	
	/**
	* 関数名: LogManage
	* 
	* @param		string		$strPath			ログの格納したファイルのパス.
	*
	* @param		string		$strLogs			データベースからログを読み取り、ログの名前に格納する


	*
	*/
	function LogManage($strPath,$strLogs)
	{
		try{
			//トラザクションの開始.
			$this->objDb->begin();
			$today = date("Y-m-d")." 00:00:00";
			
			//SQL形成する
			$strLogLastTimeSql  = " SELECT value";
			$strLogLastTimeSql .= " FROM sv_logconfig";
			$strLogLastTimeSql .= " WHERE name='log_last_time'";
			
			$strLogLastTime = $this->objDb->ExecuteCommand( $strLogLastTimeSql );
			$this->startDate = $strLogLastTime[0];
			
			//最後のログインのタイムがない場合は
			if( $strLogLastTime[0] == "" )
			{
				$strSelectSql  = " SELECT *";
				$strSelectSql .= " FROM sv_authlog";
				$strSelectSql .= " WHERE";
				$strSelectSql .= " datetime<'".$today."'";
				
				$strSelectASql  = " SELECT *";
				$strSelectASql .= " FROM sv_accesslog";
				$strSelectASql .= " WHERE"; 
				$strSelectASql .= " datetime<'".$today."'";
				
				// Phase2 added by wuxj Begin
				$strSelectAdminSql  = " SELECT *";
				$strSelectAdminSql .= " FROM sv_adminlog";
				$strSelectAdminSql .= " WHERE"; 
				$strSelectAdminSql .= " cdate<'".$today."'";
			}
			//最後のログインのタイムがある場合は
			else
			{
				$strSelectSql  = " SELECT *";
				$strSelectSql .= " FROM sv_authlog";
				$strSelectSql .= " WHERE"; 
				$strSelectSql .= " datetime<'".$today."'";
				$strSelectSql .= " AND datetime>='".$strLogLastTime[0]."'";
				
				$strSelectASql  = " SELECT *";
				$strSelectASql .= " FROM sv_accesslog";
				$strSelectASql .= " WHERE"; 
				$strSelectASql .= " datetime<'".$today."'";
				$strSelectASql .= " AND datetime>='".$strLogLastTime[0]."'";
				
				// Phase2 added by wuxj Begin
				$strSelectAdminSql  = " SELECT *";
				$strSelectAdminSql .= " FROM sv_adminlog";
				$strSelectAdminSql .= " WHERE"; 
				$strSelectAdminSql .= " cdate<'".$today."'";
				$strSelectAdminSql .= " AND cdate>='".$strLogLastTime[0]."'";
			}
			
			$authsConn = @fopen( $strPath.$strLogs."auth.log","w" );
			//$arrResult = array(0=>1);
			$firstNum = 0;
			
			
			do{
				try{
					$strSelectSql2 = $strSelectSql." ORDER BY id ASC LIMIT ".G_DBTOLOG_PER_QUERY_NUM." OFFSET ".$firstNum;
					$arrResult = $this->objDb->ExecuteArrayCommand($strSelectSql2);
					//ファイルを開く		
					//ループで配列から、データを取得し、ファイルに書き込む
					for( $i=0;$i<count( $arrResult );$i++ )
					{
						for($j=0;$j<G_DBTOLOG_AUTHLOG_COLS_NUM;$j++)
						{
							$arrFilterResult[$j] = $arrResult[$i][$j];
						}
						$strInsSqlS = '"'.implode( "\",\"" ,$arrFilterResult).'"';
						@fputs( $authsConn,@mb_convert_encoding( $strInsSqlS,"SJIS","UTF-8" )."\n" );
					}
					$firstNum = $firstNum +G_DBTOLOG_PER_QUERY_NUM;
				}catch( Exception $e ){
					break;
				}
			}while(sizeof($arrResult) > 0);
			
			@fclose( $authsConn );
			
			$authsConn = @fopen( $strPath.$strLogs."access.log","w" );
			//$arrAResult = array(0=>1);
			$secondNum = 0;
			
			do{
				try{
					$strSelectASql2 = $strSelectASql." ORDER BY id ASC LIMIT ".G_DBTOLOG_PER_QUERY_NUM." OFFSET ".$secondNum;
					$arrAResult = $this->objDb->ExecuteArrayCommand($strSelectASql2);
					//ループで配列から、データを取得し、ファイルに書き込む
					for( $i=0;$i<count( $arrAResult );$i++ )
					{
						for($j=0;$j<G_DBTOLOG_ACCESSLOG_COLS_NUM;$j++)
						{
							$arrFilterAResult[$j] = $arrAResult[$i][$j];
						}
						$strInsSqlS = '"'.implode( "\",\"" ,$arrFilterAResult).'"';
						@fputs( $authsConn,@mb_convert_encoding( $strInsSqlS,"SJIS","UTF-8" )."\n" );
					}
					$secondNum = $secondNum +G_DBTOLOG_PER_QUERY_NUM;
				}catch( Exception $e ){
					break;
				}
			}while(sizeof($arrAResult) > 0);
			
			@fclose( $authsConn );
			//readfile($strPath.$strLogs."access.log");
			$authsConn = @fopen( $strPath.$strLogs."admin.log","w" );
			$thirdNum = 0;
			
			do{
				try{
					$strSelectAdminSql2 = $strSelectAdminSql." ORDER BY id ASC LIMIT ".G_DBTOLOG_PER_QUERY_NUM." OFFSET ".$thirdNum;
					$arrAResult = $this->objDb->ExecuteArrayCommand($strSelectAdminSql2);
					//ループで配列から、データを取得し、ファイルに書き込む
					for( $i=0;$i<count( $arrAResult );$i++ )
					{
						for($j=0;$j<G_DBTOLOG_ACCESSLOG_COLS_NUM;$j++)
						{
							$arrFilterAResult[$j] = $arrAResult[$i][$j];
						}
						$strInsSqlS = '"'.implode( "\",\"" ,$arrFilterAResult).'"';
						@fputs( $authsConn,@mb_convert_encoding( $strInsSqlS,"SJIS","UTF-8" )."\n" );
					}
					$thirdNum = $thirdNum +G_DBTOLOG_PER_QUERY_NUM;
				}catch( Exception $e ){
					break;
				}
			}while(sizeof($arrAResult) > 0);
			
			//ファイルを閉じる
			@fclose( $authsConn );
			// Phase2 added by wuxj End
			
			//Sql文を作成する
			$strUpdateSql  = " UPDATE";
			$strUpdateSql .= " sv_logconfig";
			$strUpdateSql .= " SET value='".$today."'";
			$strUpdateSql .= " WHERE name='log_last_time'";
			
			$this->objDb->ExecuteNonQuery( $strUpdateSql );
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
	}
	
	/**
	* 関数名: LogToZIP
	* 属性の情報を表示する.
	* 
	* @param		string		$strPath			ログの格納したファイルのパス.
	*
	* @param		string		$strLogs			データベースからログを読み取り、ログの名前に格納する


	*
	* @return		boolean		$isTrue				属性の情報.
	*/
	function LogToZIP($strLogs,$strPath)
	{
		$isTrue=false;
		
		//トラザクションの開始.
		$this->objDb->begin();
		$today = date("Ymd");

		//圧縮のタイプと最後データベースに操作した時間を取得する
		$strTypeSql  = "SELECT value ";
		$strTypeSql .= " FROM sv_logconfig";
		$strTypeSql .= " WHERE name='type'";
		
		$strType = $this->objDb->ExecuteCommand( $strTypeSql );
		// Phase2 added by wuxj Begin
		try
		{	
			//switch
			switch( $strType[0] )
			{
				/*データベースに最後操作時間が取得するかを判定する。
				  データベースに最後の操作時間を取得した場合は、最後の操作時間からカレントの０点０分０秒までのログを取得する。
				  ZIPを圧縮する
				*/
				case 1:
					try
					{
						$this->LogManage($strPath,$strLogs);
						$lastdate = $this->startDate == "" ? "YYYYMMDD": str_replace("-","",substr($this->startDate,0,10));
						$zipFileName = $lastdate."-".$today;
						
						$this->compress($strLogs."access.log",$strPath.$zipFileName.".access.zip");
						
						$this->compress($strLogs."admin.log",$strPath.$zipFileName.".admin.zip");
						
						$this->compress($strLogs."auth.log",$strPath.$zipFileName.".auth.zip");
						@unlink($strPath.$strLogs."access.log");
						@unlink($strPath.$strLogs."admin.log");
						@unlink($strPath.$strLogs."auth.log");
					}
					catch( Exception $e )
					{
						// エラーメッセージを表示する.
						ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
						exit;
					}
					break;
				//カレントの日付は日曜日の場合は
				case 2:
					try
					{
						//SQL形成する
						$isSundaySql  = " SELECT"; 
						$isSundaySql .= " EXTRACT(DOW FROM TIMESTAMP 'now()')";
						
						$isSunday = $this->objDb->ExecuteCommand( $isSundaySql );
						//カレントの日付は日曜日が場合は
						if( $isSunday[0] == 0 )
						{
							$this->LogManage($strPath,$strLogs);
							$lastdate = $this->startDate == "" ? "YYYYMMDD": str_replace("-","",substr($this->startDate,0,10));
							$zipFileName = $lastdate."-".$today;
							$this->compress($strLogs."access.log",$strPath.$zipFileName.".access.zip");
							$this->compress($strLogs."admin.log",$strPath.$zipFileName.".admin.zip");
							$this->compress($strLogs."auth.log",$strPath.$zipFileName.".auth.zip");
						}
						//カレントの日付は日曜日がない場合は
						else
						{
							break;
						}
						@unlink($strPath.$strLogs."access.log");
						@unlink($strPath.$strLogs."admin.log");
						@unlink($strPath.$strLogs."auth.log");
					}
					catch( Exception $e )
					{
						// エラーメッセージを表示する.
						ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
						exit;
					}
					break;
				//カレントの日付は当月の一日の場合は
				case 3:
					try
					{
						//SQL形成する
						$isFirstDayOFMonthSql  = " SELECT"; 
						$isFirstDayOFMonthSql .= " EXTRACT(DAY from TIMESTAMP 'now()')";
						
						$isFirstDayOFMonth = $this->objDb->ExecuteCommand($isFirstDayOFMonthSql);
							
						//カレントの日付は当月の一日の場合は
						if( $isFirstDayOFMonth[0] == 1 )
						{
							$this->LogManage($strPath,$strLogs);
						$lastdate = $this->startDate == "" ? "YYYYMMDD": str_replace("-","",substr($this->startDate,0,10));
							$zipFileName = $lastdate."-".$today;
							$this->compress($strLogs."access.log",$strPath.$zipFileName.".access.zip");
							$this->compress($strLogs."admin.log",$strPath.$zipFileName.".admin.zip");
							$this->compress($strLogs."auth.log",$strPath.$zipFileName.".auth.zip");
						}
						//カレントの日付は当月の一日がない場合は
						else
						{
							break;
						}
						@unlink($strPath.$strLogs."access.log");
						@unlink($strPath.$strLogs."admin.log");
						@unlink($strPath.$strLogs."auth.log");
					}
					catch( Exception $e )
					{
						// エラーメッセージを表示する.
						ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
						exit;
					}
					break;
				//カレントの日付は当月の一日で、かつ前の操作日から、カレントの日付まで期間は3ヶ月の場合は
				case 4:
					try
					{
						//SQL形成する
						$strLogLastTimeSql  = "SELECT value";
						$strLogLastTimeSql .= " FROM sv_logconfig";
						$strLogLastTimeSql .= " WHERE name='log_last_time'";
						
						$strLogLastTime = $this->objDb->ExecuteCommand( $strLogLastTimeSql );
						
						//カレントの日付は当月の一日の場合は
						$isFirstDayOFMonthSql  = " SELECT"; 
						$isFirstDayOFMonthSql .= " EXTRACT(DAY from TIMESTAMP 'now()')";
						
						$isFirstDayOFMonth = $this->objDb->ExecuteCommand( $isFirstDayOFMonthSql );
						
						//カレントの年
						$CurrentYearSql = "select date_part('year', timestamp 'now()')";
						$LogLastYearSql = "select date_part('year', timestamp '".$strLogLastTime[0]."')";
						$CurrentYear = $this->objDb->ExecuteCommand( $CurrentYearSql );
						$LogLastYear = $this->objDb->ExecuteCommand( $LogLastYearSql );
						
						//カレントの日付まで期間は3ヶ月の場合は
						$isThreeMonthAlreadySql = "select date_part('month', timestamp 'now()')";
						$MonthLogLastTime = "select date_part('month', timestamp '".$strLogLastTime[0]."')";
						
						$isThreeMonthAlready = $this->objDb->ExecuteCommand( $isThreeMonthAlreadySql );
						$MonthOFlastTime = $this->objDb->ExecuteCommand($MonthLogLastTime);
						if($CurrentYear[0]==$LogLastYear[0])
						{
							$monthResult = intval( $isThreeMonthAlready[0] )-intval( $MonthOFlastTime[0] );
						}
						if(intval($CurrentYear[0])-intval($LogLastYear[0])==1)
						{
							$monthResult=intval( $isThreeMonthAlready[0]+12 )-intval( $MonthOFlastTime[0] );
						}
						//カレントの日付は当月の一日で、かつ前の操作日から、カレントの日付まで期間は3ヶ月の場合は
						if( $isFirstDayOFMonth[0] == 1 and $monthResult == 3 )
						{
							$this->LogManage($strPath,$strLogs);
							$lastdate = $this->startDate == "" ? "YYYYMMDD": str_replace("-","",substr($this->startDate,0,10));
							$zipFileName = $lastdate."-".$today;
							$this->compress($strLogs."access.log",$strPath.$zipFileName.".access.zip");
							$this->compress($strLogs."admin.log",$strPath.$zipFileName.".admin.zip");
							$this->compress($strLogs."auth.log",$strPath.$zipFileName.".auth.zip");
						}
						//カレントの日付は当月の一日で、かつ前の操作日から、カレントの日付まで期間は3ヶ月がない場合は
						else
						{
							break;
						}
						@unlink($strPath.$strLogs."access.log");
						@unlink($strPath.$strLogs."admin.log");
						@unlink($strPath.$strLogs."auth.log");
					}
					catch( Exception $e )
					{
						// エラーメッセージを表示する.
						ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
						exit;
					}
					break;
			}
			
			$strRepire = "VACUUM ANALYZE";
			$this->objDb->ExecuteNonQuery( $strRepire );
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			//ロールバック
			$this->objDb->rollback();
			throw $e;	
		}
		// Phase2 added by wuxj End
		$isTrue = true;
		return $isTrue;
	}
}
?>