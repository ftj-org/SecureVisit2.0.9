<?php
/**
 * epass1000NDのユーザー一覧ファイル.
 *
 * @author	呉暁挙.
 * @since	2007-09-03
 * @version	1.0
 */
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";


class User_detail_M{
	
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
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例化");
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: User_list_M
	* コンストラクタ.
	* 
	*/
	function User_detail_M(){		
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
	*ユーザー属性項目を取得する.
	* 
	*
	* @return		Array		$res		リザルト1から、リザルト2に存在する項目を取得し、返す.
	*/
	function readTableNew(){
		try{
			$strSql  = " SELECT type,";
			$strSql  .= " name,";
			$strSql  .= " nameuser,";
			$strSql  .= " memouser";
			//$strSql  .= " btoken";
			$strSql  .= " FROM sv_customtmpl";
			$strSql  .= " WHERE visibleuser=true";
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
	* 関数名: readUserDetail
	* ユーザの情報を読みとる.
	* 
	* @param		string		$userId			ユーザーID.
	*
	* @return		Array		$res			ユーザーデータ.
	*/
	function readUserDetail($userId){
		try{
			$res = array();
			
			//ユーザに適用した属性を取得する.

			$strSql  = " SELECT * ";
			$strSql .= " FROM sv_user ";
			$strSql .= " WHERE userid ='".$userId."'";
			
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
			$res = $this->objDb->ExecuteArrayCommand($strSql);
			
			// group表ユーザの属性を取得する.

			$strSql2  = " SELECT ";
			$strSql2 .= " sv_group.id, ";
			$strSql2 .= " sv_group.name ";
			$strSql2 .= " FROM sv_group,sv_usergroup ";
			$strSql2 .= " WHERE sv_group.id = sv_usergroup.grpid ";
			$strSql2 .= " AND sv_usergroup.userid ='".$userId."'";
			
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2");
			$resGroup = $this->objDb->ExecuteArrayCommand($strSql2);
			$res["sv_group"] =array();
			if($resGroup == "") $resGroup = array();
			foreach($resGroup as $key => $value ){
			$res["sv_group"][$value["id"]] =$value["name"];
			}
			
			// password表ユーザの属性を取得する.

			$strSql3  = " SELECT ";
			$strSql3 .= " id, ";
			$strSql3 .= " pwd ";
			$strSql3 .= " FROM sv_user,sv_password ";
			$strSql3 .= " WHERE (sv_user.pwdid=sv_password.id ";
			$strSql3 .= " AND sv_user.userid ='".$userId."')";
			//$strSql3 .= " OR (sv_user.rpwdid=sv_password.id ";
			//$strSql3 .= " AND sv_user.userid ='".$userId."')";
			
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql3");
			$res["sv_password"] = $this->objDb->ExecuteCommand($strSql3);
					
			// token表ユーザの属性を取得する.

			$strSql4  = " SELECT id,(hid || '/' || CASE access";
			$strSql4 .= " WHEN 1 THEN '有効' ";
			$strSql4 .= " ELSE '無効' ";
			$strSql4 .= " END || '/' || case when memo is not null then (  ";
			$strSql4 .= " CASE when char_length(memo)<10 then memo  ";
			$strSql4 .= " else substring( memo from 1 for 10 )||'...'  ";
			$strSql4 .= " end ) else ''  ";
			$strSql4 .= " END) as option  ";
			$strSql4 .= " FROM sv_token ";
			$strSql4 .= " WHERE sv_token.userid ='".$userId."'";
			
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql4");
			$resHid = $this->objDb->ExecuteArrayCommand($strSql4);
			$res["sv_token"] =array();
			if($resHid == "") $resHid = array();
			foreach($resHid as $key => $value ){
				$res["sv_token"][$value["id"]] =$value["option"];
			}
			
			// sv_userparamから、データの取得
			$strSqlParam ="select id, tag ,value from sv_userparam where userid ='".$userId."'";
			$resCombine = $this->objDb->ExecuteArrayCommand($strSqlParam);
			if($resCombine == "") $resCombine = array();
			foreach($resCombine as $key => $value )
			{
				$res['combine'][$value['tag']."=".$value['value']] = $value['tag']."=".$value['value'];
			}
			// password表緊急のpwd情報を取得
			$strSql5  = " SELECT ";
			$strSql5 .= " sv_password.id, ";
			$strSql5 .= " sv_password.pwd, ";
			$strSql5 .= " sv_password.access, ";
			$strSql5 .= " sv_password.startdate, ";
			$strSql5 .= " sv_password.expire, ";
			$strSql5 .= " sv_password.counter ";
			$strSql5 .= " FROM sv_user,sv_password ";
			$strSql5 .= " WHERE (sv_user.rpwdid=sv_password.id ";
			$strSql5 .= " AND sv_user.userid ='".$userId."')";
			
			$res["sv_repwdinfo"] =$this->objDb->ExecuteCommand($strSql5);
			
			// 端末表取得する.add by liyang
			$strSqlT  = " SELECT ";
			$strSqlT .= " ticket, ";
			$strSqlT .= " counter ";
			$strSqlT .= " FROM sv_termreg ";
			$strSqlT .= " WHERE (userid ='".$userId."')";
			//echo $strSqlT;
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlT");
			$res["sv_term"] = $this->objDb->ExecuteCommand($strSqlT);
			//print_r($res["sv_term"]);;
			
			// 端末数取得する.add by liyang
			$strSqlC  = " SELECT ";
			$strSqlC .= " COUNT(id) ";
			$strSqlC .= " FROM sv_terminfo ";
			$strSqlC .= " WHERE (userid ='".$userId."')";
			//echo $strSqlC;
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlC");
			$res["sv_termnum"] = $this->objDb->ExecuteCommand($strSqlC);
			//print_r($res["sv_term"]);;			

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
        $res1 = $this->objDb->ExecuteArrayCommand("select name from sv_customtmpl where visibleuser='true'");
        if(sizeof($this->arrayCheck($res1)) == 0) return true;
        foreach($_POST as $key1 =>$value1 )
        {
            foreach ($res1 as $key2 => $value2)
            {
				if(substr(trim($key1),3)===trim("txt".$value2[0]))
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
	* 関数名: setUserDetail
	* ユーザの情報をセットする.
	* 
	* @param		string		$arrUserInfo		ユーザーデータ..
	*
	* @return		void.
	*/
	function setUserDetail($arrUserInfo,$strLastupdate){
		try{
			if(!$this->ComtomCompare())
			{
				return "costomdel";
			}
			$this->objDb->begin();
			$sqltime=" SELECT COUNT (userid)";
			$sqltime.=" FROM sv_user";
			$sqltime.=" WHERE userid ='".$arrUserInfo["userid"]."'";
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
				if(trim($arrUserInfo[$arrNewTitle[$i]["name"]])==""){
					$arrUserInfo[$arrNewTitle[$i]["name"]] = "null";
					$strTitleSql .= " ".$arrNewTitle[$i]["name"]."=null,";
				}else{
					$strTitleSql .= " ".$arrNewTitle[$i]["name"]."='".$arrUserInfo[$arrNewTitle[$i]["name"]]."',";
				}
			}
			$strSql4  = " SELECT COUNT(userid)";
			$strSql4 .=" FROM sv_user";
			$strSql4 .=" WHERE userid = '".$arrUserInfo["userid"]."'";
			$resUser = $this->objDb->ExecuteCommand($strSql4);
			if($resUser[0] != 0)
			{
				// user表ユーザの属性を更新する.
				$strSql  = " UPDATE sv_user";
				$strSql .= " SET ";
				$strSql .= "  fullname='".$arrUserInfo["fullname"]."',";
				$strSql .= "  email='".$arrUserInfo["email"]."',";
				$strSql .= "  memo='".$this->objDb->sqlescapestring($arrUserInfo["memo"])."',";
				$strSql .= "  access=".$arrUserInfo["access"].",";
				if($arrUserInfo["startdate"]!=" ")
					$strSql .= "  startdate='".$arrUserInfo["startdate"]."',";
				else
					$strSql .= "  startdate='".date("Y/m/d H:i:s")."',";
				if($arrUserInfo["expire"]!=" ")	
					$strSql .= "  expire='".$arrUserInfo["expire"]."',";
				else
					$strSql .= "  expire='2099/12/31 23:59:59',";
				if($arrUserInfo["token1id"]!="")
					$strSql .= "  token1id=".$arrUserInfo["token1id"].",";
				else
					$strSql .= "  token1id=null,";
				$strSql .= $strTitleSql;
				if($arrUserInfo["token2id"]!="")
					$strSql .= "  token2id=".$arrUserInfo["token2id"].",";
				else
					$strSql .= "  token2id=null,";
				if($arrUserInfo["token3id"]!="")
					$strSql .= "  token3id=".$arrUserInfo["token3id"]."";
				else
					$strSql .= "  token3id=null";
				
				$strSql .= " WHERE userid ='".$arrUserInfo["userid"]."'";
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
				$this->objDb->ExecuteNonQuery($strSql);
				
				$sqlupdate="UPDATE sv_user";
				$sqlupdate.=" SET ";
				$sqlupdate.=" lastupdate = CURRENT_TIMESTAMP";
				$sqlupdate.=" WHERE userid ='".$arrUserInfo["userid"]."'";
				$this->objDb->ExecuteNonQuery($sqlupdate);
				// group表ユーザの属性を更新する.
				$strSql2_1  = " DELETE ";
				$strSql2_1 .= " FROM sv_usergroup ";
				$strSql2_1 .= " WHERE userid ='".$arrUserInfo["userid"]."'";
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2_1");
				$this->objDb->ExecuteNonQuery($strSql2_1);
				$arrUserInfo["sv_group"] = $arrUserInfo["sv_group"] == ""?array():$arrUserInfo["sv_group"];
				foreach( $arrUserInfo["sv_group"] as $key => $value ){
					$strSql2  = " INSERT INTO sv_usergroup";
					$strSql2 .= " (userid, ";
					$strSql2 .= " grpid) ";
					$strSql2 .= " VALUES( ";
					$strSql2 .= " '".$arrUserInfo["userid"]."', ";
					if($value["id"]!="")
						$strSql2 .= " ".$value["id"].")";
					else
						$strSql2 .= " null)";
					
					// DEBUG メッセージ
					ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2");
					try{
						$this->objDb->ExecuteNonQuery($strSql2);
					}catch( Exception $e ){
					}
				}
				
				$arrUserIf = $this->readUserDetail($arrUserInfo["userid"]);
				// ｐｗｄのIDはヌルではない場合
				if($arrUserIf[0]["pwdid"] != "")
				{
					//　ｐｗｄのIDは存在する場合
					$sqlexit=" SELECT count(id) FROM sv_password";
					$sqlexit.=" WHERE id = '".$arrUserIf[0]["pwdid"]."'";
					$exit = $this->objDb->ExecuteCommand($sqlexit);
					if($exit[0] != 0)
					{
						// password表ユーザの属性を更新する.
						$strSql3  = " UPDATE sv_password";
						$strSql3 .= " SET pwd='".$arrUserInfo["sv_password"][0]["pwd"]."'";
						$strSql3 .= " WHERE id =".$arrUserIf[0]["pwdid"]."";
						$this->objDb->ExecuteNonQuery($strSql3);
						
						
						
					}
					//　ｐｗｄをインサートする
					else
					{
						$sqlmax  = " SELECT case when MAX(id)+1 ISNULL then 0 else MAX(id)+1 end as num";
						$sqlmax .= " FROM sv_password";
						$maxPwdId = $this->objDb->ExecuteCommand($sqlmax);
						
						$sqlpwd =" INSERT INTO sv_password(";
						$sqlpwd.= " id,";
						$sqlpwd.= " pwd,";
						$sqlpwd.= " access,";
						$sqlpwd.= " startdate,";
						$sqlpwd.= " expire,";
						$sqlpwd.= " counter)";
						$sqlpwd.= " VALUES(";
						$sqlpwd.= "".$maxPwdId[0].",";
						$sqlpwd.= "'".$arrUserInfo["sv_password"][0]["pwd"]."',";
						$sqlpwd .= " 1,";
						$sqlpwd.= " '".date("Y/m/d H:i:s")."',";
						$sqlpwd.= " '2099/12/31 23:59:59',";
						$sqlpwd.= " -1)"; 
						
						$this->objDb->ExecuteNonQuery($sqlpwd);

						//　sv_userを更新する
						$sqlpwdupdate = " UPDATE sv_user";
						$sqlpwdupdate.=" SET pwdid = ".$maxPwdId[0]."";
						$sqlpwdupdate.=" WHERE userid = '".$arrUserInfo["userid"]."'";
						$this->objDb->ExecuteNonQuery($sqlpwdupdate);
						
					}
				}
				//　ｐｗｄをインサートする
				else
				{
					$sqlmax  = " SELECT case when MAX(id)+1 ISNULL then 0 else MAX(id)+1 end as num";
					$sqlmax .= " FROM sv_password";
					$maxPwdId = $this->objDb->ExecuteCommand($sqlmax);
					
					$sqlpwd =" INSERT INTO sv_password(";
					$sqlpwd.= " id,";
					$sqlpwd.= " pwd,";
					$sqlpwd.= " access,";
					$sqlpwd.= " startdate,";
					$sqlpwd.= " expire,";
					$sqlpwd.= " counter)";
					$sqlpwd.= " VALUES(";
					$sqlpwd.= " ".$maxPwdId[0].",";
					$sqlpwd.= "'".$arrUserInfo["sv_password"][0]["pwd"]."',";
					$sqlpwd .= " 1,";
					$sqlpwd.= " '".date("Y/m/d H:i:s")."',";
					$sqlpwd.= " '2099/12/31 23:59:59',";
					$sqlpwd.= " -1)"; 
					
					$this->objDb->ExecuteNonQuery($sqlpwd);
					
					//　sv_userを更新する
					$sqlpwdupdate = " UPDATE sv_user";
					$sqlpwdupdate.=" SET pwdid = ".$maxPwdId[0]."";
					$sqlpwdupdate.=" WHERE userid = '".$arrUserInfo["userid"]."'";
					
					$this->objDb->ExecuteNonQuery($sqlpwdupdate);
					
				}
				// 緊急ｐｗｄのIDはヌルではない場合
				if($arrUserIf[0]["rpwdid"] != "")
				{
					//　緊急ｐｗｄのIDは存在する場合
					$sqlRpwdexit=" SELECT count(id) FROM sv_password";
					$sqlRpwdexit.=" WHERE id = '".$arrUserIf[0]["rpwdid"]."'";
					$Rpwdexit = $this->objDb->ExecuteCommand($sqlexit);
					
					// password表を更新する.
					if($Rpwdexit[0] != 0)
					{
						$strSqlRpwd = "UPDATE sv_password";
						$strSqlRpwd.=" SET pwd='".$arrUserInfo["sv_password"][1]["pwd"]."',";
						if($arrUserInfo["sv_password"][1]["pwd"] !="")
						{
							$strSqlRpwd.=" access = 1,";
						}
							else{
						$strSqlRpwd.=" access = 0,";
						}
						$strSqlRpwd.=" startdate = '".$arrUserInfo["rstartday"]."',";
						$strSqlRpwd.=" expire ='".$arrUserInfo["rexpireday"]."',";
						$strSqlRpwd.=" counter='".$arrUserInfo["rpwdtimes"]."' ";
						$strSqlRpwd .= " WHERE id =".$arrUserIf[0]["rpwdid"]."";
						$this->objDb->ExecuteNonQuery($strSqlRpwd);
					}
					//　ｐｗｄをインサートする
					else
					{
						$sqlmax  = " SELECT case when MAX(id)+1 ISNULL then 0 else MAX(id)+1 end as num";
						$sqlmax .= " FROM sv_password";
						$maxPwdId = $this->objDb->ExecuteCommand($sqlmax);
						
						$sqlRpwd  = " INSERT INTO sv_password(";
						$sqlRpwd.= " id,";
						$sqlRpwd.= " pwd,";
						$sqlRpwd.= " access,";
						$sqlRpwd.= " startdate,";
						$sqlRpwd.= " expire,";
						$sqlRpwd.= " counter)";
						$sqlRpwd.= " VALUES(";
						$sqlRpwd.= " ".($maxPwdId[0]).",";
						$sqlRpwd.= " '".$arrUserInfo["sv_password"][1]["pwd"]."',";
						$sqlRpwd.= " 1,";
						$sqlRpwd.= " '".$arrUserInfo["rstartday"]."',";
						$sqlRpwd.= " '" .$arrUserInfo["rexpireday"]."',";
						$sqlRpwd.= " '" .$arrUserInfo["rpwdtimes"]."')";
						
						$this->objDb->ExecuteNonQuery($sqlRpwd);
						
						//　sv_userを更新する
						$sqlRpwdupdate = " UPDATE sv_user";
						$sqlRpwdupdate.=" SET rpwdid = ".($maxPwdId[0]+1)."";
						$sqlRpwdupdate.=" WHERE userid = '".$arrUserInfo["userid"]."'";
						$this->objDb->ExecuteNonQuery($sqlRpwdupdate);
					}
					
				}
				//　ｐｗｄをインサートする
				else
				{
					$sqlmax  = " SELECT case when MAX(id)+1 ISNULL then 0 else MAX(id)+1 end as num";
					$sqlmax .= " FROM sv_password";
					$maxPwdId = $this->objDb->ExecuteCommand($sqlmax);
					
					$sqlRpwd  = " INSERT INTO sv_password(";
					$sqlRpwd.= " id,";
					$sqlRpwd.= " pwd,";
					$sqlRpwd.= " access,";
					$sqlRpwd.= " startdate,";
					$sqlRpwd.= " expire,";
					$sqlRpwd.= " counter)";
					$sqlRpwd.= " VALUES(";
					$sqlRpwd.= " ".($maxPwdId[0]).",";
					$sqlRpwd.= " '".$arrUserInfo["sv_password"][1]["pwd"]."',";
					$sqlRpwd.= " 1,";
					$sqlRpwd.= " '".$arrUserInfo["rstartday"]."',";
					$sqlRpwd.= " '" .$arrUserInfo["rexpireday"]."',";
					$sqlRpwd.= " '" .$arrUserInfo["rpwdtimes"]."')";
					
					$this->objDb->ExecuteNonQuery($sqlRpwd);
					
					//　sv_userを更新する
					$sqlRpwdupdate = " UPDATE sv_user";
					$sqlRpwdupdate.=" SET rpwdid = ".($maxPwdId[0])."";
					$sqlRpwdupdate.=" WHERE userid = '".$arrUserInfo["userid"]."'";
					$this->objDb->ExecuteNonQuery($sqlRpwdupdate);
					
				}
				//liyang
				//存在する場合
				$sqlexi=" SELECT count(userid) FROM sv_termreg";
				$sqlexi.=" WHERE userid = '".$arrUserInfo["userid"]."'";
				$exi = $this->objDb->ExecuteCommand($sqlexi);
				if($exi[0] != 0)
				{
					if($arrUserInfo["ticket"]==""||$arrUserInfo["counter"]=="")
					{
						$strSqlt  = " DELETE ";
						$strSqlt .= " FROM sv_termreg";
						$strSqlt .= " WHERE userid ='".$arrUserInfo["userid"]."'";
						//echo $strSqlt;
						ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlt");
						$this->objDb->ExecuteNonQuery($strSqlt);
						
						$strSqli  = " DELETE ";
						$strSqli .= " FROM sv_terminfo";
						$strSqli .= " WHERE userid ='".$arrUserInfo["userid"]."'";
						//echo $strSqli;
						ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqli");
						$this->objDb->ExecuteNonQuery($strSqli);
					}else{
						$strSqlt  = " UPDATE ";
						$strSqlt .= " sv_termreg ";
						$strSqlt .= " SET ";
						$strSqlt .= " ticket = '".$arrUserInfo["ticket"]."',";
						$strSqlt .= " counter = '".$arrUserInfo["counter"]."'";
						$strSqlt .= " WHERE userid ='".$arrUserInfo["userid"]."'";
						//echo $strSqlt;
						ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlt");
						$this->objDb->ExecuteNonQuery($strSqlt);
					}
				}else{
						if($arrUserInfo["ticket"]!=""&&$arrUserInfo["counter"]!="")
						{
						$strSqlt  = " INSERT INTO";
						$strSqlt .= " sv_termreg (userid,ticket,counter)VALUES('".$arrUserInfo["userid"]."','".$arrUserInfo["ticket"]."','".$arrUserInfo["counter"]."')";
						$t_a=$arrUserInfo["ticket"]!=""||$arrUserInfo["counter"]!="";
						//echo $strSqlt;
						//echo $t_a;
						//print_r($arrUserInfo);
						ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlt");
						$this->objDb->ExecuteNonQuery($strSqlt);						
						}
				}
				//print_r($exi);
				//echo $strSqlt;
				// DEBUG メッセージ
				//ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlt");
				//$this->objDb->ExecuteNonQuery($strSqlt);
				
				
				// token表ユーザの属性を更新する
				$strSql4  = " UPDATE ";
				$strSql4 .= " sv_token ";
				$strSql4 .= " SET ";
				$strSql4 .= " userid = null";
				$strSql4 .= " WHERE userid ='".$arrUserInfo["userid"]."'";
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql4");
				$this->objDb->ExecuteNonQuery($strSql4);
				
				$strSql4_2  = " UPDATE ";
				$strSql4_2 .= " sv_token ";
				$strSql4_2 .= " SET ";
				$strSql4_2 .= " userid = '".$arrUserInfo["userid"]."'";
				if($arrUserInfo["token1id"]=="") $arrUserInfo["token1id"]="null";
				if($arrUserInfo["token2id"]=="") $arrUserInfo["token2id"]="null";
				if($arrUserInfo["token3id"]=="") $arrUserInfo["token3id"]="null";
				$strSql4_2 .= " WHERE id IN(".$arrUserInfo["token1id"].",".$arrUserInfo["token2id"].",".$arrUserInfo["token3id"].")";
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql4_2");
				$this->objDb->ExecuteNonQuery($strSql4_2);
				
				$strDelParam ="delete from sv_userparam where userid ='";
				$strDelParam.= $arrUserInfo["userid"]."'";
				$this->objDb->ExecuteNonQuery($strDelParam);
				
				$arrUserInfo["combine"] = $arrUserInfo["combine"] == ""?array():$arrUserInfo["combine"];
				
				foreach( $arrUserInfo["combine"] as $key => $value )
				{ 
					$arrCombine  = explode('=' , $value );
					$strTag = $arrCombine[0];
					$strParam = $arrCombine[1];
					
					$strSqlParam  = " INSERT INTO sv_userparam";
					$strSqlParam.= " (userid, ";
					$strSqlParam.= " tag, ";
					$strSqlParam.= " value) ";
					$strSqlParam .= " VALUES( ";
					$strSqlParam .= " '".$arrUserInfo["userid"]."', ";
					$strSqlParam .= " '".$strTag."', ";
					$strSqlParam .= " '".$strParam."')";
					
					// DEBUG メッセージ
					ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlParam");
					$this->objDb->ExecuteNonQuery($strSqlParam);
				}
				
				$this->objDb->commit();
				return true;
			}
			else
			{
				return false;
			}
			
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			$this->objDb->rollback();
			throw $e;
		}	
	}

    
	/**
	* 関数名: addUserDetail
	* ユーザを追加する.
	* 
	* @param		string		$arrUserInfo		ユーザーデータ..
	*
	* @return		string		$res				fail：主键重复fail返回，正常结束true返回.
	*/
	function addUserDetail( $arrUserInfo ){
		try{
            
			if(!$this->ComtomCompare())
			{
				return "costomdel";
			}
            
			$this->objDb->begin();
			
			// 登録できるUSBトークンの最大件数
			try{
				$strSqlCount  = " SELECT count(*) from sv_user";
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlCount");
				$strUserCount = $this->objDb->ExecuteCommand($strSqlCount);
				
				$strUserLimit = SESSION::GET("SESS_LIC_USERLIMIT");
				
				if($strUserCount[0] >= $strUserLimit)
				{
					return "licensefail";
				}
				
			}
			catch( Exception $e ){
				$this->objDb->rollback();
				return "licensefail";
			}
			
			
			// ユーザを追加する.
			try{
				$strSqlUser  = " INSERT INTO sv_user";
				$strSqlUser .= " (userid, ";
				$strSqlUser .= " email) ";
				$strSqlUser .= " VALUES( ";
				$strSqlUser .= " '".$arrUserInfo["userid"]."', ";
				$strSqlUser .= " '".$arrUserInfo["email"]."')";
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlUser");
				$this->objDb->ExecuteNonQuery($strSqlUser);
			}
			catch( Exception $e ){
				$this->objDb->rollback();
				return "fail";
			}
					
			// group表ユーザの情報を削除する.
			$strSql2_1  = " DELETE ";
			$strSql2_1 .= " FROM sv_usergroup ";
			$strSql2_1 .= " WHERE userid ='".$arrUserInfo["userid"]."'";
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2_1");
			$this->objDb->ExecuteNonQuery($strSql2_1);
			
			$arrUserInfo["sv_group"] = $arrUserInfo["sv_group"] == ""?array():$arrUserInfo["sv_group"];
			foreach( $arrUserInfo["sv_group"] as $key => $value ){
				$strSql2  = " INSERT INTO sv_usergroup";
				$strSql2 .= " (userid, ";
				$strSql2 .= " grpid) ";
				$strSql2 .= " VALUES( ";
				$strSql2 .= " '".$arrUserInfo["userid"]."', ";
				if($value["id"]!="")
					$strSql2 .= " ".$value["id"].")";
				else
					$strSql2 .= " null)";
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2");
				$this->objDb->ExecuteNonQuery($strSql2);
			}
			
			// password表最大IDを取得する.			
			$strSql3  = " SELECT case when MAX(id)+1 ISNULL then 0 else MAX(id)+1 end as num";
			$strSql3 .= " FROM sv_password";
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql3");
			$maxPwdId = $this->objDb->ExecuteCommand($strSql3);
			
			$strSql3_2  = " INSERT INTO sv_password(";
			$strSql3_2 .= " id,";
			$strSql3_2 .= " pwd,";
			$strSql3_2 .= " access,";
			$strSql3_2 .= " startdate,";
			$strSql3_2 .= " expire,";
			$strSql3_2 .= " counter)";
			$strSql3_2 .= " VALUES(";
			$strSql3_2 .= " ".$maxPwdId[0].",";
			$strSql3_2 .= " '".$arrUserInfo["sv_password"][0]["pwd"]."',";
			$strSql3_2 .= " 1,";
			$strSql3_2 .= " '".date("Y/m/d H:i:s")."',";
			$strSql3_2 .= " '2099/12/31 23:59:59',";
			$strSql3_2 .= " -1)";
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql3_2");
			$this->objDb->ExecuteNonQuery($strSql3_2);
			
			// レスキューパスワードをインサートする。			
			$strSql3_3  = " INSERT INTO sv_password(";
			$strSql3_3 .= " id,";
			$strSql3_3 .= " pwd,";
			$strSql3_3 .= " access,";
			$strSql3_3 .= " startdate,";
			$strSql3_3 .= " expire,";
			$strSql3_3 .= " counter)";
			$strSql3_3 .= " VALUES(";
			$strSql3_3 .= " ".($maxPwdId[0]+1).",";
			$strSql3_3 .= " '".$arrUserInfo["sv_password"][1]["pwd"]."',";
			if($arrUserInfo["sv_password"][1]["pwd"] !="")
			{
				$strSql3_3 .= " 1,";
			}
			else{
				$strSql3_3 .= " 0,";
			}
			$strSql3_3 .= " '".$arrUserInfo["rstartday"]."',";
			$strSql3_3 .= " '" .$arrUserInfo["rexpireday"]."',";
			$strSql3_3 .= " '" .$arrUserInfo["rpwdtimes"]."')";
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql3_3");
			$this->objDb->ExecuteNonQuery($strSql3_3);
						
			// token表ユーザの情報を更新する.
			$strSql4  = " UPDATE ";
			$strSql4 .= " sv_token ";
			$strSql4 .= " SET ";
			$strSql4 .= " userid = ''";
			$strSql4 .= " WHERE userid ='".$arrUserInfo["userid"]."'";
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql4");
			$this->objDb->ExecuteNonQuery($strSql4);
			
			$strSql4_2  = " UPDATE ";
			$strSql4_2 .= " sv_token ";
			$strSql4_2 .= " SET ";
			$strSql4_2 .= " userid = '".$arrUserInfo["userid"]."'";
			if($arrUserInfo["token1id"]=="") $arrUserInfo["token1id"]="null";
			if($arrUserInfo["token2id"]=="") $arrUserInfo["token2id"]="null";
			if($arrUserInfo["token3id"]=="") $arrUserInfo["token3id"]="null";
			
			$strSql4_2 .= " WHERE id IN(".$arrUserInfo["token1id"].",".$arrUserInfo["token2id"].",".$arrUserInfo["token3id"].")";
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql4_2");
			$this->objDb->ExecuteNonQuery($strSql4_2);
			
			$arrNewTitle = $this->readTableNew();
			$strTitleSql = "";
			
			$arrNewTitle = $arrNewTitle==""?array():$arrNewTitle;
			// 指定した配列に 関してループ処理を行って、CSVファイルのタイトルを追加する.
			for($i=0;$i<count($arrNewTitle);$i++)
			{
				if(trim($arrNewTitle[$i]["name"])=="") continue;
				if(trim($arrUserInfo[$arrNewTitle[$i]["name"]])==""){
					$arrUserInfo[$arrNewTitle[$i]["name"]] = "null";
					$strTitleSql .= " ".$arrNewTitle[$i]["name"]."=null,";
				}else{
					$strTitleSql .= " ".$arrNewTitle[$i]["name"]."='".$arrUserInfo[$arrNewTitle[$i]["name"]]."',";
				}
			}
			// 端末を追加するadd by liyang.
			if($arrUserInfo["ticket"]!=""&&$arrUserInfo["counter"]!="")
			{
				try{
					$strSqlTerm  = " INSERT INTO sv_termreg";
					$strSqlTerm .= " (userid, ";
					$strSqlTerm .= " ticket,";
					$strSqlTerm .= " counter) ";
					$strSqlTerm .= " VALUES( ";
					$strSqlTerm .= " '".$arrUserInfo["userid"]."', ";
					$strSqlTerm .= " '".$arrUserInfo["ticket"]."', ";
					$strSqlTerm .= " '".$arrUserInfo["counter"]."')";
					// DEBUG メッセージ
					ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlTerm");
					$this->objDb->ExecuteNonQuery($strSqlTerm);
				}
				catch( Exception $e ){
					$this->objDb->rollback();
					return "fail";
				}
			}
			
			
			// 用户表ユーザの情報を更新する.
			$strSql  = " UPDATE sv_user";
			$strSql .= " SET ";
			$strSql .= "  fullname='".$arrUserInfo["fullname"]."',";
			$strSql .= "  email='".$arrUserInfo["email"]."',";
			$strSql .= "  memo='".$this->objDb->sqlescapestring($arrUserInfo["memo"])."',";
			$strSql .= "  access=".$arrUserInfo["access"].",";
			if($arrUserInfo["startdate"]!=" ")
				$strSql .= "  startdate='".$arrUserInfo["startdate"]."',";
			else
				$strSql .= "  startdate='".date("Y/m/d H:i:s")."',";
			if($arrUserInfo["expire"]!=" ")
				$strSql .= "  expire='".$arrUserInfo["expire"]."',";
			else
				$strSql .= "  expire='2099/12/31 23:59:59',";
			if($arrUserInfo["token1id"]!="")
				$strSql .= "  token1id=".$arrUserInfo["token1id"].",";
			else
				$strSql .= "  token1id=null,";
			$strSql .= $strTitleSql;
			if($arrUserInfo["token2id"]!="")
				$strSql .= "  token2id=".$arrUserInfo["token2id"].",";
			else
				$strSql .= "  token2id=null,";
			if($arrUserInfo["token3id"]!="")
				$strSql .= "  token3id=".$arrUserInfo["token3id"].",";
			else
				$strSql .= "  token3id=null,";
			
			$strSql .= "  pwdid=".$maxPwdId[0].",";
			$strSql .= "  rpwdid=".($maxPwdId[0]+1).",";
			$strSql .= "  lastlogindate=null";
			
			$strSql .= " WHERE userid ='".$arrUserInfo["userid"]."'";
			
			$sqlupdate="UPDATE sv_user";
			$sqlupdate.=" SET ";
			$sqlupdate.=" lastupdate = CURRENT_TIMESTAMP";
			$sqlupdate.=" WHERE userid ='".$arrUserInfo["userid"]."'";
			$this->objDb->ExecuteNonQuery($sqlupdate);

			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
			$strIndexSQL = "SELECT pg_catalog.setval('sv_password_id_seq',";
			$strIndexSQL.= $maxPwdId[0]+1;
			$strIndexSQL.=", true)";
			
			$arrUserInfo["combine"] = $arrUserInfo["combine"] == ""?array():$arrUserInfo["combine"];
			
			foreach( $arrUserInfo["combine"] as $key => $value )
			{ 
				$arrCombine  = explode('=' , $value );
				$strTag = $arrCombine[0];
				$strParam = $arrCombine[1];
				
				$strSqlParam  = " INSERT INTO sv_userparam";
				$strSqlParam.= " (userid, ";
				$strSqlParam.= " tag, ";
				$strSqlParam.= " value) ";
				$strSqlParam .= " VALUES( ";
				$strSqlParam .= " '".$arrUserInfo["userid"]."', ";
				$strSqlParam .= " '".$strTag."', ";
				$strSqlParam .= " '".$strParam."')";
				
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSqlParam");
				$this->objDb->ExecuteNonQuery($strSqlParam);
			}
			
			$this->objDb->ExecuteNonQuery($strIndexSQL);
			$this->objDb->ExecuteNonQuery($strSql);
			
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
	* ドロップタウンのリストを取得する.
	* 
	*
	* @return		array		$res				ドロップタウンのコレクション.
	*/
	function getCboList(){
	try{
		//動的列にタイプは1の場合時、データのコレクションを取得する.

		$arrNewCols = $this->readTableNew();
		$arrNewCols = $arrNewCols==""?array():$arrNewCols;
		foreach($arrNewCols as $key => $value){
			if($value["type"] == "t"){
				// 動的列にタイプは1の場合時、データのコレクションを取得する.

				
				$strSql  = " SELECT DISTINCT(".$value["name"].") AS newvalue";
				$strSql .= " FROM sv_user WHERE ".$value["name"]." IS NOT NULL";
				$strSql .= " AND ".$value["name"]."!=''";
				//if($value["btoken"] == "t"){
					$strSql .= " UNION ";
					$strSql .= " SELECT DISTINCT(".$value["name"].") AS newvalue ";
					$strSql .= " FROM sv_token WHERE ".$value["name"]." IS NOT NULL";
					$strSql .= " AND ".$value["name"]."!=''";
				//}
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
			else{
				$res["cols"][$key] = "";
			}
		}
		
		//　group表にgroupのコレクションを取得する.

		// 動的列にタイプは1の場合時、データのコレクションを取得する.

		
		$strSql2  = " SELECT id,name AS group";
		$strSql2 .= " FROM sv_group ";
		// DEBUG メッセージ
		ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2");
		$resGroup = $this->objDb->ExecuteArrayCommand($strSql2);
		$res["sv_group"] =array();
		if($resGroup == "") $resGroup = array();
		foreach($resGroup as $key => $value ){
			$res["sv_group"][$value["id"]] =$value["group"];
		}
		
		// token表にhid/accessのコレクションを取得する.

			$strSql3  = " SELECT id,(hid || '/' || CASE access ";
			$strSql3 .= " WHEN 1 THEN '有効' ";
			$strSql3 .= " ELSE '無効' ";
			$strSql3 .= " END || '/' || case when memo is not null then (  ";
			$strSql3 .= " CASE when char_length(memo)<10 then memo  ";
			$strSql3 .= " else substring( memo from 1 for 10 )||'...'  ";
			$strSql3 .= " end ) else ''  ";
			$strSql3 .= " END) as option ";
			$strSql3 .= " FROM sv_token ";
			$strSql3 .= " WHERE userid= ''";
			$strSql3 .= " OR userid is null";
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql3");
			$resHid = $this->objDb->ExecuteArrayCommand($strSql3);
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
	* 関数名: getGroup
	* アクセスグループ.
	* 
	*
	* @return		array		$res				アクセスグループ.
	*/
	function getGroup( $arrGroupId ){
		try{
			if( !is_array($arrGroupId)) return array();
			if( sizeof($arrGroupId) == 0) return array();
			$strSqlHid = "";
			$beginFlag = false;
			foreach( $arrGroupId as $k => $v ){
				if($beginFlag) $strSqlHid .= ",";
				$strSqlHid .= "$v";
				$beginFlag = true;
			}
			
			$strSql2  = " SELECT id,name AS group";
			$strSql2 .= " FROM sv_group ";
			$strSql2 .= " WHERE id in ($strSqlHid) ";
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2");
			$resGroup = $this->objDb->ExecuteArrayCommand($strSql2);
			$res =array();
			if($resGroup == "") $resGroup = array();
			foreach($resGroup as $key => $value ){
				$res[$value["id"]] =$value["group"];
			}
			return $res;
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: getHids
	* USBトークン.
	* 
	*
	* @return		array		$res				USBトークン.
	*/
	function getHids( $arrHid ){
		try{
			if( !is_array($arrHid)) return array();
			if( sizeof($arrHid) == 0) return array();
			$strSqlHid = "";
			$beginFlag = false;
			foreach( $arrHid as $k => $v ){
				if($beginFlag) $strSqlHid .= ",";
				$strSqlHid .= "$v";
				$beginFlag = true;
			}
			
			$strSql3  = " SELECT id,(hid || '/' || CASE access ";
			$strSql3 .= " WHEN 1 THEN '有効' ";
			$strSql3 .= " ELSE '無効' ";
			$strSql3 .= " END || '/' || case when memo is not null then (  ";
			$strSql3 .= " CASE when char_length(memo)<10 then memo  ";
			$strSql3 .= " else substring( memo from 1 for 10 )||'...'  ";
			$strSql3 .= " end ) else ''  ";
			$strSql3 .= " END) as option ";
			$strSql3 .= " FROM sv_token ";
			$strSql3 .= " WHERE id in ($strSqlHid)";
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql3");
			$resHid = $this->objDb->ExecuteArrayCommand($strSql3);
			$res =array();
			if($resHid == "") $resHid = array();
			foreach($resHid as $key => $value ){
				$res[$value["id"]] =$value["option"];
			}			
			return $res;
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	//
	function admingroupcheck( $userid ){
		try{
			$strSql="SELECT count(userid)FROM sv_usergroup as a,sv_group as b WHERE a.grpid = b.id AND b.name = 'Administrators' AND userid!='".$userid."'";
			$count = $this->objDb->ExecuteCommand($strSql);
			return $count;
		
		}
		
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	function admingroupid($arrGrpId){
		try{
			$strGrpId=implode(",",$arrGrpId);
			$strSql="SELECT COUNT (id)  FROM sv_group  WHERE name ='Administrators'";
			$strSql.=" AND id in($strGrpId)";
			$count = $this->objDb->ExecuteCommand($strSql);
			return $count;
			
		}
		
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}		
	
}

?>
