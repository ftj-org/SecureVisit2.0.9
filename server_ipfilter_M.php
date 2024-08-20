<?php
/**
 * epass1000NDのサーバー設定IPフィルタ.
 *
 * @author 文傑.
 * @since  2007-09-17
 * @version 1.0
 */
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/xmlread.php";

 
class Server_ipfilter_M{
	
	/**
	* データベースの実例.
	*/
	var $objXml = null;
	
	/**
	* 関数名: __construct
	* コンストラクタ.
	* 
	*/
	function __construct()
	{
		try{
			$this->objXml = new Node();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例");
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->GetMessage());
			exit;
		}
	}
	 
	/**
	* 関数名: Server_ipfilter_M
	* コンストラクタ.
	* 
	*/
	function Server_ipfilter_M(){		
		try{
			$this->__construct();
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
 
 
 	/**
	* 関数名:  readIPFilter
	* xmlファイルの内容を読みだす.
	* 
	* @param		string		$userId			ユーザーID.
	*
	* @return		Array		$res			ユーザーデータ.
	*/

	function readIPFilter()
	{		
		try{
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"readIPFilter begin");
			//ファイルをインポートしノードを取得する																		
			$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);																				
																							
			//ノードの属性を取得する

			//  $nodewhite      whitelist
			//  $nodeblack      blacklist 
																																								
			$nodewhite = $this->objXml->GetElementsByTagName("whitelist");	
			$nodeblack = $this->objXml->GetElementsByTagName("blacklist");																				
			
			// list 属性
			$attrwhite = $nodewhite[0]->Attribute();
			$attrblack = $nodeblack[0]->Attribute();				
			
			//ホワイトリストを有効にする
			if( $attrwhite["enable"]=="true")
			{
				$arrEnable[0]="checked";
			}
			else
			{
				$arrEnable[0]="";
			}
			
			//ブラックリストを有効にする
			if( $attrblack["enable"]=="true")
			{
				$arrEnable[1]="checked";
			}
			else
			{
				$arrEnable[1]="";
			}
			
			//ホワイトリスト

			if( $attrwhite["auth"]=="must")
			{
				//ホワイトリストのみ許可する
				$arrEnable[2] = 0; 
			}
			elseif($attrwhite["auth"]=="none")
			{
				//ホワイトリストの認証しない
				$arrEnable[2] = 1; 
			}
			else{
				$arrEnable[2] = 2; 
			}
			
			//ipリスト


			$whitelist = array();
			$blacklist = array();
			
			//ホワイトリストの属性のノードを読みだす.
			//	$childwhite = host   $whitelist 属性
			$childwhite = $nodewhite[0]->ChildNodes();																	
			for($i = 0;$i<count($childwhite);$i++)
			{				
				$attr = $childwhite[$i]->Attribute();
				if( isset($attr["ip"]) && $attr["ip"] != "")
				{
					$whitelist[$attr["ip"]] = $attr["ip"];
				}
				else if( isset($attr["ipstart"]) && $attr["ipstart"] != "")
				{
					$whitelist[ $attr["ipstart"]."-".$attr["ipend"]] = $attr["ipstart"]."-".$attr["ipend"];
				}
				else if( isset($attr["ips"]) && $attr["ips"] != "")
				{
					$whitelist[$attr["ips"]] = $attr["ips"];
				}							
			}																													
													
			//ブラックリストの属性のノードを読みだす.
			$childblack = $nodeblack[0]->ChildNodes();																	
			for($i = 0;$i<count($childblack);$i++)
			{	
				$attr = $childblack[$i]->Attribute();
				if( isset($attr["ip"]) && $attr["ip"] != "")
				{
					$blacklist[$attr["ip"]] = $attr["ip"];
				}
				else if( isset($attr["ipstart"]) && $attr["ipstart"] != "")
				{
					$blacklist[ $attr["ipstart"]."-".$attr["ipend"]] = $attr["ipstart"]."-".$attr["ipend"];
				}
				else if( isset($attr["ips"]) && $attr["ips"] != "")
				{
					$blacklist[$attr["ips"]] = $attr["ips"];
				}							
			}																													
																			
			//ホワイトリスト、ブラックリストを表示する.
			return Array($whitelist,$blacklist,$arrEnable);	
													
 		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
 
 	/**
	* 関数名: updateIPFilter
	* ユーザの情報を更新する.
	* 
	* @param		string				ユーザーデータ.
	*
	* @return		void.
	*/
	function updateIPFilter($arrWhiteList,$arrBlackList,$strWhiteSelect,$strWhiteEnable,$strBlackEnable){
		try{
			
			
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"updateIPFilter begin");
			//ファイルのインプット.


			$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);

			$nodewhite = $this->objXml->GetElementsByTagName("whitelist");
			$nodeblack = $this->objXml->GetElementsByTagName("blacklist");
			
			$childwhite = $nodewhite[0]->ChildNodes();		
					
			//ホワイトリストの属性を更新する.
			$nodewhite[0]->setAttribute("enable",$strWhiteEnable);
			$nodewhite[0]->setAttribute("auth",$strWhiteSelect);	
			
			//ブラックリストの属性を更新する.
			$nodeblack[0]->setAttribute("enable",$strBlackEnable);			
			

			//ホワイトリストのホストを削除する.
			$childwhite = $nodewhite[0]->ChildNodes();
			for($i = 0;$i<count($childwhite);$i++)
			{
				$nodewhite[0]->RemoveChild($childwhite[0]);
			}
			//ブラックリストのホストを削除する.
			$childblack = $nodeblack[0]->ChildNodes();
			for($i = 0;$i<count($childblack);$i++)
			{
				$nodeblack[0]->RemoveChild($childblack[0]);
			}
			
			//削除を実行する.
			$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	
	
			//ホワイトリストを更新する.
			for($i = 0;$i<count($arrWhiteList);$i++)
			{
				$nodewhitehost	= new Node();
				$nodewhitehost->setNodeName("host");
				
				$arrCheckIp = ips2ip( $arrWhiteList[$i] );
				
				if($arrCheckIp[0] == "ip")
				{				
					$nodewhitehost->setAttribute("ip",$arrCheckIp[1]);
				}
				
				else if($arrCheckIp[0] == "ips")
				{
					$nodewhitehost->setAttribute("ipstart",$arrCheckIp[1]);
					$nodewhitehost->setAttribute("ipend",$arrCheckIp[2]);
				}
				
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"whitelist save");
				$nodewhite[0]->AppendChild($nodewhitehost);
				unset($nodewhitehost);
			}
			//更新したホワイトリストをXMLに書き込む.
			$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	

			//ブラックリストを更新する.


			for($i = 0;$i<count($arrBlackList);$i++)
			{
				$nodeblackhost	= new Node();
				$nodeblackhost->setNodeName("host");

				$arrCheckIp = ips2ip( $arrBlackList[$i] );
				
				if($arrCheckIp[0] == "ip")
				{				
					$nodeblackhost->setAttribute("ip",$arrCheckIp[1]);
				}
				
				else if($arrCheckIp[0] == "ips")
				{
					$nodeblackhost->setAttribute("ipstart",$arrCheckIp[1]);
					$nodeblackhost->setAttribute("ipend",$arrCheckIp[2]);
				}
				
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"blacklist save");
				$nodeblack[0]->AppendChild($nodeblackhost);
				unset($nodeblackhost);
			}
			//更新したブラックリストをXMLに書き込む.
			$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	

 		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			return false;
			throw $e;
		}
	}
}

