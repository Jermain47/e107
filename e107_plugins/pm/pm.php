<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2009 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 *	PM plugin - main user interface
 *
 * $Source: /cvs_backup/e107_0.8/e107_plugins/pm/pm.php,v $
 * $Revision$
 * $Date$
 * $Author$
 */


/**
 *	e107 Private messenger plugin
 *
 *	@package	e107_plugins
 *	@subpackage	pm
 *	@version 	$Id$;
 */


$retrieve_prefs[] = 'pm_prefs';
require_once('../../class2.php');


if (!e107::isInstalled('pm')) 
{
	header('location:'.e_BASE.'index.php');
	exit;
}



if(vartrue($_POST['keyword']))
{
	pm_user_lookup();
}



require_once(e_PLUGIN.'pm/pm_class.php');
require_once(e_PLUGIN.'pm/pm_func.php');
include_lan(e_PLUGIN.'pm/languages/'.e_LANGUAGE.'.php');
e107::getScParser();
require_once(e_PLUGIN.'pm/pm_shortcodes.php');

define('ATTACHMENT_ICON', "<img src='".e_PLUGIN."pm/images/attach.png' alt='' />");

$qs = explode('.', e_QUERY);
$action = varset($qs[0],'inbox');
if (!$action) $action = 'inbox';

$pm_proc_id = intval(varset($qs[1],0));

//$pm_prefs = $sysprefs->getArray('pm_prefs');

$pm_prefs = e107::getPlugPref('pm');


$pm_prefs['perpage'] = intval($pm_prefs['perpage']);
if($pm_prefs['perpage'] == 0)
{
	$pm_prefs['perpage'] = 10;
}

if(!isset($pm_prefs['pm_class']) || !check_class($pm_prefs['pm_class']))
{
	require_once(HEADERF);
	$ns->tablerender(LAN_PM, LAN_PM_12);
	require_once(FOOTERF);
	exit;
}

//setScVar('pm_handler_shortcodes','pmPrefs', $pm_prefs);
$pmManager = new pmbox_manager($pm_prefs);
//setScVar('pm_handler_shortcodes','pmManager', &$pmManager);







class pm_extended extends private_message
{
	protected	$pmManager = NULL;

	/**
	 *	Constructor
	 *
	 *	@param array $prefs - pref settings for PM plugin
	 *	@return none
	 */
	public	function __construct($prefs, $manager)
	{
		$this->pmManager = $manager;
		parent::__construct($prefs);
	}



	/**
	 *	Show the 'Send to' form
	 *	@param array|int $to_uid - a PM block of message to reply to, or UID of user to send to
	 *
	 *	@return string text for display
	 */
	function show_send($to_uid)
	{
		$pm_info = array();
		$pm_outbox = $this->pmManager->pm_getInfo('outbox');
		if (is_array($to_uid))
		{
			$pm_info = $to_uid;		// We've been passed a 'reply to' PM
			$to_uid = $pm_info['pm_from'];
		}
		if($to_uid)
		{
			$sql2 = new db;
			if($sql2->db_Select('user', 'user_name', 'user_id = '.intval($to_uid)))
			{
				$row=$sql2->db_Fetch();
				$pm_info['from_name'] = $row['user_name'];
			}
		}
		//echo "Show_send: {$to_uid} from {$pm_info['from_name']} is happening<br />";
			
		if($pm_outbox['outbox']['filled'] >= 100)
		{
			return str_replace('{PERCENT}', $pm_outbox['outbox']['filled'], LAN_PM_13);
		}
		$tpl_file = THEME.'pm_template.php';
		include_once(is_readable($tpl_file) ? $tpl_file : e_PLUGIN.'pm/pm_template.php');
		$enc = (check_class($this->pmPrefs['attach_class']) ? "enctype='multipart/form-data'" : '');
	//	setScVar('pm_handler_shortcodes','pmInfo', $pm_info);
		
		$sc = e107::getScBatch('pm',TRUE);
		$sc->setVars($pm_info);
		
		$text = "<form {$enc} method='post' action='".e_SELF."' id='dataform'>
		<div><input type='hidden' name='numsent' value='{$pm_outbox['outbox']['total']}' />".
		$this->e107->tp->parseTemplate($PM_SEND_PM, TRUE).
		'</div></form>';
		return $text;
	}


