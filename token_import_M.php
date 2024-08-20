<?php
/**
 * epass1000NDのUSBトークン管理属性一覧.
 *
 * @author 文傑.
 * @since  2007-09-17
 * @version 1.0
 */
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";

class Token_import_M{

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
	try
	{
		$this->objDb = new DB_Pgsql();
		ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例化");
	}
	catch( Exception $e )
	{
		ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
		throw $e;
	}
}	

/**
* 関数名: Token_import_M
* データベースのオブジェクトを初期化する.
*/
function Token_import__M()
{
	try
	{
		$this->__construct();
	}
	catch( Exception $e )
	{
		ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
		throw $e;
	}
}

/**
* 関数名: importTokenCSV
* CSVファイルをデータベースにインポートする.
* 
* @param		string		$condition		インポートの条件.
* @return		$rescount.
*/
function importTokenCSV($condition)
{		
	try{
		$LicenseFlag = false;
				
		$pconn = @fopen(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."TokenImportCSV.csv",'r');
		
		// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
		if( !$pconn ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
		}
		
		// CSVファイルのタイトルを小文字で取得する.
		$fristline = sjis2utf8(fgetcsv($pconn,8192),true);
		
		//hid列はCSVファイルに存在するかをチェックする.
		
		if (@in_array("hid",$fristline))
		{
			if(@in_array("secret",$fristline))
			{
				
				//配列を反転して返す.
				$newcontent=array_flip($fristline );
				
				//token表からタイトルを取得する.
				$res =$this->objDb->metadata("sv_token");
				
				//ループで新しい配列の要素を取得する.
				for($i = 0; $i<count($res);$i++ )
				{
					$arrResCol[$i] = $res[$i]["name"];
				}
				
				$newres = array_intersect($fristline,$arrResCol);
				
				//読み込んだ行数
				$TotalReadCount = 0;
				//Token表にINSERTした行数
				$InsertCount = 0;
				//Token表にUPDATEした行数
				$UpdateCount = 0;
				//スキップした行数
				$SkipCount = 0;
				//ループで CSVファイルのデータを取得する.
				while (!feof($pconn))
				{
					$content = fgets($pconn, 8192);
					if(trim($content) =="") continue;
					$content = sjis2utf8($content);
					$content = csv2arr($content);
					
					try
					{
						//ユーザidを存在するかを判定する.
						$strQuery = " SELECT count(*) from sv_token ";
						
						$strQuery .= " Where hid='".$content[$newcontent["hid"]]."'";
						
						$existCount = $this->objDb->ExecuteCommand($strQuery);
						//読み込んだ行数
						$TotalReadCount++;
						//ユーザが既に存在しない.
						if ($existCount[0] == 0)
						{
							//Phase2 added by wenjie Begin
							// Lisence check
							if($LicenseFlag)
							{
								$SkipCount++;
								continue;
							}
							
							if( $this->CountHID() >= SESSION::GET("SESS_LIC_TOKENLIMIT"))
							{     
								$LicenseFlag = true;
								$SkipCount++;
								continue;
							}
							//Phase2 added by wenjie End
							try
							{						
								//token表に各項目値をインサートする.
								$strSql6 = " INSERT INTO sv_token(";
								//Token表にINSERTした行数
								$InsertCount++;
								$i = 0;
								$names = "";
								$values = "";
								if(!is_array($newres)) $newres=array();
								foreach ($newres as $key => $colName)
								{
									if($i!=	0)
									{
										$names.= ",";
										$values .= ",";
									}
									$names .= $colName;
									
									if($colName == "access" && $content[$key]==""){
										$values .= "1";
									}else if($colName == "startdate" && $content[$key]==""){
										$values .= "'".date("Y-m-d H:i:s")."'";
									}else if($colName == "expire" && $content[$key]==""){
										$values .= "'2099/12/31 23:59:59'";
									}else if($colName == "userid"){
										$values .= "null";
									}else{
											$values .= "'".addslashes($content[$key])."'";
										}								
									$i++;							
								}
								
								$strSql6.=  $names .") VALUES( ".$values.")";
								
								$this->objDb->ExecuteNonQuery($strSql6);
							}
							catch( Exception $e ){
								$this->objDb->rollback();
								$InsertCount--;
							}
							
							
						}
						else if ($condition!="skip")
							{
								
								$userid =	$this->GetUserHID($content[$newcontent["hid"]]);
								// HIDはToken表に存在する場合、Token表のUserIDがNULLではない且つ③で取得した

								// UserIDと不一致する場合、該当行をスキップして、次の行を①から処理する

								if( $userid != null && $userid !="" && $userid != $content[$newcontent["userid"]] ){
									$SkipCount++;
									continue;
								}
								
								$access =	$this->GetCountAccessHID($content[$newcontent["userid"]],$content[$newcontent["hid"]]);
								// HIDはToken表に存在する且つToken表のUserIDがNULLではないかつ③で取得したAccess＝１の場合、

								// Token表の同じUserID違うHIDのAccess＝１の合計数は＞０個の場合は該当行をスキップして、次の
								// 行を①から処理する

								if( $userid != null && $userid !="" && (1 == $content[$newcontent["access"]]
											|| "" == $content[$newcontent["access"]]) && $access > 0 ){
									
									$SkipCount++;
									continue;
								}
								
								// Token表のUserIDがNULL
								if($userid == null&&  $access > 0 && (1 == $content[$newcontent["access"]]
												|| "" == $content[$newcontent["access"]]))
								{
										
										$SkipCount++;
										continue;
								}
								try
								{
									//ユーザが既に存在、かつskipしない、token表を更新する.
									$strSql7 = "UPDATE sv_token SET";
									
									//Token表にUPDATEした行数
									$UpdateCount++;	
									$i = 0;
									foreach ($newres as $key => $colName)
									{
										if($colName!="hid")
										{
											if($i!=0)
											{
												$strSql7.= ",";
											}
											
											if($colName == "access" && $content[$key]==""){
												$strSql7.= " ".$colName."=1";
											}else if($colName == "startdate" && $content[$key]==""){
												$strSql7.= " ".$colName."='".date("Y-m-d H:i:s")."'";
											}else if($colName == "expire" && $content[$key]==""){
												$strSql7.= " ".$colName."='2099/12/31 23:59:59'";
											}else if($colName == "userid" && $userid =="" /*&& $userid ==$content[$newcontent["userid"]]*/){
												$strSql7.=" ".$colName."=null";
											}else if($colName == "userid" && $userid !="" && $userid==$content[$newcontent["userid"]]){
												$strSql7.=" ".$colName."='".$content[$newcontent["userid"]]."'";
											}else if($colName == "userid" && $userid !="" && $userid !=$content[$newcontent["userid"]]){
												$strSql7.=" ".$colName."=null";
											}
											else{
													$strSql7.= " ".$colName."='".addslashes($content[$key])."'";
											}
											$i++;
										}
									}
									$strSql7.=" WHERE hid ='".$content[$newcontent["hid"]]."'";
									$this->objDb->ExecuteNonQuery($strSql7);
									
									
								}
								catch( Exception $e ){
									$UpdateCount--;
								}
								
							}
							else
							{
								//スキップした行数
								$SkipCount++;
							}
					}
					catch( Exception $e )
					{
					}
				}
			}
			else
			{
				return false;
			}
		}
	
		else{
				return false;
			}
		$result = @fclose($pconn);
		@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."TokenImportCSV.csv");
		$rescount[0]=$TotalReadCount;
		$rescount[1]=$InsertCount;
		$rescount[2]=$UpdateCount;
		$rescount[3]=$SkipCount;
		$rescount[4]=$LicenseFlag;
		return($rescount);
		
	}
	catch( Exception $e )
	{
		// エラーメッセージを表示する.
		ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
		exit;
	}
}

