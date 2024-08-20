<?php
/**
 * epass1000NDのユーザー一覧ファイル.
 *
 * @author	呉暁挙.
 * @since	2007-09-03
 * @version	1.0
 */
require_once "../lib/log.php";
require_once "../lib/db.php";
require_once "../lib/pageview.php";


class User_list_M{

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
	* 関数名: readTableNew
	*ユーザー属性項目を取得する.
	* 
	*
	* @return		Array		$res		リザルト1から、リザルト2に存在する項目を取得し、返す.
	*/
	function readTableNew(){
		try{
			$res =array();
			
			$strSql  = " SELECT name,";
			$strSql .= " nameuser ";
			$strSql .= " FROM sv_customtmpl";
			$strSql .= " WHERE visibleuser=true";
			$strSql .= " AND buser=true";
			
			$res1 = $this->objDb->ExecuteArrayCommand($strSql);
			$res2 = $this->objDb->metadata("sv_user");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
			
			// リザルトの項目の数を判定する.
			if(count($res1)==0) return "";
			
			// 指定した配列に 関してループ処理を行う.
			foreach($res1 as $key => $value){
				foreach($res2 as $key2 => $value2){
					if( $value["name"] == $value2["name"]){
						$res[$key]["name"] =$value["name"];
						$res[$key]["nameuser"] =$value["nameuser"];
					}
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
			$pageObj = new PageView("sv_user",$pageNum,$offset ,$this->objDb);
			$pageObj->SetCondition($condition,$orderBy);
			$res = $pageObj->ReadList();
			$resList = $pageObj->MakePage();
			
			$cssFlag = true;
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");
			
			// 配列かどうかを判定する.
			if(!is_array($res)) return "";
			foreach( $res as $key => $value){
				
				// ユーザのグループのリザルトを返す.
				$strSql  = " SELECT sv_group.name ";
				$strSql .= " FROM sv_group,sv_usergroup";
				$strSql .= " WHERE sv_usergroup.grpid=sv_group.id";
				$strSql .= " AND sv_usergroup.userid = '".$value["userid"]."'";			
				$uGroup = $this->objDb->ExecuteArrayCommand($strSql);
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
				
				// ユーザの有効なtoken hidを取得する.
				$strSql2  = " SELECT sv_token.hid ";
				$strSql2 .= " FROM sv_token ";
				$strSql2 .= " WHERE sv_token.id IN (".$value["token1id"].",".$value["token2id"].",".$value["token3id"].")";
				$strSql2 .= " AND sv_token.access = 1";
				$TokenHid = $this->objDb->ExecuteArrayCommand($strSql2);
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2");
				
				//配列要素を文字列により連結し、新しいリザルトを返す.
				$newRes[$key] = $value;
				$newRes[$key]["accessgroup"] = sizeof($uGroup)>1?implode(",",$uGroup):$uGroup;
				$newRes[$key]["hid"] = $TokenHid;
				
				// cssの仕様を設定する.
				if( $cssFlag ) 
					$newRes[$key]["css_class"] = "evenrowbg";
				else 
					$newRes[$key]["css_class"] = "oddrowbg";
				
				$cssFlag = $cssFlag==true?false:true;
			}
			return array($resList,$newRes);
			
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
			$strSql .= " OR     sv_password.id =sv_user.rpwdid ";
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
			
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql3");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql4");
			
			$this->objDb->ExecuteNonQuery($strSql);
			$this->objDb->ExecuteNonQuery($strSql2);
			$this->objDb->ExecuteNonQuery($strSql3);
			$this->objDb->ExecuteNonQuery($strSql4);
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
			$strSql  = " UPDATE sv_user ";
			$strSql .= " SET access= access*(-1)+1 ";
			$strSql .= " WHERE userid ='".$UserID."'";
			
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");			
			$this->objDb->ExecuteNonQuery($strSql);			
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
			// 指定した配列に 関してループ処理を行って、CSVファイルのタイトルを追加する.
			for($i=0;$i<count($arrNewTitle);$i++)
			{
				$strTitleSql .= " ,".$arrNewTitle[$i]["name"]."";
			}
			
			$strSql  = " SELECT userid,";
			$strSql .= " access, ";
			$strSql .= " startdate, ";
			$strSql .= " expire,memo, ";
			$strSql .= " lastlogindate, ";
			$strSql .= " token1id AS token1, ";
			$strSql .= " token2id AS token2, ";
			$strSql .= " token3id AS token3 ";
			$strSql .= $strTitleSql;
			$strSql .= " FROM sv_user ";
			$strSql .= $condition.$orderBy ;
			$res = $this->objDb->ExecuteArrayCommand($strSql);
			
			$pconn = @fopen(G_DOC_TMP."UserCSV.csv",'w+');
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$pconn ){
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}
			
			// ファイルのタイトル.
			$strTitleSql = "userid,access,startdate,exipre,memo,lastlogindate,token1,token2,token3".$strTitleSql.",accessgroup";
			$arrTitle = explode(",",$strTitleSql);
								
			@fputcsv($pconn,$arrTitle);
			
			// DEBUG メッセージ
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");
			
			$res = $res==""?array():$res;
			// 指定した配列に 関してループ処理を行う. 
			foreach( $res as $key => $value){
				
				// ユーザのグループのリザルトを返す.
				$strSql  = " SELECT sv_group.name ";
				$strSql .= " FROM sv_group,sv_usergroup";
				$strSql .= " WHERE sv_usergroup.grpid=sv_group.id";
				$strSql .= " AND sv_usergroup.userid = '".$value["userid"]."'";			
				$uGroup = $this->objDb->ExecuteArrayCommand($strSql);
				
				// ユーザの有効なtoken hidを取得する.
				$strSql2  = " SELECT sv_token.hid ";
				$strSql2 .= " FROM sv_token ";
				$strSql2 .= " WHERE sv_token.id IN ('".$value["token1"]."','".$value["token2"]."','".$value["token3"]."')";
				$TokenHid = $this->objDb->ExecuteArrayCommand($strSql2);
				
				// 新しいリザルトを作成し、返す.
				$value["accessgroup"] = sizeof($uGroup)>1?implode("/",$uGroup):$uGroup;
				$value["token1"] = $TokenHid[0];
				$value["token2"] = $TokenHid[1];
				$value["token3"] = $TokenHid[2];
				
				for($j=0;$j<count($value);$j++)
					$arrNewValue[$j]=$value[$j];
				
				//print_r($value);exit;
				@fputcsv($pconn,$arrNewValue);
			}
			$result = @fclose($pconn);
			if( !$result ){
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR202);
			}
			else{
				// 「Download」画面が表示する.
				header('Content-type: application/csv');
				header('Content-Disposition: attachment; filename="UserCSV.csv"');
				readfile(G_DOC_TMP."UserCSV.csv");
				@unlink(G_DOC_TMP."UserCSV.csv");
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