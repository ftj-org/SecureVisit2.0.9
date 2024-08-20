<?php
/**
 *　ePassND1000のSSL証明書ファイル
 *
 *　@author  張新星 *　@since 　2007-09-07
 *　@version 1.0
 */

//指定されたファイルを読み込む
$G_APPPATH = explode("mdl",__FILE__);
require_once($G_APPPATH[0]."lib/log.php");
require_once($G_APPPATH[0]."lib/xmlread.php");


/**
*クラス名:Server_ssl_M
*/
class Server_ssl_M{
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
	function Server_ssl_M()
	{
		try{
			$this->__construct();
		}catch(exception $e){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: readSSL
	* xmlファイル中にSSLの内容を読み出す.
	* 
	*
	* @return    Array       $list      配列.
	*/
	function readSSL(){
		$list = array();
		$path = null;
		//ファイルのインポト.
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);		
		//ノードの取得.
		$node = $this->objXml->GetElementsByTagName("ssl");
		//ノードが存在しない場合NO_NODに返す.
		//ノードの属性を取得する.
		$arrAttrs = $node[0]->Attribute();
		$path = trim($arrAttrs["path"]);
		$ca=trim($arrAttrs["ca"]);
		$password = trim($arrAttrs["password"])==null?"":trim($arrAttrs["password"]);
		$crl=trim($arrAttrs["crl"]);
		$enable = trim($arrAttrs["enable"])==null?"":trim($arrAttrs["enable"]);
		$list["spwd"] = $password;
		$list["enable"] = $enable;
		$list["clientdepth"]=$arrAttrs["clientdepth"];
		$list["ciphers"]=$arrAttrs["ciphers"];
		$list["clientauth"]=$arrAttrs["clientauth"];
		//ファイル存在の場合、かつファイルが読み取りの場合

		if(is_readable($path) && is_file($path) )
		{
			$list["scer"] = @file_get_contents($path);
			$list["pathsatae"]="ok";
		}else
		{
			 $list["pathsatae"]=null;
		}
		if(is_readable($ca) && is_file($ca))
			{
			$list["ca"]=@file_get_contents($ca);
			$list["casatae"]="ok";
		}
		else
		{
			$list["casatae"]=null;
		}
		if(is_readable($crl) && is_file($crl))
		{
			$list["crl"]=@file_get_contents($crl);
			$list["crlsatae"]="ok";
		}
		else
		{
			$list["crlsatae"]=null;
		}
		return $list;
	}
	
	/**
	* 関数名: setSSL
	* xmlファイル中にSSLの更新.
	* 
	* @param     Array			$arrSSL
	*
	* @return    Array			$list      配列.
	*/
	function setSSL($arrSSL,$strAccess,$strClientauth,$strClientdepth,$strCiphers){
		
		//ファイルのインポト
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	
		//ノードの取得
		$node = $this->objXml->GetElementsByTagName("ssl");
		$path = null;
		
		// ノードの属性を取得する.
		$arrAttrs = $node[0]->Attribute();
		$path = trim($arrAttrs["path"]);
		$ca =trim($arrAttrs["ca"]);
		$crl=trim($arrAttrs["crl"]);
		$node[0]->SetAttribute("password",$arrSSL["password"]);
		$node[0]->SetAttribute("clientdepth",$strClientdepth);
		$node[0]->SetAttribute("ciphers",$strCiphers);
		if($strAccess == "active")
		$arrSSL["enable"] = 1;
		if($strAccess == "inactive")
		$arrSSL["enable"] = 0;
		$node[0]->SetAttribute("enable",$arrSSL["enable"]);
		if($strClientauth==0)
		$arrSSL["clientauth"]=0;
		if($strClientauth==1)
		$arrSSL["clientauth"]=1;
		if($strClientauth==2)
		$arrSSL["clientauth"]=2;
		if($strClientauth==3)
		$arrSSL["clientauth"]=3;
		$node[0]->SetAttribute("clientauth",$arrSSL["clientauth"]);
		// xmlファイルの保存
		$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		//更新した内容がヌルではない、かつファイルのパスが有効の場合、該当ファイルを更新あるいは作成する.
		if( is_writable($path) && is_file($path)){
			@file_put_contents($path,$arrSSL["scer"]);
		}
		
		if( is_writable($ca) && is_file($ca)){
			@file_put_contents($ca,$arrSSL["ca"]);
		}
		
		if( is_writable($crl) && is_file($crl)){
			@file_put_contents($crl,$arrSSL["crl"]);
		}
						
	}
}
?>
