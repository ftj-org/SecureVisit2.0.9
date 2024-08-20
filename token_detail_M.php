<?php
/**
 * epass1000NDのUSBトークン登録／変更ファイル.
 *
 * @author 文傑.
 * @since  2007-09-18
 * @version 1.0
 */

$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."lib/xmlread.php";
 
class Token_detail_M{
	
	/**
	* データベースの実例.
	*/
	var $objDb = null;
	var $objXml = null;
	
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
	* 関数名: Token_detail_M
	* コンストラクタ.
	* 
	*/
	function Token_detail_M(){		
		try{
			$this->__construct();
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	} 
 
	/**
	* 関数名: readTableNew
	*トークン属性項目を取得する.
	* 
	*
	* @return		Array		$res		リザルト1から、リザルト2に存在する項目を取得し、返す.
	*/
	function readTableNew(){
		try{
			$strSql  = " SELECT type,";
			$strSql .= " name, ";
			$strSql .= " nametoken, ";
			$strSql .= " memotoken ";
			$strSql .= " FROM sv_customtmpl";
			$strSql .= " WHERE visibletoken=true";
			$strSql .= " ORDER BY ordertoken ASC";

			$res = $this->objDb->ExecuteArrayCommand($strSql);
			return $res;			
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: readTokenDetail
	* TOKENの情報を読み出す.
	* 
	* @param		string		$tokenHID			トークンID.
	*
	* @return		Array		$res			　　トークンデータ.
	*/
	
	function readTokenDetail($tokenHID){
		try{
			$res = array();
			
			// TOKENの情報を読み出す.

			$strSql  = " SELECT * ";
			$strSql .= " FROM sv_token ";
			$strSql .= " WHERE hid ='".$tokenHID."'";
			
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
			$res = $this->objDb->ExecuteArrayCommand($strSql);
			
			$res["sv_token"] =array();
			if($resHid == "") $resHid = array();
			foreach($resHid as $key => $value ){
				$res["sv_token"][$value["id"]] =$value["option"];
			}
			return $res;		
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
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
    * 関数名: ComtomCompare
    * 同時属性処理時の属性の比較.
    * @param		
    *
    * @return		trueの場合は一致.falseの場合一致ではない.
    */
    function ComtomCompare()
    {
        $nCounter = 0;
        $res1 = $this->objDb->ExecuteArrayCommand("select name from sv_customtmpl where visibletoken='true'");
        if(sizeof($this->arrayCheck($res1)) == 0) return true;
        foreach($_POST as $key1 =>$value1 )
        {
            foreach ($res1 as $key2 => $value2)
            {
                if(stristr(trim($key1),trim("txt".$value2[0])))
                {
                    $nCounter+=1;
                    continue;
                }
            }
        }
        if($nCounter == sizeof($res1))
        {
            return true;
        }
        else
        {
            return false;
        }
    }


	/**
	* 関数名: setTokenDetail
	* TOKENの情報を更新する.
	* 
	* @param		string		$arrTokenInfo		TOKENの情報.
	*
	* @return		void.
	*/
	function setTokenDetail($arrTokenInfo,$strLastupdate){
		try{
			
			$resUserId = $this->SetCheckToken($arrTokenInfo["tokenhid"],$arrTokenInfo["access"]);
			if(  $resUserId !== true){
				return $resUserId;
			}
            
            if(!$this->ComtomCompare())
            {
                return "costomdel";
            }
            
			$this->objDb->begin();
			
			$sqltime =" SELECT COUNT (hid)";
			$sqltime.=" FROM sv_token";
			$sqltime.=" WHERE hid = '".$arrTokenInfo["tokenhid"]."'";
			$sqltime.=" AND lastupdate='".$strLastupdate."'";
			$restime = $this->objDb->ExecuteCommand($sqltime);
			if($restime[0]!=1)
			return "fail";
			
			$arrNewTitle = $this->readTableNew();
			$strTitleSql = "";
				
			$arrNewTitle = $arrNewTitle==""?array():$arrNewTitle;
			// 指定した配列に 関してループ処理を行って、CSVファイルのタイトルを追加する.
			for($i=0;$i<count($arrNewTitle);$i++)
			{
				if(trim($arrNewTitle[$i]["name"])=="") continue;
				if(trim($arrTokenInfo[$arrNewTitle[$i]["name"]])=="")
				{
					$arrTokenInfo[$arrNewTitle[$i]["name"]] = "null";
					$strTitleSql .= " ".$arrNewTitle[$i]["name"]."=null,";
				}
				else
				{
					$strTitleSql .= " ".$arrNewTitle[$i]["name"]."='".$arrTokenInfo[$arrNewTitle[$i]["name"]]."',";
				}
			}
			
			// TOKENの情報の更新
			$strSql  = " UPDATE sv_token";
			$strSql .= " SET ";
			$strSql .= "  memo='".$this->objDb->sqlescapestring($arrTokenInfo["memo"])."',";
			$strSql .= "  access='".$arrTokenInfo["access"]."',";
			$strSql .= $strTitleSql;			
			if($arrTokenInfo["startdate"]!=" ")
				$strSql .= "  startdate='".$arrTokenInfo["startdate"]."',";
			else
				$strSql .= "  startdate='".date("Y/m/d H:i:s")."',";
			if($arrTokenInfo["expire"]!=" ")	
				$strSql .= "  expire='".$arrTokenInfo["expire"]."'";
			else
				$strSql .= "  expire='2099/12/31 23:59:59'";
			$strSql .= " WHERE hid ='".$arrTokenInfo["tokenhid"]."'";
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
			$this->objDb->ExecuteNonQuery($strSql);
			
			$sqlupdate =" UPDATE sv_token";
			$sqlupdate.=" SET ";
			$sqlupdate.=" lastupdate = CURRENT_TIMESTAMP";
			$sqlupdate.=" WHERE hid ='".$arrTokenInfo["tokenhid"]."'";
			$this->objDb->ExecuteNonQuery($sqlupdate);
			
			// Phase2 added by yudx Begin
			// log to db
			$sqlupdate =" UPDATE sv_token";
			$sqlupdate.=" SET ";
			$sqlupdate.= $_POST["chkPin"] == "" ? " bchangepin = false":" bchangepin = true";
			$sqlupdate.=" WHERE hid ='".$arrTokenInfo["tokenhid"]."'";
			$this->objDb->ExecuteNonQuery($sqlupdate);
			// Phase2 added by yudx End
			
			$this->objDb->commit();
			return true;
	
		}
		
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			$this->objDb->rollback();
			throw $e;
		}	
	}

	/**
	* 関数名: addTokenDetail
	* TOKENの情報の追加.
	* 
	* @param		string		$arrTokenInfo		TOKENの情報.
	*
	* @return		string		$res				fail：主键重复fail返回，正常结束true返回.
	*/
	function addTokenDetail( $arrTokenInfo ){
		try
		{
            if(!$this->ComtomCompare())
            {
                return "costomdel";
            }
            
			$this->objDb->begin();
	
			try{
				// 登録できるUSBトークンの最大件数
				try{
					$strSqlCount  = " SELECT count(*) from sv_token";
					// DEBUG メッセージ
					ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlCount");
					$strTokenCount = $this->objDb->ExecuteCommand($strSqlCount);
					
					$strTokenLimit = SESSION::GET("SESS_LIC_TOKENLIMIT");
					
					if($strTokenCount[0] >= $strTokenLimit)
					{
						return "licensefail";
					}
				}
				catch( Exception $e ){
					$this->objDb->rollback();
					return "licensefail";
				}
				
				//HIDが存在するかをチェックする.
				$sql = "select count(*) from sv_token";
				$sql.= " where hid='".$arrTokenInfo["tokenhid"]."'";
				$res = $this->objDb->ExecuteCommand($sql);
			
				//HIDが存在しない場合.
				if($res[0] == 0)
				{	
					//插入动态列数据
					$arrNewTitle = $this->readTableNew();
					$strTitleSql = "";
					$arrNewTitle = $arrNewTitle==""?array():$arrNewTitle;
					
					for($i=0;$i<count($arrNewTitle);$i++)
					{
						if(trim($arrNewTitle[$i]["name"])=="") continue;
						if(trim($arrTokenInfo[$arrNewTitle[$i]["name"]])==""){
							$arrTokenInfo[$arrNewTitle[$i]["name"]] = "null";
							$strTitleSql .= " ".$arrNewTitle[$i]["name"].",";
							$strTitleSql2 .= " null,";
						}else{
							$strTitleSql .= " ".$arrNewTitle[$i]["name"].",";
							$strTitleSql2 .= " '".$arrTokenInfo[$arrNewTitle[$i]["name"]]."',";
						}
					}
					
					$strSqlToken  = " INSERT INTO sv_token";
					$strSqlToken .= " (hid, ";
					$strSqlToken .= " memo, ";
					$strSqlToken .= " access, ";
					$strSqlToken .= " startdate, ";
					$strSqlToken .= " secret, ";
					$strSqlToken .= " typeid, ";	
					$strSqlToken .= " bchangepin , ";
					$strSqlToken .= $strTitleSql;								
					$strSqlToken .= " expire) ";								
					$strSqlToken .= " VALUES( ";
					$strSqlToken .= " '".$arrTokenInfo["tokenhid"]."', ";
					$strSqlToken .= " '".$this->objDb->sqlescapestring($arrTokenInfo["memo"])."', ";
					$strSqlToken .= " '".$arrTokenInfo["access"]."', ";
					if($arrTokenInfo["startdate"]==" ")
					{
						$strSqlToken .= " '".date("Y/m/d H:i:s")."', ";
					}
					else
					{
						$strSqlToken .= " '".$arrTokenInfo["startdate"]."', ";
					}
					
					$strSqlToken .= " '".$arrTokenInfo["secret"]."', ";				
					$strSqlToken .= " '".$arrTokenInfo["typeid"]."', ";
					$strSqlToken .= $_POST["chkPin"] == "" ? "false," : "true,";
					$strSqlToken .= $strTitleSql2;					
					if($arrTokenInfo["expire"]==" ")
					{
						$strSqlToken .= " '2099/12/31 23:59:59' ) ";
					}
					else
					{
						$strSqlToken .= " '".$arrTokenInfo["expire"]."')";		
					}						
			
					// DEBUG メッセージ
					ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlToken");
					$this->objDb->ExecuteNonQuery($strSqlToken);
					
					$sqlupdate =" UPDATE sv_token";
					$sqlupdate.=" SET ";
					$sqlupdate.=" lastupdate = CURRENT_TIMESTAMP";
					$sqlupdate.=" WHERE hid ='".$arrTokenInfo["tokenhid"]."'";
					$this->objDb->ExecuteNonQuery($sqlupdate);
				
								
				}
				else
				{
					$this->objDb->rollback();
					return "fail";
				}
			}
			catch( Exception $e )
			{
				$this->objDb->rollback();
				return "fail";
			}
			$this->objDb->commit();
						
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			$this->objDb->rollback();
			throw $e;
		}	
	}

	/**
	* 関数名: getCboList
	* ドロップダウンコレクションを取得する.
	* 
	*
	* @return		array		$res				ドロップダウンコレクション.
	*/
	function getCboList(){
		try{
			//動的の列にtype＝trueのデータのコレクションを取得する.

			$arrNewCols = $this->readTableNew();
			$arrNewCols = $arrNewCols==""?array():$arrNewCols;
			foreach($arrNewCols as $key => $value)
			{
				if($value["type"] == "t"){
					// 動的の列にtype＝trueのデータのコレクションを取得する。

					$strSql  = " SELECT DISTINCT(".$value["name"].") AS newvalue ";
					$strSql .= " FROM sv_token WHERE ".$value["name"]." IS NOT NULL";
					$strSql .="  AND ".$value["name"]."!=''";
					$strSql .= " UNION ";
					$strSql .= " SELECT DISTINCT(".$value["name"].") AS newvalue ";
					$strSql .= " FROM sv_user WHERE ".$value["name"]." IS NOT NULL";
					$strSql .="  AND ".$value["name"]."!=''";
					// DEBUG メッセージ
					ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
					try{
						$res["cols"][$key] = $this->objDb->ExecuteArrayCommand($strSql);
						if($res["cols"][$key] == "" ) $res["cols"][$key] = array();
						for($i=0;$i<count($res["cols"][$key]);$i++)
						{
							$res["cols"][$key][$i] = $res["cols"][$key][$i][0];
						}
					}catch(Exception $e){
					}
				
				}
				else
				{
					$res["cols"][$key] = "";
				}
			}
			return $res;
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}	
	
	function checkHID( $strHid )
	{
		try
		{
			$this->objDb->begin();
			//HIDが存在するかをチェックする.
			$sql = "select count(*) from sv_token";
			$sql.= " where hid='".$strHid."'";
			$res = $this->objDb->ExecuteCommand($sql);
			return $res[0];	
		}
		catch( Exception $e )
		{
			return 2;
		}
	}
	
	function CountHID()
	{
		try
		{
			//HIDが存在するかをチェックする.
			$sql = "select count(*) from sv_token";
			$res = $this->objDb->ExecuteCommand($sql);
			return $res;	
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	function SetCheckToken($hid,$access)
	{
		try
		{
			// 無効→有効の場合

			$sql = "select access from sv_token";
			$sql.=" where hid='".$hid."'";
			$res = $this->objDb->ExecuteCommand($sql);
			
			if( $access == 1 && $res["access"] != 1){
				
				// Access＝１の数を取得する

				$strselect ="SELECT count(id) as num,userid FROM sv_token ";
				$strselect.=" WHERE access = 1";
				$strselect.=" AND userid in (select userid from sv_token where hid ='".$hid ."')";
				$strselect.=" AND userid !=''";
				$strselect.=" AND userid is not null group by userid";
				
				$res = $this->objDb->ExecuteArrayCommand($strselect);
				if( $res[0]["num"] > 0 ){
					return $res[0]["userid"];
				}
			}
			return true;	
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	function checkExist( $arrTokenInfo )
	{
		try
		{
			$sql = "select count(*) from sv_token";
			$sql.=" where hid='".$arrTokenInfo["tokenhid"]."'";
			$res = $this->objDb->ExecuteCommand($sql);
			if($res[0]==0)
			{
				return false;
			
			}
			else
			{
				return true;
			}
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	
	}
	function checkTokentype()
	{
		try
		{
			$sql = "select id from sv_tokentype";
			$sql.=" where value='ePass1000ND'";
			$res = $this->objDb->ExecuteCommand($sql);
			$sql1 = "select id from sv_tokentype";
			$sql1.=" where value='ePass1000'";
			$res1 = $this->objDb->ExecuteCommand($sql1);
			$sql2 = "select id from sv_tokentype";
			$sql2.=" where value='ePass2001H'";
			$res2 = $this->objDb->ExecuteCommand($sql2);
			
			$typeres=array();
			$typeres[0]=$res[0];
			$typeres[1]=$res1[0];
			$typeres[2]=$res2[0];	
		    return $typeres;
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	
	}
	
	function checkCurrentTokentype($tokenHID)
	{
		try
		{
			$sql = "select typeid from sv_token";
			$sql.=" where hid='".$tokenHID."'";
			$res = $this->objDb->ExecuteCommand($sql);
			$sql1 = "select value from sv_tokentype";
			$sql1.=" where id='".$res[0]."'";
			$res1 = $this->objDb->ExecuteCommand($sql1);
		    return $res1[0];
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	
	}
}
?>
