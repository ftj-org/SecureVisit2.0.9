<?php
/**
 * epass1000NDの認証DB・設定ファイル-認証データベース・設定ファイルのバックアップ管理.
 *
 * @author 韓陽光

 * @since  2007-09-17
 * @version 1.0
 * 
 * @history : updata by 于大興

 * @since 2008-03-01
 * @history : updata by liyang

 * @since 2010-09-01 

 */

$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."lib/page.php";
require_once $G_APPPATH[0]."lib/pageview.php";
require_once $G_APPPATH[0]."mdl/dbview.php";
require_once $G_APPPATH[0]."/lib/xmlread.php";

class db_backup_M{
	
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
		try{
			$this->objXml = new Node();
			$this->objDb = new DB_Pgsql();
		}
		catch( Exception $e ){
			throw $e;
		}
	}
	
	
	/**
	* 関数名: db_backup_M
	* コンストラクタ.
	* 
	*/
	function db_backup_M()
	{
		try{
			$this->__construct();
		}
		catch( Exception $e ){
			throw $e;
		}
	}
	
	/**
	* 関数名: arrayCheck
	* 導入されたパラメーターは配列かどうかを判断してから、配列に戻る
	*
	*
	* Phase2 added by yudx
	* @param      object   $arr
	* @return     array      array() or $arr
	*/
	function arrayCheck($arr)
	{
		if(is_array($arr))
		{
			return $arr;
		}
		return array();
	}
	
	/**
	* 関数名: nArrDataCheck
	* 読み込まれたパラメーターは0を上回るかどうかを判断し、0を上回る場合はtrueに戻り、0を下回る場合はfalseに戻る。
	
	*
	*
	* Phase2 added by yudx
	* @param      int   $n
	* @return      boolen
	*/
	function nArrDataCheck($n)
	{
		if($n < 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	* 関数名: exportDB
	* 条件によりデータを検索しCSVファイルを作成し、ダウンロードする.
	* 
	*
	* Phase2 added by yudx
	* @return       void.
	*/
	function exportDB()
	{
		// dir checking
		if(!is_dir(G_DOC_TMP))
		{
			mkdir(G_DOC_TMP);
		}
		
		$fileName = G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."s20db_bak.txt";
		
		try
		{
			// open file as stream
			$pconn = @fopen($fileName,'w+');
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$pconn )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}
			
			// 01/08..# バージョンの書き込み
			// 20091009 db_bak.txtバージョンアップ必要です。（1.1→1.2）
			// 20100901 >>1.4  端末認証
			fwrite($pconn,VERSION."1.4\r\n\r\n\r\n");
			
			// 02/08..# TokenType Start
			fwrite($pconn,TOKENTYPE_START."\r\n");
			fwrite($pconn,"value\r\n");            
			// sv_tokentype　のデータを読み込む
			$strTokenTypeSQL = " select * from sv_tokentype order by id";
			$res = $this->objDb->ExecuteArrayCommand($strTokenTypeSQL);
			// データの書き込み
			foreach($this->arrayCheck($res) as $key => $value)
			{
				fwrite($pconn, "\"".$value["value"]."\"\r\n");
			}
			fwrite($pconn,TOKENTYPE_END."\r\n\r\n\r\n");
			
			// 03/08..# CustAttr start
			fwrite($pconn,CUSTATTR_START."\r\n");
			fwrite($pconn,"name,type,nameuser,nametoken,visibleuser,visibletoken,memouser,memotoken,orderuser,ordertoken\r\n");
			//  sv_customtmpl のデータを読み込む
			$strCustomtmplSQL = " select name,type,nameuser,nametoken,visibleuser,visibletoken,memouser,memotoken,orderuser,ordertoken from sv_customtmpl";
			$res = $this->objDb->ExecuteArrayCommand($strCustomtmplSQL);
			// データの書き込み
			foreach($this->arrayCheck($res) as $key => $value)
			{
				$strLine = "\"".$value["name"];
				$strLine.= $value["type"] == "f" ? "\",\""."0" : "\",\""."1";
				$strLine.= "\",\"".$value["nameuser"];
				$strLine.= "\",\"".$value["nametoken"];
				$strLine.= $value["visibleuser"] == "f" ? "\",\""."0" : "\",\""."1";
				$strLine.= $value["visibletoken"] == "f" ? "\",\""."0" : "\",\""."1";
				$strLine.= "\",\"".$value["memouser"];
				$strLine.= "\",\"".$value["memotoken"];
				$strLine.= "\",\"".$value["orderuser"];
				$strLine.= "\",\"".$value["ordertoken"];
				$strLine.="\"\r\n";
				
				fwrite($pconn,$strLine);
			}
			fwrite($pconn,CUSTATTR_END."\r\n\r\n\r\n");
			
			// 04/08..# Token Start
			fwrite($pconn,TOKEN_START."\r\n");
			// sv_token 中の動的な属性名を取得する
			$res1 = $this->objDb->ExecuteArrayCommand(" select name from sv_customtmpl  where btoken = true");
			$res2 = $this->objDb->metadata("sv_token");
			$arrCol = array();
			$strTokenTitle ="hid,type,secret,bchangepin,access,startdate,expire,userid,memo";
			foreach($this->arrayCheck($res1) as $key1 => $value1)
			{
				foreach($this->arrayCheck($res2) as $key2 => $value2)
				{
					if( $value1["name"] == $value2["name"] )
					{
						$strTokenTitle.= ",".$value1["name"];
						$arrCol[$key1] =$value1["name"];
					}
				}
			}
			$strTokenTitle.="\r\n";
			// タイトルに書き込む
			fwrite($pconn,$strTokenTitle);
			// データの取得
			$strTokenSQL = " select hid,value as type,secret,bchangepin,access,startdate,expire,userid,memo" ;
			foreach($arrCol as $key => $value)
			{
				$strTokenSQL.=",".$value;
			}
			$strTokenSQL.= " from sv_token a left join sv_tokentype b on a.typeid = b.id";
			$res = $this->objDb->ExecuteArrayCommand($strTokenSQL);
			// データの書き込み
			foreach($this->arrayCheck($res) as $key => $value)
			{
				$strLine = "\"".$value["hid"];
				$strLine.= "\",\"".$value["type"];
				$strLine.= "\",\"".trim($value["secret"]);
				$strLine.= $value["bchangepin"] == "t" ? "\",\""."1" : "\",\""."0";
				$strLine.= "\",\"".$value["access"];
				$strLine.= "\",\"".substr($value["startdate"],0,19);
				$strLine.= "\",\"".substr($value["expire"],0,19);
				$strLine.= "\",\"".$value["userid"];
				$strLine.= "\",\"".$value["memo"];
				foreach($this->arrayCheck($arrCol) as $colkey => $colvalue)
				{
					$strLine.= "\",\"".$value[$colvalue];
				}
				$strLine.= "\"\r\n";
				
				fwrite($pconn,$strLine);
			}
			fwrite($pconn,TOKEN_END."\r\n\r\n\r\n");
			
			// 05/08..# Group start
			fwrite($pconn,GROUP_START."\r\n");
			fwrite($pconn,"name,memo,startdate,expire\r\n");
			// データの取得
			$strGroupSQL = " select name,memo,startdate,expire from sv_group";
			$res = $this->objDb->ExecuteArrayCommand($strGroupSQL);
			// データの書き込み
			foreach($this->arrayCheck($res) as $key => $value)
			{
				$strLine = "\"".$value["name"];
				$strLine.= "\",\"".$value["memo"];
				$strLine.= "\",\"".substr($value["startdate"],0,19);
				$strLine.= "\",\"".substr($value["expire"],0,19);
				$strLine.="\"\r\n";
				
				fwrite($pconn,$strLine);
			}
			fwrite($pconn,GROUP_END."\r\n\r\n\r\n");
			
			// 06/08..# User start
			fwrite($pconn,USER_START."\r\n");
			// sv_user 中の動的な属性名を取得する
			$res1 = $this->objDb->ExecuteArrayCommand(" select name from sv_customtmpl  where buser = true");
			$res2 = $this->objDb->metadata("sv_user");
			$arrCol = array();
			$strUserTitle ="userid,fullname,email,memo,access,startdate,expire,lastlogindate";
			foreach($this->arrayCheck($res1) as $key1 => $value1)
			{
				foreach($this->arrayCheck($res2) as $key2 => $value2)
				{
					if( $value1["name"] == $value2["name"] )
					{
						$strUserTitle.= ",".$value1["name"];
						$arrCol[$key1] =$value1["name"];
					}
				}
			}
			$strUserTitle.="\r\n";
			// タイトルに書き込む
			fwrite($pconn,$strUserTitle);
			// データの取得
			$strUserSQL = " select userid,fullname,email,memo,access,startdate,expire,lastlogindate" ;
			foreach($this->arrayCheck($arrCol) as $key => $value)
			{
				$strUserSQL.=",".$value;
			}
			$strUserSQL.= " from sv_user";
			$res = $this->objDb->ExecuteArrayCommand($strUserSQL);
			// データの書き込み
			foreach($this->arrayCheck($res) as $key => $value)
			{
				$strLine = "\"".$value["userid"];
				$strLine.= "\",\"".$value["fullname"];
				$strLine.= "\",\"".$value["email"];
				$strLine.= "\",\"".$value["memo"];
				$strLine.= "\",\"".$value["access"];
				$strLine.= "\",\"".substr($value["startdate"],0,19);
				$strLine.= "\",\"".substr($value["expire"],0,19);
				$strLine.= "\",\"".substr($value["lastlogindate"],0,19);
				foreach($arrCol as $colkey => $colvalue)
				{
					$strLine.= "\",\"".$value[$colvalue];
				}
				$strLine.= "\"\r\n";
				
				fwrite($pconn,$strLine);
			}
			fwrite($pconn,USER_END."\r\n\r\n\r\n");
			
			// 07/08..# UserGroup start
			fwrite($pconn,USERGROUP_START."\r\n");
			fwrite($pconn,"userid,group\r\n");
			$strUserGroupSQL = " select sv_usergroup.userid,sv_group.name as group from sv_usergroup left join sv_group on sv_usergroup.grpid = sv_group.id";
			$res = $this->objDb->ExecuteArrayCommand($strUserGroupSQL);
			foreach($this->arrayCheck($res) as $key => $value)
			{
				fwrite($pconn,"\"".$value["userid"]."\",\"".$value["group"]."\"\r\n");
			}
			fwrite($pconn,USERGROUP_END."\r\n\r\n\r\n");
			
			// 08/08..# Pwd start
			fwrite($pconn,PWD_START."\r\n");
			fwrite($pconn,"pwd,access,startdate,expire,counter,userid,pwdtype\r\n");
			// データの取得
			$strPwdSQL = " (select a.pwd,a.access,a.startdate,a.expire,a.counter,b.userid,1 as pwdtype from sv_password a left join sv_user b on a.id = b.pwdid where b.userid is not null)";
			$strPwdSQL.= " union ";
			$strPwdSQL.= " (select a.pwd,a.access,a.startdate,a.expire,a.counter,b.userid,2 as pwdtype from sv_password a left join sv_user b on a.id = b.rpwdid where b.userid is not null)";
			$res = $this->objDb->ExecuteArrayCommand($strPwdSQL);
			// データの書き込み
			foreach($this->arrayCheck($res) as $key => $value)
			{
				$strLine = "\"".$value["pwd"];
				$strLine.= "\",\"".$value["access"];
				$strLine.= "\",\"".substr($value["startdate"],0,19);
				$strLine.= "\",\"".substr($value["expire"],0,19);
				$strLine.= "\",\"".$value["counter"];
				$strLine.= "\",\"".$value["userid"];
				$strLine.= "\",\"".$value["pwdtype"];
				$strLine.= "\"\r\n";
				
				fwrite($pconn,$strLine);
			}
			fwrite($pconn,PWD_END."\r\n\r\n\r\n");
			
			
			// 2009/06/23 UserParam について追加する。start
			fwrite($pconn,PAR_START."\r\n");
			fwrite($pconn,"userid,tag,value\r\n");
			$strParSQL = " select userid,tag,value from sv_userparam ";
			$res = $this->objDb->ExecuteArrayCommand($strParSQL);
			foreach($this->arrayCheck($res) as $key => $value)
			{
				$strLine = "\"".$value["userid"];
				$strLine.= "\",\"".$value["tag"];
				$strLine.= "\",\"".$value["value"];
				$strLine.= "\"\r\n";
				fwrite($pconn,$strLine);
			}
			fwrite($pconn,PAR_END."\r\n\r\n\r\n");
			// 2009/06/23 UserParam について追加する。end

			// 2010/09/01 TermReg について追加する。start by liyang
			fwrite($pconn,TERMREG_START."\r\n");
			fwrite($pconn,"userid,ticket,counter\r\n");
			$strTermRegSQL = " select userid,ticket,counter from sv_termreg ";
			$res = $this->objDb->ExecuteArrayCommand($strTermRegSQL);
			foreach($this->arrayCheck($res) as $key => $value)
			{
				$strLine = "\"".$value["userid"];
				$strLine.= "\",\"".$value["ticket"];
				$strLine.= "\",\"".$value["counter"];
				$strLine.= "\"\r\n";
				fwrite($pconn,$strLine);
			}
			fwrite($pconn,TERMREG_END."\r\n\r\n\r\n");
			// 2010/09/01 TermReg について追加する。end

			// 2010/09/01 TermInfo について追加する。start by liyang
			fwrite($pconn,TERMINFO_START."\r\n");
			fwrite($pconn,"userid,termid,memo\r\n");
			$strTermInfoSQL = " select userid,termid,memo from sv_terminfo ";
			$res = $this->objDb->ExecuteArrayCommand($strTermInfoSQL);
			foreach($this->arrayCheck($res) as $key => $value)
			{
				$strLine = "\"".$value["userid"];
				$strLine.= "\",\"".$value["termid"];
				$strLine.= "\",\"".$value["memo"];
				$strLine.= "\"\r\n";
				fwrite($pconn,$strLine);
			}
			fwrite($pconn,TERMINFO_END."\r\n\r\n\r\n");
			// 2010/09/01 TermInfo について追加する。end
			
			// 2009/10/09 LogConfigについて追加する。start
			fwrite($pconn,LOGCONFIG_START."\r\n");
			fwrite($pconn,"name,value\r\n");
			$strLogConfigSQL ="select name,value from sv_logconfig ";
			$resLogConfig = $this->objDb->ExecuteArrayCommand($strLogConfigSQL);
			foreach($this->arrayCheck($resLogConfig) as $key => $value)
			{
				$strLine = "\"".$value["name"];
				$strLine.= "\",\"".$value["value"];
				$strLine.= "\"\r\n";
				fwrite($pconn,$strLine);
			}
			fwrite($pconn,LOGCONFIG_END."\r\n\r\n\r\n");
			// 2009/10/09 LogConfig について追加する。end
			
			// // 2009/10/09 AdminLogMsgについて追加する。start
			// fwrite($pconn,ADMINMSGLOG_START."\r\n");
			// fwrite($pconn,"id,opname,opcore,oprecord\r\n");
			// $strAdminLogMsgSql = "select id,opname,opcore,oprecord from sv_adminlogmsg ";
			// $resAdminLogMsg =  $this->objDb->ExecuteArrayCommand($strAdminLogMsgSql);
			// foreach($this->arrayCheck($resAdminLogMsg) as $key => $value)
			// {
				// $strLine = "\"".$value["id"];
				// $strLine.= "\",\"".$value["opname"];
				// $strLine.= "\",\"".$value["opcore"];
				// $strLine.= "\",\"".$value["oprecord"];
				// $strLine.= "\"\r\n";
				// fwrite($pconn,$strLine);
			// }
			// fwrite($pconn,ADMINMSGLOG_END);
			// // 2009/10/09 AdminLogMsg について追加する。end
			
			
			// finish file writting
			$result = @fclose($pconn);
			if( !$result )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR202);
			}
			
			header("location: ../ctrl/download.php?s=".urlencode($fileName));
			exit;
		}
		catch( Exception $e )
		{
			return;
		}
	}
	
	
	/**
	* 関数名: inputDB
	* CSVファイルをインポートする.
	* 
	*
	* Phase2 added by yudx
	* @return       void.
	*/
	function inputDB()
	{
		// バックアップファイル を dbsql.sqlに 導入する
		try
		{
			@PAGE::SaveUplfile("file_name",G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
		}
		catch (Exception $e)
		{
			// delete dbsql.sql
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
			return "readfail";
		}
		// ノードの行数
		$key1;$key2;
		
		// バックアップファイルを配列に書き直す
		$arrFile = @file(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
		
		if(!$arrFile)
		{
			// delete dbsql.sql
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
			return "readfail";
		}
		
		// 有効なノードカウンター
		$nTimesCount = 0;
		
		// 指定した配列に 関してループ処理を行う
		foreach($this->arrayCheck($arrFile) as $key => $value)
		{
			// 空行 配列に 関するループ処理の続き
			if(strlen(trim($value)) == 0) continue;
			
			// 01/08..# version のノードに属するデータ
			if(stristr($value,VERSION))
			{
				
				if(stristr($value, VERSION."1.0"))
				{
					$_SESSION["version"]="0";
				}
				elseif(stristr($value, VERSION."1.1"))
				{
					$_SESSION["version"]="1";
				}
				elseif(stristr($value, VERSION."1.2"))
				{
					$_SESSION["version"]="2";
				}
				elseif(stristr($value, VERSION."1.3"))
				{
					$_SESSION["version"]="3";
				}
				else
				{
					$_SESSION["version"]="4";
				}
				
				if(strlen(trim($value)) <= 15)
				{
					$nTimesCount+=1;
					continue;
				}
				
				// delete dbsql.sql
				@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
				return "version";
			}
			
			// 02/08..# sv_tokentype のノードに属するデータ
			if(stristr($value,TOKENTYPE_START))
			{
				$key1 = $key;
				continue;
			}
			if(stristr($value,TOKENTYPE_END))
			{
				$key2 = $key;
				if(!$this->nArrDataCheck($key2-$key1-2))
				{
					// delete dbsql.sql
					@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
					return "readfail";
				}
				$arrTokenType = array_slice($arrFile,$key1+2,$key2-$key1-2);
				$nTimesCount+=1;
				continue;
			}
			
			// 03/08..# sv_customtmpl のノードに属するデータ
			if(stristr($value,CUSTATTR_START))
			{
				$key1 = $key;
				continue;
			}
			if(stristr($value,CUSTATTR_END))
			{
				$key2 = $key;
				if(!$this->nArrDataCheck($key2-$key1-2))
				{
					// delete dbsql.sql
					@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
					return "readfail";
				}
				$arrCustattr = array_slice($arrFile,$key1+2,$key2-$key1-2);
				foreach($this->arrayCheck($arrCustattr) as $key => $value)
				{
					$arrCustattr[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
				}
				$nTimesCount+=1;
				continue;
			}
			
			// 04/08..# sv_token のノードに属するデータ
			if(stristr($value,TOKEN_START))
			{
				$key1 = $key;
				continue;
			}
			if(stristr($value,TOKEN_END))
			{
				$key2 = $key;
				if(!$this->nArrDataCheck($key2-$key1-2))
				{
					// delete dbsql.sql
					@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
					return "readfail";
				}
				$arrTokenTitle = array_slice($arrFile,$key1+1,1);
				$arrTokenTitle = preg_split("/,/",trim($arrTokenTitle[0]));
				$arrToken = array_slice($arrFile,$key1+2,$key2-$key1-2);
				foreach($this->arrayCheck($arrToken) as $key => $value)
				{
					$arrToken[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
				}
				$nTimesCount+=1;
				continue;
			}
			
			// 05/08..# sv_group のノードに属するデータ
			if(stristr($value,GROUP_START))
			{
				$key1 = $key;
				continue;
			}
			if(stristr($value,GROUP_END))
			{
				$key2 = $key;
				if(!$this->nArrDataCheck($key2-$key1-2))
				{
					// delete dbsql.sql
					@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
					return "readfail";
				}
				$arrGroup = array_slice($arrFile,$key1+2,$key2-$key1-2);
				foreach($this->arrayCheck($arrGroup) as $key => $value)
				{
					$arrGroup[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
				}
				$nTimesCount+=1;
				continue;
			}
			
			// 06/08..# sv_usergroup のノードに属するデータ
			if(stristr($value,USER_START))
			{
				$key1 = $key;
				continue;
			}
			if(stristr($value,USER_END))
			{
				$key2 = $key;
				if(!$this->nArrDataCheck($key2-$key1-2))
				{
					// delete dbsql.sql
					@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
					return "readfail";
				}
				$arrUserTitle = array_slice($arrFile,$key1+1,1);
				$arrUserTitle = preg_split("/,/",trim($arrUserTitle[0]));
				$arrUser = array_slice($arrFile,$key1+2,$key2-$key1-2);
				foreach($this->arrayCheck($arrUser) as $key => $value)
				{
					$arrUser[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
				}
				$nTimesCount+=1;
				continue;
			}
			
			// 07/08..# sv_usergroup のノードに属するデータ
			if(stristr($value,USERGROUP_START))
			{
				$key1 = $key;
				continue;
			}
			if(stristr($value,USERGROUP_END))
			{
				$key2 = $key;
				if(!$this->nArrDataCheck($key2-$key1-2))
				{
					// delete dbsql.sql
					@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
					return "readfail";
				}
				$arrUserGroup = array_slice($arrFile,$key1+2,$key2-$key1-2);
				foreach($this->arrayCheck($arrUserGroup) as $key => $value)
				{
					$arrUserGroup[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
				}
				$nTimesCount+=1;
				continue;
			}
			
			// 08/08..# sv_password のノードに属するデータ
			if(stristr($value,PWD_START))
			{
				$key1 = $key;
				continue;
			}
			if(stristr($value,PWD_END))
			{
				$key2 = $key;
				if(!$this->nArrDataCheck($key2-$key1-2))
				{
					// delete dbsql.sql
					@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
					return "readfail";
				}
				$arrPwd = array_slice($arrFile,$key1+2,$key2-$key1-2);
				foreach($this->arrayCheck($arrPwd) as $key => $value)
				{
					$arrPwd[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
				}
				$nTimesCount+=1;
				continue;
			}
			if($_SESSION["version"] > 0 )
			{
				if(stristr($value,PAR_START))
				{
					$key1 = $key;
					continue;
				}
				if(stristr($value,PAR_END))
				{
					$key2 = $key;
					if(!$this->nArrDataCheck($key2-$key1-2))
					{
						// delete dbsql.sql
						@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
						return "readfail";
					}
					$arrPAR = array_slice($arrFile,$key1+2,$key2-$key1-2);
					foreach($this->arrayCheck($arrPAR) as $key => $value)
					{
						$arrPAR[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
					}
					$nTimesCount+=1;
					continue;
				}
			}
			//liyang
			if($_SESSION["version"] > 3 )
			{
				if(stristr($value,TERMREG_START))
				{
					$key1 = $key;
					continue;
				}
				if(stristr($value,TERMREG_END))
				{
					$key2 = $key;
					if(!$this->nArrDataCheck($key2-$key1-2))
					{
						// delete dbsql.sql
						@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
						return "readfail";
					}
					$arrTERMREG = array_slice($arrFile,$key1+2,$key2-$key1-2);
					foreach($this->arrayCheck($arrTERMREG) as $key => $value)
					{
						$arrTERMREG[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
					}
					$nTimesCount+=1;
					continue;
				}
			}
			//liyang
			if($_SESSION["version"] > 3 )
			{
				if(stristr($value,TERMINFO_START))
				{
					$key1 = $key;
					continue;
				}
				if(stristr($value,TERMINFO_END))
				{
					$key2 = $key;
					if(!$this->nArrDataCheck($key2-$key1-2))
					{
						// delete dbsql.sql
						@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
						return "readfail";
					}
					$arrTERMINFO = array_slice($arrFile,$key1+2,$key2-$key1-2);
					foreach($this->arrayCheck($arrTERMINFO) as $key => $value)
					{
						$arrTERMINFO[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
					}
					$nTimesCount+=1;
					continue;
				}
			}
			
			// 2009/10/09 LogConfigとAdminLogMsg insert start
			if($_SESSION["version"] > 1)
			{
				if(stristr($value,LOGCONFIG_START))
				{
					$key1 = $key;
					continue;
				}
				if(stristr($value,LOGCONFIG_END))
				{
					$key2 = $key;
					if(!$this->nArrDataCheck($key2-$key1-2))
					{
						// delete dbsql.sql
						@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
						return "readfail";
					}
					$arrLogConfig = array_slice($arrFile,$key1+2,$key2-$key1-2);
					foreach($this->arrayCheck($arrLogConfig) as $key => $value)
					{
						$arrLogConfig[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
					}
					//print_r($arrLogConfig);
					$nTimesCount+=1;
					continue;
				}
				
				// if(stristr($value,ADMINMSGLOG_START))
				// {	
					// $key1 = $key;
					// continue;
				// }
				// if(stristr($value,ADMINMSGLOG_END))
				// {
					// $key2 = $key;
					// if(!$this->nArrDataCheck($key2-$key1-2))
					// {
						// // delete dbsql.sql
						// @unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
						// return "readfail";
					// }
					// $arrAdminMsg = array_slice($arrFile,$key1+2,$key2-$key1-2);
					// foreach($this->arrayCheck($arrAdminMsg) as $key => $value)
					// {
						// $arrAdminMsg[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
					// }
					// $nTimesCount+=1;
					// continue;
				// }
				
			}
			// 2009/10/09 LogConfigとAdminLogMsg insert end
			
		}

		// 十一個のノード
		if( $_SESSION["version"] > 3)
		{
			if($nTimesCount != 12)
			{
				// delete dbsql.sql
				@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
				return "readfail";
			}
		}		
		
		// 十個のノード
		if( $_SESSION["version"]==2)
		{
			if($nTimesCount != 10)
			{
				// delete dbsql.sql
				@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
				return "readfail";
			}
		}
		// 九つのノード
		if( $_SESSION["version"]==1)
		{
			if($nTimesCount != 9)
			{
				// delete dbsql.sql
				@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
				return "readfail";
			}
		}
		// 八つのノード
		if( $_SESSION["version"]==0)
		{
			if($nTimesCount != 8)
			{
				// delete dbsql.sql
				@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
				return "readfail";
			}
			
		}
		
		// import db
		try
		{
			$this->objDb->begin();
			
			// 01/07..# import TokenType
			// sv_tokentype のデータの削除
			$this->objDb->ExecuteNonQuery("DELETE FROM sv_tokentype");
			foreach($this->arrayCheck($arrTokenType) as $key => $value)
			{
				$key = $key+1;
				$strTokenTypeSQL = " insert into sv_tokentype (id,value)";
				$strTokenTypeSQL.= " values (".$key.",'".addslashes(trim(trim($value),"\""))."')";
				$this->objDb->ExecuteNonQuery($strTokenTypeSQL);
			}
			
			if( $_SESSION["version"] < 3)
			{
				$strTokenTypeSQL = " insert into sv_tokentype (id,value) values ( 3,'ePass2001H')";
				$this->objDb->ExecuteNonQuery($strTokenTypeSQL);
			}
			//echo "1111";
			// SET index start
			$strIndexSQL = "SELECT pg_catalog.setval('sv_tokentype_id_seq',";
			$strIndexSQL.= sizeof($arrTokenType) > 1 ? sizeof($arrTokenType) - 1 : 1;
			$strIndexSQL.= ", true)";
			$this->objDb->ExecuteNonQuery($strIndexSQL);
			
			
			// 02/07..# import CustAttr
			// sv_customtmpl のデータの削除
			$this->objDb->ExecuteNonQuery("DELETE FROM sv_customtmpl");
			// sv_customtmpl のデータの導入
			foreach($this->arrayCheck($arrCustattr) as $key => $value)
			{
				$strCustAttrSQL = " insert into sv_customtmpl (name,type,nameuser,nametoken,visibleuser,visibletoken,buser,btoken,memouser,memotoken,lastupdate,orderuser,ordertoken) ";
				$strCustAttrSQL.= " values ('";
				$strCustAttrSQL.= addslashes($value[0]);
				$strCustAttrSQL.= $value[1] == "0" ? "',false" : "',true";
				$strCustAttrSQL.= ",'".addslashes($value[2]);
				$strCustAttrSQL.= "','".addslashes($value[3]);
				$strCustAttrSQL.= $value[4] == "0" ? "',false" : "',true";
				$strCustAttrSQL.= $value[5] == "0" ? ",false" : ",true";
				$strCustAttrSQL.= ",true,true";
				$strCustAttrSQL.= ",'".addslashes($value[6]);
				$strCustAttrSQL.= "','".addslashes($value[7]);
				$strCustAttrSQL.= "',current_timestamp";
				$strCustAttrSQL.= ",".$value[8];
				$strCustAttrSQL.= ",".$value[9];
				$strCustAttrSQL.= ")";
				
				$this->objDb->ExecuteNonQuery($strCustAttrSQL);
			}
			
			
			// 03/07..# import Pwd
			// sv_password のデータの削除
			$this->objDb->ExecuteNonQuery("DELETE FROM sv_password");
			// sv_password のデータの導入
			foreach($this->arrayCheck($arrPwd) as $key => $value)
			{
				$strPwdSQL = " insert into sv_password (id,pwd,access,startdate,expire,counter)";
				$strPwdSQL.= " values (";
				$strPwdSQL.= $key;
				$strPwdSQL.= ",'".$value[0];
				$strPwdSQL.= "',".$value[1];
				$strPwdSQL.= ",'".substr($value[2],0,19);
				$strPwdSQL.= "','".substr($value[3],0,19);
				$strPwdSQL.= "',".$value[4];
				$strPwdSQL.=")";
				
				$this->objDb->ExecuteNonQuery($strPwdSQL);
			}
			
			// SET index start
			$strIndexSQL = "SELECT pg_catalog.setval('sv_password_id_seq',";
			$strIndexSQL.= sizeof($arrPwd) > 1 ? sizeof($arrPwd) - 1 : 1;
			$strIndexSQL.= ", true)";
			$this->objDb->ExecuteNonQuery($strIndexSQL);
			
			
			// 04/07..# import Group
			// sv_group のデータの削除
			$this->objDb->ExecuteNonQuery("DELETE FROM sv_group");
			// sv_group のデータの導入
			foreach($this->arrayCheck($arrGroup) as $key => $value)
			{
				$strGroupSQL = " insert into sv_group (id,name,memo,startdate,expire,lastupdate)";
				$strGroupSQL.= "values (";
				$strGroupSQL.= $key;
				$strGroupSQL.= ",'".addslashes($value[0]);
				$strGroupSQL.= "','".addslashes($value[1]); // 返回值含有单引号
				$strGroupSQL.= "','".substr($value[2],0,19);
				$strGroupSQL.= "','".substr($value[3],0,19);
				$strGroupSQL.= "',current_timestamp )";
				
				$this->objDb->ExecuteNonQuery($strGroupSQL);
			}
			
			// SET index start
			$strIndexSQL = "SELECT pg_catalog.setval('sv_group_id_seq',";
			$strIndexSQL.= sizeof($arrGroup) > 1 ? sizeof($arrGroup) - 1 : 1;
			$strIndexSQL.= ", true)";
			$this->objDb->ExecuteNonQuery($strIndexSQL);
			
			
			// 05/07..# import UserGroup     
			// sv_usergroup のデータの削除
			$this->objDb->ExecuteNonQuery("DELETE FROM sv_usergroup");
			// sv_usergroup のデータの導入
			foreach($this->arrayCheck($arrUserGroup) as $key => $value)
			{
				foreach($this->arrayCheck($arrGroup) as $key1 => $value1)
				{
					if($value[1] == $value1[0])
					{
						$strUserGroupSQL = " insert into sv_usergroup (id,userid,grpid)";
						$strUserGroupSQL.= " values (";
						$strUserGroupSQL.= $key;
						$strUserGroupSQL.= ",'".addslashes($value[0]);
						$strUserGroupSQL.= "',".$key1;
						$strUserGroupSQL.= " )";
						
						$this->objDb->ExecuteNonQuery($strUserGroupSQL);
					}
				}
			}
			
			// SET index start
			$strIndexSQL = "SELECT pg_catalog.setval('sv_usergroup_id_seq',";
			$strIndexSQL.= sizeof($arrUserGroup) > 1 ? sizeof($arrUserGroup) - 1 : 1;
			$strIndexSQL.= ", true)";
			$this->objDb->ExecuteNonQuery($strIndexSQL);
			
			if($_SESSION["version"] > 0)
			{
				// 2009/06/23..# import UserParam start
				$this->objDb->ExecuteNonQuery("DELETE FROM sv_userparam");
				// sv_userparam のデータの導入
				foreach($this->arrayCheck($arrPAR) as $key => $value)
				{
					$strParSQL ="insert into sv_userparam(id,userid,tag,value)";
					$strParSQL.=" values (";
					$strParSQL.=$key;
					$strParSQL.=",'".addslashes($value[0]);
					$strParSQL.="','".addslashes($value[1]);
					$strParSQL.="','".addslashes($value[2]). "')";
					$this->objDb->ExecuteNonQuery($strParSQL);
				}
				// SET index start
				$strIndexSQL = "SELECT pg_catalog.setval('sv_userparam_id_seq',";
				$strIndexSQL.= sizeof($arrPAR) > 1 ? sizeof($arrPAR) - 1 : 1;
				$strIndexSQL.= ", true)";
				$this->objDb->ExecuteNonQuery($strIndexSQL);
			}
			// 2009/06/23..# import UserParam end 
			
			//liyang import TermReg start 2010.9.1
			if($_SESSION["version"] > 3)
			{
				$this->objDb->ExecuteNonQuery("DELETE FROM sv_termreg");
				//データの導入
				foreach($this->arrayCheck($arrTERMREG) as $key => $value)
				{
					$strParSQL ="insert into sv_termreg(userid,ticket,counter)";
					$strParSQL.=" values (";
					//$strParSQL.=$key;
					$strParSQL.="'".addslashes($value[0]);
					$strParSQL.="','".addslashes($value[1]);
					$strParSQL.="','".addslashes($value[2]). "')";
					$this->objDb->ExecuteNonQuery($strParSQL);
				}
				// SET index start
				$strIndexSQL = "SELECT pg_catalog.setval('sv_termreg_id_seq',";
				$strIndexSQL.= sizeof($arrTERMREG) > 1 ? sizeof($arrTERMREG) - 1 : 1;
				$strIndexSQL.= ", true)";
				$this->objDb->ExecuteNonQuery($strIndexSQL);
			}
			// 2009/06/23..# import TermReg end 			
			
			//liyang import TermInfo start 2010.9.1
			if($_SESSION["version"] > 3)
			{
				$this->objDb->ExecuteNonQuery("DELETE FROM sv_terminfo");
				//データの導入
				foreach($this->arrayCheck($arrTERMINFO) as $key => $value)
				{
					$strParSQL ="insert into sv_terminfo(userid,termid,memo)";
					$strParSQL.=" values (";
					//$strParSQL.=$key;
					$strParSQL.="'".addslashes($value[0]);
					$strParSQL.="','".addslashes($value[1]);
					$strParSQL.="','".addslashes($value[2]). "')";
					$this->objDb->ExecuteNonQuery($strParSQL);
				}
				
				// SET index start
				$strIndexSQL = "SELECT pg_catalog.setval('sv_terminfo_id_seq',";
				$strIndexSQL.= sizeof($arrTERMINFO) > 1 ? sizeof($arrTERMINFO) - 1 : 1;
				$strIndexSQL.= ", true)";
				$this->objDb->ExecuteNonQuery($strIndexSQL);
			}
			// 2009/06/23..# import TermInfo end 					
			
			
			// 06/07..# import Token
			// sv_token のデータの削除
			$this->objDb->ExecuteNonQuery("DELETE FROM sv_token");
			// sv_Token 表の構成を読み込む
			$res2 = $this->objDb->metadata("sv_token");
			// 動的な属性の構成を取得する
			if(count($res2)>11)
			{
				// 動的な属性の構成を取得する
				$arrDelCol = array_splice($res2,11);
				// sv_token の動的な属性を追加する
				foreach($arrDelCol as $key => $value)
				{
					$strAlterDelSQL = "alter table sv_token drop COLUMN ".$value["name"]." CASCADE";
					if(SESSION::GET("SESS_LIC_ServerID")!=1 || SESSION::GET("SESS_LIC_ServerID")==null)
					{
						$this->objDb->ExecuteNonQuery($strAlterDelSQL);
					}
					else
					{
						$pconn = @fopen(G_DOC_TMP."sv_alter.sql",'a+');
						fwrite($pconn,$strAlterDelSQL.";\n"); 
						fclose($pconn);
						$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
						$node = $this->objXml->GetElementsByTagName("cnodes");
						$child = $node[0]->ChildNodes();
						for($i = 0;$i<count($child);$i++)
						{
							$attr = $child[$i]->Attribute();
							if($attr['id'] == 1)
							{
								$ip = $attr['ip'];
							}
						}
						exec("sh /svisit/lib/scripts/sv_pgexec.sh " . $ip);
					}
				}
			}
			// 動的な属性列に対するループ処理を行います
			foreach($this->arrayCheck($arrCustattr) as $key => $value)
			{
				if(count($this->arrayCheck($arrCustattr)) != 0)
				{
					$strAlterAddSQL = "alter table sv_token add COLUMN ".$value[0]." character varying(256)";
					if(SESSION::GET("SESS_LIC_ServerID")!=1 || SESSION::GET("SESS_LIC_ServerID")==null)
					{
						$this->objDb->ExecuteNonQuery($strAlterAddSQL);
					}
					else
					{
						$pconn = @fopen(G_DOC_TMP."sv_alter.sql",'a+');
						fwrite($pconn,$strAlterAddSQL.";\n"); 
						fclose($pconn);
						$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
						$node = $this->objXml->GetElementsByTagName("cnodes");
						$child = $node[0]->ChildNodes();
						for($i = 0;$i<count($child);$i++)
						{
							$attr = $child[$i]->Attribute();
							if($attr['id'] == 1)
							{
								$ip = $attr['ip'];
							}
						}
						exec("sh /svisit/lib/scripts/sv_pgexec.sh " . $ip);
					}                
				}
			}
			// sv_token　のバックアップファイル中の動的な属性名を取得する
			$strTableTitle = "";
			for ($nTokenCol = 9; $nTokenCol < count($arrTokenTitle); $nTokenCol++)
			{
				$strTableTitle.= ",".$arrTokenTitle[$nTokenCol];
			}
			// sv_token のデータを読み込む
			foreach($this->arrayCheck($arrToken) as $key => $value)
			{
				foreach($arrTokenType as $key1 => $value1)
				{
					if( $value[1] == trim(trim($value1),"\""))
					{	$key1 = $key1+1;
						$strTokenSQL = " insert into sv_token (id,hid,typeid,secret,bchangepin,access,startdate,expire,userid,memo,lastupdate".$strTableTitle.")";
						$strTokenSQL.= " values (";
						$strTokenSQL.= $key;
						$strTokenSQL.= ",'".$value[0];
						$strTokenSQL.= "',".$key1;
						$strTokenSQL.= ",'".addslashes($value[2]);
						$strTokenSQL.= $value[3] == 0 ? "',false" : "',true" ;
						$strTokenSQL.= ",".$value[4];
						$strTokenSQL.= ",'".substr($value[5],0,19);
						$strTokenSQL.= "','".substr($value[6],0,19);
						$strTokenSQL.= "','".addslashes($value[7]);
						$strTokenSQL.= "','".addslashes($value[8]);
						$strTokenSQL.= "',current_timestamp";
						// sv_token のバックアップファイル中の動的な属性値の列
						for($nCount = 9; $nCount<count($value); $nCount++)
						{
							$strTokenSQL.= strlen(trim($value[$nCount])) == 0 ? ",null" : ",'".addslashes($value[$nCount])."'";
						}
						
						$strTokenSQL.=")";
						
						$this->objDb->ExecuteNonQuery($strTokenSQL);
					}
				}
			}
			
			// SET index start
			$strIndexSQL = "SELECT pg_catalog.setval('sv_token_id_seq',";
			$strIndexSQL.= sizeof($arrToken) > 1 ? sizeof($arrToken) - 1 : 1;
			$strIndexSQL.= ", true)";
			$this->objDb->ExecuteNonQuery($strIndexSQL);
			
			
			// 07/07..# import User
			// sv_user のデータの削除
			$this->objDb->ExecuteNonQuery("DELETE FROM sv_user");
			// sv_user 表の構成を取得する
			
			$res2 = $this->objDb->metadata("sv_user");
			// 動的な属性の構成を取得する
			if(count($res2)>14)
			{
				// 動的な属性を取得する
				$arrDelCol = array_splice($res2,14);
				// sv_token の動的な属性を追加する
				foreach($arrDelCol as $key => $value)
				{
					$strAlterDelSQL = "alter table sv_user drop COLUMN ".$value["name"]." CASCADE";
					if(SESSION::GET("SESS_LIC_ServerID")!=1 || SESSION::GET("SESS_LIC_ServerID")==null)
					{
						$this->objDb->ExecuteNonQuery($strAlterDelSQL);
					}
					else
					{
						$pconn = @fopen(G_DOC_TMP."sv_alter.sql",'a+');
						fwrite($pconn,$strAlterDelSQL.";\n"); 
						fclose($pconn);
						$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
						$node = $this->objXml->GetElementsByTagName("cnodes");
						$child = $node[0]->ChildNodes();
						for($i = 0;$i<count($child);$i++)
						{
							$attr = $child[$i]->Attribute();
							if($attr['id']==1)
							{
								$ip = $attr['ip'];
							}
						}
						exec("sh /svisit/lib/scripts/sv_pgexec.sh " . $ip);
					}
				}
			}
			// 動的な属性列を取得する
			foreach($this->arrayCheck($arrCustattr) as $key => $value)
			{
				if(count($this->arrayCheck($arrCustattr)) != 0)
				{
					$strTableTitle.= ",".$value[0];
					$strAlterAddSQL = "alter table sv_user add COLUMN ".$value[0]." character varying(256)";
					if(SESSION::GET("SESS_LIC_ServerID")!=1 || SESSION::GET("SESS_LIC_ServerID")==null)
					{
						$this->objDb->ExecuteNonQuery($strAlterAddSQL);
					}
					else
					{
						$pconn = @fopen(G_DOC_TMP."sv_alter.sql",'a+');
						fwrite($pconn,$strAlterAddSQL.";\n"); 
						fclose($pconn);
						$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
						$node = $this->objXml->GetElementsByTagName("cnodes");
						$child = $node[0]->ChildNodes();
						for($i = 0;$i<count($child);$i++)
						{
							$attr = $child[$i]->Attribute();
							if($attr['id']==1)
							{
								$ip = $attr['ip'];
							}
						}
						exec("sh /svisit/lib/scripts/sv_pgexec.sh " . $ip);
					}
				}
			}
			// sv_user のバックアップ中の動的な属性名を取得する
			$strTableTitle = "";
			for ($nUserCol = 8; $nUserCol < count($arrUserTitle); $nUserCol++)
			{
				$strTableTitle.= ",".$arrUserTitle[$nUserCol];
			}
			// sv_token のデータの導入
			foreach($this->arrayCheck($arrUser) as $key => $value)
			{
				$strUserSQL = " insert into sv_user (userid,fullname,email,memo,access,startdate,expire,token1id,token2id,token3id,pwdid,rpwdid,lastlogindate,lastupdate".$strTableTitle.")";
				$strUserSQL.= " values ('";
				$strUserSQL.= addslashes($value[0]);
				$strUserSQL.= "','".addslashes($value[1]);
				$strUserSQL.= "','".addslashes($value[2]);
				$strUserSQL.= "','".addslashes($value[3]);
				$strUserSQL.= "',".$value[4];
				$strUserSQL.= ",'".substr($value[5],0,19);
				$strUserSQL.= "','".substr($value[6],0,19);
				$strUserSQL.= "'";
				
				// sv_user 表の中にある，tokenid のカウンター
				$nTokenID = 0;
				foreach($arrToken as $key2 => $value2)
				{
					if($value[0] == $value2[7])
					{
						foreach($arrTokenType as $key3 => $value3)
						{
							if ($value2[1] == trim(trim($value3),"\"") )
							{
								$strUserSQL.= ",".$key2;
								$nTokenID += 1;
							}
						}
					}
				}
				switch($nTokenID)
				{
					case 0:
						$strUserSQL.= ",null,null,null";
						break;
					case 1:
						$strUserSQL.= ",null,null";
						break;
					case 2:
						$strUserSQL.= ",null";
						break;
					default :
						break;
				}
				
				// sv_password 中のpwdidを取得する
				$nPwdCount = 0;
				foreach( $arrPwd as $key4 => $value4 )
				{
					if($value4[6] == "1")
					{
						if( $value[0] == $value4[5])
						{
							$strUserSQL.= ",".$key4;
							$nPwdCount += 1;
						}
					}
				}
				switch($nPwdCount)
				{
					case 0 :
						$strUserSQL.=",-1";
						break;
					default :
						break;
				}
				
				// sv_password 中のrpwdidを取得する
				$nRPwdCount = 0;
				foreach( $arrPwd as $key5 => $value5 )
				{
					if($value5[6] == "2")
					{
						if( $value[0] == $value5[5])
						{
							$strUserSQL.= ",".$key5;
							$nRPwdCount += 1;
						}
					}
				}
				switch($nRPwdCount)
				{
					case 0 :
						$strUserSQL.=",-1";
						break;
					default :
						break;
				}
				
				$strUserSQL.= strlen(trim($value[7])) == 0 ? ",null" : ",'".substr($value[7],0,19)."'";
				$strUserSQL.= ",current_timestamp";
				
				// sv_user のバックアップファイル中の動的な属性値
				for($nCount = 8; $nCount<count($value); $nCount++)
				{
					$strUserSQL.= strlen(trim($value[$nCount])) == 0 ? ",null" : ",'".addslashes($value[$nCount])."'";
				}
				
				$strUserSQL.=")";
				
				$this->objDb->ExecuteNonQuery($strUserSQL);
			}
			
			
			
			if($_SESSION["version"] > 1)
			{
				// 2009/10/09..# import LogConfig  start
				$this->objDb->ExecuteNonQuery("DELETE FROM sv_logconfig");
				
				// sv_logconfig のデータの導入
				foreach($this->arrayCheck($arrLogConfig) as $key => $value)
				{
					$strLogConfigSQL ="insert into sv_logconfig(name, value)";
					$strLogConfigSQL.=" values (";
					$strLogConfigSQL.="'".addslashes($value[0]);
					//$strLogConfigSQL.="','".addslashes($value[1]). "')";
					$strLogConfigSQL.="','".$value[1]. "')";
					
					$this->objDb->ExecuteNonQuery($strLogConfigSQL);
				}
				// $this->objDb->ExecuteNonQuery("DELETE FROM sv_adminlogmsg");
				// foreach($this->arrayCheck($arrAdminMsg) as $key => $value)
				// {
					// $strAdminMsgSQL ="insert into sv_adminlogmsg(id, opname, opcore, oprecord)";
					// $strAdminMsgSQL.=" values (";
					// $strAdminMsgSQL.=$key+1;
					// $strAdminMsgSQL.=",'".addslashes($value[1]);
					// $strAdminMsgSQL.="','".addslashes($value[2]);
					// $strAdminMsgSQL.="','".addslashes($value[3]). "')";
					// $this->objDb->ExecuteNonQuery($strAdminMsgSQL);
				// }
				// SET index start
				// $strIndexSQL = "SELECT pg_catalog.setval('sv_adminlogmsg_id_seq',";
				// $strIndexSQL.= sizeof($arrAdminMsg) > 1 ? sizeof($arrAdminMsg) - 1 : 1;
				// $strIndexSQL.= ", true)";
				// $this->objDb->ExecuteNonQuery($strIndexSQL);
			}
			// 2009/10/09..# import LogConfig end 
			
			// submit and exit db import
			$this->objDb->commit();
		}
		catch( Exception $e )
		{
			$temp = $strUserSQL;
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			$this->objDb->rollback();
			// delete dbsql.sql
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
			return "fail";
			throw $e;
		}
		
		// ビューを再び再生する
		try
		{
			// DbView を実例化する
			$viewObj = new DbView();
			
			try
			{
				// ビューを削除する
				$viewObj->dropview();
			}
			catch(exception $e)
			{
			}
			
			// ビューを再生する
			$viewObj->createview();
		}
		catch( Exception $e )
		{
		}
		
		// delete dbsql.sql
		@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
		return "success";
	}
	
	
	/**
	* 関数名: exportConfig
	* 条件によりデータを検索しConfigファイルを作成し、ダウンロードする.
	*
	*
	* @return       void.
	*/
	function exportConfig()
	{
		try
		{
			// 「Download」画面を表示する.
			header("location: ../ctrl/download.php?s=".urlencode(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME));
			exit; 
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	
	/**
	* 関数名: inputConfig
	* Configファイルをインポートする.
	*
	* Phase2 added by yudx
	* @return       void.
	*/
	function inputConfig()
	{
		try
		{
			// ファイルを臨時フォルダーに読み取る
			@PAGE::SaveUplfile("configfile_name",G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
		}
		catch (Exception $e)
		{
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			return "readfail";
		}
		
		// ファイルを配列に読み取る
		$arrFile = @file(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
		
		// 読み込みに失敗すると、ファイルを削除する
		if(!$arrFile) 
		{
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			return "readfail";
		}
		
		// 第一行の記号列の長さ
		if(strlen(trim($arrFile[0])) == 0) 
		{
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			return "readfail";
		}
		
		// 第一行が "xml version="　を含んだ場合
		if(stristr(trim($arrFile[0]),"xml version=") && substr(trim($arrFile[0]),0,2) == "<?")
		{		
			$this->objXml->LoadXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			$node = $this->objXml->GetElementsByTagName("license");
			$attr = $node[0]->Attribute();
			$ver = $attr["version"];
			
			if(stristr($ver, "1.1"))
			{
				$_SESSION["version"]="11";
			}
			elseif(stristr($ver, "1.2"))
			{
				$_SESSION["version"]="12";
			}
			elseif(stristr($ver, "1.3"))
			{
				$_SESSION["version"]="13";
			}
			elseif(stristr($ver, "1.4"))
			{
				$_SESSION["version"]="14";
			}
			elseif(stristr($ver, "1.5"))
			{
				$_SESSION["version"]="15";
				$chkactive = $attr["chkactive"];
				$node[0]->setAttribute("chkactive",$chkactive);
			}
			else
			{
				$_SESSION["version"]="10";
			}
			$node[0]->setAttribute("version","1.5");
			$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			
			// TokenType Ver1.0->1.1
			$list = array();
			if ($_SESSION["version"] < 11)
			{
				// add Token type
				$this->objXml->LoadXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				$node = $this->objXml->GetElementsByTagName("authenticate");
				$child = $node[0]->ChildNodes();
				
				$insNode = new Node("sss");
				$insNode->setNodeName("authtype");
				$insNode->setAttribute("tokentype","ePass1000ND");
				$insNode->setAttribute("alg","hmac-md5");
				$node[0]->AppendChild($insNode);
				$insNode->setNodeName("authtype");
				$insNode->setAttribute("tokentype","ePass1000");
				$insNode->setAttribute("alg","hmac-md5");
				$node[0]->AppendChild($insNode);
				$insNode->setNodeName("authtype");
				$insNode->setAttribute("tokentype","ePass2001H");
				$insNode->setAttribute("alg","hmac-sha1");
				$node[0]->AppendChild($insNode);
				$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);

				// rescuer password
				
				$this->objXml->LoadXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				$node = $this->objXml->GetElementsByTagName("keyid");
				$attr = $node[0]->Attribute();
				$respw = $attr["rescuepwd"];
				
				if($respw == '')
				{
					$node[0]->setAttribute("rescuepwd","0");
					$node[0]->setAttribute("rescueretry","10");
				}
				$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				
				// --
				
				
				
				$strSql  = " SELECT name ";
				$strSql .= " FROM sv_group ";
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
				$resGroup = $this->objDb->ExecuteArrayCommand($strSql);
				if($resGroup == "") $resGroup = array();
				$res =array();
				foreach($resGroup as $key=>$values)
				{
					$res[$key]=$resGroup[$key][0];
				}
				
				$this->objXml->LoadXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				
				// linksetノード				
				$node = $this->objXml->GetElementsByTagName("linkset");
				$child = $node[0]->ChildNodes();
				for($i = 0;$i<count($child);$i++)
				{ 
					$cChild = $child[$i]->ChildNodes();
					for($j = 0;$j<count($cChild);$j++)
					{
						if($cChild[$j]->_nodeName==="member")
						{
							$ccChild = $cChild[$j]->ChildNodes();
							$tmp=0;
							for($k = 0;$k<count($ccChild);$k++)
							{
								$ccAttr = $ccChild[$tmp]->Attribute();
								if(!in_array($ccAttr["name"],$res))
								{
									if(count($cChild[$j]->ChildNodes())==1)
									{
									$child[$i]->RemoveChild2($cChild[$j]);
									}else
									{	
										$cChild[$j]->RemoveChild2($ccChild[$tmp]);
									}
								}
								else
								{	
									$tmp++;
									continue;
								}
							}
						}
						else
						{
							continue;
						}
					}				
				}
				$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				
				// portforwardingノード				
				$portnode = $this->objXml->GetElementsByTagName("portforwarding");
				$portchild = $portnode[0]->ChildNodes();
				for($i = 0;$i<count($portchild);$i++)
				{ 
					$cChild = $portchild[$i]->ChildNodes();
					for($j = 0;$j<count($cChild);$j++)
					{
						if($cChild[$j]->_nodeName==="member")
						{
							$ccChild = $cChild[$j]->ChildNodes();
							$tmp=0;
							for($k = 0;$k<count($ccChild);$k++)
							{
								$ccAttr = $ccChild[$tmp]->Attribute();
								if(!in_array($ccAttr["name"],$res))
								{
									if(count($cChild[$j]->ChildNodes())==1)
									{
										$child[$i]->RemoveChild2($cChild[$j]);
									}else
									{	
										$cChild[$j]->RemoveChild2($ccChild[$tmp]);
									}
								}
								else
								{	
									$tmp++;
									continue;
								}
							}
						}
						else
						{
							continue;
						}
					}
				}
				$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				
				// defaultlinkノード				
				$defaultnode = $this->objXml->GetElementsByTagName("defaultlink");
				$defaultchild = $defaultnode[0]->ChildNodes();
					
				for($m = 0;$m<count($defaultchild);$m++)
				{
					$defaultcChild = $defaultchild[$m]->ChildNodes();
					for($x = 0;$x<count($defaultcChild);$x++)
					{
						if($defaultcChild[$x]->_nodeName==="member")
						{
							$defaultccChild = $defaultcChild[$x]->ChildNodes();
							$tmp=0;
							for($y= 0;$y<count($defaultccChild);$y++)
							{
								$defaultccAttr = $defaultccChild[$tmp]->Attribute();
								if(!in_array($defaultccAttr["name"],$res))
								{
									if(count($defaultcChild[$x]->ChildNodes())==1)
									{
									$defaultchild[$m]->RemoveChild2($defaultcChild[$x]);
										}
									else
									{	
										$defaultcChild[$x]->RemoveChild2($defaultccChild[$tmp]);
									}								
								}
								else
								{
									$tmp++;
									continue;
								}
							}
						}
						else
						{
							continue;
						}
					}
				}
				$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			}
			
			// termconfig add by liyang 2010.9.1
			if ($_SESSION["version"] < 12)
			{
								
				$this->objXml->LoadXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				$node = $this->objXml->GetElementsByTagName("keyid");
				$attr = $node[0]->Attribute();
				$respw = $attr["termlimit"];
				
				if($respw == '')
				{
					$node[0]->setAttribute("termlimit","0");
					$node[0]->setAttribute("termretry","10");
					$node[0]->setAttribute("termvalue","0");
				}
				$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			}
			// --
			
			// otp add by liyang 2011.02.09
			if ($_SESSION["version"] < 13)
			{						
				$this->objXml->LoadXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				$node = $this->objXml->GetElementsByTagName("authenticate");
				$child = $node[0]->ChildNodes();
				
				$insNode = new Node("sss");
				$insNode->setNodeName("otpauth");
				$insNode->setAttribute("enable","false");
				$node[0]->AppendChild($insNode);
				$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				
				$this->objXml->LoadXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				$node = $this->objXml->GetElementsByTagName("keyid");
				$attr = $node[0]->Attribute();
				$respw = $attr["termvalue"];
				
				if($respw == '')
				{
					$node[0]->setAttribute("termvalue","0");
				}
				$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			}
			// --
			
			// keeppostparam add by liyang 2013.08.15
			if ($_SESSION["version"] < 14)
			{						
				$this->objXml->LoadXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				$node = $this->objXml->GetElementsByTagName("keyid");
				$attr = $node[0]->Attribute();
				$respw = $attr["keeppostparam"];
				
				if($respw == '')
				{
					$node[0]->setAttribute("keeppostparam","0");
				}
				$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			}
			// --
			
			// chkactive add by liyang 2014.10.30
			if ($_SESSION["version"] < 15)
			{						
				$this->objXml->LoadXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				$node = $this->objXml->GetElementsByTagName("license");
				$attr = $node[0]->Attribute();
				$respw = $attr["chkactive"];
				
				if($respw == '')
				{
					$node[0]->setAttribute("chkactive","0");
				}
				$this->objXml->SaveXml(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			}
			// --

			// もし "xml version=" を含んだ場合，ファイルを指定したディレクトリーにコピーする
			if(copy(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME, G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME ))
			{
				@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
				return "success";
			}
			// ファイルをコピーするのに失敗したら、ファイルを削除する
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
			return "fail";			
		}
		// 設定ファイルではない場合、ファイルを削除する
		@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE").G_HOME_CONFIG_NAME);
		return "readfail";
	}
		
		
	/**
	* 関数名: exportLog
	* 条件によりデータを検索しLogからCSVファイルを作成し、ダウンロードする.
	* 
	*
	* added by wuyq
	* @return       void.
	*/
	function exportLog()
	{
		// dir checking
		if(!is_dir(G_DOC_TMP))
		{
			mkdir(G_DOC_TMP);
		}
		
		//　臨時ファイルにコピーする
		$fileName = G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."s20log_bak.txt";
			
		try
		{
			// ファイルを開く
			$pconn = @fopen($fileName,'w+');
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$pconn )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}
			
			// 2009/10/09.# バージョンの書き込み
			fwrite($pconn,VERSION."1.0\r\n\r\n\r\n");
			
			// ACCESSLOG_START
			fwrite($pconn,ACCESSLOG_START."\r\n");
			fwrite($pconn,"id, userid, groupname, clientip, tokenid, tokentype, requrl, hostname, url, detail, datetime, status\r\n");            
			
			// BUG#1119対応
			$limit = 10000;
			$offset = 0;
			$record = 10000;
			while ($limit == $record)
			{
				$record = 0;
				// sv_accesslogのデータを読み込む
				$strAccessSQL = " select id, userid, groupname, clientip, tokenid, tokentype, requrl, hostname, url, detail, datetime, status from sv_accesslog order by id limit ".$limit." offset ".$offset;
				$resAccess = $this->objDb->ExecuteArrayCommand($strAccessSQL);
				
				// データの書き込み
				foreach($this->arrayCheck($resAccess) as $key => $value)
				{
					$strLine = "\"".$value["id"];
					$strLine.= "\",\"".$value["userid"];
					$strLine.= "\",\"".$value["groupname"];
					$strLine.= "\",\"".$value["clientip"];
					$strLine.= "\",\"".$value["tokenid"];
					$strLine.= "\",\"".$value["tokentype"];
					$strLine.= "\",\"".$value["requrl"];
					$strLine.= "\",\"".$value["hostname"];
					$strLine.= "\",\"".$value["url"];
					$strLine.= "\",\"".$value["detail"];
					$strLine.= "\",\"".$value["datetime"];
					$strLine.= "\",\"".$value["status"];
					$strLine.="\"\r\n";
				
					fwrite($pconn,$strLine);
					$record = $record + 1;
				}
				$offset = $offset + $limit;
			}
			
			fwrite($pconn,ACCESSLOG_END."\r\n\r\n\r\n");
			// ACCESSLOG_END
			
			
			
			// ADMINLOG_START
			fwrite($pconn,ADMINLOG_START."\r\n");
			fwrite($pconn,"id, cdate, adminid, opcode, param\r\n");
			
			$limit = 10000;
			$offset = 0;
			$record = 10000;
			while ($limit == $record)
			{
				$record = 0;
				//  AdminLog のデータを読み込む
				$strAdminSQL = " select id, cdate, adminid, opcode, param from sv_adminlog order by id limit ".$limit." offset ".$offset;
				$resAdmin = $this->objDb->ExecuteArrayCommand($strAdminSQL);
				
				// データの書き込み
				foreach($this->arrayCheck($resAdmin) as $key => $value)
				{
					$strLine = "\"".$value["id"];
					$strLine.= "\",\"".$value["cdate"];
					$strLine.= "\",\"".$value["adminid"];
					$strLine.= "\",\"".$value["opcode"];
					$strLine.= "\",\"".$value["param"];
					$strLine.="\"\r\n";
				
					fwrite($pconn,$strLine);
					$record = $record + 1;
				}
				$offset = $offset + $limit;
			}
			fwrite($pconn,ADMINLOG_END."\r\n\r\n\r\n");
			// ADMINLOG_END
			
			
			// AUTHLOG_START
			fwrite($pconn,AUTHLOG_START."\r\n");
			fwrite($pconn,"id, userid, clientip, tokenid, tokentype, authmethod, hostname, url, detail, datetime, status\r\n");            
						
			$limit = 10000;
			$offset = 0;
			$record = 10000;
			while ($limit == $record)
			{
				$record = 0;
				// sv_accesslog　のデータを読み込む
				$strAuthSQL = " select id,userid,clientip,tokenid,tokentype,authmethod,hostname,url,detail,datetime,status from sv_authlog order by id limit ".$limit." offset ".$offset;
				$resAuth = $this->objDb->ExecuteArrayCommand($strAuthSQL);
				
				// データの書き込み
				foreach($this->arrayCheck($resAuth) as $key => $value)
				{
					$strLine = "\"".$value["id"];
					$strLine.= "\",\"".$value["userid"];
					$strLine.= "\",\"".$value["clientip"];
					$strLine.= "\",\"".$value["tokenid"];
					$strLine.= "\",\"".$value["tokentype"];
					$strLine.= "\",\"".$value["authmethod"];
					$strLine.= "\",\"".$value["hostname"];
					$strLine.= "\",\"".$value["url"];
					$strLine.= "\",\"".$value["detail"];
					$strLine.= "\",\"".$value["datetime"];
					$strLine.= "\",\"".$value["status"];
					$strLine.="\"\r\n";
				
					fwrite($pconn,$strLine);
					$record = $record + 1;
				}
				$offset = $offset + $limit;
			}
			fwrite($pconn,AUTHLOG_END."\r\n\r\n\r\n");
			// AUTHLOG_END
			
			// finish file writting
			$result = @fclose($pconn);
			if( !$result )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR202);
			}
		
			header("location: ../ctrl/download.php?s=".urlencode($fileName));
			exit;
		}
		catch( Exception $e )
		{
			return;
		}
	}
		
	/**
	* 関数名: inputLog
	* CSVファイルをインポートする.
	* 
	*
	* Phase2 added by wuyq
	* @return       void.
	*/
	function inputLog()
	{
		// バックアップファイルをdbsql.sqlに導入する
		try
		{
			@PAGE::SaveUplfile("logfile_name",G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
		}
		catch (Exception $e)
		{
			// delete dbsql.sql
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
			return "readfail";
		}
		// 桁数
		$key1;$key2;
		
		// バックアップファイルを配列に書き直す
		$arrFile = @file(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
		
		// 配列ではない場合
		if(!$arrFile)
		{
			// dbsql.sqlを削除
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
			return "readfail";
		}
		// 有効な桁数
		$nTimesCount = 0;
		
		// 指定した配列に関してループ処理を行う
		foreach($this->arrayCheck($arrFile) as $key => $value)
		{
			// 空行配列に関するループ処理の続き
			if(strlen(trim($value)) == 0) continue;
			
			if(stristr($value,VERSION))
            {
				// バーションは1.0の場合、かつ長さは15より小さい場合
                if( stristr($value, VERSION."1.0") && strlen(trim($value)) <= 15)
                {
					//1を加える
                    $nTimesCount+=1;
                    continue;
                }
                // dbsql.sqlを削除
                @unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
                return "version";
            }
			// ACCESSLOG_STARTを見つけた場合
			if(stristr($value,ACCESSLOG_START))
			{
				// $keyを$key1代入する。
				$key1 = $key;
				continue;
			}
			// ACCESSLOG_ENDを見つけた場合
			if(stristr($value,ACCESSLOG_END))
			{
				// $keyを$key2代入する。
				$key2 = $key;
				
				if(!$this->nArrDataCheck($key2-$key1-2))
				{
					// dbsql.sqlを削除
					@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
					return "readfail";
				}
				// 配列の一部を展開する
				$arrAccessLog = array_slice($arrFile,$key1+2,$key2-$key1-2);
				
				// 配列のループを行う
				foreach($this->arrayCheck($arrAccessLog) as $key => $value)
				{
					$arrAccessLog[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
				}
				// 1を加える
				$nTimesCount+=1;
				continue;
			}
			
			//ADMINLOG_STARTを見つけた場合
			if(stristr($value,ADMINLOG_START))
			{
				// $keyを$key2代入する。
				$key1 = $key;
				continue;
			}
			// ADMINLOG_ENDを見つけた場合
			if(stristr($value,ADMINLOG_END))
			{
				// $keyを$key2代入する。
				$key2 = $key;
				
				if(!$this->nArrDataCheck($key2-$key1-2))
				{
					// dbsql.sqlを削除
					@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
					return "readfail";
				}
				// 配列の一部を展開する
				$arrAdminLog =  array_slice($arrFile,$key1+2,$key2-$key1-2);
				
				// 配列のループを行う
				foreach($this->arrayCheck($arrAdminLog) as $key => $value)
				{
					$arrAdminLog[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
				}
				//　1を加える
				$nTimesCount+=1;
				continue;	
			}
			
			// AUTHLOG_STARTを見つけた場合
			if(stristr($value,AUTHLOG_START))
			{
				// $keyを$key1代入する。
				$key1 = $key;
				continue;
			}
			// AUTHLOG_ENDを見つけた場合
			if(stristr($value,AUTHLOG_END))
			{
				// $keyを$key2代入する。
				$key2 = $key;
				
				if(!$this->nArrDataCheck($key2-$key1-2))
				{
					// dbsql.sqlを削除
					@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
					return "readfail";
				}
				// 配列の一部を展開する
				$arrAuthLog =  array_slice($arrFile,$key1+2,$key2-$key1-2);
				
				// 配列のループを行う
				foreach($this->arrayCheck($arrAuthLog) as $key => $value)
				{
					$arrAuthLog[$key] = preg_split("/\",\"/", substr( trim($value), 1, strlen(trim($value))-2 ));
				}
				//　1を加える
				$nTimesCount+=1;
				continue;	
			}
	
		}
		
		// $nTimesCountは4ではない場合
		if($nTimesCount != 4)
		{
			// dbsql.sqlを削除
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
			return "readfail";
		}
		try
		{
			$this->objDb->begin();
			
			// sv_accesslogを削除
			$this->objDb->ExecuteNonQuery("DELETE FROM sv_accesslog");
			
			// $arrAccessLogをループして、SQL文を組む
			foreach($this->arrayCheck($arrAccessLog) as $key => $value)
			{
				$strAccessLogSQL = " insert into sv_accesslog (id, userid, groupname, clientip, tokenid, tokentype, requrl, hostname, url, detail, datetime, status)";
				$strAccessLogSQL.=" values (";
				$strAccessLogSQL.=$key;
				$strAccessLogSQL.=",'".addslashes($value[1]);
				$strAccessLogSQL.= "','".addslashes($value[2]); 
				$strAccessLogSQL.="','".addslashes($value[3]); 
				$strAccessLogSQL.="','".$value[4]; 
				$strAccessLogSQL.="','".$value[5];
				$strAccessLogSQL.="','".$value[6];
				$strAccessLogSQL.="','".$value[7];
				$strAccessLogSQL.="','".$value[8];
				$strAccessLogSQL.="','".addslashes($value[9]);
				$strAccessLogSQL.="','".$value[10];
				$strAccessLogSQL.="','".addslashes($value[11]). "')";
				
				// SQL文を実行する。
				$this->objDb->ExecuteNonQuery($strAccessLogSQL);
			}
			
			// indexを設置する。
			$strIndexSQL = "SELECT pg_catalog.setval('sv_accesslog_id_seq',";
			$strIndexSQL.= sizeof($arrAccessLog) > 1 ? sizeof($arrAccessLog) - 1 : 1;
			$strIndexSQL.= ", true)";
			
			// SQL文を実行する。
			$this->objDb->ExecuteNonQuery($strIndexSQL);
			
			// sv_adminlogを削除
			$this->objDb->ExecuteNonQuery("DELETE FROM sv_adminlog");
			
			// $arrAdminLogをループして、SQL文を組む
			foreach($this->arrayCheck($arrAdminLog) as $key => $value)
			{
				$strAdminLogSQL = " insert into sv_adminlog (id, cdate, adminid, opcode, param)";
				$strAdminLogSQL.=" values (";
				$strAdminLogSQL.=$key;
				$strAdminLogSQL.=",'".$value[1];
				$strAdminLogSQL.="','".addslashes($value[2]);
				$strAdminLogSQL.="','".addslashes($value[3]);
				$strAdminLogSQL.="','".addslashes($value[4]). "')";
				
				// SQL文を実行する。
				$this->objDb->ExecuteNonQuery($strAdminLogSQL);
			}
			
			// indexを設置する。
			$strIndexSQL = "SELECT pg_catalog.setval('sv_adminlog_id_seq',";
			$strIndexSQL.= sizeof($arrAdminLog) > 1 ? sizeof($arrAdminLog) - 1 : 1;
			$strIndexSQL.= ", true)";
			
			// SQL文を実行する。
			$this->objDb->ExecuteNonQuery($strIndexSQL);
			
			// sv_authlogを削除
			$this->objDb->ExecuteNonQuery("DELETE FROM sv_authlog");
			
			// $arrAuthLogをループして、SQL文を組む
			foreach($this->arrayCheck($arrAuthLog) as $key => $value)
			{
				$strAuthLogSQL = " insert into sv_authlog (id,userid,clientip,tokenid,tokentype,authmethod,hostname,url,detail,datetime,status)";
				$strAuthLogSQL.=" values (";
				$strAuthLogSQL.=$key;
				$strAuthLogSQL.=",'".addslashes($value[1]);
				$strAuthLogSQL.="','".$value[2];
				$strAuthLogSQL.="','".$value[3];
				$strAuthLogSQL.="','".$value[4];
				$strAuthLogSQL.="','".$value[5];
				$strAuthLogSQL.="','".$value[6];
				$strAuthLogSQL.="','".$value[7];
				$strAuthLogSQL.="','".addslashes($value[8]);
				$strAuthLogSQL.="','".$value[9];
				$strAuthLogSQL.="','".addslashes($value[10]). "')";
				
				// SQL文を実行する。
				$this->objDb->ExecuteNonQuery($strAuthLogSQL);
			}
			
			// indexを設置する。
			$strIndexSQL = "SELECT pg_catalog.setval('sv_authlog_id_seq',";
			$strIndexSQL.= sizeof($arrAuthLog) > 1 ? sizeof($arrAuthLog) - 1 : 1;
			$strIndexSQL.= ", true)";
			
			// SQL文を実行する。
			$this->objDb->ExecuteNonQuery($strIndexSQL);
			
		    $this->objDb->commit();
		}
		catch( Exception $e )
		{
			$temp = $strUserSQL;
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			$this->objDb->rollback();
			// delete dbsql.sql
			@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
			return "fail";
			throw $e;
		}
				// ビューを再び再生する
		try
		{
			// DbView を実例化する
			$viewObj = new DbView();
			
			try
			{
				// ビューを削除する
				$viewObj->dropview();
			}
			catch(exception $e)
			{
			}
			
			// ビューを再生する
			$viewObj->createview();
		}
		catch( Exception $e )
		{
		}
		
		// dbsql.sqlを削除
		@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."dbsql.sql");
		return "success";
		
	}
	
}
?>