function ips2ip( $ips )
{
	$rezult = array();
	$arrIps = explode("-", $ips);
	if( count($arrIps) == 1 )
	{
		$arrIps[0] = iptoint($arrIps[0]);
		if( isip( $arrIps[0] )){
			array_push($rezult, "ip");
			array_push($rezult, $arrIps[0]);
			return $rezult;
		/*}else if (isip( $arrIps[0] )) {
			array_push($rezult, "ips");
			array_push($rezult, str_replace("*","1",$arrIps[0]));
			array_push($rezult, str_replace("*","255",$arrIps[0]));
			return $rezult;*/	
		}else{
			array_push($rezult, "noip");
			return $rezult;
		}
	}else if ( count($arrIps) == 2 ){
		$resFirst = ips2ip( iptoint($arrIps[0]) );
		$resSecond = ips2ip( iptoint($arrIps[1]) );		
		if ($resFirst[0] == "noip"  || $resSecond[0] == "noip" )
		{
			array_push($rezult, "noip");
			return $rezult;
		}	
		array_push($rezult, "ips");
		array_push($rezult, $resFirst[1]);		
		if ( $resSecond[0] == "ips" )
		{
			array_push($rezult, $resSecond[2]);
			return $rezult;
		}
		array_push($rezult, $resSecond[1]);
		return $rezult;			
	}
	array_push($rezult, "noip");
	return $rezult;	
}

function isip( $ip )
{
	$arrIp = explode(".", $ip);
	return count($arrIp) == 4?(ipCheck($arrIp[0]) && ipCheck($arrIp[1]) && ipCheck($arrIp[2]) && ipCheck($arrIp[3])):false;
}

function ipCheck( $string )
{
	return ($string >= 0 && $string <= 255);
}

function iptoint( $ip )
{
	$arrIp = explode(".", $ip);
	$strip=array();
	foreach( $arrIp as $key => $value)
		$strip[$key] =intval($value);
	return implode(".",$strip);
}

?>