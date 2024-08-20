<?php
/**
 * epass1000NDのUSBトークン一覧ファイル.
 *
 * @author 文傑.
 * @since  2007-09-03
 * @version 1.0
 */
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."lib/pageview.php";


class Token_list_M{

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
	* 関数名: Token_list_M
	* コンストラクタ.
	*
	*/
	function Token_list_M(){
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
	* 関数名: readTableNew
	* 属性項目を取得する..
	*
	*
	* @return    Array       $res      配列.
	*/
	function readTableNew(){
		try{
			$strSql  = " SELECT name,";
			$strSql .= " nametoken ";
			$strSql .= " FROM sv_customtmpl";
			$strSql .= " WHERE visibletoken=true";
			$strSql .= " ORDER BY ordertoken ASC";
			$res1 = $this->objDb->ExecuteArrayCommand($strSql);
			$res2 = $this->objDb->metadata("sv_token");
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");

			//データがない場合は.
			
			if(is_array($res1)==false){
				return "";
			}else if(count($res1)==0){
				return "";
			}
			 
			// ループでリストを表示する.
			foreach($res1 as $key => $value){
				foreach($res2 as $key2 => $value2){
					//動的の列を取得する.
					if( $value["name"] == $value2["name"]){
						$res[$key]["name"] =$value["name"];
						$res[$key]["nametoken"] =$value["nametoken"];
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
	* 関数名: readTableOld
	* 属性項目を取得する..
	*
	*
	* @return    Array       $res      配列.
	*/
	function readTableOld(){
		try{
			$strSql  = " SELECT name";
			$strSql .= " FROM sv_customtmpl";
			$strSql .= " WHERE visibletoken=false";
			$strSql .= " ORDER BY name ASC";
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
	* 条件によってデータを検索して、データのリストを作成する.
	*
	* @param       string    $orderBy        ソーティングする条件.
	* @param       string    $condition      検索の条件.
	* @param       string    $offset         オフセット.
	* @param       string    $pageNum        ページの件数.
	*
	* @return      Array     $re             データのリスト.
	*/
	function readTableList($orderBy,$condition,$offset,$pageNum){
		try{
			// オフセットを設定する.
			$pageObj = new PageView("sv_token",$pageNum,$offset,$this->objDb);
			$pageObj->SetCondition($condition,$orderBy);
			$res = $pageObj->ReadList();
			$resList = $pageObj->MakePage();
			$resTotal = $pageObj->Total;
			$resNew = $this->readTableNew();
			
			$cssFlag = true;
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");

			// 配列かどうかを判定する.
			if(!is_array($res)) return array($resList,$res,intval($resTotal));
			foreach( $res as $key => $value){

				//配列要素を文字列により連結し、新しいリザルトを返す.
				$value["startdate"] = str_replace("-","/",substr($value["startdate"],0,10));
				$value["expire"] = str_replace("-","/",substr($value["expire"],0,10));
				$value["lastlogindate"] = str_replace("-","/",substr($value["lastlogindate"],0,10));
				
				if(!is_array($resNew)) $resNew = array();
				$arrAttris = array();
				foreach($resNew as $key2 => $value2 ){
						$arrAttris[$value2["name"]] = $value[$value2["name"]];
				}

				$newRes[$key] = &$value;
				$newRes[$key]["attris"] = $arrAttris;
				unset($value);
				// cssの仕様を設定する.
				if( $cssFlag )
					$newRes[$key]["css_class"] = "evenrowbg";
				else
					$newRes[$key]["css_class"] = "oddrowbg";

				ksort($newRes[$key]);
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
	* 関数名: deleteToken
	* 指定したトークンを削除する.
	*
	* @param       string    $TokenHID        トークンID.
	*
	* @return      void.
	*/
	function deleteToken( $TokenHID ){
		try{
			$this->objDb->begin();
			$strSql  = " DELETE ";
			$strSql .= " FROM sv_token";
			$strSql .= " WHERE HID ='".$TokenHID."'";

			$strSql2  = " UPDATE sv_user ";
			$strSql2 .= " SET token1id=null ";
			$strSql2 .= " WHERE UserID ='".$TokenHID."'";

			$strSql3  = " UPDATE sv_user ";
			$strSql3 .= " SET token2id=null ";
			$strSql3 .= " WHERE UserID ='".$TokenHID."'";

			$strSql4  = " UPDATE sv_user ";
			$strSql4 .= " SET token3id=null ";
			$strSql4 .= " WHERE UserID ='".$TokenHID."'";

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
	* 状態を更新する.
	*
	* @param       string    $TokenHID        トークンID.
	*
	* @return      void.
	*/
	function setState( $TokenHID ){
		try{
			
			$strselect ="SELECT count(id) as num,userid FROM sv_token ";
			$strselect.=" WHERE access = 1";
			$strselect.=" AND userid in (select userid from sv_token where hid ='".$TokenHID ."')";
			$strselect.=" AND userid !=''";
			$strselect.=" AND userid is not null";
			$strselect.=" Group by userid";
			$res = $this->objDb->ExecuteArrayCommand($strselect);
			$strSql  = " Select access ";
			$strSql .= " FROM sv_token ";
			$strSql .= " WHERE hid ='".$TokenHID."'";
			$access = $this->objDb->ExecuteCommand($strSql);
			if($res[0]["num"]> 1)
			{
				return $res[0]["userid"];
			}
			elseif($res[0]["num"] == 1 && $access[0] == 0 )
			{
				return $res[0]["userid"];
			}
			else
			{
			
				if($access[0]==1)
				{
					$TokenAccess=0;
				}
				else
				{
					$TokenAccess=1;
					
				}
				$strSql2  = " UPDATE sv_token ";
				$strSql2 .= " SET access= '".$TokenAccess."', ";
				$strSql2 .= " lastupdate = CURRENT_TIMESTAMP";
				$strSql2 .= " WHERE hid ='".$TokenHID."'";
				
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
				$this->objDb->ExecuteNonQuery($strSql2);
				
			}
			return true;
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}


	/**
	* 関数名: exportTokenCSV
	* 入力した条件でデータを検索してかつCSVファイルを作成して、そしてダウンロードする.
	*
	* @param       string    $orderBy        ソーティングする条件.
	* @param       string    $condition      検索の条件.
	*
	* @return      void.
	*/
	function exportTokenCSV($orderBy,$condition){
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
			
			$strSql  = " SELECT hid,typeid,secret,bchangepin,access,startdate,expire".$strTitleSql.",userid,memo ";
			$strSql .= " FROM sv_token ";
			$strSql .= $condition.$orderBy ;

			//$res = $this->objDb->ExecuteArrayCommand($strSql);
			
			$fileName = G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."TokenCSV.csv";
			$fileName2 = SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."TokenCSV.csv";
			$pconn = @fopen($fileName,'w+');
			
			// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
			if( !$pconn ){
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
			}

			// ファイルのタイトル.
			$strTitleSql = "hid,typeid,secret,bchangepin,access,startdate,expire".$strTitleSql.",userid,memo";
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
						for($j=0;$j<($iNewTitleCount+9);$j++)
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
