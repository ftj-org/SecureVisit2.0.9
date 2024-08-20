<?php
/**
 * ePassND1000のアクセス権限設定マッピング一覧ファイル
 *
 * @author 張新星
 * @since  2007-9-17
 * @version 1.0
 */


//1.指定されたファイルを読み込む
$G_APPPATH = explode("ctrl",__FILE__);
require_once $G_APPPATH[0]."/lib/html.php";
require_once $G_APPPATH[0]."/lib/header.php";
require_once $G_APPPATH[0]."/mdl/access_control_detail_M.php";
require_once $G_APPPATH[0]."/lib/page.php";


//2.データを受け取り、フィルタを行う
SESSION::OStart();
$strDir = trim($_POST["txtDir"]);
$strUrl = trim($_POST["txtUrl"]);
$strDomain = HTML::GetParam("txtDomain");
$strUser = HTML::GetParam("txtUser");
$strPwd = HTML::GetParam("txtPwd");
$strPerGroup = HTML::GetParam("cboPerGroup");
$strNotPerGroup = HTML::GetParam("cboNotPerGroup");
$strReceiveDir = trim($_GET["dir"]);
$strHidSubmit = HTML::GetParam("hidSubmit");
$strButton = HTML::GetParam("hidButton");
$btnSubmit = HTML::GetParam("btnSubmit");
$strSelect = HTML::GetParam("rdoSelect"); 


// 3.Model層のオブジェクトをインスタンス化をする.
try{
	$objAccess_control_detail = new Access_control_detail_M();
}catch(exception $e){
	ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
	exit;
}


// 4.イベントタイプの処理.
try{
	//変数を初期化する
	if(!$flag)	$flag = 1;
	if(!$strButton) $strButton="INSERT";
	$checkFlag = false;
	$error = array();
	$strTitle = "マッピング登録";
	$strTagName = "tagAccessAdd";
	if(!$strReadOnly) $strReadOnly = "";
	if(trim($strSelect) === "") $strSelect = 2;
	$arrGroupList = $objAccess_control_detail->getGroupList();
	//データを取得する
		if($strReceiveDir != "")
			{
			$strTitle = "マッピング変更";
			$strButton = "UPDATE";
			$strReadOnly = "READONLY";
			$arrlink = $objAccess_control_detail->readAccess($strReceiveDir);
			
			$strDir = $arrlink["dir"];
			$strDomain = $arrlink["domain"];
			$strUrl = $arrlink["url"];
			$strUser = $arrlink["basicuser"];
			$strPwd = $arrlink["basicpwd"];
			$strPerGroup = array();
			$strPerGroup = $arrlink["name"];
			$strAccess = $arrlink["access"];
			$strMember = $arrlink["member"];
			//memberの存在しない場合
			if($strMember == "false")
			{
				$strSelect = 0;
			}
			//accessかありません
		    if($strMember == "true")
			{	
				if($strPerGroup[0]=="nobody"&&$strAccess[0]=="deny")
			
				{
					$strSelect =1;
				}
				//ほかの場合
				else
				{	
					$strSelect = 2;
				}
			}
		
	}
	//情報を編集する
	if($btnSubmit)
	{
		$error[0] = PAGE::CheckData("変換元PATH",$strDir,PAGE::CHECKDATE_C,1,16,"");
		$error[1] = PAGE::CheckData("変換元ドメイン名",$strDomain,PAGE::CHECKDATE_C,1,50,"");
		$error[2] = PAGE::CheckData("変換先URL",$strUrl,PAGE::CHECKDATE_C,1,50,"");
		
		//用户信息检测
			if(trim(implode("",$error)) == "" )
			{
				$checkFlag = true;
				$arrlink["dir"][0] = $strDir;
				$arrlink["domain"][0] = $strDomain;
				$arrlink["url"][0] = $strUrl;
				$arrlink["basicuser"][0] = $strUser;
				$arrlink["basicpwd"][0] = $strPwd;
				$arrlink["name"] = array();
				$arrlink["name"] = $strPerGroup;
			}
		
		// 用户信息登录

		if( trim($strButton) == "INSERT" && $checkFlag == true)
		{
			$objAccess_control_detail->insertNode($arrlink,$strSelect);
			$error[0] = "添加完成";
			$flag = 0;
		}
		
		// 用户信息更新
		if( trim($strButton) == "UPDATE" && $checkFlag == true )
		{
			$objAccess_control_detail->changeNode($arrlink,$strSelect);
			$error[0] = "更新完成。";
			$flag = 0;
		}
	}
}catch(exception $e)
{
	// エラーメッセージを表示する.
	ToLog::ToLogs(ERROR,__FILE__,__LINE__,$e->getMessage());
	exit;
}


// 5.置換処理.
// テンプレートに切り替えす
if( !is_array($strPerGroup))  $strPerGroup = array();
$arrSelect = array(	0 =>"認証しない（匿名アクセス可）<br>",
							1 =>"認証済みのすべてのユーザーを許可する<br>",
							2 =>"指定されたアクセスグループのみを許可する ");

$strTplUrl = HTML::GetText("txtUrl", $strUrl, 9, 32, "", "class=\"iw200\" id=\"urlId\"" );
$strTplDir = HTML::GetText("txtDir",$strDir,9,16,$strReadOnly,"class=\"iw200\" id=\"dirId\"");
$strTplDomain = HTML::GetText("txtDomain",$strDomain,9,16,"","class=\"iw200\"");
$strTplUser = HTML::GetText("txtUser",$strUser,9,16,"","class=\"iw200\" id=\"txtUserID\"");
$strTplPwd = HTML::GetText("txtPwd",$strPwd,9,16,"","class=\"iw200\" id=\"txtPwdID\"");
$strTplPerGroup = HTML::GetSelectm("cboPerGroup",$strPerGroup,$strPerGroup,"NOTNULL","","class=\"sw200\" id=\"cboPerGroupId\"");
$strTplNotPerGroup = HTML::GetSelectm("cboNotPerGroup",$strNotPerGroup,$arrGroupList,"NOTNULL","","class=\"sw200\" id=\"cboNotPerGroupId\"");
$strTplSelect = HTML::GetRadio("rdoSelect",$strSelect,$arrSelect,"","id=\"sel\" onclick=\"changeSel(this)\"");

// 変数を入れ替える
$tpl->assign( "ServerTime", date("Y-m-d H:i:s") );
$tpl->assign("html_title", $strTitle );
$tpl->assign("html_url",$strTplUrl);
$tpl->assign("html_dir",$strTplDir);
$tpl->assign("html_domain",$strTplDomain);
$tpl->assign("html_basic_user",$strTplUser);
$tpl->assign("html_basic_pwd",$strTplPwd);
$tpl->assign("html_pergroup",$strTplPerGroup);
$tpl->assign("html_not_pergroup",$strTplNotPerGroup);
$tpl->assign( "html_error", implode("",$error) );
$tpl->assign( "html_flag_show", $flag );
$tpl->assign( "html_flag", $strButton );
$tpl->assign("html_select",$strTplSelect);


// 6.テンプレートに出力する.
$smartyObj->Flush("../tpl/access_control_detail.tpl");


// 7.各変数をクリアする.
unset($objAccess_control_detail);
SESSION::OClose();
?>