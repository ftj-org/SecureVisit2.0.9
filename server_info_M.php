<?php
/**
 * epass1000NDの属性一覧ファイル.
 *
 * @author 呉艶秋.
 * @since  2008-12-29
 * @version 1.0
 */

//指定されたファイルを読み込む
$G_APPPATH = explode("mdl",__FILE__);
require_once($G_APPPATH[0]."lib/log.php");
require_once($G_APPPATH[0]."lib/xmlread.php");
require_once $G_APPPATH[0]."lib/db.php";


/**
*クラス名:server_info_M
*/
class server_info_M{
	/**
	* ノードの実例.
	*/
	var $objXml = null;
	var $objDb = null;
	/**
	* 関数名: __construct
	* コンストラクタ.
	* 
	*/
	function __construct(){
		try
		{
			$this->objDb = new DB_Pgsql();
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
	*関数名: server_info_M
	* コンストラクタ
	*
	*/
	function server_info_M()
	{
		try
		{
			$this->__construct();
		}catch(exception $e)
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: readSSL
	* xmlファイル中にSSLの内容を読み出す. 
	* @return  void
	*/
	function readSSL(){
		$path = null;
		//ファイルのインポト.
		$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);		
		//ノードの取得.
		$node = $this->objXml->GetElementsByTagName("ssl");
		//ノードが存在しない場合NO_NODに返す.
		//ノードの属性を取得する.
		$arrAttrs = $node[0]->Attribute();
		//ファイル存在の場合、かつファイルが読み取りの場合
		
		//pathのパスを読み取る
		$path = trim($arrAttrs["path"]);
		//caのパスを読み取る
		$ca=trim($arrAttrs["ca"]);
		//crlのパスを読み取る
		$crl=trim($arrAttrs["crl"]);
		
		//configファイルを文字列に読み込む
		$strConfig=@file_get_contents(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
		//pathが正しい時
		if(is_readable($path) && is_file($path) )
		{
			//ファイルを文字列に読み込む
			$strPath = @file_get_contents($path);
		}
		if(is_readable($ca) && is_file($ca))
		{
			$strCa = @file_get_contents($ca);
		}
		if(is_readable($crl) && is_file($crl))
		{
			$strCrl = @file_get_contents($crl);
		}
		
		// configテーブルにデータがあるかを判断する
		$strSelectSQL1 = "select attr from sv_config where id =1";
		$strSelectSQL2 = "select attr from sv_config where id =2";
		$strSelectSQL3 = "select attr from sv_config where id =3";
		$strSelectSQL4 = "select attr from sv_config where id =4";
		$res1Count = $this->objDb->ExecuteCommand($strSelectSQL1);
		$res2Count = $this->objDb->ExecuteCommand($strSelectSQL2);
		$res3Count = $this->objDb->ExecuteCommand($strSelectSQL3);
		$res4Count = $this->objDb->ExecuteCommand($strSelectSQL4);
		// データがない場合、文字列にDBにインサーとする
		if($res1Count[0] == '')
		{
			$strInsertSQL1 = "insert into sv_config(id,attr)values(1,'".$strConfig."')";
			
			$this->objDb->ExecuteNonQuery($strInsertSQL1);
			
		}
		// データがある場合
		else
		{
			// 二つの文字列が一致しない場合
			if($res1Count[0] != $strConfig)
			{
				// 更新する
				$strUpdateSQL1 = "update sv_config set attr = '".$strConfig."' where id = 1 ";
				$this->objDb->ExecuteNonQuery($strUpdateSQL1);
			}
		}
		if($res2Count[0] == '')
		{
			$strInsertSQL2 = "insert into sv_config(id,attr)values(2,'".$strCa."')";
			$this->objDb->ExecuteNonQuery($strInsertSQL2);
		}
		else
		{
			if($res2Count[0] != $strCa)
			{
				$strUpdateSQL2 = "update sv_config set attr = '".$strCa."' where id = 2 ";
				$this->objDb->ExecuteNonQuery($strUpdateSQL2);
			}
		}
		if($res3Count[0] == '')
		{
			$strInsertSQL3 = "insert into sv_config(id,attr)values(3,'".$strCrl."')";
			$this->objDb->ExecuteNonQuery($strInsertSQL3);
		}
		else
		{
			if($res3Count[0] != $strCrl)
			{
				$strUpdateSQL3 = "update sv_config set attr = '".$strCrl."' where id = 3 ";
				$this->objDb->ExecuteNonQuery($strUpdateSQL3);
			}
		}
		if($res4Count[0] == '')
		{
			$strInsertSQL4 = "insert into sv_config(id,attr)values(4,'".$strPath."')";
			$this->objDb->ExecuteNonQuery($strInsertSQL4);
		}
		else
		{
			if($res4Count[0] != $strPath)
			{
				$strUpdateSQL4 = "update sv_config set attr = '".$strPath."' where id = 4 ";
				$this->objDb->ExecuteNonQuery($strUpdateSQL4);
			}
		}
		
	}
	
}
?>