	/**
	 *	Show inbox
	 *	@param int $start - offset into list
	 *
	 *	@return string text for display
	 */

	function show_inbox($start = 0)
	{
		$tp = e107::getParser();
		
		$tpl_file = THEME.'pm_template.php';
		include(is_readable($tpl_file) ? $tpl_file : e_PLUGIN.'pm/pm_template.php');
		
		$pm_blocks = $this->block_get();
		$pmlist = $this->pm_get_inbox(USERID, $start, $this->pmPrefs['perpage']);
		
	//	setScVar('pm_handler_shortcodes', 'pmNextPrev', array('start' => $start, 'total' => $pmlist['total_messages']));
		
		$sc = e107::getScBatch('pm',TRUE);
		$sc->pmNextPrev = array('start' => $start, 'total' => $pmlist['total_messages']);
		
		$txt = "<form method='post' action='".e_SELF."?".e_QUERY."'>";
		$txt .= $tp->parseTemplate($PM_INBOX_HEADER, true);
		
		if($pmlist['total_messages'])
		{
			foreach($pmlist['messages'] as $rec)
			{
				if(trim($rec['pm_subject']) == '') { $rec['pm_subject'] = '['.LAN_PM_61.']'; }
				$sc->setVars($rec);	
				$txt .= $tp->parseTemplate($PM_INBOX_TABLE, true);
			}
		}
		else
		{
			$txt .= $tp->parseTemplate($PM_INBOX_EMPTY, true);
		}
		
		$txt .= $tp->parseTemplate($PM_INBOX_FOOTER, true);
		$txt .= "</form>";
		
		return $txt;
	}




	/**
	 *	Show outbox
	 *	@param int $start - offset into list
	 *
	 *	@return string text for display
	 */
	function show_outbox($start = 0)
	{
		$tp = e107::getParser();
		
		$tpl_file = THEME.'pm_template.php';
		include(is_readable($tpl_file) ? $tpl_file : e_PLUGIN.'pm/pm_template.php');
		$pmlist = $this->pm_get_outbox(USERID, $start, $this->pmPrefs['perpage']);
	//	setScVar('pm_handler_shortcodes', 'pmNextPrev', array('start' => $start, 'total' => $pmlist['total_messages']));
		
		$sc = e107::getScBatch('pm',TRUE);
		$sc->pmNextPrev = array('start' => $start, 'total' => $pmlist['total_messages']);
		
		
		$txt = "<form method='post' action='".e_SELF."?".e_QUERY."'>";
		$txt .= $tp->parseTemplate($PM_OUTBOX_HEADER, true);
		if($pmlist['total_messages'])
		{
			foreach($pmlist['messages'] as $rec)
			{
				if(trim($rec['pm_subject']) == '') { $rec['pm_subject'] = '['.LAN_PM_61.']'; }
			//	setScVar('pm_handler_shortcodes','pmInfo', $rec);
				$sc->setVars($rec);	
				$txt .= $tp->parseTemplate($PM_OUTBOX_TABLE, true);
			}
		}
		else
		{
			$txt .= $tp->parseTemplate($PM_OUTBOX_EMPTY, true);
		}
		$txt .= $tp->parseTemplate($PM_OUTBOX_FOOTER, true);
		$txt .= '</form>';
		return $txt;
	}



