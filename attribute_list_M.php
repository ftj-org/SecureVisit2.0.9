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

class Attribute_list_M{

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
	* 関数名: User_attribute_list_M
	* データベースのオブジェクトを初期化する.
	*/
	function Attribute_list_M()
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
	* データのリストを作る.
	* @return Array			$res		データのリスト.
	*/
	function readTableList()
	{
		try
		{	
			//customtmpl表から、属性ID、属性表示名、説明文、適用を取得する.
			$strSql  =  " SELECT name,";
			$strSql .=  " nameuser,"; 
			$strSql .=  " memouser,"; 
			$strSql .=  " visibleuser,";
			$strSql .=  " visibletoken,";
			$strSql .=  " type,";
            $strSql .=  " orderuser,";
            $strSql .=  " ordertoken";
			$strSql .=  " FROM sv_customtmpl";
			$strSql .=  " ORDER BY orderuser";
			
			$res = $this->objDb->ExecuteArrayCommand($strSql);
			//データは存在しない場合は.
			if(count ($res) == 0)
			return "";

			$resNew = array();
			$cssFlag = false;
			//ループ処理で、cssの仕様を実現する.
			foreach( $res as $key => $value)
			{  
				$resNew[$key] = $value;
				//cssFlagはtrueの場合はoddrowbgする.falseの場合はevenrowbgをする.
				$resNew[$key]["css"] = $cssFlag==true?"oddrowbg":"evenrowbg";
				//cssFlagは新たに値を代入する.trueの場合は、falseを設定、falseの場合は、trueを設定する.
				$cssFlag =  $cssFlag==true?false:true;
				if($resNew[$key]["name"]==$value["name"]);
				{
					$resNew[$key]["name"]=ucfirst($value["name"]);
				}
			}
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
			return $resNew;
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: deleteAttribute
	* 指定した属性を削除する.
	* 
    * @param	string		$AttributeID		属性.
	*
	* @return		void.
	*/
	function deleteAttribute( $AttributeID )
	{
		try{
			//イベントの開始
			$this->objDb->begin();

			$strSql  = " SELECT count(*)";
			$strSql .= " FROM sv_customtmpl ";
			$strSql .= " WHERE name ='".$AttributeID."'";	
			
			$res = $this->objDb->ExecuteCommand($strSql);
			if($res[0] != 1) return "delfalse";
           
			$strSql1  = " ALTER TABLE sv_user"; 
			$strSql1 .= " DROP COLUMN ".$AttributeID." CASCADE";
			if(SESSION::GET("SESS_LIC_ServerID")!=1 ||SESSION::GET("SESS_LIC_ServerID")==null)
            {
				$this->objDb->ExecuteNonQuery($strSql1);	
			}
			else
			{
				$pconn = @fopen(G_DOC_TMP."sv_alter.sql",'a+');
				fwrite($pconn,$strSql1.";\n"); 
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

			$strSql2  = " ALTER TABLE sv_token"; 
			$strSql2 .= " DROP COLUMN ".$AttributeID." CASCADE";
			if(SESSION::GET("SESS_LIC_ServerID")!=1 || SESSION::GET("SESS_LIC_ServerID")==null)
			{
				$this->objDb->ExecuteNonQuery($strSql2);
			}
			else
			{
				$pconn = @fopen(G_DOC_TMP."sv_alter.sql",'a+');
				fwrite($pconn,$strSql2.";\n"); 
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

			$strSql3  = " UPDATE sv_customtmpl"; 
			$strSql3 .= " SET"; 
			$strSql3 .= " orderuser = orderuser-1 ,";
			$strSql3 .= " ordertoken = ordertoken-1 ";
			$strSql3 .= " WHERE ";
			$strSql3 .= " orderuser > ";
			$strSql3 .= " ( SELECT  ";
			$strSql3 .= " orderuser ";
			$strSql3 .= " FROM sv_customtmpl";
			$strSql3 .= " WHERE name ='".$AttributeID."')";

			$this->objDb->ExecuteNonQuery($strSql3);	
	
			//CustomTmpl表の該当Name列を削除する.
			$strSql4  = " DELETE";
			$strSql4 .= " FROM sv_customtmpl ";
			$strSql4 .= " WHERE name ='".$AttributeID."'";	

			$this->objDb->ExecuteNonQuery($strSql4);
			
			//イベントの終了
			$this->objDb->commit();

		}	
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			$this->objDb->rollback();
			throw $e;
		}

		try{
			
			$viewObj = new DbView();
			$viewObj -> dropview();
			
		}	
		catch( Exception $e )
		{
		}
		$viewObj -> createview();
	}
	
	function MoveUpAttribute( $AttributeID,$strOrder )
	{
		try{
			$this->objDb->begin();
			
			$strsqlcount	= " SELECT count(*) FROM sv_customtmpl";
			$strsqlcount .= " WHERE name ='".$AttributeID."'";
			$strsqlcount	= $this->objDb->ExecuteCommand($strsqlcount);
	
			if($strsqlcount[0] != 1) return "datafalse";
			
			$strsqlorder	= " SELECT count(*) FROM sv_customtmpl";
			$strsqlorder .= " WHERE name ='".$AttributeID."'";
			$strsqlorder .= " AND orderuser ='".$strOrder."'";
			$strsqlorder  = $this->objDb->ExecuteCommand($strsqlorder);
			
			if($strsqlorder[0] != 1) return "orderchanged";

			if($strOrder==1)
			{
				return ;
			}
			else
			{
				$strSql3  = " SELECT name FROM sv_customtmpl";
				$strSql3 .= " WHERE orderuser = ".$strOrder."-1";
				$name = $this->objDb->ExecuteCommand($strSql3);
				
				$strSql4  = " UPDATE sv_customtmpl SET orderuser=".$strOrder.",ordertoken=".$strOrder."";
				$strSql4 .= " WHERE name ='".$name[0]."'";
				$this->objDb->ExecuteCommand($strSql4);
				
				$strSql5  = " UPDATE sv_customtmpl SET orderuser=".$strOrder."-1,ordertoken=".$strOrder."-1";
				$strSql5 .= " WHERE name ='".$AttributeID."'";
				
				$this->objDb->ExecuteCommand($strSql5);
				
				//トラザクションの終了.
				$this->objDb->commit();				
			}	
		}
		catch( Exception $e )
		{
			$this->objDb->rollback();
			throw $e;
		}
	
		try{
			$viewObj = new DbView();
			$viewObj ->dropview();
			
		}	
		catch( Exception $e )
		{
		}
		$viewObj -> createview();
	}
	
	
	function MoveDownAttribute( $AttributeID,$strOrder )
	{
		try{
			$this->objDb->begin();
			
			$strsqlcount  = " SELECT count(*) FROM sv_customtmpl";
			$strsqlcount .= " WHERE name ='".$AttributeID."'";
			$strsqlcount  = $this->objDb->ExecuteCommand($strsqlcount);
			
			if($strsqlcount[0] != 1) return "datafalse";
			
			$strsqlorder  = " SELECT count(*) FROM sv_customtmpl";
			$strsqlorder .= " WHERE name ='".$AttributeID."'";
			$strsqlorder .= " AND orderuser ='".$strOrder."'";
			$strsqlorder  = $this->objDb->ExecuteCommand($strsqlorder);
			
			if($strsqlorder[0] != 1) return "orderchanged";
			
			$strsqlcount2 = " SELECT count(*) FROM sv_customtmpl";
			$strsqlcount2 = $this->objDb->ExecuteCommand($strsqlcount2);
			
			if($strOrder == $strsqlcount2[0])
			{
				return ;
			}
			else
			{
				$strSql3  = " SELECT name FROM sv_customtmpl";
				$strSql3 .= " WHERE orderuser = ".$strOrder."+1";
				$name = $this->objDb->ExecuteCommand($strSql3);
				
				$strSql4 = " UPDATE sv_customtmpl SET orderuser=".$strOrder.",ordertoken=".$strOrder."";
				$strSql4.= " WHERE name ='".$name[0]."'";
				$this->objDb->ExecuteCommand($strSql4);
				 
				$strSql5  = " UPDATE sv_customtmpl SET orderuser=".$strOrder."+1,ordertoken=".$strOrder."+1";
				$strSql5 .= " WHERE name ='".$AttributeID."'";
				
				$this->objDb->ExecuteCommand($strSql5);
				
				//トラザクションの終了.
				$this->objDb->commit();
			}
		}
		catch( Exception $e )
		{
			throw $e;
		}
	
		try{
			$viewObj = new DbView();
			$viewObj -> dropview();
		}	
		catch( Exception $e )
		{	
		}
		$viewObj -> createview();
	}
}

?>