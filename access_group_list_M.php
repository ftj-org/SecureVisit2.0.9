<?php
/** 
 * ePassND1000のアクセス権限設定マッピング一覧ファイル
 *
 * @author  張新星


 * @since   2007-09-24
 * @version 1.0
 */

// 指定されたファイルを読み込む
$G_APPPATH_M = explode("mdl",__FILE__);
require_once $G_APPPATH_M[0]."/lib/db.php";
require_once $G_APPPATH_M[0]."/lib/log.php";
require_once $G_APPPATH_M[0]."/lib/pageview.php";
require_once $G_APPPATH_M[0]."/lib/xmlread.php";


class Access_group_list_M
{
	/**
	* データベースの実例.
	*/
	var $objDb = null;
	
	/**
	* 関数名: __construct
	* コンストラクタ.
	* 
	*/
	function __construct(){
		try{
			$this->objDb = new DB_Pgsql();
			$this->objXml = new Node();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"実例化");
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: Access_group_list_M
	* コンストラクタ.
	* 
	*/
	function Access_group_list_M(){
		try{
			$this->__construct();
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}

        /**
        * 関数名: escapeStringForQuery
        *クエリに使う文字列をエスケープする.
        *
        * @param                string          $data                エスケープするテキスト文字列.
        *
        * @return              エスケープされたデータを文字列で返します.
        */
        function escapeStringForQuery( $data ){
                return $this->objDb->SqlEscapeString($data);
        }
	
	/**
	* 関数名: readGroupList
	* 条件によってデータを検索して、データのリストを作る.
	* 
	* @param		string		$orderBy		ソーティングする条件.
	* @param		string		$condition		検索の条件.
	* @param		string		$offset			オフセット.
	* @param		string		$pageNum		ページの表示データの件数.
	*
	* @return		Array		$list			データのリスト.
	*/
	function readGroupList($orderBy,$condition,$offset,$pageNum)
	{
		try{
			// ページビューのインスタンス化


			$pageObj = new PageView("sv_group",$pageNum,$offset,$this->objDb);
			$pageObj->SetCondition($condition,$orderBy);
			$res = $pageObj->ReadList();
			$resList=$pageObj->MakePage();
			
			$cssFlag = true;
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$condition.$orderBy");
			
			// ループでリストを表示する.
			if(!is_array($res)) return array($resList,$res);
			foreach( $res as $key => $value)
			{
				
				// css仕様を設定する.
				if( $cssFlag ) 
					$res[$key]["css_class"] = "evenrowbg";
				else 
					$res[$key]["css_class"] = "oddrowbg";
				
				$cssFlag = $cssFlag==true?false:true;
			}
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$resList.$res");
			return array($resList,$res);

		}
		catch(exception $e)
		{
			ToLog::ToLogs($ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	/**
	* 関数名: deleteGroup
	* 指定したユーザーを削除する.
	* 
	* @param		string		$groupName		グループ名.
	*
	* @return		void.
	*/
	function deleteGroup( $groupName ){
		try
		{
			$this->objDb->begin();
			$strSql4 = " SELECT COUNT(id)";
			$strSql4.= " FROM sv_group";
			$strSql4.= " WHERE name = '".$groupName."'";
			
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql4");
			$res2 = $this->objDb->ExecuteCommand($strSql4);
			if($res2[0] != 0)
			{
				$strSql  = " SELECT sv_group.id";
				$strSql .= " FROM   sv_group";
				$strSql .= " WHERE name='".$groupName."'";
				
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql");
				$res1 = $this->objDb->ExecuteCommand($strSql);
							
				$strSql2  = " DELETE ";
				$strSql2 .= " FROM sv_group ";
				$strSql2 .= " WHERE name = '".$groupName."'";
				
				$strSql3  = " DELETE ";
				$strSql3 .= " FROM sv_usergroup ";
				$strSql3 .= " WHERE sv_usergroup.grpid = '".$res1["id"]."'";
				
				
				$this->objXml->LoadXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);
				
				$node = $this->objXml->GetElementsByTagName("linkset");
				$child = $node[0]->ChildNodes();
				for($i = 0;$i<count($child);$i++)
				{
					$cChild = $child[$i]->ChildNodes();
						
					for($j = 0;$j<count($cChild);$j++)
					{
						if($cChild[$j]->_nodeName==="member")
						{
							$ccChild = $cChild[$j]->ChildNodes();
							for($k = 0;$k<count($ccChild);$k++)
							{
								$ccAttr = $ccChild[$k]->Attribute();
								if($ccAttr["name"]===$groupName)
								{  
									if(count($ccChild)==1)
									{
									  $child[$i]->RemoveChild($cChild[$j]);
									}else
									{	
										$cChild[$j]->RemoveChild($ccChild[$k]);
									}
									$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	
										
								}
								else
								{
									continue;
								}		
							}
						}
						else
						{
							continue;
						}
					}
					
				}
				$defaultnode = $this->objXml->GetElementsByTagName("defaultlink");
				$defaultchild = $defaultnode[0]->ChildNodes();
			
				for($m = 0;$m<count($defaultchild);$m++)
				{
					$defaultcChild = $defaultchild[$m]->ChildNodes();
					for($x = 0;$x<count($defaultcChild);$x++)
					{
						if($defaultcChild[$x]->_nodeName==="member")
						{
							$defaultccChild = $defaultcChild[$x]->ChildNodes();
							for($y= 0;$y<count($defaultccChild);$y++)
							{
								$defaultccAttr = $defaultccChild[$y]->Attribute();
								if($defaultccAttr["name"]===$groupName)
								{
									if(count($defaultccChild)==1)
									{
										$defaultchild[$m]->RemoveChild($defaultcChild[$x]);
									}
									else
									{	
										$defaultcChild[$x]->RemoveChild($defaultccChild[$y]);
									}
									$this->objXml->SaveXml(G_HOME_CONFIG_PATH.G_HOME_CONFIG_NAME);	
								}
								else
								{
									continue;
								}
							}
						}
						else
						{
							continue;
						}
					}
				}
			
				$this->objDb->ExecuteNonQuery($strSql2);
				$this->objDb->ExecuteNonQuery($strSql3);
				// DEBUG メッセージ
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql2");
				ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"$strSql3");
			}
			else
			{
				return ;
			}
			$this->objDb->commit();
		}
		catch( Exception $e ){
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			$this->objDb->rollback();
			throw $e;
		}
	}
}
?>