	/**
	 *	Show details of a pm
	 *	@param int $pmid - DB ID for PM
	 *	@param string $comeFrom - inbox|outbox - determines whether inbox or outbox is shown after PM
	 *
	 *	@return string text for display
	 */
	function show_pm($pmid, $comeFrom = '')
	{
		$tpl_file = THEME.'pm_template.php';
		include_once(is_readable($tpl_file) ? $tpl_file : e_PLUGIN.'pm/pm_template.php');
		$pm_info = $this->pm_get($pmid);
	//	setScVar('pm_handler_shortcodes','pmInfo', $pm_info);
		$sc = e107::getScBatch('pm',TRUE);
		$sc->setVars($pm_info);	
		if($pm_info['pm_to'] != USERID && $pm_info['pm_from'] != USERID)
		{
			$this->e107->ns->tablerender(LAN_PM, LAN_PM_60);
			require_once(FOOTERF);
			exit;
		}
		if($pm_info['pm_read'] == 0 && $pm_info['pm_to'] == USERID)
		{	// Inbox
			$now = time();
			$pm_info['pm_read'] = $now;
			$this->pm_mark_read($pmid, $pm_info);
		}
		$txt = $this->e107->tp->parseTemplate($PM_SHOW, true);
		$this->e107->ns->tablerender(LAN_PM, $txt);
		if (!$comeFrom)
		{
			if ($pm_info['pm_from'] == USERID) { $comeFrom = 'outbox'; } 
		}
		// Need to show inbox or outbox from start
		if ($comeFrom == 'outbox')
		{	// Show Outbox
			$this->e107->ns->tablerender(LAN_PM." - ".LAN_PM_26, $this->show_outbox(), 'PM');
		} 
		else
		{	// Show Inbox
			$this->e107->ns->tablerender(LAN_PM.' - '.LAN_PM_25, $this->show_inbox(), 'PM');
		}
	}




	/**
	 *	Show list of blocked users
	 *	@param int $start - not used at present; offset into list
	 *
	 *	@return string text for display
	 */
	public function showBlocked($start = 0)
	{
		$tpl_file = THEME.'pm_template.php';
		include(is_readable($tpl_file) ? $tpl_file : e_PLUGIN.'pm/pm_template.php');
		$pmBlocks = $this->block_get_user();			// TODO - handle pagination, maybe (is it likely to be necessary?)
		$sc = e107::getScBatch('pm',TRUE);
		$sc->pmBlocks = $pmBlocks; 
	
		$txt = "<form method='post' action='".e_SELF."?".e_QUERY."'>";
		$txt .= $this->e107->tp->parseTemplate($PM_BLOCKED_HEADER, true);
		if($pmTotalBlocked = count($pmBlocks))
		{
			foreach($pmBlocks as $pmBlocked)
			{
				$sc->pmBlocked = $pmBlocked; 
			//	setScVar('pm_handler_shortcodes','pmBlocked', $pmBlocked);
				$txt .= $this->e107->tp->parseTemplate($PM_BLOCKED_TABLE, true);
			}
		}
		else
		{
			$txt .= $this->e107->tp->parseTemplate($PM_BLOCKED_EMPTY, true);
		}
		$txt .= $this->e107->tp->parseTemplate($PM_BLOCKED_FOOTER, true);
		$txt .= '</form>';
		return $txt;
	}





