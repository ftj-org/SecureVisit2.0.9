<?php
 /**
 *epass1000NDの属性登録/変更
 *
 * @author	呉艶秋.
 * @since	2007-09-17
 * @version	1.0
 */
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."mdl/dbview.php";
require_once $G_APPPATH[0]."/lib/xmlread.php";
class Attribute_detail_M{

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
			$this->objXml = new Node();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例化");
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}

	 /**
	 * 関数名: Attribute_detail_M
	 * コンストラクタ.
	 * 
	 */
	function Attribute_detail_M(){				
		try{
			$this->__construct();
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}				
	}

	/**
	* 関数名: readAttributeDetail
	* 属性の情報を表示する.
	* 
	* @param		string		$attributeId			属性ID.
	*
	* @return		Array		$arrUserAttributeInfo	属性の情報.
	*/
	function readAttributeDetail($attributeId)
	{
		try
		{
			$strsql = " SELECT ";
			$strsql.= " type,";
			$strsql.= " nameuser,";
			$strsql.= " nametoken,";
			$strsql.= " visibleuser,";
			$strsql.= " visibletoken,";
			$strsql.= " memouser,";
			$strsql.= " memotoken,";
			$strsql.= " lastupdate";
			$strsql.= " FROM sv_customtmpl";
			$strsql.= " WHERE name='".$attributeId."'";
			$arrAttributeInfo=$this->objDb->ExecuteArrayCommand($strsql);
			return $arrAttributeInfo;
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;	
		}
	}


	/**
	* 関数名: setAttributeDetail
	* 属性の情報を更新する.
	* 
	* @param		string		$arrAttributeInfo	　属性の情報.
	* @param		string		$strLastupdate	　属性の情報.
	*
	* @return		void.
	*/
	function setAttributeDetail($arrAttributeInfo,$strLastupdate)
	{
		try
		{	
			//トラザクションの開始.
			$this->objDb->begin();
			
			$strexit = " SELECT COUNT(name)";
			$strexit.= " FROM sv_customtmpl";
			$strexit.= " WHERE name ='".$arrAttributeInfo["name"]."'";
			$strexit.= " AND lastupdate='".$strLastupdate."'";
			$restime = $this->objDb->ExecuteCommand($strexit);
			
			if($restime[0]!=1)
			return "fail";			
			
			//属性の情報を更新する.
			$strsql1 = " UPDATE sv_customtmpl";
			$strsql1.= " SET ";
			$strsql1.= " type ='".$arrAttributeInfo["type"]."',";
			$strsql1.= " nameuser='".$arrAttributeInfo["nameuser"]."',";
			$strsql1.= " nametoken='".$arrAttributeInfo["nametoken"]."',";
			$strsql1.= " memouser='".$arrAttributeInfo["memouser"]."',";
			$strsql1.= " memotoken='".$arrAttributeInfo["memotoken"]."',";
			$strsql1.= " visibleuser='".$arrAttributeInfo["visibleuser"]."',";
			$strsql1.= " visibletoken='".$arrAttributeInfo["visibletoken"]."',";	
			$strsql1.= " lastupdate=CURRENT_TIMESTAMP";
			$strsql1.= " WHERE name ='".$arrAttributeInfo["name"]."'";
			
			$this->objDb->ExecuteNonQuery($strsql1);
			
			//トラザクションの終了.
			$this->objDb->commit();
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			//ロールバック
			$this->objDb->rollback();
			throw $e;	
		}
		
		//存在する場合は.
		try{
			$viewObj = new DbView();
			$viewObj->dropview();		
		}	
		catch( Exception $e )
		{
		}
		$viewObj->createview();
	}
	
		
	/**
	* 関数名: addAttributeDetail
	* 属性をインサートする.
	* 
	* @param		string		$arrAttributeInfo		属性の情報.
	*
	* @return		void.				
	*/
	function addAttributeDetail($arrAttributeInfo)
	{
		try
		{
			//トラザクションの開始.
			$this->objDb->begin();
		
			$strsqlCount = "SELECT count(*) FROM sv_customtmpl";
			$resCount=$this->objDb->ExecuteCommand($strsqlCount);
			if($resCount[0] >= 10)
			{
				return "failinsert";
			}
			
			$orderID =$this->maxOrderID($arrAttributeInfo);
			$arrAttributeInfo["orderuser"] =$orderID[0];
			$arrAttributeInfo["ordertoken"] =$orderID[1];
			
			$strSql1 = "SELECT count(*) FROM sv_customtmpl WHERE  name ='".$arrAttributeInfo["name"]."'";
			$res=$this->objDb->ExecuteCommand($strSql1);
			if($res[0] != 0) return "fail" ;
			
			$strsql = " INSERT INTO sv_customtmpl(";
			$strsql.= " type, ";
			$strsql.= " name, ";
			$strsql.= " nameuser,";
			$strsql.= " nametoken,";
			$strsql.= " visibleuser,";
			$strsql.= " visibletoken,";
			$strsql.= " buser,";
			$strsql.= " btoken,";
			$strsql.= " memouser,";
			$strsql.= " memotoken,";
			$strsql.= " orderuser,";
			$strsql.= " ordertoken,";
			$strsql.= " lastupdate)";
			$strsql.= " VALUES(";
			$strsql.= " '".$arrAttributeInfo["type"]."',";
			$strsql.= " '".$arrAttributeInfo["name"]."',";
			$strsql.= " '".$arrAttributeInfo["nameuser"]."',";
			$strsql.= " '".$arrAttributeInfo["nametoken"]."',";
			$strsql.= " '".$arrAttributeInfo["visibleuser"]."',";
			$strsql.= " '".$arrAttributeInfo["visibletoken"]."',";
			$strsql.= " 'true',";
			$strsql.= " 'true',";
			$strsql.= " '".$arrAttributeInfo["memouser"]."',";
			$strsql.= " '".$arrAttributeInfo["memotoken"]."',";
			$strsql.= " '".$arrAttributeInfo["orderuser"]."',";
			$strsql.= " '".$arrAttributeInfo["ordertoken"]."',";
			$strsql.= "  CURRENT_TIMESTAMP )";
			$this->objDb->ExecuteNonQuery($strsql);

			$strsql1=" ALTER TABLE sv_user";
			$strsql1.=" ADD COLUMN  ".$arrAttributeInfo["name"]." character varying(256)";
			if( SESSION::GET("SESS_LIC_ServerID")!=1 || SESSION::GET("SESS_LIC_ServerID")==null)
			{
				$this->objDb->ExecuteNonQuery($strsql1);
			}
			else
			{
				$pconn = @fopen(G_DOC_TMP."sv_alter.sql",'a+');
				fwrite($pconn,$strsql1.";\n"); 
				fclose($pconn);
				$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
				$node = $this->objXml->GetElementsByTagName("cnodes");
				$child = $node[0]->ChildNodes();
				for($i = 0;$i<count($child);$i++)
				{
					$attr = $child[$i]->Attribute();
					if($attr['id'] == 1)
					{
						$ip = $attr['ip'];
					}
				}
				exec("sh /svisit/lib/scripts/sv_pgexec.sh " . $ip);
			}
			$strsql2=" ALTER TABLE sv_token";
			$strsql2.=" ADD COLUMN  ".$arrAttributeInfo["name"]." character varying(256)";
			
			if( SESSION::GET("SESS_LIC_ServerID")!=1 || SESSION::GET("SESS_LIC_ServerID")==null)
			{
				$this->objDb->ExecuteNonQuery($strsql2);
			}
			else
			{
				$pconn = @fopen(G_DOC_TMP."sv_alter.sql",'a+');
				fwrite($pconn,$strsql2.";\n"); 
				fclose($pconn);
				$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
				$node = $this->objXml->GetElementsByTagName("cnodes");
				$child = $node[0]->ChildNodes();
				for($i = 0;$i<count($child);$i++)
				{
					$attr = $child[$i]->Attribute();
					if($attr['id'] == 1)
					{
						$ip = $attr['ip'];
					}
				}
				
				exec("sh /svisit/lib/scripts/sv_pgexec.sh " . $ip);
			}
			
			//トラザクションの終了.
			$this->objDb->commit();
			
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			//ロールバック
			$this->objDb->rollback();
			throw $e;	
		}

		try{
			$viewObj = new DbView();
			$viewObj->DbCDView();
		}	
		catch( Exception $e )
		{
			throw $e;
		}
	}
	
	
	function checkExist($arrAttributeInfo)
	{
		try{
			$strsql = " SELECT count(name) FROM sv_customtmpl";
			$strsql.= " WHERE name = '".$arrAttributeInfo["name"]."'";
			$strexist= $this->objDb->ExecuteCommand($strsql);
			if($strexist[0]==0)
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		catch(Exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;	
		}
		
	}
	
	function maxOrderID($arrAttributeInfo)
	{
		try{
			$strsql = " SELECT max(orderuser)+1,max(ordertoken)+1 FROM sv_customtmpl";
			$orderID = $this->objDb->ExecuteCommand($strsql);
			
			if(isset($orderID[0]) == false)
			{
				$orderID[0] = 1;
			}
			if(isset($orderID[1]) == false)
			{
				$orderID[1] = 1;
			}
				
			return $orderID ;
		}
		catch(Exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;	
		}	
	}
	
}

?>