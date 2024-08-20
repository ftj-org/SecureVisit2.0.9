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
require_once $G_APPPATH[0]."lib/pageview.php";


class User_list_M{

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
			$this->userView();
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
	function User_list_M(){		
		try{
			$this->__construct();			
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}

        /**
        * 関数名: escapeStringForQuery
        *クエリに使う文字列をエスケープする.
        *
        * @param                string          $data                エスケープするテキスト文字列.
        *
        * @return              エスケープされたデータを文字列で返します.
        */
        function escapeStringForQuery( $data ){
                return $this->objDb->SqlEscapeString($data);
        }
	
	/**
	* 関数名: userView
	* create view.
	* 
	*/
	function userView(){		
		try{
			
			
			$arrNewTitle = $this->readTableNew();
			$strTitleSql = "";
			
			$arrNewTitle = $arrNewTitle==""?array():$arrNewTitle;
			// 指定した配列に 関してループ処理を行って、CSVファイルのタイトルを追加する.
			for($i=0;$i<count($arrNewTitle);$i++)
			{
				if(trim($arrNewTitle[$i]["name"])=="") continue;
				$strTitleSql .= " sv_user.".$arrNewTitle[$i]["name"].",";
			}
			//ビューを作る.
			$strSql  = " CREATE VIEW userview AS ";
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
			$this->viewCreateSql .= " sv_password.pwd as password";
			$this->viewCreateSql .= " FROM sv_user";
			$this->viewCreateSql .= " left join sv_usergroup on sv_user.userid = sv_usergroup.userid";
			$this->viewCreateSql .= " left join sv_group on sv_usergroup.grpid = sv_group.id";
			$this->viewCreateSql .= " left join sv_token on (sv_token.userid = sv_user.userid AND sv_token.access=1)";
			$this->viewCreateSql .= " left join sv_password on sv_password.id = sv_user.pwdid";
			$this->viewCreateSql .= " ";
			
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
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
			$pageObj = new PageView("(SELECT  DISTINCT ON(userid) * FROM userview ) as temp",$pageNum,$offset ,$this->objDb);
			$pageObj->SetCondition($condition,$orderBy);
			$res = $pageObj->ReadList();
			$resList = $pageObj->MakePage();
			$resTotal = $pageObj->Total;
			$resNew = $this->readTableNew();
			$cssFlag = true;
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");
			
			// 配列かどうかを判定する.
			if(!is_array($res)) return array($resList,$res,intval($resTotal));
			foreach( $res as $key => $value)
			{
					// ユーザのグループのリザルトを返す.
					$strSql  = " SELECT sv_group.name ";
					$strSql .= " FROM sv_group,sv_usergroup";
					$strSql .= " WHERE sv_usergroup.grpid=sv_group.id";
					$strSql .= " AND sv_usergroup.userid = '".$value["userid"]."'";
					$uGroup = $this->objDb->ExecuteArrayCommand($strSql);
					
					ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
					for( $i = 0;$i<count($uGroup);$i++)
					{
						$uGroup[$i] = $uGroup[$i][0]; 
					}
					
					//配列要素を文字列により連結し、新しいリザルトを返す.
					
					$value["startdate"] = str_replace("-","/",substr($value["startdate"],0,10));
					$value["expire"] = str_replace("-","/",substr($value["expire"],0,10));
					$value["lastlogindate"] = str_replace("-","/",substr($value["lastlogindate"],0,10));
					$newRes[$key] = $value;
					
					//ie9.start
					if(!is_array($resNew)) $resNew = array();
					$arrAttris = array();
					foreach($resNew as $key2 => $value2 ){
							$arrAttris[$value2["name"]] = $value[$value2["name"]];
					}

					$newRes[$key] = &$value;
					$newRes[$key]["attris"] = $arrAttris;
					unset($value);	
					//ie9.end
					
					
					$newRes[$key]["accessgroup"] = sizeof($uGroup)>=1?implode(",<br>",$uGroup):$uGroup;
					if(stristr($newRes[$key]["accessgroup"],"Administrators"))
					{
						$newRes[$key]["exist"]=1;
					}
					else
					{
						$newRes[$key]["exist"]=2;
					}
								
					// cssの仕様を設定する.
					if( $cssFlag ) 
						$newRes[$key]["css_class"] = "evenrowbg";
					else 
						$newRes[$key]["css_class"] = "oddrowbg";
					
					$cssFlag = $cssFlag==true?false:true;
			}
			return array($resList,$newRes,$resTotal);
			
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}


	/**
	* 関数名: deleteUser
	* 指定しユーザーを削除する.
	* 
	* @param		string		$UserID		ユーザーID.
	*
	* @return		void.
	*/
	function deleteUser( $UserID ){
		try{
			$this->objDb->begin();
			$strSql  = " DELETE ";
			$strSql .= " FROM   sv_password";
			$strSql .= " USING  sv_user";
			$strSql .= " WHERE  sv_password.id =sv_user.pwdid ";
			//$strSql .= " OR     sv_password.id =sv_user.rpwdid ";
			$strSql .= " AND    sv_user.userid ='".$UserID."'";			
			
			$strSql2  = " UPDATE sv_token ";
			$strSql2 .= " SET userid=null ";
			$strSql2 .= " WHERE userid ='".$UserID."'";			
			
			$strSql3  = " DELETE ";
			$strSql3 .= " FROM sv_usergroup ";
			$strSql3 .= " WHERE userid ='".$UserID."'";			
			
			$strSql4  = " DELETE ";
			$strSql4 .= " FROM sv_user ";
			$strSql4 .= " WHERE userid ='".$UserID."'";
			
			$strSql5  = " DELETE ";
			$strSql5 .= " FROM   sv_password";
			$strSql5 .= " USING  sv_user";
			$strSql5 .= " WHERE    sv_password.id =sv_user.rpwdid ";
			$strSql5 .= " AND    sv_user.userid ='".$UserID."'";	
			
			$strDelParam ="delete from sv_userparam where userid ='";
			$strDelParam.= $UserID."'";
			
			//liyang
			$strDelTermreg ="delete from sv_termreg where userid ='";
			$strDelTermreg.= $UserID."'";
			$strDelTerminfo ="delete from sv_terminfo where userid ='";
			$strDelTerminfo.= $UserID."'";
			
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql3");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql4");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strDelParam");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strDelTermreg");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strDelTerminfo");
			
			
			$this->objDb->ExecuteNonQuery($strSql);
			$this->objDb->ExecuteNonQuery($strSql2);
			$this->objDb->ExecuteNonQuery($strSql3);
			$this->objDb->ExecuteNonQuery($strSql5);
			$this->objDb->ExecuteNonQuery($strSql4);
			$this->objDb->ExecuteNonQuery($strDelParam);
			$this->objDb->ExecuteNonQuery($strDelTermreg);
			$this->objDb->ExecuteNonQuery($strDelTerminfo);
			$this->objDb->commit();
			
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			$this->objDb->rollback();
			throw $e;
		}	
	}
	