function CountHID()
{
	try
	{
		//HIDが存在するかをチェックする.
		$sql = "select count(*) from sv_token";
		$res = $this->objDb->ExecuteCommand($sql);
		return $res[0];	
	}
	catch( Exception $e )
	{
	}
}

function GetUserHID( $hid )
{
	try
	{
		//HIDが存在するかをチェックする.
		$sql = "select case when userid is null then '' else userid end from sv_token where hid='".$hid."'";
		$res = $this->objDb->ExecuteCommand($sql);
		return $res[0];	
	}
	catch( Exception $e )
	{
	}
}

function GetCountAccessHID( $userid, $hid)
{
	try
	{
			//HIDが存在するかをチェックする.
			$sql = "select count(id) from sv_token where userid = '".$userid."' and access=1";
			$sql.=" and hid!= '".$hid."'";
			$sql.=" and userid !=''";
			$res = $this->objDb->ExecuteCommand($sql);
			return $res[0];	
	}
	catch( Exception $e )
	{
	}
}
}

function sjis2utf8( $var, $first=false )
{
	if(is_array($var)){		
		foreach($var as $key => $value)
			if($first)
				$var[$key] =strtolower(@mb_convert_encoding($value,"UTF-8","sjis-win"));  //SJIS 
			else
				$var[$key] =@mb_convert_encoding($value,"UTF-8","sjis-win");
	}else{
		if($first)
			$var =strtolower(@mb_convert_encoding($var,"UTF-8","sjis-win"));
		else
			$var =@mb_convert_encoding($var,"UTF-8","sjis-win");
	}
	return $var;
}

function csv2arr( $string )
{
	$arrBurrer = preg_split('/(,)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
	$rezult = array();
	$tflag = false;
	$tempstr = "";
	for($m=0;$m<count($arrBurrer);$m++)
	{
		$arrBurrer[$m] = trim($arrBurrer[$m]);
		$beginc = mb_substr ( $arrBurrer[$m], 0, 1, "UTF-8");
		$lastc = mb_substr ( $arrBurrer[$m], (mb_strlen ( $arrBurrer[$m] ,"UTF-8")-1), 1, "UTF-8");
		
		if(!$tflag && $lastc ==  '"' && $beginc == '"' )
		{
			$arrBurrer[$m] = preg_replace('/(^")|("$)/', "", $arrBurrer[$m]);
			array_push($rezult, $arrBurrer[$m]);
			continue;
		}
				
		if(!$tflag && $lastc !=  '"' && $beginc != '"' && $arrBurrer[$m] != "," )
		{
			array_push($rezult, $arrBurrer[$m]);
			continue;
		}
		
		if(!$tflag && $arrBurrer[$m] != "," && $beginc == '"' )
		{
			$tflag = true;
			$tempstr = mb_substr($arrBurrer[$m], 1, (mb_strlen ( $arrBurrer[$m] ,"UTF-8")-1), "UTF-8");
			continue;
		}
				
		if($tflag && $lastc != '"')
		{
			$tempstr .= $arrBurrer[$m];
			continue;
		}
		
		if($tflag && $lastc == '"')
		{
			array_push($rezult, $tempstr.mb_substr($arrBurrer[$m], 0, (mb_strlen ( $arrBurrer[$m] ,"UTF-8")-1), "UTF-8"));
			$tempstr = "";
			$tflag = false;
			continue;
		}
	}
	
	if($tflag && $tempstr !="")
	{
		$tempstr = preg_replace('/(^")|("$)/', "", $tempstr);
		array_push($rezult, $tempstr);
	}
	return $rezult;
}

?>
