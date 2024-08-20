<?php
/**
 * epass1000NDのDBVIEWファイル.
 *
 * @author	呉暁挙.
 * @since	2007-09-03
 * @version	1.0
 */
$G_APPPATH = explode("mdl",__FILE__);
require_once $G_APPPATH[0]."lib/log.php";
require_once $G_APPPATH[0]."lib/db.php";
require_once $G_APPPATH[0]."mdl/user_list_M.php";
require_once $G_APPPATH[0]."mdl/log_list_M.php";
require_once $G_APPPATH[0]."mdl/log_search1_M.php";
require_once $G_APPPATH[0]."mdl/log_search2_M.php";

class DbView{
	
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
		}
		catch( Exception $e ){
			throw $e;
			return;
		}
	}
	
	/**
	* 関数名: DbView
	* コンストラクタ.
	* 
	*/
	function DbView(){		
		try{
			$this->__construct();			
		}
		catch( Exception $e ){
			throw $e;
		}
	}
	
	/**
	* 関数名: DbView
	* コンストラクタ.
	* 
	*/
	function DbCDView(){		
		try{
			$this->dropview();	
		}
		catch( Exception $e ){
			throw $e;
			return;
		}
		
		try{
			$this->createview();	
		}
		catch( Exception $e ){
			throw $e;
			return;
		}
	}
	
	/**
	* 関数名: dropview
	* drop view.
	* 
	*/
	function dropview(){		
		try{
			$this->objDb->begin();
			$strSql  = " DROP VIEW userview";
			$strSql2  = " DROP VIEW logview";
			$strSql3  = " DROP VIEW logsoneview";
			$strSql4  = " DROP VIEW loglistview";
			try{
				@$this->objDb->ExecuteNonQuery($strSql);
			}catch( Exception $e ){}
			try{
				@$this->objDb->ExecuteNonQuery($strSql2);
			}catch( Exception $e ){}
			try{
				@$this->objDb->ExecuteNonQuery($strSql3);
			}catch( Exception $e ){}
			try{
				@$this->objDb->ExecuteNonQuery($strSql4);
			}catch( Exception $e ){}
			$this->objDb->commit();
		}
		catch( Exception $e ){
			$this->objDb->rollback();
			throw $e;
		}
	}
	
	/**
	* 関数名: createview
	*/
	function createview(){
		try{
			$userObj = new User_list_M();
			$strsql = " CREATE VIEW userview AS ".$userObj->viewCreateSql;
			//unset($userObj);
			
			$userObj2 = new Log_list_M();
			$strsql2 = " CREATE VIEW loglistview AS ".$userObj2->viewCreateSql;
			//unset($userObj2);
			
			$userObj3 = new log_search1_M();
			$strsql3 = " CREATE VIEW logsoneview AS ".$userObj3->viewCreateSql;
			//unset($userObj3);
			
			$userObj4 = new log_search2_M();
			$strsql4 = " CREATE VIEW logview AS ".$userObj4->viewCreateSql;
			//unset($userObj4);
			
			try{
				@$this->objDb->ExecuteNonQuery($strsql);
			}catch( Exception $e ){}
			try{
				@$this->objDb->ExecuteNonQuery($strsql2);
			}catch( Exception $e ){}
			try{
				@$this->objDb->ExecuteNonQuery($strsql3);
			}catch( Exception $e ){}
			try{
				@$this->objDb->ExecuteNonQuery($strsql4);
			}catch( Exception $e ){}
		}
		catch( Exception $e ){
		}
		
	}
}

?>