	/**
	* 関数名: setState
	* 状態を変換する.
	* 
	* @param		string		$UserID		ユーザーID.
	*
	* @return		void.
	*/
	function setState( $UserID ){
	try{
			$strSql  = " SELECT access ";
			$strSql .= " FROM sv_user";
			$strSql .= " WHERE userid ='".$UserID."'";
			$uAccess = $this->objDb->ExecuteArrayCommand($strSql);
			
			switch($uAccess[0][0]){
				case "0":
						$strSql  = " UPDATE sv_user ";
						$strSql .= " SET ";
						$strSql .= " access= access+2, ";
						$strSql .= " lastupdate = CURRENT_TIMESTAMP";
						$strSql .= " WHERE userid ='".$UserID."'";
						
						// DEBUG メッセージ
						ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");			
						$this->objDb->ExecuteNonQuery($strSql);	
				break;
				case "1":
						$strSql  = " UPDATE sv_user ";
						$strSql .= " SET ";
						$strSql .= " access= access-1, ";
						$strSql .= " lastupdate = CURRENT_TIMESTAMP";
						$strSql .= " WHERE userid ='".$UserID."'";
						
						// DEBUG メッセージ
						ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");			
						$this->objDb->ExecuteNonQuery($strSql);							
				break;
				case "2":
						$strSql  = " UPDATE sv_user ";
						$strSql .= " SET ";
						$strSql .= " access= access-1, ";
						$strSql .= " lastupdate = CURRENT_TIMESTAMP";
						$strSql .= " WHERE userid ='".$UserID."'";
						
						// DEBUG メッセージ
						ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");			
						$this->objDb->ExecuteNonQuery($strSql);							
				break;
			}			
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}	
	}
	
