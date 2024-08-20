<?php
/**
 * ���O�Ǘ�_���O�̓ǂݍ��݊Ǘ�
 *
 * @author ���̉�.
 * @since  2007-09-24
 * @version 1.0
 */

require_once "../lib/log.php";
require_once "../lib/db.php";

class LogToDB_M
{
	/**
	 * �f�[�^�x�[�X�̎���.
	 */
	var $objDb = null;
	
	/**
	* �֐���: __construct
	* �R���X�g���N�^.
	* 
	*/
	function __construct()
	{		
		try
		{
			$this->objDb = new DB_Pgsql();
			ToLog::ToLogs(DEBUG,__CLASS__.__FUNCTION__,__LINE__,"���ቻ");
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}
	}
	
	
	/**
	* �֐���: LogToDB_M
	* �R���X�g���N�^.
	* 
	*/
	function LogToDB_M()
	{				
		try
		{
			$this->__construct();
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;
		}				
	}
	
	/**
	* �֐���: readLogToTemp
	* �����̏���\������.
	* 
	* @param		string		$strFPath			�\�[�X�t�@�C���̃p�X.
	*
	* @param		string		$strAuthsLog		�F�؂̐����̃��O
	*
	* @param		string		$strAuthfLog		�F�؂̎��s�̃��O
	*
	* @param		string		$strAccessLog		�A�N�Z�X�̃��O
	*
	* @param		string		$strTPath			�e���|�����t�@�C���̃p�X
	*
	* @return		boolean		$isTrue				�����̏��.
	*/
	function readLogToTemp($strAuthsLog,$strAuthfLog,$strAccessLog,$strFPath,$strTPath)
	{
		try
		{	
			$isTrue=false;
			
			@file_put_contents($strTPath.$strAuthsLog,file_get_contents($strFPath.$strAuthsLog));
			@file_put_contents($strFPath.$strAuthsLog,"");
			
			@file_put_contents($strTPath.$strAuthfLog,file_get_contents($strFPath.$strAuthfLog));	
			@file_put_contents($strFPath.$strAuthfLog,"");	

			@file_put_contents($strTPath.$strAccessLog,file_get_contents($strFPath.$strAccessLog));	
			@file_put_contents($strFPath.$strAccessLog,"");
			
			$isTrue=true;
			
			return $isTrue;
		}
		catch( Exception $e )
		{
			ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
			throw $e;	
		}
		
	}
	
	/**
	* �֐���: readLogToDataBase
	* �����̏���\������.
	* 
	* @param		string		$strFPath			�\�[�X�t�@�C���̃p�X.
	*
	* @param		string		$strAuthsLog		�F�؂̐����̃��O
	*
	* @param		string		$strAuthfLog		�F�؂̎��s�̃��O
	*
	* @param		string		$strAccessLog		�A�N�Z�X�̃��O
	*
	* @param		string		$strTPath			�e���|�����t�@�C���̃p�X
	*
	* @return		boolean		$isTrue				�����̏��.
	*/
	function readLogToDataBase($strAuthsLog,$strAuthfLog,$strAccessLog,$strFPath,$strTPath)
	{
		$isTrue=false;
		try
		{	
			//�g���U�N�V�����̊J�n.
			$this->objDb->begin();
			
			//���t���擾����.
			$strsql= " SELECT value";
			$strsql.=" FROM sv_logconfig";
			$strsql.=" WHERE name ='monthnum'";
			
			$strValue= ord($this->objDb->ExecuteCommand($strsql));
			
			//�w��̎��Ԃ̃f�[�^���폜����
			$strDelSqlA= " delete";
			$strDelSqlA.=" FROM sv_accesslog";
			$strDelSqlA.=" WHERE datetime<(select current_timestamp - interval '".strValue." month')";
			
			$this->objDb->ExecuteCommand($strDelSqlA);
			
			$strDelSqlSF= " delete";
			$strDelSqlSF.=" FROM sv_authlog";
			$strDelSqlSF.=" WHERE datetime<(select current_timestamp - interval '".strValue." month')";
			
			$this->objDb->ExecuteCommand($strDelSqlSF);
			
			//�F�؂̐����̃��O���擾���邩�擾�������O�̓f�[�^�x�[�X�Ɋi�[����B
			$authsConn=@fopen($strFPath.$strAuthsLog,"r");
			while(!feof($authsConn))	
			{	
				$nCount=0;
				$strRow = @fgets($authsConn,4096);
				$arrRow=explode($strRow,"-");
			    $strInsSqlS="insert into sv_authlog values(";
				for($i=0;$i<sizeof($arrRow);$i++)
				{
					if($nCount==0)
					{
					}
					else
					{
						$strInsSql.=",";
					}
					$strInsSqlS.="'".$strRow[$i]."'";
				}
				$strInsSqlS.=")";
			}	
			$this->objDb->ExecuteCommand($strInsSqlS);
			@fclose($authsConn);
			@unlink($strFPath.$strAuthsLog);
			
			//�F�؂̎��s�̃��O���擾���邩�擾�������O�̓f�[�^�x�[�X�Ɋi�[����B
			$authsConn=@fopen($strFPath.$strAuthfLog,"r");
			while(!feof($authsConn))	
			{	
				$nCount=0;
				$strRow = @fgets($authsConn,4096);
				$arrRow=explode($strRow,"-");
				$strInsSqlF="insert into sv_authlog values(";
				for($i=0;$i<sizeof($arrRow);$i++)
				{
					if($nCount==0)
					{
					}
					else
					{
						$strInsSqlF.=",";
					}
					$strInsSqlF.="'".$strRow[$i]."'";
				}
				$strInsSqlF.=")";
			}	
			$this->objDb->ExecuteCommand($strInsSqlF);
			@fclose($authsConn);
			@unlink($strFPath.$strAuthfLog);
			
			//�A�N�Z�X�̂̃��O���擾���邩�擾�������O�̓f�[�^�x�[�X�Ɋi�[����B
			$authsConn=@fopen($strFPath.$strAccessLog,"r");
			while(!feof($authsConn))	
			{	
				$nCount=0;
				$strRow = @fgets($authsConn,4096);
				$arrRow=explode($strRow,"-");
				$strInsSqlA="insert into sv_authlog values(";
				for($i=0;$i<sizeof($arrRow);$i++)
				{
					if($nCount==0)
					{
					}
					else
					{
						$strInsSqlA.=",";
					}
					$strInsSqlA.="'".$strRow[$i]."'";
				}
				$strInsSqlA.=")";
			}	
			$this->objDb->ExecuteCommand($strInsSqlA);
			@fclose($authsConn);
			@unlink($strFPath.$strAccessLog);
			
			//�g���U�N�V�����̏I��.
			$this->objDb->commit();
			}
			catch( Exception $e )
			{
				ToLog::ToLogs(ERROR,__CLASS__.__FUNCTION__,__LINE__,$e->getMessage());
				//���[���o�b�N
				$this->objDb->rollback();
				throw $e;	
			}
		$isTrue=true;
		return $isTrue;
		}
}
?>