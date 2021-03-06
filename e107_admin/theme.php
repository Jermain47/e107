<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2009 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 *
 *
 * $Source: /cvs_backup/e107_0.8/e107_admin/theme.php,v $
 * $Revision$
 * $Date$
 * $Author$
 */

require_once("../class2.php");
if (!getperms("1")) {
	header("location:".e_BASE."index.php");
	exit;
}

include_lan(e_LANGUAGEDIR.e_LANGUAGE.'/admin/lan_'.e_PAGE);

$e_sub_cat = 'theme_manage';

e107::css("inline","
.hide						{ display: none }
.admin-theme-thumb			{ height:130px;overflow:hidden;border:1px solid black;margin-bottom:10px   }
.admin-theme-thumb:hover	{ opacity:0.4 }

.admin-theme-options		{ transition: opacity .20s ease-in-out;
							 -moz-transition: opacity .20s ease-in-out;
							 -webkit-transition: opacity .20s ease-in-out;
							 opacity:0; 
							 width:100%;
							 height:80px;
							 padding-top:50px;
							 white-space:nowrap;
							 background-color:black;
							 display:block;position:relative; text-align:center; vertical-align:middle; top:-141px;}

.admin-theme-options:hover	{ opacity:0.8; }

.admin-theme-title			{ font-size: 15px; overflow:hidden; padding-left:5px; white-space:no-wrap; width:200px; position:relative; top:-132px; }

.admin-theme-select			{border:1px dotted silver;background-color:#DDDDDD;float:left }

.admin-theme-select-active	{ background-color:red;float:left }

.admin-theme-cell			{ width:202px; height:160px; padding:10px; -moz-border-radius: 5px; border-radius: 5px; margin:5px}

.admin-theme-cell-default   { border:1px dotted silver; background-color:#DDDDDD }



.admin-theme-cell-site		{ background-color: #d9edf7;  border: 1px solid #bce8f1; }

.admin-theme-cell-admin	 	{ background-color:#FFFFD5; border: 1px solid #FFCC00; }


");


require_once(e_HANDLER."theme_handler.php");
$themec = new themeHandler;
if(e_AJAX_REQUEST)
{
	define('e_IFRAME',true);
}
	


if(e_AJAX_REQUEST)
{
	if(vartrue($_GET['src'])) // Process Theme Download. 
	{				
		$string =  base64_decode($_GET['src']);	
		parse_str($string,$p);
		
		if(vartrue($_GET['info']))
		{		
			echo $themec->renderThemeInfo($p);
		//	print_a($p);
			exit;
		}
				
		$remotefile = $p['url'];
			
		$localfile = md5($remotefile.time()).".zip";
		$status = "Downloading...";
		
		e107::getFile()->getRemoteFile($remotefile,$localfile);
		
		if(!file_exists(e_TEMP.$localfile))
		{
			$status = ADMIN_FALSE_ICON."<br /><a href='".$remotefile."'>Download Manually</a>";
			echo $status;
			exit;	
		}
	//	chmod(e_PLUGIN,0777);
		chmod(e_TEMP.$localfile,0755);
		
		require_once(e_HANDLER."pclzip.lib.php");
		$archive = new PclZip(e_TEMP.$localfile);
		$unarc = ($fileList = $archive -> extract(PCLZIP_OPT_PATH, e_THEME, PCLZIP_OPT_SET_CHMOD, 0755));
	//	chmod(e_PLUGIN,0755);
		$dir 		= basename($unarc[0]['filename']);
	//		chmod(e_UPLOAD.$localfile,0666);
	
	
	
		/* Cannot use this yet until 'folder' is included in feed. 
		if($dir != $p['plugin_folder'])
		{
			
			echo "<br />There is a problem with the data submitted by the author of the plugin.";
			echo "dir=".$dir;
			echo "<br />pfolder=".$p['plugin_folder'];
			exit;
		}	
		*/
			
		if($unarc[0]['folder'] ==1 && is_dir($unarc[0]['filename']))
		{
			$status = "Unzipping...";
			$dir 		= basename($unarc[0]['filename']);
			$plugPath	= preg_replace("/[^a-z0-9-\._]/", "-", strtolower($dir));	
			$status = ADMIN_TRUE_ICON;
			//unlink(e_UPLOAD.$localfile);
			
		}
		else 
		{
			// print_a($fileList);
			$status = ADMIN_FALSE_ICON."<br /><a href='".$remotefile."'>Download Manually</a>";
			//echo $archive->errorInfo(true);
			// $status = "There was a problem";	
			//unlink(e_UPLOAD.$localfile);
		}
		
		echo $status;
	//	@unlink(e_TEMP.$localfile);
	
	//	echo "file=".$file;
		exit;				
	}		
		
	
	$tm = (string) $_GET['id'];	
	$data = $themec->getThemeInfo($tm);
	echo $themec->renderThemeInfo($data);
	
	exit;	
}
else 
{
		require_once("auth.php");
	

		echo '

		 <div id="myModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			    <div class="modal-header">
			    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			    &nbsp;
			    </div>
			    <div class="modal-body">
			    <p>Loading…</p>
			    </div>
			    <div class="modal-footer">
			    <a href="#" data-dismiss="modal" class="btn btn-primary">Close</a>
			    </div>
			    </div>';	
}				




$mode = varset($_GET['mode'],'main'); // (e_QUERY) ? e_QUERY :"main" ;

if(vartrue($_POST['selectadmin']))
{
	$mode = "admin";
}

if(vartrue($_POST['upload']))
{
	$mode = "choose";
}

if(vartrue($_POST['selectmain']) || varset($_POST['setUploadTheme']))
{
	$mode = "main";
}

$themec -> showThemes($mode);
// <a data-toggle="modal" href="'.e_SELF.'" data-target="#myModal" class="btn" >Launch demo modal</a>




require_once("footer.php");

function theme_adminmenu()
{
	//global $mode;
	
	$mode = varset($_GET['mode'],'main');
	
  // 	$e107 = &e107::getInstance();

		$var['main']['text'] = TPVLAN_33;
		$var['main']['link'] = e_SELF;

		$var['admin']['text'] = TPVLAN_34;
		$var['admin']['link'] = e_SELF."?mode=admin";

		$var['choose']['text'] = TPVLAN_51;
		$var['choose']['link'] = e_SELF."?mode=choose";
		
		$var['online']['text'] = "Find Themes";
		$var['online']['link'] = e_SELF."?mode=online";

		$var['upload']['text'] = TPVLAN_38;
		$var['upload']['link'] = e_SELF."?mode=upload";

      //  $selected = (e_QUERY) ? e_QUERY : "main";


		e107::getNav()->admin(TPVLAN_26, $mode, $var);
}





?>