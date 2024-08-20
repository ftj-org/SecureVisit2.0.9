<?php
/**
 *　ePassND1000のアクセス権限設定マッピング一覧ファイル
 *
 *　@author  張新星
 *　@since 　2007-9-17
 *　@version 1.0
 */


//指定されたファイルを読み込む
$G_APPPATH_M = explode("mdl",__FILE__);
require_once $G_APPPATH_M[0]."/lib/log.php";
require_once $G_APPPATH_M[0]."/lib/xmlread.php";


/**
*クラス名:Access_control_list_M
*/
class Access_control_list_M{
    /**
    * ノードの実例.
    */
    var $objXml = null;
	
	/**
	* 関数名: __construct
	* コンストラクタ.
	* 
	*/
	function __construct(){
		try{
			$this->objXml = new Node();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例化");
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}

	/**
	*関数名: Access_control_list_M
	* コンストラクタ
	*
	*/
	function Access_control_list_M(){
		try{
			$this->__construct();
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: readTableList
	* xmlファイル中にデータのリストを作る.
	* 
	*
	* @return    Array       $list     配列.
	*/
	function readTableList(){

		//ファイルのインポト
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);

		//ノードの取得		
		$node = $this->objXml->GetElementsByTagName("linkset");
		$list = array();
		$child = $node[0]->ChildNodes();
		
		//linkノードの取得
		for($i = 0;$i<count($child);$i++){
			$list[$i]["_NODE_ID_"] = $child[$i]->NODE_ID_;
			$list[$i]["_nodename"] = $child[$i]->_nodeName;
			$attr = $child[$i]->Attribute();
			$list[$i]["dir"] = isset($attr["dir"])==true?$attr["dir"]:"";
			$list[$i]["url"] = isset($attr["url"])==true?$attr["url"]:"";
			$list[$i]["port"] = isset($attr["port"])==true?$attr["port"]:"";
			$list[$i]["mode"] = isset($attr["mode"])==true?$attr["mode"]:"";
			if(!$list[$i]["port"])
			{
				$list[$i]["url1"] = $list[$i]["url"];
			}
			else{
				if(stripos($list[$i]["url"],"/"))
				{
					$int = stripos($list[$i]["url"],"/");
					if($int != null)
					{
						$char = substr($list[$i]["url"],$int+1,1);
						if($char == "/")
						{
							$char1 = substr($list[$i]["url"],$int+2);
							if(stripos($char1,"/"))
							{
								$int1 =stripos($char1,"/");
								if($int1 != null)
								{
									$char2 = substr($char1,$int1);
									$char3 = substr($char1,0,$int1);
									$char4 = substr($list[$i]["url"],0,$int+2);
									$list[$i]["url1"] = $char4.$char3.":".$list[$i]["port"].$char2;
								}
							}
							else
							{
								$list[$i]["url1"] = $list[$i]["url"].":".$list[$i]["port"];
							}
						}
						else
						{
							$char5 = substr($list[$i]["url"],$int);
							$char6 = substr($list[$i]["url"],0,$int);
							$list[$i]["url1"] = $char6.":".$list[$i]["port"].$char5;
						}
					}
				}
				else
				{
					$list[$i]["url1"] = $list[$i]["url"].":".$list[$i]["port"];
				}
			}
			$list[$i]["memo"] = isset($attr["memo"])==true?$attr["memo"]:"";
			//cssClassのスタイル
			$list[$i]["cssClass"] = $i%2==0? "evenrowbg":"oddrowbg";			
			
			$cChild = $child[$i]->ChildNodes();
			$list[$i]["member"] = "false";
			
			//memberノードの取得
			for($j = 0;$j<count($cChild);$j++){

				//memberのノードは存在しない
				if($cChild[$j]->_nodeName!="member")
					continue 1;
				$list[$i]["member"] = "true";
				$list[$i]["group"] = "";
				$ccChild = $cChild[$j]->ChildNodes();

				//groupノードの取得
				for($k = 0;$k<count($ccChild);$k++){
					$ccAttr = $ccChild[$k]->Attribute();

					//k=0の場合
					if($k == 0)
						$list[$i]["group"] = isset($ccAttr["name"])==true?$ccAttr["name"]:"";
					//k>0の場合
					else{
						$list[$i]["group"].=isset($ccAttr["name"])==true?",".$ccAttr["name"]:"";
					}
				}
			}
		}		
		
		//defaultlinkノードの取得
		//新しい内容を増える
		$node = $this->objXml->GetElementsByTagName("defaultlink");
		$defaultlink = array();
		$child = $node[0]->ChildNodes();
		
		for($m = 0;$m<count($child);$m++){
			$defaultlink[$m]["_NODE_ID_"] = $child[$m]->NODE_ID_;
			$defaultlink[$m]["_nodename"] = $child[$m]->_nodeName;
			$attr = $child[$m]->Attribute();
			$defaultlink[$m]["dir"] = isset($attr["dir"])==true?"default":"";
			$defaultlink[$m]["url"] = isset($attr["url"])==true?$attr["url"]:"";
			$defaultlink[$m]["port"] = isset($attr["port"])==true?$attr["port"]:"";
			$defaultlink[$m]["mode"] = isset($attr["mode"])==true?$attr["mode"]:"";
			if(!$defaultlink[$m]["port"])
			{
				$defaultlink[$m]["url1"] = $defaultlink[$m]["url"];
			}
			else{
				if(stripos($defaultlink[$m]["url"],"/"))
				{
					$int = stripos($defaultlink[$m]["url"],"/");
					if($int != null)
					{
						$char = substr($defaultlink[$m]["url"],$int+1,1);
						if($char == "/")
						{
							$char1 = substr($defaultlink[$m]["url"],$int+2);
							if(stripos($char1,"/"))
							{
								$int1 =stripos($char1,"/");
								if($int1 != null)
								{
									$char2 = substr($char1,$int1);
									$char3 = substr($char1,0,$int1);
									$char4 = substr($defaultlink[$m]["url"],0,$int+2);
									$defaultlink[$m]["url1"] = $char4.$char3.":".$defaultlink[$m]["port"].$char2;
								}
							}
							else
							{
								$defaultlink[$m]["url1"] = $defaultlink[$m]["url"].":".$defaultlink[$m]["port"];
							}
						}
						else
						{
							$char5 = substr($defaultlink[$m]["url"],$int);
							$char6 = substr($defaultlink[$m]["url"],0,$int);
							$defaultlink[$m]["url1"] = $char6.":".$defaultlink[$m]["port"].$char5;
						}
					}
				}
				else
				{
					$defaultlink[$m]["url1"] = $defaultlink[$m]["url"].":".$defaultlink[$m]["port"];
				}
			}
			$defaultlink[$m]["memo"] = isset($attr["memo"])==true?$attr["memo"]:"";
			//cssClassのスタイル
			$defaultlink[$m]["cssClass"] = $i%2==0? "evenrowbg":"oddrowbg";			
			
			$cChild = $child[$m]->ChildNodes();
			$defaultlink[$m]["member"] = "false";
			
			//memberノードの取得
			for($j = 0;$j<count($cChild);$j++){

				//memberのノードは存在しない
				if($cChild[$j]->_nodeName!="member")
					continue 1;
				$defaultlink[$m]["member"] = "true";
				$defaultlink[$m]["group"] = "";
				$ccChild = $cChild[$j]->ChildNodes();

				//groupノードの取得
				for($k = 0;$k<count($ccChild);$k++){
					$ccAttr = $ccChild[$k]->Attribute();

					//k=0の場合
					if($k == 0)
						$defaultlink[$m]["group"] = isset($ccAttr["name"])==true?$ccAttr["name"]:"";
					//k>0の場合
					else{
						$defaultlink[$m]["group"].=isset($ccAttr["name"])==true?",".$ccAttr["name"]:"";
					}
				}
			}
			array_push($list, $defaultlink[0]);
		}		
		return $list;		
	}

	/**
	*関数名: deleteAccess
	*xmlファイル中にノードを削除する
	*
	* @param	string		$nodeName		　　ノードの名
	* @param	string		$dir				アクセスのpath
	* @param	string		$url				アクセスのurl
	* 
	* @return	void
	*/
	function deleteAccess($nodeName,$dir,$url){
		//ファイルのインポト
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);

		//ノードの取得
		$node = $this->objXml->GetElementsByTagName("linkset");
		$child = $node[0]->ChildNodes();

		//linkノードの取得
		for($i = 0;$i<count($child);$i++){
			$list[$i]["__NODE_ID__"] = $child[$i]->__NODE_ID__;
			$list[$i]["_nodename"] = $child[$i]->_nodeName;
			$attr = $child[$i]->Attribute();
			$list[$i]["dir"] = isset($attr["dir"])==true?$attr["dir"]:"false";
			$list[$i]["url"] = isset($attr["url"])==true?$attr["url"]:"false";
			$list[$i]["memo"] = isset($attr["memo"])==true?$attr["memo"]:"false";

			$cChild = $child[$i]->ChildNodes();

			//ノードを削除する
			if( $list[$i]["_nodename"] == $nodeName && 
				$list[$i]["dir"] == $dir && $list[$i]["url"] == $url ){
				$node[0]->RemoveChild($child[$i]);
			}
		}
		$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
	}
	
	function GetCurNode($dir)
	{
		$node = $this->objXml->GetElementsByTagName("linkset");
		$child = $node[0]->ChildNodes();
		for($i = 0;$i<count($child);$i++){
			$attr = $child[$i]->Attribute();
			if($attr["dir"]==$dir)
			{
				return $child[$i];
			}
		}
		//update for php5.3
		//$rNode =& new Node(NULL); 
		$rNode = new Node(NULL); 
		return $rNode;
	}
		
	// flag:before,after
	function SwapNode($dir,$flag)
	{
		if(strlen(trim($dir))==0 || ( $flag != "before"
			&& $flag != "after" ) )
			return null;
		
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		$curNode = $this->GetCurNode($dir);
				
		$curNode2 = new Node();
		$curNode2->MClone($curNode);
				
		if($curNode->IsNull()) return null;
				
		$pNode = $curNode->ParentNode();
		
		if($flag=="before"){
			$preNode = $curNode->PreviousSibling();
			$pNode->InsertBefore($curNode2,$preNode);
			$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
			
			$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
			$curNode = $this->GetCurNode($dir);
			$pNode = $curNode->ParentNode();
			$attrDelNode = $curNode->NextSibling()->NextSibling()->Attribute();
			if($attrDelNode["dir"]==$dir){
				$pNode->RemoveChild($curNode->NextSibling()->NextSibling());
			}
			
		}else if($flag=="after"){
			$lastNode = $curNode->NextSibling();
			$pNode->InsertAfter($curNode2, $lastNode);
			$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
			
			$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
			$curNode = $this->GetCurNode($dir);
			$pNode = $curNode->ParentNode();
			$attrDelNode = $curNode->NextSibling()->NextSibling()->Attribute();
			if($attrDelNode["dir"]==$dir){
				$pNode->RemoveChild($curNode);
			}
		}
		$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		return true;
	}
}
?>