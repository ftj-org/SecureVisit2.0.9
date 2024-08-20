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
require_once $G_APPPATH[0]."mdl/dbview.php";
require_once $G_APPPATH[0]."/lib/xmlread.php";

class Attribute_changeid_list_M{

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
			$this->objXml = new Node();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例化");
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}

	/**
	* 関数名: Attribute_changeid_list_M
	* データベースのオブジェクトを初期化する.
	*/
	function Attribute_changeid_list_M()
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
	* 関数名: SetAttribute
	* 指定した属性を削除する.
	* 
    * @param	string		$AttributeID		属性.
	* @param	string		$attributeId		属性.
	*
	* @return		void.
	*/
	function setAttribute($AttributeID,$attributeId)
	{
	try{
			//イベントの開始
			$this->objDb->begin();
			$AttributeID = strtolower(Attribute_.$AttributeID);
			$attributeId = strtolower(Attribute_.$attributeId);

			$strSql1 = " SELECT count(*) FROM sv_customtmpl WHERE  name ='".$AttributeID."'";
			$res=$this->objDb->ExecuteCommand($strSql1);
			if($res[0] != 0) return "fail_1" ;
			
			$strSql2 = " SELECT count(*) FROM sv_customtmpl WHERE  name ='".$attributeId."'";
			$res=$this->objDb->ExecuteCommand($strSql2);
			if($res[0] != 1) return  "fail_2";
			
			$strSqlUser = " ALTER TABLE sv_user RENAME COLUMN ".$attributeId." TO ".$AttributeID." ";
			if( SESSION::GET("SESS_LIC_ServerID")!=1||SESSION::GET("SESS_LIC_ServerID")==null)
			{
				$this->objDb->ExecuteCommand($strSqlUser);
			}
			else
			{
				$pconn = @fopen(G_DOC_TMP."sv_alter.sql",'a+');
				fwrite($pconn,$strSqlUser.";\n"); 
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
			$strSqlToken = " ALTER TABLE sv_token RENAME COLUMN ".$attributeId." TO ".$AttributeID." ";
			if( SESSION::GET("SESS_LIC_ServerID")!=1|| SESSION::GET("SESS_LIC_ServerID")==null)
			{
				$this->objDb->ExecuteCommand($strSqlToken);
			}
			else
			{
				$pconn = @fopen(G_DOC_TMP."sv_alter.sql",'a+');
				fwrite($pconn,$strSqlToken.";\n"); 
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
			$strSql = " UPDATE sv_customtmpl ";
			$strSql.= " SET name ='".$AttributeID."'";
			$strSql.= " WHERE name ='".$attributeId."'";
			$this->objDb->ExecuteCommand($strSql);
			
			$this->objDb->commit();

		}	
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			$this->objDb->rollback();
			return "fail";
		}
	}
}

?>