	/**
	* 関数名: exportUserCSV
	* 条件によりデータを検索しCSVファイルを形成し、ダウンロードする.
	* 
	* @param		string		$orderBy		ソーティングの条件.
	* @param		string		$condition		検索の条件.
	*
	* @return		void.
	*/
	function exportUserCSV($orderBy,$condition){
		try{
			$arrNewTitle = $this->readTableNew();
			$strTitleSql = "";
			
			$arrNewTitle = $arrNewTitle==""?array():$arrNewTitle;
			$iNewTitleCount = 0;
			// 指定した配列に 関してループ処理を行って、CSVファイルのタイトルを追加する.
			for($i=0;$i<count($arrNewTitle);$i++)
			{
				if(trim($arrNewTitle[$i]["name"])=="") continue;
				$strTitleSql .= ",".$arrNewTitle[$i]["name"]."";
				$iNewTitleCount++;
			}
			//データのエクスポート.
			$strSql  = " SELECT userid,";
			$strSql .= " fullname, ";
			$strSql .= " email, ";
			$strSql .= " access, ";
			$strSql .= " startdate, ";
			$strSql .= " expire,memo, ";
			$strSql .= " lastlogindate, ";
			$strSql .= " token1id AS token1, ";
			$strSql .= " token2id AS token2, ";
			$strSql .= " token3id AS token3, ";
			$strSql .= " pwdid, ";
			$strSql .= " rpwdid ";
			$strSql .= $strTitleSql;
			$strSql .= " FROM (SELECT  DISTINCT ON(userid) * FROM (".$this->viewCreateSql.") as userview ) as temp ";
			$strSql .= $condition.$orderBy ;
			//$res = $this->objDb->ExecuteArrayCommand($strSql);
			
			$fileName = G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."UserCSV.csv";
			$pconn = @fopen($fileName,'w+');
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$pconn ){
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}
			
			// ファイルのタイトル.
			$strTitleSql = "userid,fullname,email,access,startdate,expire,memo,lastlogindate,token1,token2,token3,password,rescuepassword".$strTitleSql.",rescuetimes,rstartdate,rexpire,accessgroup,params,ticket,counter";
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
				
					// 指定した配列に 関してループ処理を行う. 
					foreach( $res as $key => $value){
						
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
						// ユーザの有効なtoken hidを取得する.
						$strSql2  = " SELECT sv_token.hid ";
						$strSql2 .= " FROM sv_token ";
						$strSql2 .= " WHERE sv_token.userid ='".$value["userid"]."'";
						$TokenHid = $this->objDb->ExecuteArrayCommand($strSql2);
						// ユーザの有効なtoken hidを取得する.
						$strSql3  = " SELECT pwd ";
						$strSql3 .= " FROM sv_password ";
						$strSql3 .= " WHERE id =".$value["pwdid"]."";
						$pwd = $this->objDb->ExecuteArrayCommand($strSql3);
						
						$strSql4  = " SELECT pwd, ";
						$strSql4 .= " counter as times, ";
						$strSql4 .= " startdate as rstartdate,";
						$strSql4 .= " expire as rexpire ";
						$strSql4 .= " FROM sv_password ";
						$strSql4 .= " WHERE id =".$value["rpwdid"]."";
						$repwd = $this->objDb->ExecuteArrayCommand($strSql4);
						
						// 新しいリザルトを作成し、返す.
						$value[8] = $TokenHid[0][0];
						$value[9] = $TokenHid[1][0];
						$value[10] = $TokenHid[2][0];
						$value[11] = $pwd[0][0];
						$value[12]= $repwd [0][0];
						
						$value[12+count($arrNewTitle)+1]= $repwd [0][0]!=""?$repwd [0][1]:"";
						$value[12+count($arrNewTitle)+2]= $repwd [0][0]!=""?str_replace("-","/",substr($repwd [0][2],0,19)):"";
						$value[12+count($arrNewTitle)+3]= $repwd [0][0]!=""?str_replace("-","/",substr($repwd [0][3],0,19)):"";
						
						$value[4] = str_replace("-","/",substr($value[4],0,19));
						$value[5] = str_replace("-","/",substr($value[5],0,19));
						$value[7] = str_replace("-","/",substr($value[7],0,19));
						
						$group = sizeof($uGroup)>=1?implode("/",$uGroup):$uGroup;
						@array_push($value, $group);
						
						$strSqlParams = "select tag, value from sv_userparam where userid ='".$value["userid"]."'";
						$Params= $this->objDb->ExecuteArrayCommand($strSqlParams);
						
						$strParmas = "" ;
						$paramsk=0;
						if(is_array($Params)){
							$paramsk= count($Params);
						}	
						for( $k = 0;$k <$paramsk;$k++ )
						{
							if($k!=0)
							{
								$strParams.="/";
							}
							//$strParams.=$Params[$k]['tag'].'="'.$Params[$k]['value'].'"';
							$strParams.=$Params[$k]['tag']."='".$Params[$k]['value']."'";
						}
						@array_push($value, $strParams);
						$strParams = '';
						
						//liyang
						$strSqlTerm = "select ticket, counter from sv_termreg where userid ='".$value["userid"]."'";
						$Term= $this->objDb->ExecuteArrayCommand($strSqlTerm);
						
						$strTerm = "" ;
						$strTerm1 = '';
						$termk=0;
						if(is_array($Term)){
							$termk=count($Term);
						}
						for( $k = 0;$k < $termk;$k++ )
						{
							if($k!=0)
							{
								$strTerm="/";
								$strTerm1="/";
							}
						$strTerm=$Term[$k]['ticket'];
						$strTerm1=$Term[$k]['counter'];
						}
						@array_push($value, $strTerm);
						@array_push($value, $strTerm1);
						$strTerm = '';
						$strTerm1 = '';
						
						for($j=0;$j<($iNewTitleCount+20);$j++)
							$arrNewValue[$j]=@mb_convert_encoding($value[$j],"sjis-win","UTF-8");

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
				// 「Download」画面が表示する.
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
