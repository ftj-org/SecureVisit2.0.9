<?php
/**
 * ePassND1000のサーバー設定
 *
 * @author  張榕
 * @since   2008-02-27
 * @version 1.0
 */


// 指定されたファイルを読み込む
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/xmlread.php";
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";


class Server_config_M
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
	* 関数名: Server_config_M
	* コンストラクタ.
	* 
	*/
	function Server_config_M()
	{
		try{
			$this->__construct();
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->GetMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: readSite
	* ノードの取得
	* 
	* @return Array   $List
	*/
	function readSite()
	{
		try{
			$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	
			$list = array();			
			$node = $this->objXml->GetElementsByTagName("site");			
			$child  = $node[0]->Attribute();	
			$list["ip"] = $child["ip"];			
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("site");			
			$child  = $node[0]->Attribute();			
			$list["port"] = $child["port"];
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("site");			
			$child  = $node[0]->Attribute();			
			$list["defdomain"]=$child["defdomain"];
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("keyid");			
			$child  = $node[0]->Attribute();			
			$list["live"]=$child["live"];
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("keyid");			
			$child  = $node[0]->Attribute();			
			$list["delaytimeout"]=$child["delaytimeout"];
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("authenticate");			
			$child  = $node[0]->Attribute();
			if($child["inputuserid"]=="")
			{
			    $list["inputuserid"]="false";
			}			
			else
			{
				$list["inputuserid"]=$child["inputuserid"];
			}	
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("pwdauth");				
			$child  = $node[0]->Attribute();	
			if($child["enable"]=="")
			{
				$list["enable"]="false";
			}			
			else
			{
				$list["enable"]=$child["enable"];
			}		
			
			//otp ノードの取得.
			$node = $this->objXml->GetElementsByTagName("otpauth");				
			$child  = $node[0]->Attribute();	
			if($child["enable"]=="")
			{
				$list["otpenable"]="false";
			}			
			else
			{
				$list["otpenable"]=$child["enable"];
			}		
			
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("dos");			
			$child  = $node[0]->Attribute();
			if($child["times"]=="")
			{
			$list["times"]=10000;
			}			
			else{
					$list["times"]=$child["times"];
				}
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("dos");			
			$child  = $node[0]->Attribute();
			if($child["interval"]=="")
			{
				$list["interval"]=60;
			}
			else{
					$list["interval"]=$child["interval"];
			}
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("dos");			
			$child  = $node[0]->Attribute();
			if($child["damp"]=="")
			{
				$list["damp"]=10;
			}
			else{
					 
				$list["damp"]=$child["damp"];
			}
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("authenticate");			
			$child  = $node[0]->Attribute();
			if($child["cacheuserid"]=="")
			{     
				$list["cacheuserid"]="false";
			}
			else
			{
				$list["cacheuserid"]=$child["cacheuserid"];
			}
			//ノードの取得.
			$node = $this->objXml->GetElementsByTagName("http");
			$child  = $node[0]->Attribute();
			if($child["logip"]=="" || $child["logip"]==0)
			{
				$list["logip"]=0;
			}
			if($child["logip"]==1)
			{
				$list["logip"]=1;
			}
			// rescuepwd & rescueretryの取得.
			$node = $this->objXml->GetElementsByTagName("keyid");
			$child  = $node[0]->Attribute();
			$list["rescuepwd"]= $child["rescuepwd"];
			$list["rescueretry"]= $child["rescueretry"];
			
			// 端末設定情報の取得.
			$node = $this->objXml->GetElementsByTagName("keyid");

			$child  = $node[0]->Attribute();
			$list["termlimit"]= $child["termlimit"];
			$list["termretry"]= $child["termretry"];
			$list["termvalue"]= $child["termvalue"];
			
			return $list;
		}
		catch( Exception $e )
		{
			// エラーメッセージを表示する.
			ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
			exit;
		}
	}
	
	//属性の更新.
	function insertNode($arrlink)
	{
		try{			
			$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);			
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("site");			
			$node[0]->setAttribute("ip",iptoint($arrlink["ip"]));	
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("site");			
			$node[0]->setAttribute("port",$arrlink["port"]);			
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("site");			
			$node[0]->setAttribute("defdomain",$arrlink["defdomain"]);
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("keyid");			
			$node[0]->setAttribute("live",$arrlink["live"]);
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("keyid");			
			$node[0]->setAttribute("delaytimeout",$arrlink["delaytimeout"]);
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("authenticate");			
			$node[0]->setAttribute("inputuserid",$arrlink["inputuserid"]);
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("authenticate");			
			$node[0]->setAttribute("cacheuserid",$arrlink["cacheuserid"]);
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("pwdauth");			
			$node[0]->setAttribute("enable",$arrlink["enable"]);
			//otp 属性の更新.
			$node = $this->objXml->GetElementsByTagName("otpauth");			
			$node[0]->setAttribute("enable",$arrlink["otpenable"]);
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("dos");			
			$node[0]->setAttribute("times",$arrlink["times"]);			
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("dos");		
			$node[0]->setAttribute("interval",$arrlink["interval"]);
			//属性の更新.
			$node = $this->objXml->GetElementsByTagName("dos");		
			$node[0]->setAttribute("damp",$arrlink["damp"]);
			//logipの更新.
			$node=$this->objXml->GetElementsByTagName("http");
			$node[0]->setAttribute("logip",$arrlink["logip"]);
		    // rescuepwdの更新
			$node=$this->objXml->GetElementsByTagName("keyid");
			$node[0]->setAttribute("rescuepwd",$arrlink["repwd"]);
			// rescueretryの更新
			$node=$this->objXml->GetElementsByTagName("keyid");
			$node[0]->setAttribute("rescueretry",$arrlink["retimes"]);
			// 端末の更新
			$node=$this->objXml->GetElementsByTagName("keyid");
			$node[0]->setAttribute("termlimit",$arrlink["termlimit"]);
			// 端末の更新
			$node=$this->objXml->GetElementsByTagName("keyid");
			$node[0]->setAttribute("termretry",$arrlink["termretry"]);
			
			// 端末の更新
			$node=$this->objXml->GetElementsByTagName("keyid");
			$node[0]->setAttribute("termvalue",$arrlink["termvalue"]);
			//更新したブラックリストをXMLに書き込む.
			$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		}
		
		catch( Exception $e )
		{	// エラーメッセージを表示する.
			ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
			return false;
			exit;
		}
	}	
}

//IPをチェックする
function isip( $ip )
{
	$arrIp = explode(".", $ip);
	return count($arrIp) == 4?(ipCheck($arrIp[0]) && ipCheck($arrIp[1]) && ipCheck($arrIp[2]) && ipCheck($arrIp[3])):false;
}
function iptoint( $ip )
{
	$arrIp = explode(".", $ip);
	$strip=array();
	foreach( $arrIp as $key => $value)
		$strip[$key] =intval($value);
	return implode(".",$strip);
}
function ipCheck( $string )
{
return (trim($string) >= 0 && trim($string) <= 255) || trim($string) == "*";
}

?>