<?php
/** 
 * 端末一覧ファイル
 *
 * @author  liyang



 * @since   2010-08-31
 * @version 1.0
 */

// 指定されたファイルを読み込む
$G_APPPATH_M = explode("mdl",__FILE__);
require_once $G_APPPATH_M[0]."/lib/db.php";
require_once $G_APPPATH_M[0]."/lib/log.php";
require_once $G_APPPATH_M[0]."/lib/pageview.php";
require_once $G_APPPATH_M[0]."/lib/xmlread.php";


class Terminfo_list_M
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
	function __construct(){
		try{
			$this->objDb = new DB_Pgsql();
			$this->objXml = new Node();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例化");
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: Access_group_list_M
	* コンストラクタ.
	* 
	*/
	function Terminfo_list_M(){
		try{
			$this->__construct();
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: readTermList
	* 条件によってデータを検索して、データのリストを作る.
	* 
	* @param		string		$orderBy		ソーティングする条件.
	* @param		string		$condition		検索の条件.
	* @param		string		$offset			オフセット.
	* @param		string		$pageNum		ページの表示データの件数.
	*
	* @return		Array		$list			データのリスト.
	*/
	function readTermList($orderBy,$condition,$offset,$pageNum)
	{
		try{
			// ページビューのインスタンス化



			$pageObj = new PageView("sv_terminfo",$pageNum,$offset,$this->objDb);
			$pageObj->SetCondition($condition,$orderBy);
			$res = $pageObj->ReadList();
			$resList=$pageObj->MakePage();
			
			$cssFlag = true;
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");
			
			// ループでリストを表示する.
			if(!is_array($res)) return array($resList,$res);
			foreach( $res as $key => $value)
			{
				
				// css仕様を設定する.
				if( $cssFlag ) 
					$res[$key]["css_class"] = "evenrowbg";
				else 
					$res[$key]["css_class"] = "oddrowbg";
				
				$cssFlag = $cssFlag==true?false:true;
			}
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$resList.$res");
			return array($resList,$res);

		}
		catch(exception $e)
		{
			ToLog::ToLogs($ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: deleteTerminfo
	* 指定した端末を削除する.

	*/
	function deleteTerminfo($userid,$termid,$memo){
		try
		{
			$this->objDb->begin();
			$strSql4 = " SELECT COUNT(id)";
			$strSql4.= " FROM sv_terminfo";
			$strSql4.= " WHERE userid = '".$userid."' AND termid = '".$termid."' AND memo = '".$memo."'";
			
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql4");
			$res2 = $this->objDb->ExecuteCommand($strSql4);
			if($res2[0] != 0)
			{
							
				$strSql2  = " DELETE ";
				$strSql2 .= " FROM sv_terminfo ";
				$strSql2 .= " WHERE userid = '".$userid."' AND termid = '".$termid."' AND memo = '".$memo."'";
		
				$this->objDb->ExecuteNonQuery($strSql2);
					
				$sqlselectnum = "SELECT counter from sv_termreg";
				$sqlselectnum.=" Where userid='".$userid."'";
				$selectnum = $this->objDb->ExecuteCommand($sqlselectnum);

				$selectnum[counter]=$selectnum[counter]+1;	
				$sqlup = "update sv_termreg set counter='".$selectnum[counter]."'";
				$sqlup.= " where userid='".$userid."'";
				$resultup=$this->objDb->ExecuteCommand($sqlup);
				
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2");
				
				
			}
			else
			{
				return ;
			}
			$this->objDb->commit();
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			$this->objDb->rollback();
			throw $e;
		}
	}
	
	function exportTermCSV($orderBy,$condition){
		try{
						
			$strSql  = " SELECT userid,termid,memo";
			$strSql .= " FROM sv_terminfo ";
			$strSql .= $condition.$orderBy ;

			//$res = $this->objDb->ExecuteArrayCommand($strSql);
			
			$fileName = G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."TermInfoCSV.csv";
			$fileName2 = SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."TermInfoCSV.csv";
			$pconn = @fopen($fileName,'w+');
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$pconn ){
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}

			// ファイルのタイトル.
			$strTitleSql = "userid,MAC,memo";
			$arrTitle = explode(",",$strTitleSql);

			@fputcsv($pconn,$arrTitle);

			$firstNum = 0;
			
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");
			
			do{
				try{
					$strSelectSql2 = $strSql." LIMIT ".G_DBTOLOG_PER_QUERY_NUM." OFFSET ".$firstNum;
					$res = $this->objDb->ExecuteArrayCommand($strSelectSql2);
					$res = $res==""?array():$res;
					// 指定した配列に関してループ処理を行う.
					foreach( $res as $key => $value){
						//$value[4] = str_replace("-","/",substr($value[4],0,19));
						$value[5] = str_replace("-","/",substr($value[5],0,19));
						$value[6] = str_replace("-","/",substr($value[6],0,19));
						for($j=0;$j<($iNewTitleCount+3);$j++)
						{
							$arrNewValue[$j]=@mb_convert_encoding($value[$j],"sjis-win","UTF-8");
						}
						@fputcsv($pconn,$arrNewValue);
					}
					$firstNum = $firstNum +G_DBTOLOG_PER_QUERY_NUM;
				}catch( Exception $e ){
					break;
				}
			}while( sizeof($res) > 0 );

			$result = @fclose($pconn);
			if( !$result ){
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR202);
			}
			else{
				header("location: ../ctrl/download.php?s=".urlencode($fileName));
				exit;
				
			}
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
}
?>
