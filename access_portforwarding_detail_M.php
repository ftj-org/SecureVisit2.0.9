<?php
/**
 * ePass1000NDのポートフォワーディング一覧
 * @author 呉艶秋
 * @since  2008-12-01
 * @version 1.0 
 */

// 指定されたファイルを読み込む
$G_APPPATH_M = explode("mdl",__FILE__);
require_once $G_APPPATH_M[0]."/lib/xmlread.php";
require_once $G_APPPATH_M[0]."/lib/log.php";
require_once $G_APPPATH_M[0]."/lib/db.php";

class Access_portforwarding_detail_M
{
   /**
	* ノードの実例
	*/
	var $objXml = null;
	
	/*
	*データベースの実例

	*/
	var $objDb = null;
		
   /**
	* 関数名:__construct
	* コンストラクタ
	*/
	function __construct()
	{
		try
		{
			$this->objXml = new Node();
			$this->objDb = new DB_Pgsql();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例");
		}
		catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->GetMessage());
			exit;
		}
	}
	
   /**
	* 関数名: Access_portforwarding_detail_M
	* コンストラクタ.
	*/
	function Access_portforwarding_detail_M()
	{
		try{
			$this->__construct();
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->GetMessage());
			throw $e;
		}
	}
		
   /**
	* 関数名: readAccess
	* ノードの獲得
	* @param	string　$portポートフォワーディング
	* @return Array   $List
	*/
	function readAccess($port)
	{
		ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"readAccess begin");
		//ファイルのインプット		
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		//linksetノードの獲得				
		$list = array();
		$node = $this->objXml->GetElementsByTagName("portforwarding");	
		$child = $node[0]->ChildNodes();
		for($i = 0;$i<count($child);$i++)
		{
			$attr = $child[$i]->Attribute();
			$list[$i]["type"] = isset($attr["type"])==true?$attr["type"]:"";
			$list[$i]["localport"] = isset($attr["localport"])==true?$attr["localport"]:"";
			$list[$i]["remoteip"] = isset($attr["remoteip"])==true?$attr["remoteip"]:"";
			$list[$i]["remoteport"] = isset($attr["remoteport"])==true?$attr["remoteport"]:"";
			$list[$i]["concurrent"] = isset($attr["concurrent"])==true?$attr["concurrent"]:"";
			$cChild = $child[$i]->ChildNodes();
			$list[$i]["member"] = "false";
			//memberの取得			
			for($j = 0;$j<count($cChild);$j++)
			{
				//memberのノードは存在しない

				if($cChild[$j]->_nodeName!="member")
				continue 1;
				$list[$i]["member"] = "true";
				$list[$i]["group"] = "";
				$ccChild = $cChild[$j]->ChildNodes();
				//groupノードの取得	
				for($k = 0;$k<count($ccChild);$k++)
				{
					$ccAttr = $ccChild[$k]->Attribute();
					$list[$i]["name"][$ccAttr["name"]] = isset($ccAttr["name"])==true?$ccAttr["name"]:"";
					$list[$i]["access"][$k] = isset($ccAttr["access"])==true?$ccAttr["access"]:"";
				}
			}															
			if($list[$i]["localport"]==$port)															
			{	
				return $list[$i];	
			}
		}
		return array();	
	}
	
   /**
	* 関数名: getGroupList
	* グループの取得. 
	*
	* @return		array		$resドロップダウンリスト.
	*/
	function getGroupList()
	{
		$strSql  = " SELECT name as id,name AS group";
		$strSql .= " FROM sv_group ";
		// DEBUG メッセージ
		ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
		$resGroup = $this->objDb->ExecuteArrayCommand($strSql);
		$res =array();
		if($resGroup == "") $resGroup = array();
		foreach($resGroup as $key => $value ){
			$res[$value["id"]] =$value["group"];
		}
		ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$res");
		return $res;
	}
	
   /**
	* 関数名: insertNode
	* ノードのインポート
	* @return 
	*/
	function insertNode($arrlink,$strSelect)
	{
		// ファイルのインポート	
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		$node = $this->objXml->GetElementsByTagName("portforwarding");	
		$child = $node[0]->ChildNodes();
		for($m = 0;$m<count($child);$m++)
		{
			$attr = $child[$m]->Attribute();				
			if($attr["localport"]===$arrlink["localport"])															
			{
				return "fail";	
			}
		}		
		$node2	= new Node();
		$node2->setNodeName("port");
		$node2->setAttribute("type",$arrlink["type"]);
		$node2->setAttribute("localport",$arrlink["localport"]);
		$node2->setAttribute("remoteip",$arrlink["remoteip"]);
		$node2->setAttribute("remoteport",$arrlink["remoteport"]);
		$node2->setAttribute("concurrent",$arrlink["concurrent"]);
		// ボタン1を選択する
		if($strSelect == 0)
		{
			$node[0]->AppendChild($node2);
		}	
		if($strSelect ==2)
		{
			$node3 = new Node();
			$node3->setNodeName("member");				
			for($i=4;$i<count($arrlink["name"])+4;$i++)
			{
				$node[$i] = new Node();
				$node[$i]->setNodeName("group");
				$node[$i]->setAttribute("name",$arrlink["name"][$i-4]);
				$node[$i]->setAttribute("access","permit");
				$node3->AppendChild($node[$i]);
			}			
			$node2->AppendChild($node3);
			$node[0]->AppendChild($node2);	
		}					
		$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	
	}
	
   /**
	* 関数名: changeNode
	* ノードの変更
	* @return Array   $List
	*/
	function changeNode($arrlink,$strSelect)
	{
		ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"changeNode begin");
		//ファイルのインプット
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		$list = array();	
		//linksetノードの獲得 																			
		$node = $this->objXml->GetElementsByTagName("portforwarding");			
		ToLog::ToLogs(DEBUG,__FILE__,__LINE__,"$dir");
		$child = $node[0]->ChildNodes();																	
		for($i = 0;$i<count($child);$i++)
		{																																
			$attr = $child[$i]->Attribute();														
			if($attr["localport"] == $arrlink["localport"])
			{
				$child[$i]->setAttribute("type",$arrlink["type"]);
				$child[$i]->setAttribute("remoteip",$arrlink["remoteip"]);
				$child[$i]->setAttribute("remoteport",$arrlink["remoteport"]);
				$child[$i]->setAttribute("concurrent",$arrlink["concurrent"]);
				$childNode = $child[$i]->ChildNodes();
				$countChildNode = count($childNode);
				for($j=0;$j<$countChildNode;$j++)
				{
					$child[$i]->RemoveChild($childNode[0]);
				}			
				$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	
					
				// ボタン1を選択する

				if($strSelect == 0)
				{
					$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
					return;
				}
				// ボタン2を選択する
				else
				{
					$node3 = new Node();
					$node3->setNodeName("member");				
					for($k=0;$k<count($arrlink["name"]);$k++)
					{
						$gnode[$k] = new Node();
						$gnode[$k]->setNodeName("group");
						$gnode[$k]->setAttribute("name",$arrlink["name"][$k]);
						$gnode[$k]->setAttribute("access","permit");
						$node3->AppendChild($gnode[$k]);
					}
					$child[$i]->AppendChild($node3);
				}
				$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
				return;			
			}
		}	
	}
}
?>