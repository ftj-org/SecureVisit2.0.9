<?php
/**
 * 端末インポート.
 *
 * @author liyang.
 * @since  2010-08-31
 * @version 1.0
 */
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."lib/page.php";

class Terminfo_import_M{
	
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
	

	function Terminfo_import_M()
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
	

	function importTerminfoCSV()
	{
		
	try{
		
		$pwdflag=false;
			
		$rpwdflag=false;
		
		
		
		$pconn = @fopen(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."TermImportCSV.csv",'r');
		
		// このファイルを開く時、エラーが発生した場合、エラーメッセージを表示する.
		if( !$pconn ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
		}
		
		// CSVファイルのタイトルを小文字で取得する.
		$fristline = sjis2utf8(fgetcsv($pconn,4096),true);
		
		//userid列はCSVファイルに存在するかをチェックする.
		
		if (@in_array("userid",$fristline)){
			$strResmsg=true;
			
			if(@in_array("userid",$fristline)&&@in_array("mac",$fristline)&&@in_array("memo",$fristline)){
				$termflag=true;
			}	
					
			//配列を反転して返す.
			$newcontent=array_flip($fristline);
			
			//user表からタイトルを取得する.
			$res =$this->objDb->metadata("sv_terminfo");
			
			//ループで新しい配列の要素を取得する.
			for($i = 0; $i<count($res);$i++ )
			{
				$arrResCol[$i] = $res[$i]["userid"];
			}
			
			$newres=array_intersect($fristline,$arrResCol);
			
			//読み込んだ行数
			$TotalReadCount = 0;
			//User表にINSERTした行数
			$InsertCount = 0;
			//User表にINSERT失敗行数
			$notInsertCount = 0;
			//skip count
			$skipCount = 0;
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
					
						
						//ユーザが既に存在しない.							
							try{
								//登録できる数チェック
								$sqlselectnum = "SELECT counter from sv_termreg";
								$sqlselectnum.=" Where userid='".$content[$newcontent["userid"]]."'";
								$selectnum = $this->objDb->ExecuteCommand($sqlselectnum);	
								
								// 登録した端末数取得する.add by liyang
								$strSqlC  = " SELECT ";
								$strSqlC .= " COUNT(id) ";
								$strSqlC .= " FROM sv_terminfo ";
								$strSqlC .= " WHERE (userid ='".$content[$newcontent["userid"]]."')";
								$selectednum = $this->objDb->ExecuteCommand($strSqlC);
								
								if(strlen($content[$newcontent["mac"]])==32)
								{
									$temp_md5=$content[$newcontent["mac"]];
								}else{
									$temp_md5=md5($content[$newcontent["mac"]]);
								}
								if($selectnum[counter]>0) {
								//term列liyang	
									$sqlselectterm = "SELECT COUNT(id) from sv_terminfo";
									$sqlselectterm.=" Where userid='".$content[$newcontent["userid"]]."' and termid='".$temp_md5."'";
									$selectterm = $this->objDb->ExecuteCommand($sqlselectterm);	
									
									$sqlselectmemo = "SELECT COUNT(id) from sv_terminfo";
									$sqlselectmemo.=" Where userid='".$content[$newcontent["userid"]]."' and memo='".$content[$newcontent["memo"]]."'";
									$selectmemo = $this->objDb->ExecuteCommand($sqlselectmemo);
									
									if($termflag == true)
									{	
										if($selectterm[count]==0 && $selectmemo[count]==0)
										{
											$sqlpterm =" INSERT INTO sv_terminfo(";
											$sqlpterm.= " userid,";
											$sqlpterm.= " termid,";
											$sqlpterm.= " memo)";
											$sqlpterm.= " VALUES(";
											$sqlpterm.= " '".$content[$newcontent["userid"]]."',";
											//$sqlpterm.= " '".$content[$newcontent["mac"]]."',";
											$sqlpterm.= " '".$temp_md5."',";
											$sqlpterm.= " '".$content[$newcontent["memo"]]."')";
											$terminsert = $this->objDb->ExecuteCommand($sqlpterm);
											
											$selectnum[counter]=$selectnum[counter]-1;
											$sqlup = "update sv_termreg set counter='".$selectnum[counter]."'";
											$sqlup.= " where userid='".$content[$newcontent["userid"]]."'";
											$resultup=$this->objDb->ExecuteCommand($sqlup);
											if($terminsert[0]==""&&$resultup[0]=="")
											{	
												$InsertCount++;
											}else{
											$notInsertCount++;
											}
										}else{
												$skipCount++;
										}
										// else{
											// $sqlpterm1 ="UPDATE sv_terminfo SET";
											// $sqlpterm1.=" ticket = '".$content[$newcontent["ticket"]]."',counter = '".$content[$newcontent["counter"]]."'";
											// $sqlpterm1.=" Where userid='".$content[$newcontent["userid"]]."'";
											// $this->objDb->ExecuteNonQuery($sqlpterm1);	
										// }
									}	
								}else{
									$skipCount++;
								}												
							}
							catch( Exception $e )
							{
								$this->objDb->rollback();
							}
											
					$this->objDb->commit();
				}
			}
		}
		$this->objDb->commit();
		$result = @fclose($pconn);
		@unlink(G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."TermImportCSV.csv");
		$rescount[0]=$TotalReadCount;
		$rescount[1]=$InsertCount;
		$rescount[2]=$notInsertCount;
		$rescount[3]=$skipCount;
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
				$var[$key] =strtolower(@mb_convert_encoding($value,"UTF-8","SJIS"));
			else
				$var[$key] =@mb_convert_encoding($value,"UTF-8","SJIS");
	}else{
		if($first)
			$var =strtolower(@mb_convert_encoding($var,"UTF-8","SJIS"));
		else
			$var =@mb_convert_encoding($var,"UTF-8","SJIS");
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