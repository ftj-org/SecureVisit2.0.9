<?php
/**
 * epass1000NDのログの検索ファイル.
 *
 * @author 韓陽光.
 * @since  2007-09-07
 * @version 1.0
 */

$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."lib/pageview.php";


class Log_list_M{
	
	/**
	* データベースの実例.
	*/
	var $objDb = null;
	
	var $sysSql = "";
	var $searchSql = "";
	var $sysCount = 0;
	var $viewCreateSql = null;
	
	/**
	* 関数名: __construct
	* コンストラクタ.
	* 
	*/
	function __construct(){		
		try{
			$this->objDb = new DB_Pgsql();
			$this->loglistview();			
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例化");
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: Log_list_M
	* コンストラクタ.
	* 
	*/
	function Log_list_M(){		
		try{
			$this->__construct();
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}

	/**
	* 関数名: loglistView 
	* loglistViewを作成する.
	* 
	*/
	function loglistview()
	{		
		try
		{			
			$strSql = "";
			$this->readTableNew();
			
			//ビューを作る.
			$strSql2  = " CREATE VIEW loglistview AS ";
			$strSql .= " (SELECT";
			$strSql .= $this->sysSql;
			$strSql .= " sv_accesslog.userid,";
			$strSql .= " sv_accesslog.groupname,";
			$strSql .= " sv_accesslog.clientip,";
			$strSql .= " sv_accesslog.tokenid,";
			$strSql .= " sv_accesslog.tokentype,";
			$strSql .= " sv_accesslog.requrl,";
			$strSql .= " sv_accesslog.hostname,";
			$strSql .= " sv_accesslog.url,";
			$strSql .= " sv_accesslog.detail,";
			$strSql .= " sv_accesslog.datetime,";
			$strSql .= " sv_accesslog.status,";
			$strSql .= " 1 as sflag";
			$strSql .= " FROM sv_accesslog";
			$strSql .= " left join sv_user on sv_accesslog.userid = sv_user.userid";
			$strSql .= " left join sv_token on sv_accesslog.tokenid = sv_token.hid";
			
			$strSql .= " UNION ALL";
			
			$strSql .= " SELECT";
			$strSql .= $this->sysSql;
			$strSql .= " sv_authlog.userid,";
			$strSql .= " '' as groupname,";
			$strSql .= " sv_authlog.clientip,";
			$strSql .= " sv_authlog.tokenid,";
			$strSql .= " sv_authlog.tokentype,";
			$strSql .= " sv_authlog.url,";
			$strSql .= " sv_authlog.hostname,";
			$strSql .= " sv_authlog.url,";
			$strSql .= " sv_authlog.detail,";
			$strSql .= " sv_authlog.datetime,";
			$strSql .= " sv_authlog.status,";
			$strSql .= " 2 as sflag";
			$strSql .= " FROM sv_authlog";
			$strSql .= " left join sv_user on sv_authlog.userid = sv_user.userid";
			$strSql .= " left join sv_token on sv_authlog.tokenid = sv_token.hid)";
			
			$this->viewCreateSql = $strSql;
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}

	/**
	* 関数名: readTableNew 

	* ユーザーとトークン属性項目を取得する.
	* 
	*
	* @return		Array		$res		リザルト1から、リザルト2に存在する項目を取得し、返す.
	*/
	function readTableNew(){
		try{
			$resToken = $this->readTokenTableNew();
			$resUser = $this->readUserTableNew();
			
			if( $resToken == "" && $resUser == "" ){
				$this->sysSql = "";
				$this->searchSql = "";
				return array();
			}
			
			$res = array();
			$this->sysSql = "";
			$this->searchSql = "";
			$this->sysCount = 0;
			
			if( $resToken != "" ){
				foreach($resToken as $value){
					if(trim($value["name"])=="") continue;
					$this->sysSql .= " sv_token.".$value["name"]." as t".$value["name"].",";
					$this->searchSql .= " t".$value["name"].",";
					$this->sysCount++;
					
					$tmp = array();
					array_push($tmp, $value["type"]);
					array_push($tmp, "t".$value["name"]);
					array_push($tmp, $value["nameuser"]);
					array_push($tmp, $value["nametoken"]);
					array_push($tmp, 0);
					
					array_push($res, $tmp);
					unset($tmp);
				}
			}
			if( $resUser != "" ){
				foreach($resUser as $value){
					if(trim($value["name"])=="") continue;
					$this->sysSql .= " sv_user.".$value["name"]." as u".$value["name"].",";
					$this->searchSql .= " u".$value["name"].",";
					$this->sysCount++;
					
					$tmp = array();
					array_push($tmp, $value["type"]);
					array_push($tmp, "u".$value["name"]);
					array_push($tmp, $value["nameuser"]);
					array_push($tmp, $value["nametoken"]);
					array_push($tmp, 1);
					
					array_push($res, $tmp);
					unset($tmp);
				}
			}
			
			return $res;			
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: readUserTableNew 
	
	* ユーザー属性項目を取得する.
	* 
	*
	* @return		Array		$res		リザルト1から、リザルト2に存在する項目を取得し、返す.
	*/
	function readUserTableNew(){
		try{
			$strSql  = " SELECT type,";
			$strSql  .= " name,";
			$strSql  .= " nameuser,";
			$strSql  .= " nametoken";
			$strSql  .= " FROM sv_customtmpl";
			$strSql  .= " WHERE visibleuser=true";
			//$strSql  .= " and buser=true"; 
			$strSql  .= " and name in";
			$strSql  .= " (";
			$strSql  .= " select attname ";
			$strSql  .= " from pg_class,pg_attribute"; 
			$strSql  .= " where pg_class.oid = pg_attribute.attrelid";
			$strSql  .= " and pg_class.relname='sv_user'";
			$strSql  .= " and pg_attribute.attisdropped = 'f'";
			$strSql  .= " ) ";
			$strSql  .= " ORDER BY orderuser ASC";
			$res = $this->objDb->ExecuteArrayCommand($strSql);
			
			return $res;			
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: readTokenTableNew 
	
	* トークン属性項目を取得する.
	* 
	*
	* @return		Array		$res		リザルト1から、リザルト2に存在する項目を取得し、返す.
	*/
	function readTokenTableNew(){
		try{
			$strSql  = " SELECT type,";
			$strSql  .= " name,";
			$strSql  .= " nameuser,";
			$strSql  .= " nametoken";
			$strSql  .= " FROM sv_customtmpl";
			$strSql  .= " WHERE visibletoken=true";
			//$strSql  .= " and btoken=true";
			$strSql  .= " and name in";
			$strSql  .= " (";
			$strSql  .= " select attname ";
			$strSql  .= " from pg_class,pg_attribute"; 
			$strSql  .= " where pg_class.oid = pg_attribute.attrelid";
			$strSql  .= " and pg_class.relname='sv_token'";
			$strSql  .= " and pg_attribute.attisdropped = 'f'";
			$strSql  .= " ) ";
			$strSql  .= " ORDER BY ordertoken ASC";
			$res = $this->objDb->ExecuteArrayCommand($strSql);
			
			return $res;			
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	

	/**
	* 関数名: readTableList
	* 条件によってデータを検索して、データのリストを形成する
	* 
	* @param       string    $orderBy        ソーティングする条件.
	* @param       string    $condition      検索の条件.
	* @param       string    $offset         偏りの度合.
	* @param       string    $pageNum        ページごとの表示の記録の数量.
	*
	* @return      Array     $res			   データのリスト.
	*/
	function readTableList($orderBy,$condition,$offset,$pageNum)
	{
		try{
			// オフセットを取得する.
			$pageObj = new PageView("loglistview",$pageNum,$offset ,$this->objDb);
			$pageObj->SetCondition($condition,$orderBy);
			$res = $pageObj->ReadList();
			$resList = $pageObj->MakePage();
			
			$cssFlag = true;
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");
				
			// ループでリストを表示する.
			if(!is_array($res)) return array($resList,$res);
			foreach( $res as $key => $value)
			{
				$res[$key] = $value;
				// css仕様を設定する.
				$res[$key]["datetime"] = str_replace("-","/",substr($value["datetime"],0,19));
				
				if($value["sflag"]==2)
				{
					if( $cssFlag )
						$res[$key]["css_class"] = "authlogbg";
					else
						$res[$key]["css_class"] = "authlogbgrow";
				}
				else
				{
					if( $cssFlag ) 
						$res[$key]["css_class"] = "evenrowbglog";
					else 
						$res[$key]["css_class"] = "oddrowbglog";
				}
				
				$cssFlag = $cssFlag==true?false:true;			
			}
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$resList.$res");	
			return array($resList,$res);
			
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
		
	/**
	* 関数名: exportLogCSV  
	* 入力した条件でデータを検索してかつCSVファイルを作成して、そしてダウンロードする
	* 
	* @param       string    $orderBy        ソーティングする条件.
	* @param       string    $condition      検索の条件.
	*
	* @return      void.
	*/
	function exportLogCSV($orderBy,$condition){
		try{
			
			$iNewTitleCount = $this->sysCount;
			
			$strSynSql = "";
			$first = 0;
			foreach($_SESSION as $key2 => $value2)
			{
				if(substr($key2,0,16) != "SESS_LOG_ISSHOW_") continue;
				if(strtolower(substr($key2,16)) == "urlhostname"){
					continue;
				}
				if($value2 === "0"){
					$strSynSql .= ",";
					$strSynSql .= strtolower(substr($key2,16));
					$first++;
					continue;
				}
				$iNewTitleCount--;
			}
			
			$strSql2  = " SELECT ";
			$strSql2 .= " count(datetime) as num";
			$strSql2 .= " FROM (".$this->viewCreateSql.") as loglistview";
			$strSql2 .= $condition ;
			$logNum = $this->objDb->ExecuteCommand($strSql2);
			if($logNum[0] >= 100000) return "MOREDATA";
						
			$strSql  = " SELECT ";
			$strSql .= " to_char(datetime,'YYYY-MM-DD HH24:MI:SS'),hostname ";
			$strSql .= $strSynSql;
			$strSql .= " FROM (".$this->viewCreateSql.") as loglistview";
			$strSql .= $condition.$orderBy ;
			
			// prepare
			$prepareconn = $this->objDb->PrepareQuery($strSql);
			$fileName = G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."LogListCSV.csv";
			
			// ファイルのタイトル.
			$arrTitle = explode(",","datetime,hostname".$strSynSql);
			$pconn = fopen($fileName,'w+');
					
			// DEBUG MESSAGE
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");
			$strData = "datetime,hostname".$strSynSql."\n";
			if( !$prepareconn ){
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
				exit;
			}
			
			do{
				$res = $this->objDb->PQ_getData($prepareconn);
				// ループで配列の要素を取得する.
				$res = $res==""?array():$res; 
				$strData .= '"'.mb_convert_encoding(implode('","',$res),"sjis-win", "UTF-8").'"'."\n";
				fwrite ($pconn,$strData);
				$strData="";
			}while( sizeof($res) > 0 );
			$this->objDb->Prepare_Free($prepareconn);
			@fclose($pconn);
			header("location: ../ctrl/download.php?s=".urlencode($fileName));
			exit;		
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	function maxlog()
	{
		$strSql2  = " SELECT ";
		$strSql2 .= " count(datetime) as num";
		$strSql2 .= " FROM (".$this->viewCreateSql.") as loglistview";
		$strSql2 .= $condition ;
		$logNum = $this->objDb->ExecuteCommand($strSql2);
		if($logNum[0] >= 100000) return "MOREDATA";
	}	
	
}

?>