	/**
	 *	Send a PM based on $_POST parameters
	 *
	 *	@return string text for display
	 */
	function post_pm()
	{
		// print_a($_POST);
		
		
		if(!check_class($this->pmPrefs['pm_class']))
		{
			return LAN_PM_12;
		}

		$pm_info = $this->pmManager->pm_getInfo('outbox');
		if($pm_info['outbox']['total'] != $_POST['numsent'])
		{
			return LAN_PM_14;
		}

		if(isset($_POST['user']))
		{
			$_POST['pm_to'] = $_POST['user'];
		}
		if(isset($_POST['pm_to']))
		{
			$msg = '';
			if(isset($_POST['to_userclass']) && $_POST['to_userclass'])
			{
				if(!check_class($this->pmPrefs['opt_userclass']))
				{
					return LAN_PM_15;
				}
				elseif((!check_class($_POST['pm_userclass']) || !check_class($this->pmPrefs['multi_class'])) && !ADMIN)
				{
					return LAN_PM_16;
				}
			}
			else
			{
				$to_array = explode("\n", trim($_POST['pm_to']));
				foreach($to_array as $k => $v)
				{
					$to_array[$k] = trim($v);
				}
				$to_array = array_unique($to_array);
				if(count($to_array) == 1)
				{
					$_POST['pm_to'] = $to_array[0];
				}
				if(check_class($this->pmPrefs['multi_class']) && count($to_array) > 1)
				{
					foreach($to_array as $to)
					{
						if($to_info = $this->pm_getuid($to))
						{	// Check whether sender is blocked - if so, add one to count
							if(!$this->e107->sql->db_Update('private_msg_block',"pm_block_count=pm_block_count+1 WHERE pm_block_from = '".USERID."' AND pm_block_to = '".$tp -> toDB($to)."'"))
							{
								$_POST['to_array'][] = $to_info;
							}
						}
					}
				}
				else
				{
					if($to_info = $this->pm_getuid($_POST['pm_to']))
					{
						$_POST['to_info'] = $to_info;
					}
					else
					{
						return LAN_PM_17;
					}

					if($this->e107->sql->db_Update('private_msg_block',"pm_block_count=pm_block_count+1 WHERE pm_block_from = '".USERID."' AND pm_block_to = '{$to_info['user_id']}'"))
					{
						return LAN_PM_18.$to_info['user_name'];
					}
				}
			}

			if(isset($_POST['receipt']))
			{
				if(!check_class($this->pmPrefs['receipt_class']))
				{
					unset($_POST['receipt']);
				}
			}
			$totalsize = strlen($_POST['pm_message']);
			$maxsize = intval($this->pmPrefs['attach_size']) * 1024;
			foreach(array_keys($_FILES['file_userfile']['size']) as $fid)
			{
				if($maxsize > 0 && $_FILES['file_userfile']['size'][$fid] > $maxsize)
				{
					$msg .= str_replace("{FILENAME}", $_FILES['file_userfile']['name'][$fid], LAN_PM_62)."<br />";
					$_FILES['file_userfile']['size'][$fid] = 0;
				}
				$totalsize += $_FILES['file_userfile']['size'][$fid];
			}

			if(intval($this->pmPrefs['pm_limits']) > 0)
			{
				if($this->pmPrefs['pm_limits'] == '1')
				{
					if($pm_info['outbox']['total'] == $pm_info['outbox']['limit'])
					{
						return LAN_PM_19;
					}
				}
				else
				{
					if($pm_info['outbox']['size'] + $totalsize > $pm_info['outbox']['limit'])
					{
						return LAN_PM_21;
					}
				}
			}

			if($_FILES['file_userfile']['name'][0])
			{
				if(check_class($this->pmPrefs['attach_class']))
				{
					require_once(e_HANDLER.'upload_handler.php');
					$randnum = rand(1000, 9999);			
					$_POST['uploaded'] = file_upload(e_PLUGIN.'pm/attachments', 'attachment', $randnum.'_');
					if($_POST['uploaded'] == FALSE)
					{
						unset($_POST['uploaded']);
						$msg .= LAN_PM_22."<br />";
					}
				}
				else
				{
					$msg .= LAN_PM_23.'<br />';
					unset($_POST['uploaded']);
				}
			}
			$_POST['from_id'] = USERID;
			return $msg.$this->add($_POST);
		}
	}
}



/**
 *	Look up users matching a keyword, output a list of those found
 *	Direct echo
 */
function pm_user_lookup()
{
	$sql = e107::getDb();

	$query = "SELECT * FROM #user WHERE user_name REGEXP '^".$_POST['keyword']."' ";
	if($sql -> db_Select_gen($query))
	{
		echo '[';
		while($row = $sql-> db_Fetch())
		{
			  $u[] =  "{\"caption\":\"".$row['user_name']."\",\"value\":".$row['user_id']."}";
		 }

		echo implode(",",$u);
		echo ']';
	}
	exit;
}






//$pm =& new private_message;
$pm = new pm_extended($pm_prefs, &$pmManager);

$message = '';
$pmSource = '';
if (isset($_POST['pm_come_from']))
{
	$pmSource = $tp->toDB($_POST['pm_come_from']);
}
elseif (isset($qs[2]))
{
	$pmSource = $tp->toDB($qs[2]);
}



