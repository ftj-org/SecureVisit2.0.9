<?php
/**
 * epass1000NDのDBアクセスするためのモデル.
 *
 * @author 侯偉勝
 * @since  2008-02-27
 * @version 1.0
 */
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."lib/pageview.php";

class Log_admin_M{

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
	* 関数名: Log_admin_M
	* データベースのオブジェクトを初期化する.
	*/
	function Log_admin_M()
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
	* 関数名: readTableList
	* 条件によってデータを検索して、データのリストを生成する
	
	* 
	* @param       string    $orderBy        ソーティングする条件.
	* @param       string    $condition      検索の条件.
	* @param       string    $offset         偏りの度合.
	* @param       string    $pageNum        ページごとの表示の記録の数量.
	*
	* @return      Array     $res			   データのリスト.
	*/
	function readTableList($orderBy,$condition,$offset,$pageNum){
		try{
		$strSQl = " ( SELECT sv_adminlog.id, sv_adminlog.cdate, sv_adminlog.adminid, sv_adminlog.opcode, sv_adminlogmsg.oprecord, sv_adminlog.param, sv_adminlogmsg.opname";
		$strSQl .= " FROM sv_adminlog, sv_adminlogmsg";
		$strSQl .= " WHERE sv_adminlog.opcode = sv_adminlogmsg.opcore) as logadminview";
		
		// オフセットを取得する.
		$pageObj = new PageView($strSQl,$pageNum,$offset ,$this->objDb);
		$pageObj->SetCondition($condition,$orderBy);
		$res = $pageObj->ReadList();
		$resList = $pageObj->MakePage();

		$cssFlag = true;
		ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");	
		//検索結果から、パラメータでそのメッセージの中の％を置き換える。
		$resReturn = array();
		$res = is_array($res)?$res:array();
		foreach($res as $rec){
			$recTemp = array();
			$str1 = $rec["oprecord"];
			$str2 = $rec["param"];
			$strMessageMerged = $this->mergeParamtoMessage($rec["oprecord"],$rec["param"]);
			array_push($recTemp,$rec["id"]);
			array_push($recTemp,str_replace("-","/",substr($rec["cdate"],0,19)));  //　日時から"-"を"/"に置き換えます。
			array_push($recTemp,$rec["adminid"]);
			array_push($recTemp,$rec["opcode"]);
			array_push($recTemp,$strMessageMerged);
			array_push($recTemp,$rec["param"]);
			array_push($recTemp,$rec["opname"]);
			
			if( $cssFlag ) 
				array_push($recTemp,"evenrowbg");
			else
				array_push($recTemp,"oddrowbg");
			$cssFlag = $cssFlag==true?false:true;
				
			array_push($resReturn,$recTemp);
		}
		return array($resList,$resReturn);
		}catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
			
	}
	
	/**
	* 関数名: mergeParamtoMessage
	* 条件によってデータを検索して、データのリストを生成する
		
	* 
	* @param       string    $strMessage     メッセージ情報.
	* @param       string    $strParam       検索の条件.
	*
	* @return      Array     $strReturn      マージされたメッセージ.
	*/
	function mergeParamtoMessage($strMessage, $strParam){
		
		$pieces = explode("％", $strMessage);
		$params = explode(",",$strParam);
		
		array_push($params,"");
		$strReturn = "";
		
		foreach($pieces as $key => $part){
			if($key != 0){
				$pieces[$key] = substr($pieces[$key],1);
			}
			$strReturn .= $pieces[$key];
			$strReturn .= trim($params[$key]);
		}
		return $strReturn;
	}
	

	/**
	* 関数名: exportLogCSV  
	* 入力した条件でデータを検索してかつCSVファイルを作成して、そしてダウンロードする
	* 
	* @param       string    $orderBy        ソーティングする条件.
	* @param       string    $condition      検索の条件.
	*
　　* @return      　void.
	*/
	function exportLogCSV($orderBy,$condition)
	{
		try{
			$strSql2  = " SELECT ";
			$strSql2 .= " count(cdate) as num";
			$strSql2 .= " FROM sv_adminlog LEFT JOIN sv_adminlogmsg";
			$strSql2 .= " ON sv_adminlog.opcode = sv_adminlogmsg.opcore";
			$strSql2 .= $condition ;
			$logNum = $this->objDb->ExecuteCommand($strSql2);
			if($logNum[0] >= 100000) return "MOREDATA";
			
			$strSql  = " SELECT cdate,";
			$strSql .= " adminid, ";
			$strSql .= " opname, ";
			$strSql .= " param ";
			$strSql .= " FROM sv_adminlog LEFT JOIN sv_adminlogmsg ";
			$strSql .= " ON sv_adminlog.opcode = sv_adminlogmsg.opcore ";
			$strSql .= $condition.$orderBy ;
			
			// prepare
			$prepareconn = $this->objDb->PrepareQuery($strSql);
			$fileName = G_DOC_TMP.SESSION::Get("SESS_USERID").SESSION::Get("SESS_LOGINDATE")."LogAdminCSV.csv";
			
			// ファイルのタイトル.
			$pconn = fopen($fileName,'w+');
					
			// DEBUG MESSAGE
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql &  $strSql2");
			$strData = "cdate,adminid,opname,param\n";
			if( !$prepareconn ){
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,ERR203);
				exit;
			}
			
			do{
				$res = $this->objDb->PQ_getData($prepareconn);
				// ループで配列の要素を取得する.
				$res = $res==""?array():$res; 
				foreach( $res as $key => $value)
				{
					$res[0]= str_replace("-","/",substr($res[0],0,19));
				}
				
				$strData .= '"'.mb_convert_encoding(implode('","',$res),"SJIS", "UTF-8").'"'."\n";
				fwrite ($pconn,$strData);
				$strData="";
			}while( sizeof($res) > 0 );
			$this->objDb->Prepare_Free($prepareconn);
			@fclose($pconn);
			header("location: ../ctrl/download.php?s=".urlencode($fileName));
			exit;			
		}catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
}

?>