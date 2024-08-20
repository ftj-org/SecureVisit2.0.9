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
 */

$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."lib/page.php";
require_once $G_APPPATH[0]."lib/pageview.php";
//require_once $G_APPPATH[0]."mdl/dbview.php";
require_once $G_APPPATH[0]."/lib/xmlread.php";

class slave_db_backup_M{

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
	function slave_db_backup_M()
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
}
?>
