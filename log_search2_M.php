<?php
/**
 * epass1000NDのログ管理-ログインしていないユーザーの検索.
 *
 * @author 韓陽光
 * @since  2007-09-17
 * @version 1.0
 */
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."lib/pageview.php";


class log_search2_M{

	/**
	* データベースの実例.
	*/
	var $objDb = null;
	var $viewCreateSql = null;

	/**
	* 関数名: __construct
	* コンストラクタ.
	* 
	*/
	function __construct(){		
		try{
			$this->objDb = new DB_Pgsql();
			$this->logView();
		}
		catch( Exception $e ){
			throw $e;
		}
	}


	/**
	* 関数名: log_search2_M
	* コンストラクタ.
	* 
	*/
	function log_search2_M(){		
		try{
			$this->__construct();
		}
		catch( Exception $e ){
			throw $e;
		}
	}


	/**
	* 関数名: logview  
	* logviewを作成する.
	* 
	*/
	function logView(){		
		try{
			/*try{
				$strSql  = " DROP VIEW logview";
				@$this->objDb->ExecuteNonQuery($strSql);
			}
			catch( Exception $e ){
			}*/
			
			$arrNewTitle = $this->readTableNew();
			$strTitleSql = "";
			
			$arrNewTitle = $arrNewTitle==""?array():$arrNewTitle;
			// 指定した配列に関してループ処理を行って、CSVファイルのタイトルを追加する.
			for($i=0;$i<count($arrNewTitle);$i++)
			{
				if(trim($arrNewTitle[$i]["name"])=="") continue;
				$strTitleSql .= " sv_user.".$arrNewTitle[$i]["name"].",";
			}
			//ビューを作る.
			$strSql  = " CREATE VIEW logview AS ";
			$this->viewCreateSql .= " SELECT";
			$this->viewCreateSql .= " sv_user.userid,";
			$this->viewCreateSql .= " sv_user.fullname,";
			$this->viewCreateSql .= " sv_user.email,";
			$this->viewCreateSql .= " sv_user.memo,";
			$this->viewCreateSql .= " sv_user.access,";
			$this->viewCreateSql .= " sv_user.startdate,";
			$this->viewCreateSql .= " sv_user.expire,";
			$this->viewCreateSql .= " sv_user.token1id,";
			$this->viewCreateSql .= " sv_user.token2id,";
			$this->viewCreateSql .= " sv_user.token3id,";
			$this->viewCreateSql .= " sv_user.pwdid,";
			$this->viewCreateSql .= " sv_user.rpwdid,";
			$this->viewCreateSql .= " sv_user.lastlogindate,";
			$this->viewCreateSql .= $strTitleSql;
			$this->viewCreateSql .= " sv_usergroup.grpid,";
			$this->viewCreateSql .= " sv_group.name,";
			$this->viewCreateSql .= " sv_token.hid,";
			$this->viewCreateSql .= " sv_accesslog.requrl";
			$this->viewCreateSql .= " FROM sv_user";
			$this->viewCreateSql .= " left join sv_usergroup on sv_user.userid = sv_usergroup.userid";
			$this->viewCreateSql .= " left join sv_group on sv_usergroup.grpid = sv_group.id";
			$this->viewCreateSql .= " left join sv_token on sv_token.userid = sv_user.userid";
			$this->viewCreateSql .= " left join sv_accesslog on sv_accesslog.userid = sv_user.userid";
			
			//$strSql .= " WHERE sv_token.access=100";
			
			//$this->objDb->ExecuteNonQuery($strSql.$this->viewCreateSql);
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}


	/**
	* 関数名: readTableNew
	*ユーザー属性項目を取得する.
	* 
	*
	*@return		Array		$res		リザルト1から、リザルト2に存在する項目を取得し、返す.
	*/
	function readTableNew(){
		try{
			$strSql  = " SELECT type,";
			$strSql  .= " name,";
			$strSql  .= " nameuser,";
			$strSql  .= " memouser";
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
	* 関数名: readTableList
	* 条件によってデータを検索して、データのリストを作る.
	* 
	* @param		string		$orderBy		ソーティングする条件.
	* @param		string		$condition		検索の条件.
	* @param		string		$offset			オフセット.
	* @param		string		$pageNum		ページの表示データの件数.
	*
	* @return		Array		$res			データのリスト.
	*/
	function readTableList($orderBy,$condition,$offset,$pageNum){
		try{
			// ページビューのインスタンス化.
			$pageObj = new PageView("(SELECT  DISTINCT ON(userid) * FROM logview ) as temp",$pageNum,$offset ,$this->objDb);
			$pageObj->SetCondition($condition,$orderBy);
			$res = $pageObj->ReadList();
			$resList = $pageObj->MakePage();
			
			$cssFlag = true;
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");
			
			
			
			// ループでリストを表示する.
			if(!is_array($res)) return array($resList,$res);
			foreach( $res as $key => $value)
			{
				$strSql  = " SELECT sv_group.name ";
				$strSql .= " FROM sv_group,sv_usergroup";
				$strSql .= " WHERE sv_usergroup.grpid=sv_group.id";
				$strSql .= " AND sv_usergroup.userid = '".$value["userid"]."'";
				$uGroup = $this->objDb->ExecuteArrayCommand($strSql);
				for( $i = 0;$i<count($uGroup);$i++)
				{
					$uGroup[$i] = $uGroup[$i]; 
				}
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
				for( $i = 0;$i<count($uGroup);$i++)
				{
					$uGroup[$i] = $uGroup[$i][0]; 
				}
				
				$value["startdate"] = str_replace("-","/",substr($value["startdate"],0,10));
				$value["expire"] = str_replace("-","/",substr($value["expire"],0,10));
				$value["lastlogindate"] = str_replace("-","/",substr($value["lastlogindate"],0,10));
				$res[$key] = $value;
				$res[$key]["accessgroup"] = sizeof($uGroup)>=1?implode(",<br>",$uGroup):$uGroup;
				//css仕様を設定する.
				if( $cssFlag ) 
					$res[$key]["css_class"] = "evenrowbg";
				else 
					$res[$key]["css_class"] = "oddrowbg";
				
				$cssFlag = $cssFlag==true?false:true;			
			}
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$resList.$res");	
			return array($resList,$res);
			
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}

	
	/**
	* 関数名: exportLogSTwoCSV
	* 条件によりデータを検索しCSVファイルを作成し、ダウンロードする.
	* 
	* @param		string		$orderBy		ソーティングの条件.
	* @param		string		$condition		検索の条件.
	*
	* @return		void.
	*/
	function exportLogSTwoCSV($orderBy,$condition){
		try{
			$arrNewTitle = $this->readTableNew();
			$strTitleSql = "";
			
			$arrNewTitle = $arrNewTitle==""?array():$arrNewTitle;
			$iNewTitleCount = 0;
			// 指定した配列に関してループ処理を行って、CSVファイルのタイトルを追加する.
			for($i=0;$i<count($arrNewTitle);$i++)
			{
				if(trim($arrNewTitle[$i]["name"])=="") continue;
				$strTitleSql .= " ,".$arrNewTitle[$i]["name"]."";
				$iNewTitleCount++;
			}
			//データのエクスポート.
			$strSql  = " SELECT userid,";
			$strSql .= " name, ";
			$strSql .= " access, ";
			$strSql .= " lastlogindate, ";
			$strSql .= " startdate, ";
			$strSql .= " expire, ";
			$strSql .= " token1id AS token1, ";
			$strSql .= " token2id AS token2, ";
			$strSql .= " token3id AS token3, ";
			$strSql .= " memo ";
			$strSql .= $strTitleSql;
			$strSql .= " FROM (SELECT  DISTINCT ON(userid) * FROM (".$this->viewCreateSql.") as logview ) as temp ";
			$strSql .= $condition.$orderBy ;
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,$strSql);
			//$res = $this->objDb->ExecuteArrayCommand($strSql);
			
			$fileName = G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."LogSTwoCSV.csv";
			$pconn = @fopen($fileName,'w+');
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$pconn ){
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}
			
			// ファイルのタイトル.
			$strTitleSql = "userid,groupname,access,lastlogindate,startdate,exipre,token1,token2,token3,memo".$strTitleSql;
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
					foreach( $res as $key => $value)
					{	
						// ユーザのグループのリザルトを返す.
						$strSql  = " SELECT sv_group.name ";
						$strSql .= " FROM sv_group,sv_usergroup";
						$strSql .= " WHERE sv_usergroup.grpid=sv_group.id";
						$strSql .= " AND sv_usergroup.userid = '".$value["userid"]."'";
						$uGroup = $this->objDb->ExecuteArrayCommand($strSql);
						for( $i = 0;$i<count($uGroup);$i++)
						{
						$uGroup[$i] = $uGroup[$i][0]; 
						}	
						$value[1] = sizeof($uGroup)>=1?implode("/",$uGroup):$uGroup;
						$value[3] = str_replace("-","/",substr($value[3],0,19));
						$value[4] = str_replace("-","/",substr($value[4],0,19));
						$value[5] = str_replace("-","/",substr($value[5],0,19));
						@array_push($value, $group);	
						
						if($value["token1"]!='')
						{
							$strToken1="select hid from sv_token where id = '".$value["token1"]."'";
							$value[6] = $this->objDb->ExecuteCommand($strToken1);
							$value[6]=$value[6][0];
						}
						if($value["token2"]!='')
						{
							$strToken2="select hid from sv_token where id = '".$value["token2"]."'";
							$value[7] = $this->objDb->ExecuteCommand($strToken2);
							$value[7]=$value[7][0];
						}
						if($value["token3"]!='')
						{
							$strToken3="select hid from sv_token where id = '".$value["token3"]."'";
							$value[8] = $this->objDb->ExecuteCommand($strToken3);
							$value[8]=$value[8][0];
						}
						for($j=0;$j<($iNewTitleCount+10);$j++)
						$arrNewValue[$j]=mb_convert_encoding($value[$j],"sjis-win", "UTF-8");
							//$arrNewValue[$j]=$value[$j];				
						
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
			else
			{
				header("location: ../ctrl/download.php?s=".urlencode($fileName));
				exit;
				
			}
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	function exportLogSTwoNCSV($orderBy,$condition){
	try{
		$arrNewTitle = $this->readTableNew();
		$strTitleSql = "";
		
		$arrNewTitle = $arrNewTitle==""?array():$arrNewTitle;
		$iNewTitleCount = 0;
		// 指定した配列に関してループ処理を行って、CSVファイルのタイトルを追加する.
		for($i=0;$i<count($arrNewTitle);$i++)
		{
			if(trim($arrNewTitle[$i]["name"])=="") continue;
			$strTitleSql .= " ,".$arrNewTitle[$i]["name"]."";
			$iNewTitleCount++;
		}
			
			$fileName = G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."LogSTwoCSV.csv";
			$pconn = @fopen($fileName,'w+');
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$pconn )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}
			
			// ファイルのタイトル.
			$strTitleSql = "userid,groupname,access,lastlogindate,startdate,exipre,token1,token2,token3,memo".$strTitleSql;
			$arrTitle = explode(",",$strTitleSql);
			
			@fputcsv($pconn,$arrTitle);
			
			$result = @fclose($pconn);
			if( !$result )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR202);
			}
			else
			{
				header("location: ../ctrl/download.php?s=".urlencode($fileName));
				exit;
			}
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
	    }
  }	
}

?>