//Auto-delete message, if timeout set in admin
$del_qry = array();
$read_timeout = intval($pm_prefs['read_timeout']);
$unread_timeout = intval($pm_prefs['unread_timeout']);
if($read_timeout > 0)
{
	$timeout = time()-($read_timeout * 86400);
	$del_qry[] = "(pm_sent < {$timeout} AND pm_read > 0)";
}
if($unread_timeout > 0)
{
	$timeout = time()-($unread_timeout * 86400);
	$del_qry[] = "(pm_sent < {$timeout} AND pm_read = 0)";
}
if(count($del_qry) > 0)
{
	$qry = implode(' OR ', $del_qry).' AND (pm_from = '.USERID.' OR pm_to = '.USERID.')';
	if($sql->db_Select('private_msg', 'pm_id', $qry))
	{
		$delList = $sql->db_getList();
		foreach($delList as $p)
		{
			$pm->del($p['pm_id'], TRUE);
		}
	}
}



if('del' == $action || isset($_POST['pm_delete_selected']))
{
	if(isset($_POST['pm_delete_selected']))
	{
		foreach(array_keys($_POST['selected_pm']) as $id)
		{
			$message .= LAN_PM_24.": {$id} <br />";
			$message .= $pm->del($id);
		}
	}
	if('del' == $action)
	{
		$message = $pm->del($pm_proc_id);
	}
	if ($pmSource)
	{
		$action = $pmSource;
	}
	else
	{
		if(substr($_SERVER['HTTP_REFERER'], -5) == 'inbox')
		{
			$action = 'inbox';
		}
		elseif(substr($_SERVER['HTTP_REFERER'], -6) == 'outbox')
		{
			$action = 'outbox';
		}
	}
	$pm_proc_id = 0;
	unset($qs);
}



if('delblocked' == $action || isset($_POST['pm_delete_blocked_selected']))
{
	if(isset($_POST['pm_delete_blocked_selected']))
	{
		foreach(array_keys($_POST['selected_pm']) as $id)
		{
			$message .= LAN_PM_70.": {$id} <br />";
			$message .= $pm->block_del($id).'<br />';
		}
	}
	elseif('delblocked' == $action)
	{
		$message = $pm->block_del($pm_proc_id);
	}
	$action = 'blocked';
	$pm_proc_id = 0;
	unset($qs);
}


if('block' == $action)
{
	$message = $pm->block_add($pm_proc_id);
	$action = 'inbox';
	$pm_proc_id = 0;
}

if('unblock' == $action)
{
	$message = $pm->block_del($pm_proc_id);
	$action = 'inbox';
	$pm_proc_id = 0;
}

if('get' == $action)
{
	$pm->send_file($pm_proc_id, intval($qs[2]));
	exit;
}


require_once(HEADERF);

if(isset($_POST['postpm']))
{
	$message = $pm->post_pm();
	$action = 'outbox';
}

$mes = e107::getMessage();

if($message != '')
{
	
	$mes->add($message);
	
//	$ns->tablerender('', "<div class='alert alert-block'>". $message."</div>");
}



//-----------------------------------------
//			DISPLAY TASKS
//-----------------------------------------
switch ($action)
{
	case 'send' :
		$ns->tablerender(LAN_PM, $mes->render() . $pm->show_send($pm_proc_id));
		break;

	case 'reply' :
		$pmid = $pm_proc_id;
		if($pm_info = $pm->pm_get($pmid))
		{
			if($pm_info['pm_to'] != USERID)
			{
				$ns->tablerender(LAN_PM, $mes->render() . LAN_PM_56);
			}
			else
			{
				$ns->tablerender(LAN_PM, $mes->render() . $pm->show_send($pm_info));
			}
		}
		else
		{
			$ns->tablerender(LAN_PM, $mes->render() . LAN_PM_57);
		}
		break;

	case 'inbox' :
		$ns->tablerender(LAN_PM.' - '.LAN_PM_25, $mes->render() . $pm->show_inbox($pm_proc_id), 'PM');
		break;

	case 'outbox' :
		$ns->tablerender(LAN_PM.' - '.LAN_PM_26, $mes->render() . $pm->show_outbox($pm_proc_id), 'PM');
		break;

	case 'show' :
		$pm->show_pm($pm_proc_id, $pmSource);
		break;

	case 'blocked' :
		$ns->tablerender(LAN_PM.' - '.LAN_PM_66, $mes->render() . $pm->showBlocked($pm_proc_id), 'PM');
		break;
}


require_once(FOOTERF);
exit;







?>