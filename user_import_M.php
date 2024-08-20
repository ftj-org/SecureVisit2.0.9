<?php
/**
 * epass1000NDの属性一覧ファイル.
 *
 * @author 呉艶秋.
 * @since  2007-09-06
 * @version 1.0
 */
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."lib/page.php";

class User_import_M{
	
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
	* 関数名: User_import_M
	* データベースのオブジェクトを初期化する.
	*/
	function User_import__M()
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
	* 関数名: countData
	* データベースのオブジェクトを初期化する.
	*/
	function countData($token1,$token2,$token3)
	{
		try
		{
			$strSql  = " SELECT count(id) as num";
			$strSql  .= " FROM sv_token";
			$strSql  .= " WHERE hid in ('".$token1."','".$token2."','".$token3."')";
			$strSql  .= " AND access = 1";
			
			$res = $this->objDb->ExecuteCommand($strSql);
			
			return $res;
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: User_import_M
	* データベースのオブジェクトを初期化する.
	*/
	function GetUserId( $token )
	{
		try
		{
			$strSql  = " SELECT userid ";
			$strSql  .= " FROM sv_token";
			$strSql  .= " WHERE hid ='".$token."'";
			$strSql  .= " AND userid !=''";
			$strSql  .= " AND userid IS NOT NULL";
			$res = $this->objDb->ExecuteCommand($strSql);
			
			return $res;
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: importUserCSV
	* CSVファイルをデータベースにインポートする.
	* 
	* @param		string		$condition		インポートの条件.
	* @return		$rescount.
	*/
	function importUserCSV($condition)
	{
		
	try{
		
		$pwdflag=false;
			
		$rpwdflag=false;
		
		$LicenseFlag = false;
		
		$pconn = @fopen(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."UserImportCSV.csv",'r');
		
		// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
		if( !$pconn ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
		}
		
		// CSVファイルのタイトルを小文字で取得する.
		$fristline = sjis2utf8(fgetcsv($pconn,4096),true);
		
		//userid列はCSVファイルに存在するかをチェックする.
		
		if (@in_array("userid",$fristline)){
			$strResmsg=true;
			if(@in_array("secret",$fristline)) 
			{ 
				return false;
			}
			else
			{
				if(@in_array("password",$fristline)){
					$pwdflag=true;
				}
				if(@in_array("rescuepassword",$fristline)){
					$rpwdflag=true;
				}
				if(@in_array("ticket",$fristline)&&@in_array("counter",$fristline)){
					$termflag=true;
				}	
			}
		
			//配列を反転して返す.
			$newcontent=array_flip($fristline);
			
			//user表からタイトルを取得する.
			$res =$this->objDb->metadata("sv_user");
			
			//ループで新しい配列の要素を取得する.
			for($i = 0; $i<count($res);$i++ )
			{
				$arrResCol[$i] = $res[$i]["name"];
			}
			
			$newres=array_intersect($fristline,$arrResCol);
			
			//読み込んだ行数
			$TotalReadCount = 0;
			//User表にINSERTした行数
			$InsertCount = 0;
			//User表にUPDATEした行数
			$UpdateCount = 0;
			//スキップした行数
			$SkipCount = 0;
			//ループで CSVファイルのデータを取得する.
			while (!feof($pconn))
			{
				$this->objDb->begin();
				$content = fgets($pconn, 8192);
				$content = sjis2utf8($content);
				$content = csv2arr($content);

				if(trim($content[$newcontent["userid"]])!="")
				{	
					//読み込んだ行数
					$TotalReadCount++;
					// UserIDのいずれかが

					$resUserId = $this->GetUserId($content[$newcontent["token1"]]);
					if( $content[$newcontent["token1"]] != ""&& is_array($resUserId) && sizeof($resUserId) >= 1 && $resUserId["userid"] !=$content[$newcontent["userid"]] ){
						$SkipCount++;
						continue;
					}
					$resUserId = $this->GetUserId($content[$newcontent["token2"]]);
					if( $content[$newcontent["token2"]] != ""&& is_array($resUserId) && sizeof($resUserId) >= 1 && $resUserId["userid"] !=$content[$newcontent["userid"]] ){
						$SkipCount++;
						continue;
					}
					$resUserId = $this->GetUserId($content[$newcontent["token3"]]);
					if( $content[$newcontent["token3"]] != ""&& is_array($resUserId) && sizeof($resUserId) >= 1 && $resUserId["userid"] !=$content[$newcontent["userid"]] ){
						$SkipCount++;
						continue;
					}
					
					// Access＝１の合計数は＞１個の場合は該当行をスキップして、次の行を①から処理する

					$resCount = $this->countData($content[$newcontent["token1"]],$content[$newcontent["token2"]],$content[$newcontent["token3"]]);
					if($resCount["num"] > 1 ){
						$SkipCount++;
						continue;
					}
				
					//token表から、token1,token2,token3を取得する.
					$strSql1 = " SELECT id ";
					$strSql1.= " FROM sv_token ";
					$strSql1.= " WHERE hid = '".$content[$newcontent["token1"]]."'";
					
					$strSql2 = " SELECT id ";
					$strSql2.= " FROM sv_token ";
					$strSql2.= " WHERE hid = '".$content[$newcontent["token2"]]."'";
					
					$strSql3 = " SELECT id ";
					$strSql3.= " FROM sv_token ";
					$strSql3.= " WHERE hid = '".$content[$newcontent["token3"]]."'";
					
					//文字列を文字列により分割する.
					$arrGroupNames = explode("/", $content[$newcontent["accessgroup"]]);
					//ループでnameを取得する.
					$strSqlGroupName = "(";
					for($i = 0; $i<count($arrGroupNames);$i++ )
					{
						if ($i != 0)
						{
							$strSqlGroupName .= ",";
						}
						$strSqlGroupName .= "'" . $arrGroupNames[$i] . "'";
						
					}
					$strSqlGroupName .= ")";
					
					//groupのidを取得する.
					$strSql4 = " SELECT id ";
					$strSql4.= " FROM sv_group ";
					$strSql4.= " WHERE name in ".$strSqlGroupName;
					
					//sqlを行う.
					$token1id = $this->objDb->ExecuteCommand($strSql1);
					
					$token2id = $this->objDb->ExecuteCommand($strSql2);
					
					$token3id = $this->objDb->ExecuteCommand($strSql3);
					
					$groupid = $this->objDb->ExecuteArrayCommand($strSql4);
					$arrComParams = array();
					if($content[$newcontent["params"]]!='')
					{
						$arrComParams = explode("/", $content[$newcontent["params"]]);
					}	
					
					try
					{
						$sqlmax  = " SELECT case when MAX(id)+1 ISNULL then 0 else MAX(id)+1 end as num";
						
						$sqlmax .= " FROM sv_password";
						
						$maxPwdId = $this->objDb->ExecuteCommand($sqlmax);
						//ユーザidを存在するかを判定する.
						$strQuery = " SELECT count(*) from sv_user ";
						
						$strQuery .= " Where userid='".$content[$newcontent["userid"]]."'";
						
						$existCount = $this->objDb->ExecuteCommand($strQuery);
						
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

							$strSqlCount  = " SELECT count(*) from sv_user";
							$strUserCount = $this->objDb->ExecuteCommand($strSqlCount);
							$strUserLimit = SESSION::GET("SESS_LIC_USERLIMIT");
							
							if($strUserCount[0] >= $strUserLimit)
							{
								$LicenseFlag = true;
								$SkipCount++;
								continue;
							}
								//Phase2 added by wenjie End
									
							try{
								//user表に各項目値をインサートする.
								$strSql6 = " INSERT INTO sv_user(";
								//User表にINSERTした行数
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
											}else if($colName == "lastlogindate" && $content[$key]==""){
													$values .= "null";
											}else{
											$values .= "'".addslashes($content[$key])."'";
										}						
										$i++;
								
								}
								
								$strSql6.=  $names .") VALUES( ".$values.")";
								
								
								$this->objDb->ExecuteNonQuery($strSql6);
								
								//term列liyang	
								if($termflag == true&&$content[$newcontent["ticket"]]!=null)
								{
								
									$sqlpterm =" INSERT INTO sv_termreg(";
									$sqlpterm.= " userid,";
									$sqlpterm.= " ticket,";
									$sqlpterm.= " counter)";
									$sqlpterm.= " VALUES(";
									$sqlpterm.= " '".$content[$newcontent["userid"]]."',";
									$sqlpterm.= " '".$content[$newcontent["ticket"]]."',";
									$sqlpterm.= " '".$content[$newcontent["counter"]]."')";
									$this->objDb->ExecuteNonQuery($sqlpterm);
								
									$sqlpterm1 ="UPDATE sv_termreg SET";
									$sqlpterm1.=" ticket = '".$content[$newcontent["ticket"]]."',counter = '".$content[$newcontent["counter"]]."'";
									$sqlpterm1.=" Where userid='".$content[$newcontent["userid"]]."'";
									$this->objDb->ExecuteNonQuery($sqlpterm1);									
								}
								
								//password列が存在する場合は	
								if($pwdflag == true)
								{
									$sqlpwd =" INSERT INTO sv_password(";
									$sqlpwd.= " id,";
									$sqlpwd.= " pwd,";
									$sqlpwd.= " access,";
									$sqlpwd.= " startdate,";
									$sqlpwd.= " expire,";
									$sqlpwd.= " counter)";
									$sqlpwd.= " VALUES(";
									$sqlpwd.= " ".$maxPwdId[0].",";
									$sqlpwd.= " '".$content[$newcontent["password"]]."',";
									$sqlpwd .= " 1,";
									$sqlpwd.= " '".date("Y/m/d H:i:s")."',";
									$sqlpwd.= " '2099/12/31 23:59:59',";
									$sqlpwd.= " -1)"; 
									
									$this->objDb->ExecuteNonQuery($sqlpwd);
									
									$sqluserpwd ="UPDATE sv_user SET";
									$sqluserpwd.=" pwdid = ".$maxPwdId[0]."";
									$sqluserpwd.=	" Where userid='".$content[$newcontent["userid"]]."'";
									$this->objDb->ExecuteNonQuery($sqluserpwd);
								
								}
								else{
									$sqlpwd =" INSERT INTO sv_password(";
									$sqlpwd.= " id,";
									$sqlpwd.= " pwd,";
									$sqlpwd.= " access,";
									$sqlpwd.= " startdate,";
									$sqlpwd.= " expire,";
									$sqlpwd.= " counter)";
									$sqlpwd.= " VALUES(";
									$sqlpwd.= " ".$maxPwdId[0].",";
									$sqlpwd.= " null,";
									$sqlpwd .= " 1,";
									$sqlpwd.= " '".date("Y/m/d H:i:s")."',";
									$sqlpwd.= " '2099/12/31 23:59:59',";
									$sqlpwd.= " -1)"; 
									
									$this->objDb->ExecuteNonQuery($sqlpwd);
									
									$sqluserpwd ="UPDATE sv_user SET";
									$sqluserpwd.=" pwdid = ".$maxPwdId[0]."";
									$sqluserpwd.=	" Where userid='".$content[$newcontent["userid"]]."'";
								
									$this->objDb->ExecuteNonQuery($sqluserpwd);
								}
								//rescuepassword列が存在する場合は
								if($rpwdflag == true && preg_match("/^[A-Za-z\d]{8,32}$/i",$content[$newcontent["rescuepassword"]])==true)
								{
									$sqlrpwd =" INSERT INTO sv_password(";
									$sqlrpwd.= " id,";
									$sqlrpwd.= " pwd,";
									$sqlrpwd.= " access,";
									$sqlrpwd.= " startdate,";
									$sqlrpwd.= " expire,";
									$sqlrpwd.= " counter)";
									$sqlrpwd.= " VALUES(";
									$sqlrpwd.= " ".($maxPwdId[0]+1).",";
									$sqlrpwd.= " '".$content[$newcontent["rescuepassword"]]."',";
									$sqlrpwd .= " 1,";
									if(PAGE::CheckData("「レスキューパスワードの利用開始」",substr($content[$newcontent["rstartdate"]],0,10).substr($content[$newcontent["rstartdate"]],11,9),PAGE::CHECKDATE_DT,1,18,"")!="")
											
									{
										$sqlrpwd.= " '".date("Y/m/d H:i:s")."',";
									}
									else
									{
										$sqlrpwd.= " '".$content[$newcontent["rstartdate"]]."',";
									}
									if(PAGE::CheckData("「レスキューパスワードの有効期限」",substr($content[$newcontent["rexpire"]],0,10).substr($content[$newcontent["rexpire"]],11,9),PAGE::CHECKDATE_DT,1,18,"")!="")	
									{
										$sqlrpwd.= " '".date('Ymd',strtotime("+1 day"))." ".date("H:i:s")."',";
									}
									else
									{
										$sqlrpwd.= " '".$content[$newcontent["rexpire"]]."', ";
									}
									if(chkTimes($content[$newcontent["rescuetimes"]])==true)
									{
										$sqlrpwd.= $content[$newcontent["rescuetimes"]].")"; 
									}
									else
									{
										$sqlrpwd.= " 20)"; 
									}
									
									
									$this->objDb->ExecuteNonQuery($sqlrpwd);
									
									$sqluserrpwd ="UPDATE sv_user SET";
									$sqluserrpwd.=" rpwdid = ".($maxPwdId[0]+1)."";
									$sqluserrpwd.=" Where userid='".$content[$newcontent["userid"]]."'";
									
									$this->objDb->ExecuteNonQuery($sqluserrpwd);
								
								}
								else{
									$sqlrpwd =" INSERT INTO sv_password(";
									$sqlrpwd.= " id,";
									$sqlrpwd.= " pwd,";
									$sqlrpwd.= " access,";
									$sqlrpwd.= " startdate,";
									$sqlrpwd.= " expire,";
									$sqlrpwd.= " counter)";
									$sqlrpwd.= " VALUES(";
									$sqlrpwd.= " ".($maxPwdId[0]+1).",";
									$sqlrpwd.= " null,";
									$sqlrpwd .= " 0,";
									$sqlrpwd.= " '".date("Y/m/d H:i:s")."',";
									$sqlrpwd.= " '".date('Ymd',strtotime("+1 day"))." ".date("H:i:s")."',";
									$sqlrpwd.= " 20)"; 
									
									$this->objDb->ExecuteNonQuery($sqlrpwd);
									
									$sqluserrpwd ="UPDATE sv_user SET";
									$sqluserrpwd.=" rpwdid = ".($maxPwdId[0]+1)."";
									$sqluserrpwd.=" Where userid='".$content[$newcontent["userid"]]."'";
									$this->objDb->ExecuteNonQuery($sqluserrpwd);
								}
									
								//token1idが存在する場合は
								if($token1id [0]!="")
								{
									$sqltoken1id ="UPDATE sv_user SET";
									$sqltoken1id.=" token1id=".$token1id[0]."";
									$sqltoken1id.=" Where userid='".$content[$newcontent["userid"]]."'";
									
									$this->objDb->ExecuteNonQuery($sqltoken1id);
									
									$sqlupdatetoken1id ="UPDATE sv_token SET";
									$sqlupdatetoken1id.=" userid='".$content[$newcontent["userid"]]."'";
									$sqlupdatetoken1id.=" Where hid = '".$content[$newcontent["token1"]]."'";
									$this->objDb->ExecuteNonQuery($sqlupdatetoken1id);
								
								}
								//token2idが存在する場合は
								if($token2id[0]!="")
								{
									$sqltoken2id ="UPDATE sv_user SET";
									$sqltoken2id.=" token2id=".$token2id[0]."";
									$sqltoken2id.=" Where userid='".$content[$newcontent["userid"]]."'";
									$this->objDb->ExecuteNonQuery($sqltoken2id);
									
									
									$sqlupdatetoken2id ="UPDATE sv_token SET";
									$sqlupdatetoken2id.=" userid='".$content[$newcontent["userid"]]."'";
									$sqlupdatetoken2id.=" Where hid = '".$content[$newcontent["token2"]]."'";
									$this->objDb->ExecuteNonQuery($sqlupdatetoken2id);
								
								}
								//token3idが存在する場合は
								if($token3id[0]!="")
								{
									$sqltoken3id ="UPDATE sv_user SET";
									$sqltoken3id.=" token3id=".$token3id[0]."";
									$sqltoken3id.=" Where userid='".$content[$newcontent["userid"]]."'";
									
									$this->objDb->ExecuteNonQuery($sqltoken3id);
									
									$sqlupdatetoken3id ="UPDATE sv_token SET";
									$sqlupdatetoken3id.=" userid='".$content[$newcontent["userid"]]."'";
									$sqlupdatetoken3id.=" Where hid = '".$content[$newcontent["token3"]]."'";
									
									$this->objDb->ExecuteNonQuery($sqlupdatetoken3id);
								
								
								}
								//usergroup表に各項目値をインサートする.
								if(!is_array($groupid)) $groupid=array();
								foreach ($groupid as $key => $value)
								{
									$strQuery = " SELECT count(*) from sv_usergroup ";
									$strQuery .= " Where userid='".$content[$newcontent["userid"]]."'";
									$strQuery .= " and grpid='".$value[0]."'";
									
									$existCount = $this->objDb->ExecuteCommand($strQuery);
									if ($existCount[0] == 0)
									{
										$strSql8= " INSERT INTO sv_usergroup(userid, grpid) ";
										$strSql8.= " VALUES('".$content[$newcontent["userid"]]."',".$value[0].")";
										
										$this->objDb->ExecuteNonQuery($strSql8);
								
									}
								}
								
								// author by wuyq 20090604 start	
								if($arrComParams!=null && count($arrComParams)!= 0)
								{
									$arrParamKeys=array();
									for($w=count($arrComParams)-1;$w>=0;$w--)
									{
										$arrParamOne = explode("=",$arrComParams[$w]);
										if(@in_array($arrParamOne[0],$arrParamKeys))
										{
											unset($arrComParams[$w]);
										}
										else
										{
											array_push($arrParamKeys,$arrParamOne[0]);
										}
										
									}
									sort($arrComParams);
									for( $z = 0;$z < count($arrComParams);$z++ )
									{
										$arrParams = explode("=",$arrComParams[$z]);
										$strSqlParam  = " INSERT INTO sv_userparam";
										$strSqlParam.= " (userid, ";
										$strSqlParam.= " tag, ";
										$strSqlParam.= " value) ";
										$strSqlParam .= " VALUES( ";
										$strSqlParam .= " '".$content[$newcontent["userid"]]."', ";
										$strSqlParam .= " '".$arrParams[0]."', ";
										$strSqlParam .= " '".substr(substr($arrParams[1],1),0,-1)."')";
										$this->objDb->ExecuteNonQuery($strSqlParam);
									}
								}
								// author by wuyq 20090604 end 
					
							}
							catch( Exception $e )
							{
								$this->objDb->rollback();
								$InsertCount--;
							}	
						
						}
						
						else if ($condition!="skip")
						{
							
							try{
								
							//ユーザが既に存在、かつskipしない、user表を更新する.
							$strSql7 = "UPDATE sv_user SET";
							//User表にUPDATEした行数.
							$UpdateCount++;
							$i = 0;
							if(!is_array($newres)) $newres=array();
							foreach ($newres as $key => $colName)
							{
								if($colName!="userid")
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
									}else if($colName == "lastlogindate" && $content[$key]==""){
										$strSql7.= " ".$colName."=null";
									}else{
										$strSql7.= " ".$colName."='".addslashes($content[$key])."'";
									}
									$i++;
								}
							}
							$strSql7.=" WHERE userid ='".$content[$newcontent["userid"]]."'";
							
							$this->objDb->ExecuteNonQuery($strSql7);
							
							//term列liyang	
							$sqlselectterm = "SELECT ticket from sv_termreg";
							$sqlselectterm.=" Where userid='".$content[$newcontent["userid"]]."'";
							$selectterm = $this->objDb->ExecuteCommand($sqlselectterm);							
							if($termflag == true&&$content[$newcontent["ticket"]]!=null)
							{	
								if($selectterm[0]=="")
								{	
									$sqlpterm =" INSERT INTO sv_termreg(";
									$sqlpterm.= " userid,";
									$sqlpterm.= " ticket,";
									$sqlpterm.= " counter)";
									$sqlpterm.= " VALUES(";
									$sqlpterm.= " '".$content[$newcontent["userid"]]."',";
									$sqlpterm.= " '".$content[$newcontent["ticket"]]."',";
									$sqlpterm.= " '".$content[$newcontent["counter"]]."')";
									$this->objDb->ExecuteNonQuery($sqlpterm);	
								}else{
								$sqlpterm1 ="UPDATE sv_termreg SET";
								$sqlpterm1.=" ticket = '".$content[$newcontent["ticket"]]."',counter = '".$content[$newcontent["counter"]]."'";
								$sqlpterm1.=" Where userid='".$content[$newcontent["userid"]]."'";
								$this->objDb->ExecuteNonQuery($sqlpterm1);	
								}
							}									
							
							//password列が存在する場合は
							
							$sqlselectpwd = "SELECT pwdid from sv_user";
							$sqlselectpwd.=" Where userid='".$content[$newcontent["userid"]]."'";
							$selectpwd = $this->objDb->ExecuteCommand($sqlselectpwd);
							
							if( $pwdflag == true)
							{
								if($selectpwd[0]=="")
								{
									$sqlpwd =" INSERT INTO sv_password(";
									$sqlpwd.= " id,";
									$sqlpwd.= " pwd,";
									$sqlpwd.= " access,";
									$sqlpwd.= " startdate,";
									$sqlpwd.= " expire,";
									$sqlpwd.= " counter)";
									$sqlpwd.= " VALUES(";
									$sqlpwd.= " ".$maxPwdId[0].",";
									$sqlpwd.= " '".$content[$newcontent["password"]]."',";
									$sqlpwd .= " 1,";
									$sqlpwd.= " '".date("Y/m/d H:i:s")."',";
									$sqlpwd.= " '2099/12/31 23:59:59',";
									$sqlpwd.= " -1)"; 
									
									$this->objDb->ExecuteNonQuery($sqlpwd);
									
									$sqluserpwd ="UPDATE sv_user SET";
									$sqluserpwd.=" pwdid = ".$maxPwdId[0]."";
									$sqluserpwd.=	" Where userid='".$content[$newcontent["userid"]]."'";
									$this->objDb->ExecuteNonQuery($sqluserpwd);
								}
								else
								{
									//passwordを更新する
									$sqluserpwd ="UPDATE sv_password SET";
									$sqluserpwd.=" pwd='".$content[$newcontent["password"]]."',";
									$sqluserpwd.=" access=1, ";
									$sqluserpwd.=" startdate= '".date("Y/m/d H:i:s")."',";
									$sqluserpwd.=" expire='2099/12/31 23:59:59',";
									$sqluserpwd.=" counter=-1";
									$sqluserpwd.=" Where id=".$selectpwd[0]."";
									
									$this->objDb->ExecuteNonQuery($sqluserpwd);
								}
							}
							//rescuepassword列が存在する場合は
							$sqlselectrpwd = "SELECT rpwdid from sv_user";
							$sqlselectrpwd.=" Where userid='".$content[$newcontent["userid"]]."'";
							$selectrpwd = $this->objDb->ExecuteCommand($sqlselectrpwd);
							if($rpwdflag == true  )
							{	
							    if($selectrpwd[0]=="")
								{
									$sqlrpwd =" INSERT INTO sv_password(";
									$sqlrpwd.= " id,";
									$sqlrpwd.= " pwd,";
									$sqlrpwd.= " access,";
									$sqlrpwd.= " startdate,";
									$sqlrpwd.= " expire,";
									$sqlrpwd.= " counter)";
									$sqlrpwd.= " VALUES(";
									$sqlrpwd.= " ".($maxPwdId[0]+1).",";
									if(preg_match("/^[A-Za-z\d]{8,32}$/i",$content[$newcontent["rescuepassword"]])==true)
									{
										$sqlrpwd.= " '".$content[$newcontent["rescuepassword"]]."',";
										$sqlrpwd .= " 1,";
									}
									else
									{
										$sqlrpwd.=" null,";
										$sqlrpwd .= " 0,";
									}
									$sqlrpwd.= " '".date("Y/m/d H:i:s")."',";
									$sqlrpwd.= " '".date('Ymd',strtotime("+1 day"))." ".date("H:i:s")."',";
									$sqlrpwd.= " 20)"; 
									
									$this->objDb->ExecuteNonQuery($sqlrpwd);
									
									$sqluserrpwd ="UPDATE sv_user SET";
									$sqluserrpwd.=" rpwdid = ".($maxPwdId[0]+1)."";
									$sqluserrpwd.=" Where userid='".$content[$newcontent["userid"]]."'";
									
									$this->objDb->ExecuteNonQuery($sqluserrpwd);
								}
								else
								{
									
									$sqluserpwd ="UPDATE sv_password SET";
									if(preg_match("/^[A-Za-z\d]{8,32}$/i",$content[$newcontent["rescuepassword"]])==true)
									{
										$sqluserpwd.=" pwd='".$content[$newcontent["rescuepassword"]]."',";
										$sqluserpwd.=" access=1,";
									}
									else
									{
										$sqluserpwd.=" pwd=null,";
										$sqluserpwd.=" access=0,";
									}
									if(PAGE::CheckData("「レスキューパスワードの利用開始」",substr($content[$newcontent["rstartdate"]],0,10).substr($content[$newcontent["rstartdate"]],11,9),PAGE::CHECKDATE_DT,1,18,"")!="")
									{
										$sqluserpwd.= " startdate= '".date("Y/m/d H:i:s")."',";
									}
									else
									{
										$sqluserpwd.= "startdate= '".$content[$newcontent["rstartdate"]]."',";
									}
									if(PAGE::CheckData("「レスキューパスワードの有効期限」",substr($content[$newcontent["rexpire"]],0,10).substr($content[$newcontent["rexpire"]],11,9),PAGE::CHECKDATE_DT,1,18,"")!="")	
									{
										$sqluserpwd.= " expire='".date('Ymd',strtotime("+1 day"))." ".date("H:i:s")."',";
									}
									else
									{
										$sqluserpwd.= " expire='".$content[$newcontent["rexpire"]]."', ";
									}
									if(chkTimes($content[$newcontent["rescuetimes"]])==true)
									{
										$sqluserpwd.= " counter=".$content[$newcontent["rescuetimes"]]; 
									}
									else
									{
										$sqluserpwd.=" counter=20";
									}
	
									$sqluserpwd.=	" Where id=".$selectrpwd[0]."";
										
									$this->objDb->ExecuteNonQuery($sqluserpwd);
								}
							
							}
							
							//token1idが存在する場合は
							if($token1id[0]!="")
							{
								$sqlreset="UPDATE sv_token SET userid = null";
								$sqlreset.=" Where sv_token.id = (SELECT token1id FROM sv_user Where";
								$sqlreset.=" userid = '".$content[$newcontent["userid"]]."')";
								
								$this->objDb->ExecuteNonQuery($sqlreset);
								
								$sqltoken1id ="UPDATE sv_user SET";
								$sqltoken1id.=" token1id=".$token1id[0]."";
								$sqltoken1id.=" Where userid='".$content[$newcontent["userid"]]."'";
								
								$this->objDb->ExecuteNonQuery($sqltoken1id);
								
							
								$sqlupdatetoken1id ="UPDATE sv_token SET";
								$sqlupdatetoken1id.=" userid='".$content[$newcontent["userid"]]."'";
								$sqlupdatetoken1id.=" Where hid = '".$content[$newcontent["token1"]]."'";

								$this->objDb->ExecuteNonQuery($sqlupdatetoken1id);
									
							}
							else
							{
								$sqlreset="UPDATE sv_token SET userid = null";
								$sqlreset.=" Where sv_token.id = (SELECT token1id FROM sv_user Where";
								$sqlreset.=" userid = '".$content[$newcontent["userid"]]."')";
								
								$this->objDb->ExecuteNonQuery($sqlreset);
								
								$sqltoken1id ="UPDATE sv_user SET";
								$sqltoken1id.=" token1id=null";
								$sqltoken1id.=" Where userid='".$content[$newcontent["userid"]]."'";
								
								$this->objDb->ExecuteNonQuery($sqltoken1id);
								
							}
							//token2idが存在する場合は
							if($token2id[0]!="")
							{
								$sqlreset="UPDATE sv_token SET userid = null";
								$sqlreset.=" Where sv_token.id = (SELECT token2id FROM sv_user Where";
								$sqlreset.=" userid = '".$content[$newcontent["userid"]]."')";
								
								$this->objDb->ExecuteNonQuery($sqlreset);
								
								$sqltoken2id ="UPDATE sv_user SET";
								$sqltoken2id.=" token2id=".$token2id[0]."";
								$sqltoken2id.=" Where userid='".$content[$newcontent["userid"]]."'";
								
								$this->objDb->ExecuteNonQuery($sqltoken2id);
								
								$sqlupdatetoken2id ="UPDATE sv_token SET";
								$sqlupdatetoken2id.=" userid='".$content[$newcontent["userid"]]."'";
								$sqlupdatetoken2id.=" Where hid = '".$content[$newcontent["token2"]]."'";
								
								$this->objDb->ExecuteNonQuery($sqlupdatetoken2id);
								
								
							}
							else
							{
								$sqlreset="UPDATE sv_token SET userid = null";
								$sqlreset.=" Where sv_token.id = (SELECT token2id FROM sv_user Where";
								$sqlreset.=" userid = '".$content[$newcontent["userid"]]."')";
								
								$this->objDb->ExecuteNonQuery($sqlreset);
								
								$sqltoken2id ="UPDATE sv_user SET";
								$sqltoken2id.=" token2id=null";
								$sqltoken2id.=" Where userid='".$content[$newcontent["userid"]]."'";
								
								$this->objDb->ExecuteNonQuery($sqltoken2id);
									
							}
							//token3idが存在する場合は
							if($token3id[0]!="")
							{
								$sqlreset="UPDATE sv_token SET userid = null";
								$sqlreset.=" Where sv_token.id = (SELECT token3id FROM sv_user Where";
								$sqlreset.=" userid = '".$content[$newcontent["userid"]]."')";
								
								$this->objDb->ExecuteNonQuery($sqlreset);
								
								$sqltoken3id ="UPDATE sv_user SET";
								$sqltoken3id.=" token3id=".$token3id[0]."";
								$sqltoken3id.=" Where userid='".$content[$newcontent["userid"]]."'";
								
								$this->objDb->ExecuteNonQuery($sqltoken3id);
								
								$sqlupdatetoken3id ="UPDATE sv_token SET";
								$sqlupdatetoken3id.=" userid='".$content[$newcontent["userid"]]."'";
								$sqlupdatetoken3id.=" Where hid = '".$content[$newcontent["token3"]]."'";
								
								$this->objDb->ExecuteNonQuery($sqlupdatetoken3id);
							
							
							}
							else
							{
								$sqlreset="UPDATE sv_token SET userid = null";
								$sqlreset.=" Where sv_token.id = (SELECT token3id FROM sv_user Where";
								$sqlreset.=" userid = '".$content[$newcontent["userid"]]."')";
								
								$this->objDb->ExecuteNonQuery($sqlreset);
								
								$sqltoken3id ="UPDATE sv_user SET";
								$sqltoken3id.=" token3id=null";
								$sqltoken3id.=" Where userid='".$content[$newcontent["userid"]]."'";
								
								$this->objDb->ExecuteNonQuery($sqltoken3id);
							}
							//ユーザ所在のgroupを削除する.
							$strQuery = " DELETE FROM sv_usergroup";
							$strQuery.= " Where userid='".$content[$newcontent["userid"]]."'";	
							$this->objDb->ExecuteCommand($strQuery);
							
							//ループでユーザgroupにインサートする.
							if(!is_array($groupid)) $groupid=array();
							foreach ($groupid as $key => $value)
							{
								$strSql8= " INSERT INTO sv_usergroup(userid, grpid) ";
								$strSql8.= " VALUES('".$content[$newcontent["userid"]]."','".$value[0]."')";
								
								$this->objDb->ExecuteNonQuery($strSql8);
									
							}
							
							// author by wuyq 20090604start
							$strDelParmas = "delete from sv_userparam where userid = '".$content[$newcontent["userid"]]."'";
							$this->objDb->ExecuteNonQuery($strDelParmas);
							
							if($arrComParams!=null && count($arrComParams)!= 0)
							{
								$arrParamKeys=array();
								for($w=count($arrComParams)-1;$w>=0;$w--)
								{
									$arrParamOne = explode("=",$arrComParams[$w]);
								if(@in_array($arrParamOne[0],$arrParamKeys))
								{
									unset($arrComParams[$w]);
								}
								else
								{
									array_push($arrParamKeys,$arrParamOne[0]);
								}

								}
								sort($arrComParams);
								
								for( $z = 0;$z < count($arrComParams);$z++ )
								{
									$arrParams = explode("=",$arrComParams[$z]);
									$strSqlParam  = " INSERT INTO sv_userparam";
									$strSqlParam.= " (userid, ";
									$strSqlParam.= " tag, ";
									$strSqlParam.= " value) ";
									$strSqlParam .= " VALUES( ";
									$strSqlParam .= " '".$content[$newcontent["userid"]]."', ";
									$strSqlParam .= " '".$arrParams[0]."', ";
									$strSqlParam .= " '".substr(substr($arrParams[1],1),0,-1)."')";
									$this->objDb->ExecuteNonQuery($strSqlParam);
								}
							}
							
							// author by wuyq 20090604end
												
						}
						catch( Exception $e )
						{
							$this->objDb->rollback();
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
					$this->objDb->commit();
				}
			}
		}
		$this->objDb->commit();
		$result = @fclose($pconn);
		@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."UserImportCSV.csv");
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
}

function sjis2utf8( $var, $first=false )
{   
	if(is_array($var)){		
		foreach($var as $key => $value)
			if($first)
				$var[$key] =strtolower(@mb_convert_encoding($value,"UTF-8","sjis-win")); //SJIS sjis-win
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
	$rezult1=array();
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
		
		if(!$tflag && $lastc !=  '"' && $beginc != '"' && $arrBurrer[$m] != "," && $lastc != "'" )
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
		if($beginc!=''&&$lastc =="'")
		{
			array_push($rezult, $arrBurrer[$m]);
			continue;
		}
	}
	
	if($tflag && $tempstr !="")
	{
		$tempstr = preg_replace('/(^")|("$)/', "", $tempstr);
		$k=9;
		array_push($rezult, $tempstr);
	}
	return $rezult;
}

function chkTimes( $strTimes )
{
	if(preg_match("/^[-0-9]{1,3}$/i",$strTimes)==false)
	{
		return false;
	}
	if($strTimes < -1 || $strTimes > 100)
	{
		return false;
	}
	return true;
	
}
?>
