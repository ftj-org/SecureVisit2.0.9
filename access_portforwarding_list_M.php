<?php
/**
 * ePass1000NDのポートフォワーディング一覧
 * @author 呉艶秋
 * @since  2008-12-01
 * @version 1.0 
*/

//指定されたファイルを読み込む
$G_APPPATH_M = explode("mdl",__FILE__);
require_once $G_APPPATH_M[0]."/lib/log.php";
require_once $G_APPPATH_M[0]."/lib/xmlread.php";

/**
*クラス名:Access_portforwarding_list_M
*/
class Access_portforwarding_list_M
{
   /**
    * ノードの実例.
	*/
	var $objXml = null;
	
   /**
	* 関数名: __construct
	* コンストラクタ.
	* 
	*/
	function __construct()
	{
		try
		{
			$this->objXml = new Node();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例化");
		}
		catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
   /**
	*関数名: Access_portforwarding_list_M
	* コンストラクタ
	*
	*/
	function Access_portforwarding_list_M()
	{
		try
		{
			$this->__construct();
		}
		catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
   /**
	* 関数名: readTableList
	* xmlファイル中にデータのリストを作る.
	* @return    Array       $list     配列.
	*/
	function readTableList()
	{
		//ファイルのインポト
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		//ノードの取得		
		$node = $this->objXml->GetElementsByTagName("portforwarding");
		$list = array();
		$child = $node[0]->ChildNodes();
		for($i = 0;$i<count($child);$i++)
		{
			$attr = $child[$i]->Attribute();
			$list[$i]["type"] = isset($attr["type"])==true?$attr["type"]:"";
			$list[$i]["localport"] = isset($attr["localport"])==true?$attr["localport"]:"";
			$list[$i]["remoteip"] = isset($attr["remoteip"])==true?$attr["remoteip"]:"";
			$list[$i]["remoteport"] = isset($attr["remoteport"])==true?$attr["remoteport"]:"";
			$list[$i]["concurrent"] = isset($attr["concurrent"])==true?$attr["concurrent"]:"";
		
		
			//cssClassのスタイル
			$list[$i]["cssClass"] = $i%2==0? "evenrowbg":"oddrowbg";
			
			$cChild = $child[$i]->ChildNodes();
			$list[$i]["member"] = "false";
			for($j = 0;$j<count($cChild);$j++)
			{
				//memberのノードは存在しない					
				if($cChild[$j]->_nodeName!="member")
				continue 1;
				$list[$i]["member"] = "true";
				$list[$i]["group"] = "";
				$ccChild = $cChild[$j]->ChildNodes();
				for($k = 0;$k<count($ccChild);$k++)
				{
					$ccAttr = $ccChild[$k]->Attribute();

					//k=0の場合
					if($k == 0)
					$list[$i]["group"] = isset($ccAttr["name"])==true?$ccAttr["name"]:"";
					//k>0の場合
					else
					{
						$list[$i]["group"].=isset($ccAttr["name"])==true?",".$ccAttr["name"]:"";
					}
				}
			}
		}
		return $list;
	}
	
   /**
	*関数名: deleteAccess
	*xmlファイル中にノードを削除する
	* @param	string	$nodeName ノードの名
	* @return	void
	*/
	function deleteAccess($nodeName)
	{
		//ファイルのインポト
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		//ノードの取得
		$node = $this->objXml->GetElementsByTagName("portforwarding");
		$child = $node[0]->ChildNodes();
		//linkノードの取得	
		for($i = 0;$i<count($child);$i++)
		{
			$attr = $child[$i]->Attribute();
			$list[$i]["localport"] =$attr["localport"];
			//ノードを削除する
			if( $list[$i]["localport"] == $nodeName)
			{
				$node[0]->RemoveChild($child[$i]);
			}
		}
		$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
	}
 }
?>