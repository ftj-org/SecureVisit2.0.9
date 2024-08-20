<?php
/**
 * epass1000NDのログのバックアップ管理.
 *
 * @author 韓陽光.
 * @since  2007-09-07
 * @version 1.0
 */
$G_MAPPPATH = explode("mdl",__FILE__);
require_once $G_MAPPPATH[0]."lib/log.php";
require_once $G_MAPPPATH[0]."lib/db.php";
require_once $G_MAPPPATH[0]."lib/pageview.php";


class log_backup_M{
	
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
	* 関数名: log_backup_M
	* コンストラクタ.
	* 
	*/
	function log_backup_M()
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
	* 関数名: readTableList
	* ファイルのZIPファイルを読み出しリストを作成する.   

	*
	* @return      Array     $res			   配列.
	*/
	function readlogList()
	{
		try
		{
			$res = glob ( G_LOCAL_LOG_PATH."*.zip" );
			$cssFlag = true;
			
			// ループでリストを表示する.
			if(!is_array($res)) return "";
			foreach( $res as $key => $value)
			{
				$res2[$key][$key] = basename($value);
				//css仕様を設定する.
				if( $cssFlag ) 
				{
					$res2[$key]["css_class"] = "evenrowbg";
				}
				else 
				{
						$res2[$key]["css_class"] = "oddrowbg";
				}	
				$cssFlag = $cssFlag==true?false:true;			
			}
			return $res2;
						
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}


	/**
	* 関数名: logDel
	* LOGを削除する.
	* 
	* @param       string    $arrGetchk      削除したいファイル
	*/
	function logDel($arrGetchk)
	{
		try
		{
			foreach($arrGetchk as $key => $value)
			{
				@unlink ( trim( G_LOCAL_LOG_PATH.$value ) );
			}
		}		
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}

	}
	
	
	/**
	* 関数名: logconfigUpdate
	* logconfigのデータを更新する.
	* 
	* @param       string    $strmonthNum      月のデータ
	* @param       string    $strSelect        タイプ

	*
	* @return      Array     $res			     配列.
	*/
	function logconfigUpdate($strmonthNum,$strSelect,$filter)
	{
		try
		{					
			foreach($_POST as $key => $value)
			{
				if(substr($key,0,3) == "cbo" && strlen(trim($value)) !=0)
				{
					switch(strtolower(trim($value)))
					{						
						case "month":
							$strsql  = "UPDATE sv_logconfig";
							$strsql .= " SET value = '".$strmonthNum."'";
							$strsql .= " WHERE name = 'monthnum'";
							$this->objDb->ExecuteNonQuery($strsql);
							break;
						case "year":
							$strsql  = "UPDATE sv_logconfig";
							$strsql .= " SET value = '".$strmonthNum."'*12";
							$strsql .= " WHERE name = 'monthnum'";
							$this->objDb->ExecuteNonQuery($strsql);
							break;
					}
				}
			}
					
			$strsql  = "UPDATE sv_logconfig";
			$strsql .= " SET value = '".$strSelect."'";
			$strsql .= " WHERE name = 'type'";
			$this->objDb->ExecuteNonQuery($strsql);
			
			$strsql  = "UPDATE sv_logconfig";
			$strsql .= " SET value = '".$filter."'";
			$strsql .= " WHERE name = 'filter'";
			$this->objDb->ExecuteNonQuery($strsql);
						
			
		}		
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
		
	}
	
	
	/**
	* 関数名: readmonthnum
	*logconfig表にmonthnum項目を読み出す.  
	* 
	* @return      Array     $arrlogupdate			  処理したmunthnumの値.
	*/
	function readmonthnum()
	{
		try
		{		
		$strsql  = "SELECT value";
		$strsql .= " FROM sv_logconfig";
		$strsql .= " WHERE name = 'monthnum'";
		$res =$this->objDb->ExecuteCommand($strsql);
		
		$arrlogupdate = $res["0"];
			if ( $arrlogupdate%12 == 0 )
			{
				$arrlogupdate	= $arrlogupdate/12;
				return 	$arrlogupdate;
			}
			else
			{
				return 	$arrlogupdate;
			}
			return 	$arrlogupdate;
		}		
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}		
	}


	/**
	* 関数名: checkmonrhnum
	* logconfig表にmonthnum項目を読み出す.
	* 
	* @return      Array     $arrlogupdate			 munthnum値.
	*/
	function checkmonthnum()
	{
		try
		{		
			$strsql  = "SELECT value";
			$strsql .= " FROM sv_logconfig";
			$strsql .= " WHERE name = 'monthnum'";
			$res =$this->objDb->ExecuteCommand($strsql);
			$arrlogupdate = $res["0"];
		
			return $arrlogupdate;
		}		
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}		
	}


	
	/**
	* 関数名: readtype
	* logconfig表にreadtype項目を読み出す.
	* 
	* @return      Array     $strSelect		タイプの値.
	*/
	function readtype()
	{
		try
		{		
			$strsql  = "SELECT value";
			$strsql .= " FROM sv_logconfig";
			$strsql .= " WHERE name = 'type'";
			$res =$this->objDb->ExecuteCommand($strsql);
			
			$strSelect = $res["0"];
			return 	$strSelect;
		}		
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
		
	}
	
	/**
	* 関数名: readtype
	* logconfig表にreadtype項目を読み出す.
	* 
	* @return      Array     $strSelect		タイプの値.
	*/
	function readFilter()
	{
		try
		{		
			$strsql  = "SELECT value";
			$strsql .= " FROM sv_logconfig";
			$strsql .= " WHERE name = 'filter'";
			$res =$this->objDb->ExecuteCommand($strsql);
			
			$strSelect = $res["0"];
			return 	$strSelect;
		}		
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
		
	}
	
	
	/**
	* 関数名: downloadzip
	* logファイルをダウンロードする.  
	* 
	*/
	function downloadzip()
	{
		try
		{
			// 「Download」画面が表示する.
			header('Content-type: application/zip');
			header('Content-Disposition: attachment; filename="config.xml"');
			readfile(G_LOCAL_LOG_PATH."config.xml");
			exit;
			
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
			throw $e;
		}
	}	
		
}

?>
