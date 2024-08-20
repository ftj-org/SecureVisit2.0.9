<?php
/**
 * ePassND1000のアクセス権限設定マッピング一覧ファイル
 *
 *  @author  張新星

 *　@since 　2008-02-28
 *  @version 1.0
 */

// 指定されたファイルを読み込む
$G_APPPATH_M = explode("mdl",__FILE__);
require_once $G_APPPATH_M[0]."/lib/xmlread.php";
require_once $G_APPPATH_M[0]."/lib/log.php";
require_once $G_APPPATH_M[0]."/lib/db.php";

class Access_control_detail_M
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
	*
	*/
	function __construct()
	{
		try{
			$this->objXml = new Node();
			$this->objDb = new DB_Pgsql();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例");
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->GetMessage());
			exit;
		}
	}
		
	/**
	* 関数名: Access_control_detail_M
	* コンストラクタ.
	* 
	*/
	function Access_control_detail_M()
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

	* @param		string		$dir			変換元PATH
	* @return Array   $List
	*/
	function readAccess($dir)
	{
		ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"readAccess begin");
		//ファイルのインプット		
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		//linksetノードの獲得				
		$list = array();
		if($dir!="default")
		{
			$node = $this->objXml->GetElementsByTagName("linkset");
			
			$child = $node[0]->ChildNodes();
			for($i = 0;$i<count($child);$i++){
				$list[$i]["__NODE_ID__"] = $child[$i]->__NODE_ID__;
				$list[$i]["_nodename"] = $child[$i]->_nodeName;	
				$attr = $child[$i]->Attribute();	
				$list[$i]["domain"] = isset($attr["domain"])==true?$attr["domain"]:"";
				$list[$i]["dir"] = isset($attr["dir"])==true?$attr["dir"]:"";
				$list[$i]["url"] = isset($attr["url"])==true?$attr["url"]:"";
				$list[$i]["port"] = isset($attr["port"])==true?$attr["port"]:"";
				$list[$i]["basicuser"] = isset($attr["basicuser"])==true?$attr["basicuser"]:"";
				$list[$i]["basicpwd"] = isset($attr["basicpwd"])==true?$attr["basicpwd"]:"";
				$list[$i]["relocate"] = isset($attr["relocate"])==true?$attr["relocate"]:"";
				$list[$i]["mode"] = isset($attr["mode"])==true?$attr["mode"]:"";
				$list[$i]["checkip"]=$attr["checkip"];
				$cChild = $child[$i]->ChildNodes();
				$list[$i]["member"] = "false";
				//memberの取得			
				for($j = 0;$j<count($cChild);$j++){
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
				for($g = 0;$g<count($cChild);$g++)
				{
					if($cChild[$g]->_nodeName==="backend")
					{
						$attr = $cChild[$g]->Attribute();	
						$list[$i]["backip"][$g]=isset($attr["url"])==true?$attr["url"]:"";
						$list[$i]["backipport"][$g]=isset($attr["ip"])==true?$attr["ip"]:"";
						$newlist=array();
						$newlist=preg_split("/[///]/",$list[$i]["backip"][$g]);
						$new=array();
						$new[0]=$newlist[2];
						$new[1]=$list[$i]["backipport"][$g];
						$newi=implode(":",$new);
						$list[$i]["ipport"][$newi]=$newi;
                                                $list[$i]["ipport"][$newi]=$attr["ip"];
					}
				}
				for($w= 0;$w<count($cChild);$w++)
				{
					if($cChild[$w]->_nodeName==="param")
					{
						$attr = $cChild[$w]->Attribute();	
						$list[$i]["paramname"][$w]=isset($attr["name"])==true?$attr["name"]:"";
						$list[$i]["tag"][$w]=isset($attr["tag"])==true?$attr["tag"]:"";
						$new=array();
						$new[0]=$list[$i]["paramname"][$w];
						$new[1]=$list[$i]["tag"][$w];
						$newi=implode("=",$new);
						$list[$i]["combine"][$newi]=$newi;
					}
				}														
				if($list[$i]["dir"]==$dir)															
				{	
					return $list[$i];	
				}	
			}
		}
		else
		{
			//defaultlinkノードの獲得	
			//新しい内容を増える

			$node = $this->objXml->GetElementsByTagName("defaultlink");
			
			$child = $node[0]->ChildNodes();
			for($i = 0;$i<count($child);$i++){
				$list[$i]["__NODE_ID__"] = $child[$i]->__NODE_ID__;
				$list[$i]["_nodename"] = $child[$i]->_nodeName;	
				$attr = $child[$i]->Attribute();	
				$list[$i]["domain"] = isset($attr["domain"])==true?$attr["domain"]:"";
				$list[$i]["dir"] = isset($attr["dir"])==true?"default":"";
				$list[$i]["url"] = isset($attr["url"])==true?$attr["url"]:"";
				$list[$i]["port"] = isset($attr["port"])==true?$attr["port"]:"";
				$list[$i]["basicuser"] = isset($attr["basicuser"])==true?$attr["basicuser"]:"";
				$list[$i]["basicpwd"] = isset($attr["basicpwd"])==true?$attr["basicpwd"]:"";
				$list[$i]["relocate"] = isset($attr["relocate"])==true?$attr["relocate"]:"";
				$list[$i]["mode"] = isset($attr["mode"])==true?$attr["mode"]:"";
				$list[$i]["checkip"]=$attr["checkip"];
				$cChild = $child[$i]->ChildNodes();
				$list[$i]["member"] = "false";
				//memberの取得			
				for($j = 0;$j<count($cChild);$j++){
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
				//backendのノードを取得.
				for($g = 0;$g<count($cChild);$g++)
				{
					if($cChild[$g]->_nodeName==="backend")
					{
						$attr = $cChild[$g]->Attribute();	
						$list[$i]["backip"][$g]=isset($attr["url"])==true?$attr["url"]:"";
						$list[$i]["backipport"][$g]=isset($attr["ip"])==true?$attr["ip"]:"";
						$newlist=array();
						$newlist=preg_split("/[///]/",$list[$i]["backip"][$g]);
						$new=array();
						$new[0]=$newlist[2];
						$new[1]=$list[$i]["backipport"][$g];
						$newi=implode(":",$new);
						$list[$i]["ipport"][$newi]=$newi;
                                                $list[$i]["ipport"][$newi]=$attr["ip"];
					}
				}
				for($w= 0;$w<count($cChild);$w++)
				{
					if($cChild[$w]->_nodeName==="param")
					{
						$attr = $cChild[$w]->Attribute();	
						$list[$i]["paramname"][$w]=isset($attr["name"])==true?$attr["name"]:"";
						$list[$i]["tag"][$w]=isset($attr["tag"])==true?$attr["tag"]:"";
						$new=array();
						$new[0]=$list[$i]["paramname"][$w];
						$new[1]=$list[$i]["tag"][$w];
						$newi=implode("=",$new);
						$list[$i]["combine"][$newi]=$newi;
					}
				}																		
				if($list[$i]["dir"]==$dir)															
				{
					return $list[$i];	
				}	
			}
		}
		return array();	
	}
		
	/**
	* 関数名: changeNode
	* ノードの変更
	* 
	* @return Array   $List
	*/
	function changeNode($arrlink,$strSelect,$strBackend)
	{
		ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"changeNode begin");
                //foreach($strBackend as $k=>$v){
                //    ToLog::ToLogs(DEBUG,__FILE__,__LINE__, $k.$v);
                // }
		//ファイルのインプット

		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		$list = array();	
        
		if($arrlink["dir"][0]=="default")
		{
			//defaultlinkノードの獲得

			//新しい内容を増える			
			$node = $this->objXml->GetElementsByTagName("defaultlink");
			
			ToLog::ToLogs(DEBUG,__FILE__,__LINE__,"$dir");
			$child = $node[0]->ChildNodes();																	
			$i = 0;																															
			$child[$i]->setAttribute("domain",$arrlink["domain"][0]);
			
			$aURL = parse_url($arrlink["url"][0]);			
			$res = preg_match("/^(http|https)/i",$aURL["scheme"]);
			if( $res == 0) return;
			
			if ( $aURL["port"] != "" )
			{
				$url = str_replace(':'.$aURL["port"],"",$arrlink["url"][0]);
				$child[$i]->setAttribute("port",$aURL["port"]);
				$child[$i]->setAttribute("url",$url);
				$child[$i]->setAttribute("oriurl",$arrlink["url"][0]);
                        }
			else
			{
				$child[$i]->setAttribute("url",$arrlink["url"][0]);
				$child[$i]->setAttribute("port",null);
			        $child[$i]->setAttribute("oriurl",null);
			}					
			$child[$i]->setAttribute("basicuser",$arrlink["basicuser"][0]);
			$child[$i]->setAttribute("basicpwd",$arrlink["basicpwd"][0]);
			$child[$i]->setAttribute("relocate",$arrlink["reurl"]);
			$child[$i]->setAttribute("checkip",$arrlink["checkip"]);
			$child[$i]->setAttribute("mode",$arrlink["mode"]);
			
			$childNode = $child[$i]->ChildNodes();
			$countChildNode = count($childNode);
			for($j=0;$j<$countChildNode;$j++){
				$child[$i]->RemoveChild($childNode[0]);
			}			
			$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	
			
			//backendの更新.
			if($strBackend !="")
			{	
				for($g=0;$g<count($strBackend);$g++)
				{
					$attr = $child[$i]->Attribute();	
					$newarr=preg_split("/:/",$strBackend[$g]);
					
					if ( $aURL[port] != "" )
					{
						$url=preg_replace("/\/\/[a-zA-Z0-9\-_:\.]{1,}/", "//".$newarr[0],$arrlink["url"][0]);
						$newbackend = str_replace(':'.$aURL[port],"",$url);
					}
					else
					{
						$newbackend=preg_replace("/\/\/[a-zA-Z0-9\-_:\.]{1,}/", "//".$newarr[0],$arrlink["url"][0]);
					}					
					$nodebackend = new Node();
					$nodebackend->setNodeName("backend");
				        $nodebackend->setAttribute("ip", $strBackend[$g]);
                                	$nodebackend->setAttribute("url",$newbackend);
					$nodebackend->setAttribute("port",$newarr[2]);
					$child[$i]->AppendChild($nodebackend);
				}
			}
			
			if($arrlink["combine"]!='' && count($arrlink["combine"])>0)
			{
				foreach( $arrlink["combine"] as $key => $value )
				{
					$arrCombine  = explode('=' , $value );
					$strParamName = $arrCombine[0];
					$strtag= $arrCombine[1];
					$newparams = new Node();
					$newparams->setNodeName("param");
					$newparams->setAttribute("name",$strParamName);
					$newparams->setAttribute("tag",$strtag);
					$child[$i]->AppendChild($newparams);
				}
			}
			// ボタン1を選択する
           
			if($strSelect == 0)
			{
				$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
				return;
			}
			// ボタン2を選択する

			else if($strSelect == 1)
			{
				$node3 = new Node();
				$node3->setNodeName("member");	
				$node4 = new Node();
				$node4->setNodeName("group");
				$node4->setAttribute("name","nobody");
				$node4->setAttribute("access","deny");
				$node3->AppendChild($node4);
				$child[$i]->AppendChild($node3);
			}
			// ボタン3を選択する

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
		else
		{
			//linksetノードの獲得 																			
			$node = $this->objXml->GetElementsByTagName("linkset");			
			ToLog::ToLogs(DEBUG,__FILE__,__LINE__,"$dir");
			$child = $node[0]->ChildNodes();																	
			for($i = 0;$i<count($child);$i++){																																
				$attr = $child[$i]->Attribute();														
				if($attr["dir"] == $arrlink["dir"][0])
				{
					//$node[0]->RemoveChild($child[$i]);
					$child[$i]->setAttribute("domain",$arrlink["domain"][0]);
					
					$aURL = parse_url($arrlink["url"][0]);			
					$res = preg_match("/^(http|https)/i",$aURL["scheme"]);
					if( $res == 0) return;
					
					if ( $aURL["port"] != "" )
					{
						$url = str_replace(':'.$aURL["port"],"",$arrlink["url"][0]);
						$child[$i]->setAttribute("port",$aURL["port"]);
						$child[$i]->setAttribute("url",$url);
						$child[$i]->setAttribute("oriurl",$arrlink["url"][0]);
                                        }
					else
					{
						$child[$i]->setAttribute("url",$arrlink["url"][0]);
						$child[$i]->setAttribute("port",null);
			                        $child[$i]->setAttribute("oriurl",null);
					}
					
					$child[$i]->setAttribute("basicuser",$arrlink["basicuser"][0]);
					$child[$i]->setAttribute("basicpwd",$arrlink["basicpwd"][0]);
					$child[$i]->setAttribute("relocate",$arrlink["reurl"]);
					$child[$i]->setAttribute("checkip",$arrlink["checkip"]);
					$child[$i]->setAttribute("mode",$arrlink["mode"]);
					$childNode = $child[$i]->ChildNodes();
					$countChildNode = count($childNode);
					for($j=0;$j<$countChildNode;$j++){
						$child[$i]->RemoveChild($childNode[0]);
					}			
					$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	
					
					//backendの更新.
					if($strBackend !="")
					{	
						for($g=0;$g<count($strBackend);$g++)
						{
							$attr = $child[$i]->Attribute();	
							$newarr=preg_split("/:/",$strBackend[$g]);
							
							if ( $aURL[port] != "" )
							{
								//$url=preg_replace("/\/\/[a-zA-Z0-9\-_:\.]{1,}/", "//".$newarr[0],$arrlink["url"][0]);
								$newbackend = str_replace(':'.$aURL[port],"",$url);
							}
							else
							{
								$newbackend=preg_replace("/\/\/[a-zA-Z0-9\-_:\.]{1,}/", "//".$newarr[0],$arrlink["url"][0]);
							}	
							$nodebackend = new Node();
                                          		$nodebackend->setNodeName("backend");
					                $nodebackend->setAttribute("ip", $strBackend[$g]);
                                         		$nodebackend->setAttribute("url",$newbackend);
							$nodebackend->setAttribute("port",$newarr[1]);
							$child[$i]->AppendChild($nodebackend);
						}
					}
					
					if($arrlink["combine"]!='' && count($arrlink["combine"])>0)
					{
						foreach( $arrlink["combine"] as $key => $value )
						{
							$arrCombine  = explode('=' , $value );
							$strParamName = $arrCombine[0];
							$strtag= $arrCombine[1];
							$newparams = new Node();
							$newparams->setNodeName("param");
							$newparams->setAttribute("name",$strParamName);
							$newparams->setAttribute("tag",$strtag);
							$child[$i]->AppendChild($newparams);
						}
					}
                                        
				//	$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	
					// ボタン1を選択する

					if($strSelect == 0)
					{
						$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
						return;
					}
					// ボタン2を選択する

					else if($strSelect == 1)
					{
						$node3 = new Node();
						$node3->setNodeName("member");	
						$node4 = new Node();
						$node4->setNodeName("group");
						$node4->setAttribute("name","nobody");
						$node4->setAttribute("access","deny");
						$node3->AppendChild($node4);
						$child[$i]->AppendChild($node3);
					}
					// ボタン3を選択する

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
	
	/**
	* 関数名: insertNode
	* ノードのインポート

	* 
	* @return 
	*/
	function insertNode($arrlink,$strSelect,$strBackend)
	{
		// ファイルのインポート	
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		$node = $this->objXml->GetElementsByTagName("linkset");	
		$child = $node[0]->ChildNodes();
		for($m = 0;$m<count($child);$m++){
			$attr = $child[$m]->Attribute();				
			if($attr["dir"]===$arrlink["dir"][0])															
			{
		    	return "fail";	
			}
			}		

			$node2	= new Node();
			$node2->setNodeName("link");
			$node2->setAttribute("dir",$arrlink["dir"][0]);
			$node2->setAttribute("domain",$arrlink["domain"][0]);
			$aURL = parse_url($arrlink["url"][0]);		
			
			$res = preg_match("/^(http|https)/i",$aURL["scheme"]);
			if( $res == 0) return;
			
			if ( $aURL["port"] != "" )
			{
				$url = str_replace(':'.$aURL["port"],"",$arrlink["url"][0]);
				$node2->setAttribute("port",$aURL["port"]);
				$node2->setAttribute("url",$url);
			        $node2->setAttribute("oriurl",$arrlink["url"][0]);
                        }
			else
			{
				$node2->setAttribute("url",$arrlink["url"][0]);
				$node2->setAttribute("port",null);                               
			        $node2->setAttribute("oriurl",null);
			}
			$node2->setAttribute("basicuser",$arrlink["basicuser"][0]);
			$node2->setAttribute("basicpwd",$arrlink["basicpwd"][0]);
			$node2->setAttribute("relocate",$arrlink["reurl"]);
			$node2->setAttribute("checkip",$arrlink["checkip"]);
			$node2->setAttribute("mode",$arrlink["mode"]);
			
		   
			//ipはヌルではない場合.
			if($strBackend !="")
		    {	
				for($g=0;$g<count($strBackend);$g++)
				{
					$newarr=preg_split("/:/",$strBackend[$g]);
					if ( $aURL[port] != "" )
					{
						$url=preg_replace("/\/\/[a-zA-Z0-9\-_:\.]{1,}/", "//".$newarr[0],$arrlink["url"][0]);
						$newbackend = str_replace(':'.$aURL[port],"",$url);
					}
					else
					{
						$newbackend=preg_replace("/\/\/[a-zA-Z0-9\-_:\.]{1,}/", "//".$newarr[0],$arrlink["url"][0]);
					}	
					$nodebackend = new Node();
					$nodebackend->setNodeName("backend");
				        $nodebackend->setAttribute("ip", $strBackend[$g]);
		#			$nodebackend->setAttribute("url",$newbackend);
					$nodebackend->setAttribute("port",$newarr[1]);
					$node2->AppendChild($nodebackend);
				 }
			}
			if($arrlink["combine"]!='' && count($arrlink["combine"]) > 0)
			{
				foreach( $arrlink["combine"] as $key => $value )
				{
					$arrCombine  = explode('=' , $value );
					$strParamName = $arrCombine[0];
					$strtag= $arrCombine[1];
					$newparams = new Node();
					$newparams->setNodeName("param");
					$newparams->setAttribute("name",$strParamName);
					$newparams->setAttribute("tag",$strtag);
					$node2->AppendChild($newparams);
				}
			}

			// ボタン1を選択する

			if($strSelect == 0)
			{
				$node[0]->AppendChild($node2);
			}
			// ボタン2を選択する

			if($strSelect == 1)
			{
				$node3 = new Node();
				$node3->setNodeName("member");	
				$node4 = new Node();
				$node4->setNodeName("group");
				$node4->setAttribute("name","nobody");
				$node4->setAttribute("access","deny");
				$node3->AppendChild($node4);
				$node2->AppendChild($node3);
				$node[0]->AppendChild($node2);
			}
			// ボタン3を選択する

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
	* 関数名: getGroupList
	* グループの取得. 
	*
	* @return		array		$res				ドロップダウンリスト.
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
}
?>
