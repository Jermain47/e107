<?php
/*
* e107 website system
*
* Copyright (C) 2008-2009 e107 Inc (e107.org)
* Released under the terms and conditions of the
* GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
*
* Forum admin functions
*
* $URL$
* $Id$
*
*/

/* TODO
- Needs rewriting to V2 style including $msg->
- LAN
*/

class forumAdmin
{

	function show_options($action)
	{
		
		$sql = e107::getDb();
		if ($action == '') { $action = 'main'; }
		// ##### Display options ---------------------------------------------------------------------------------------------------------
		$var['main']['text'] = FORLAN_76;
		$var['main']['link'] = e_SELF;
		$var['cat']['text'] = FORLAN_83;
		$var['cat']['link'] = e_SELF.'?cat';
		if ($sql->db_Select('forum', 'forum_id', "forum_parent='0' LIMIT 1"))
		{
			$var['create']['text'] = FORLAN_77;
			$var['create']['link'] = e_SELF.'?create';
		}
		$var['order']['text'] = FORLAN_78;
		$var['order']['link'] = e_SELF.'?order';
		$var['opt']['text'] = FORLAN_79;
		$var['opt']['link'] = e_SELF.'?opt';
		$var['prune']['text'] = LAN_PRUNE;
		$var['prune']['link'] = e_SELF.'?prune';
		$var['rules']['text'] = FORLAN_123;
		$var['rules']['link'] = e_SELF.'?rules';
		$var['sr']['text'] = FORLAN_116;
		$var['sr']['link'] = e_SELF.'?sr';
		$var['mods']['text'] = FORLAN_33;
		$var['mods']['link'] = e_SELF.'?mods';
		$var['tools']['text'] = FORLAN_153;
		$var['tools']['link'] = e_SELF.'?tools';

		show_admin_menu(FORLAN_7, $action, $var);
	}

	function delete_item($id)
	{
		$sql = e107::getDb();
		$id = (int)$id;
		$confirm = isset($_POST['confirm']) ? true : false;

		if($sql->db_Select('forum', '*', "forum_id = {$id}"))
		{
			$txt = "";
			$row = $sql->db_Fetch();
			if($row['forum_parent'] == 0)
			{
				$txt .= $this->delete_parent($id, $confirm);
			}
			elseif($row['forum_sub'] > 0)
			{
				$txt .= $this->delete_sub($id, $confirm);
			}
			else
			{
				$txt .= $this->delete_forum($id, $confirm);
			}
			if($confirm)
			{
				$this->show_message($txt);
			}
			else
			{
				$this->delete_show_confirm($txt);
			}
		}
	}

	function delete_parent($id, $confirm = false)
	{
		$sql = e107::getDb();
		$ret = '';
		if($sql->db_Select('forum', 'forum_id', "forum_parent = {$id} AND forum_sub = 0"))
		{
			$fList = $sql->db_getList();
			foreach($fList as $f)
			{
				$ret .= $this->delete_forum($f['forum_id'], $confirm);
			}
		}
		if($confirm)
		{
			if($sql->db_Delete('forum', "forum_id = {$id}"))
			{
				$ret .= 'Forum parent successfully deleted'; // TODO LAN
			}
			else
			{
				$ret .= 'Forum parent could not be deleted'; // TODO LAN
			}
			return $ret;
		}
		return 'The forum parent has the following info: <br />'.$ret; // TODO LAN

	}

	function deleteForum($forumId)
	{
		$sql = e107::getDb();
		$forumId = (int)$forumId;
//		echo "id = $forumId <br />";
		// Check for any sub forums
		if($sql->db_Select('forum', 'forum_id', "forum_sub = {$forumId}"))
		{
			$list = $sql->db_getList();
			foreach($list as $f)
			{
				$ret .= $this->deleteForum($f['forum_id']);
			}
		}
		require_once(e_PLUGIN.'forum/forum_class.php');
		$f = new e107Forum;
		if($sql->db_Select('forum_thread', 'thread_id','thread_forum_id='.$forumId))
		{
			$list = $e107->sql->db_getList();
			foreach($list as $t)
			{
				$f->threadDelete($t['thread_id'], false);
			}
		}
		return $sql->db_Delete('forum', 'forum_id = '.$forumId);
	}

	function delete_forum($id, $confirm = false)
	{
		$sql = e107::getDb();
		$tp  = e107::getParser();
		$ret = '';
		if($sql->db_Select('forum', 'forum_id', 'forum_sub = '.$id))
		{
			$fList = $sql->db_getList();
			foreach($fList as $f)
			{
				$ret .= $this->delete_sub($f['forum_id'], $confirm);
			}
		}
		if($confirm)
		{
			if($this->deleteForum($id))
			{
				$ret .= "Forum {$id} successfully deleted"; // TODO LAN
			}
			else
			{
				$ret .= "Forum {$id} could not be deleted"; // TODO LAN
			} 
			return $ret;
		}

		$sql->db_Select('forum', 'forum_name, forum_threads, forum_replies', 'forum_id = '.$id);
		$row = $sql->db_Fetch();
		return "Forum {$id} [".$tp->toHTML($row['forum_name'])."] has {$row['forum_threads']} threads, {$row['forum_replies']} replies. <br />".$ret;
	}

	function delete_sub($id, $confirm = FALSE)
	{
		$sql = e107::getDb();
		$tp  = e107::getParser();
		if($confirm)
		{
			if($this->deleteForum($id))
			{
				$ret .= "Sub-forum {$id} successfully deleted"; // TODO LAN
			}
			else
			{
				$ret .= "Sub-forum {$id} could not be deleted"; // TODO LAN
			}
			return $ret;
		}

		$sql->db_Select('forum', '*', 'forum_id = '.$id);
		$row = $sql->db_Fetch();
		return "Sub-forum {$id} [".$tp->toHTML($row['forum_name'])."] has {$row['forum_threads']} threads, {$row['forum_replies']} replies. <br />".$ret;
	}

	function delete_show_confirm($txt)
	{
		$ns = e107::getRender();
		$this->show_message($txt);
		$frm = e107::getForm();
		$txt = "
		<form method='post' action='".e_SELF.'?'.e_QUERY."'>
		<div style='text-align:center'>".LAN_CONFDELETE."<br /><br />
			".$frm->admin_button('confirm', LAN_UI_DELETE_LABEL, 'submit')."
		<input type='submit' class='button' name='confirm' value='".LAN_DELETE."' />
		</div>
		</form>
		";
		$ns->tablerender(LAN_UI_DELETE_LABEL, $txt);
	}

	function show_subs($id)
	{
		$sql = e107::getDb();
		$tp = e107::getParser();
		$ns = e107::getRender();
		$frm = e107::getForm();
		$txt = "
		<form method='post' action='".e_SELF.'?'.e_QUERY."'>
		<table class='table adminlist'>
		<tr>
		<td>".LAN_ID."</td>
		<td>".LAN_NAME."</td>
		<td>".LAN_DESCRIPTION."</td>
		<td>".FORLAN_37."</td>
		<td>".FORLAN_20."</td>
		</tr>
		";
		if($sql->db_Select('forum', 'forum_id, forum_name, forum_description, forum_order', "forum_sub = {$id} ORDER by forum_order ASC"))
		{
			$subList = $sql->db_getList();
			foreach($subList as $sub)
			{
				$txt .= "
				<tr>
				<td style='vertical-align:top'>{$sub['forum_id']}</td>
				<td style='vertical-align:top'><input class='tbox' type='text' name='subname[{$sub['forum_id']}]' value='{$sub['forum_name']}' size='30' maxlength='255' /></td>
				<td style='vertical-align:top'><textarea cols='60' rows='2' class='tbox' name='subdesc[{$sub['forum_id']}]'>{$sub['forum_description']}</textarea></td>
				<td style='vertical-align:top'><input class='tbox' type='text' name='suborder[{$sub['forum_id']}]' value='{$sub['forum_order']}' size='3' maxlength='4' /></td>
				<td style='vertical-align:top; text-align:center'>
				<a href='".e_SELF."?delete.{$sub['forum_id']}'>".ADMIN_DELETE_ICON."</a>
				</td>
				</tr>
				";
			}
			$txt .= "
			<tr>
			<td colspan='5' style='text-align:center'>".$frm->admin_button('update_subs', LAN_UPDATE, 'update')."</td>
			</tr>
			<tr>
			<td colspan='5' style='text-align:center'>&nbsp;</td>
			</tr>
			";

		}
		else
		{
			$txt .= "<tr><td colspan='5' style='text-align:center'>".FORLAN_146."</td>";
		}

		$txt .= "
		<tr>
			<td>".LAN_ID."</td>
			<td>".LAN_NAME."</td>
			<td>".LAN_DESCRIPTION."</td>
			<td>".FORLAN_37."</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style='vertical-align:top'>&nbsp;</td>
			<td><input class='tbox' type='text' name='subname_new' value='' size='30' maxlength='255' /></td>
			<td><textarea cols='60' rows='2' class='tbox' name='subdesc_new'></textarea></td>
			<td><input class='tbox' type='text' name='suborder_new' value='' size='3' maxlength='4' /></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan='5' style='text-align:center'>".$frm->admin_button('create_sub', LAN_CREATE, 'submit')."</td>
		</tr>
		</table>
		</form>
		";
		$ns->tablerender(FORLAN_149, $txt); // TODO LAN
	}

	function show_existing_forums($sub_action, $id, $mode = false)
	{
		global $e107, $for;
		$frm = e107::getForm();
		$sql = e107::getDb();
		$tp = e107::getParser();

		$subList = $for->forumGetSubs();

		if (!$mode)
		{
			$text = "<div style='padding : 1px; margin-left: auto; margin-right: auto; text-align: center;'>";
		}
		else
		{
			$text = "<form method='post' action='".e_SELF."?".e_QUERY."'>";
		}
		$text .= "
		<table class='table adminlist'>
		<tr>
		<td colspan='2' text-align:center'>".FORLAN_28."</td>
		<td text-align:center'>".FORLAN_80."</td>
		</tr>";

		if (!$parent_amount = $sql->db_Select('forum', '*', "forum_parent='0' ORDER BY forum_order ASC"))
		{
			$text .= "<tr><td style='text-align:center' colspan='3'>".FORLAN_29."</td></tr>";
		}
		else
		{
			while ($row = $sql->db_Fetch(MYSQL_ASSOC))
			{
				$parentList[] = $row;
			}
			foreach($parentList as $parent)
			{
				$text .= "
				<tr>
				<td colspan='2'>".$parent['forum_name']."
				<br /><b>".FORLAN_140.":</b> ".$e107->user_class->uc_get_classname($parent['forum_class'])."&nbsp;&nbsp;<b>".FORLAN_141.":</b> ".$e107->user_class->uc_get_classname($parent['forum_postclass'])."
				</td>";

				$text .= "<td style='text-align:center'>";

				if ($mode)
				{
					$text .= "<select name='forum_order[]' class='tbox'>\n";
					for($a = 1; $a <= $parent_amount; $a++)
					{
						$text .= ($parent['forum_order'] == $a ? "<option value='{$parent['forum_id']}.{$a}' selected='selected'>$a</option>\n" : "<option value='{$parent['forum_id']}.{$a}'>$a</option>\n");
					}
					$text .= "</select>";
				}
				else
				{
					$text .= "
					<div style='text-align:left; padding-left: 30px'>
					<a href='".e_SELF."?cat.edit.{$parent['forum_id']}'>".ADMIN_EDIT_ICON."</a>
					<a href='".e_SELF."?delete.{$parent['forum_id']}'>".ADMIN_DELETE_ICON."</a>
					</div>
					";
				}
				$text .= "</td></tr>";

				$forumCount = $sql->db_Select('forum', '*', "forum_parent='".$parent['forum_id']."' AND forum_sub = 0 ORDER BY forum_order ASC");
				if (!$forumCount)
				{
					$text .= "<td colspan='4' style='text-align:center'>".FORLAN_29."</td>";
				}
				else
				{
					$forumList = array();
					while ($row = $sql->db_Fetch(MYSQL_ASSOC))
					{
						$forumList[] = $row;
					}
					foreach($forumList as $forum)
					{
						$text .= "
						<tr>
						<td style='width:5%; text-align:center'>".IMAGE_new."</td>\n<td style='width:55%'><a href='".$e107->url->create('forum/forum/view', $forum)."'>".$tp->toHTML($forum['forum_name'])."</a>";
//						<td style='width:5%; text-align:center'>".IMAGE_new."</td>\n<td style='width:55%'><a href='".e_PLUGIN."forum/forum_viewforum.php?{$forum['forum_id']}'>".$e107->tp->toHTML($forum['forum_name'])."</a>";

						$text .= "
						<br /><span class='smallblacktext'>".$e107->tp->toHTML($forum['forum_description'])."&nbsp;</span>
						<br /><b>".FORLAN_140.":</b> ".$e107->user_class->uc_get_classname($forum['forum_class'])."&nbsp;&nbsp;<b>".FORLAN_141.":</b> ".$e107->user_class->uc_get_classname($forum['forum_postclass'])."

						</td>

						<td colspan='2' style='text-align:center'>";

						if ($mode)
						{
							$text .= "<select name='forum_order[]' class='tbox'>\n";
							for($a = 1; $a <= $forumCount; $a++)
							{
								$sel = ($forum['forum_order'] == $a ? "selected='selected'" : '');
								$text .= "<option value='{$forum['forum_id']}.{$a}' {$sel}>{$a}</option>\n";
							}
							$text .= "</select>";
						}
						else
						{
							$sub_img = count($subList[$forum['forum_parent']][$forum['forum_id']]) ? IMAGE_sub : IMAGE_nosub;
							$text .= "
							<div style='text-align:left; padding-left: 30px'>
							<a href='".e_SELF."?create.edit.{$forum['forum_id']}'>".ADMIN_EDIT_ICON."</a>
							<a href='".e_SELF."?delete.{$forum['forum_id']}'>".ADMIN_DELETE_ICON."</a>
							&nbsp;&nbsp;<a href='".e_SELF."?subs.{$forum['forum_id']}'>".$sub_img."</a> (".count($subList[$forum['forum_parent']][$forum['forum_id']]).")
							</div>
							";
						}
						$text .= "</td>\n</tr>";
					}
				}
			}
		}

		if (!$mode)
		{
			$text .= "</table></div>";
			$e107->ns->tablerender(FORLAN_30, $text);
		}
		else
		{
			$text .= "<tr>\n<td colspan='4' style='text-align:center'>\n".$frm->admin_button('update_order', LAN_UPDATE, 'update')."\n</td>\n</tr>\n</table>\n</form>";
			$e107->ns->tablerender(FORLAN_37, $text);
		}

	}

	function create_parents($sub_action, $id)
	{
		global $e107;
		$frm = e107::getForm();
		$sql = e107::getDb();
		$tp = e107::getParser();
		$ns = e107::getRender();

		$id = (int)$id;
		if ($sub_action == 'edit' && !$_POST['update_parent'])
		{
			if ($sql->db_Select('forum', '*', "forum_id=$id"))
			{
				$row = $sql->db_Fetch(MYSQL_ASSOC);
			}
		}
		else
		{
			$row = array();
			$row['forum_name'] = '';
			$row['forum_class'] = e_UC_PUBLIC;
			$row['forum_postclass'] = e_UC_MEMBER;
			$row['forum_threadclass'] = e_UC_MEMBER;
		}

		$text = "
		<form method='post' action='".e_SELF.'?'.e_QUERY."'>
		<table class='table adminform'>

		<tr>
		<td>".LAN_NAME.":</td>
		<td>
		<input class='tbox' type='text' name='forum_name' size='60' value='".$tp->toForm($row['forum_name'])."' maxlength='250' />
		</td>
		</tr>

		<tr>
		<td>".FORLAN_23.":</td>
		<td>".$e107->user_class->uc_dropdown('forum_class', $row['forum_class'], 'nobody,public,member,admin,classes')."<span class='field-help'>".FORLAN_24."</span></td>
		</tr>

		<tr>
		<td>".FORLAN_142.":</td>
		<td>".$e107->user_class->uc_dropdown("forum_postclass", $row['forum_postclass'], 'nobody,public,member,admin,classes')."<span class='field-help'>".FORLAN_143."</span></td>
		</tr>

		<tr>
		<td>".FORLAN_184.":</td>
		<td>".$e107->user_class->uc_dropdown('forum_threadclass', $row['forum_threadclass'], 'nobody,public,member,admin,classes')."<span class='field-help'>".FORLAN_185."</span></td>
		</tr>

		<tr style='vertical-align:top'>
		<td colspan='2'  style='text-align:center'>";

		if ($sub_action == 'edit')
		{
			$text .= $frm->admin_button('update_parent', LAN_UPDATE, 'update');
		}
		else
		{
			$text .= $frm->admin_button('submit_parent', LAN_CREATE, 'submit');
		}

		$text .= "</td>
		</tr>
		</table>
		</form>";

		$ns->tablerender(FORLAN_75, $text);
	}

	function create_forums($sub_action, $id)
	{
		global $e107;
		$frm = e107::getForm();
		$sql = e107::getDb();
		$tp = e107::getParser();
		$ns = e107::getRender();

		$id = (int)$id;
		if ($sub_action == 'edit' && !$_POST['update_forum'])
		{
			if ($sql->db_Select('forum', '*', "forum_id=$id"))
			{
				$fInfo = $e107->sql->db_Fetch(MYSQL_ASSOC);
			}
		}
		else
		{
			$fInfo = array(
				'forum_parent' => 0,
				'forum_moderators' => e_UC_ADMIN,
				'forum_class' => e_UC_PUBLIC,
				'forum_postclass' => e_UC_MEMBER,
				'forum_threadclass' => e_UC_MEMBER
			);
		}

		$text = "
		<form method='post' action='".e_SELF.'?'.e_QUERY."'>\n
		<table class='table adminform'>
		<tr>
		<td>".FORLAN_22.":</td>
		<td>";

		$sql->db_Select('forum', '*', 'forum_parent=0');
		$text .= "<select name='forum_parent' class='tbox'>\n";
		while (list($fid, $fname) = $sql->db_Fetch(MYSQL_NUM))
		{
			$sel = ($fid == vartrue($fInfor['forum_parent']) ? "selected='selected'" : '');
			$text .= "<option value='{$fid}' {$sel}>{$fname}</option>\n";
		}
		$text .= "</select>
		</td>
		</tr>

		<tr>
			<td>".LAN_NAME.":</td>
			<td><input class='tbox' type='text' name='forum_name' size='60' value='".$tp->toForm(vartrue($fInfo['forum_name']))."' maxlength='250' /><span class='field-help'>".FORLAN_179."</span></td>
		</tr>

		<tr>
			<td>".LAN_DESCRIPTION.":</td>
			<td><textarea class='tbox' name='forum_description' cols='50' rows='5'>".$tp->toForm(vartrue($fInfo['forum_description']))."</textarea></td>
		</tr>

		<tr>
			<td>".FORLAN_33.":</td>
			<td>";
			$text .= $e107->user_class->uc_dropdown('forum_moderators', $fInfo['forum_moderators'], 'admin,classes')."<span class='field-help'>".FORLAN_34."</span>";
			$text .= "</td>
		</tr>
		
		<tr>
			<td>".FORLAN_23.":</td>
			<td>".$e107->user_class->uc_dropdown('forum_class', $fInfo['forum_class'], 'nobody,public,member,admin,classes')."<span class='field-help'>".FORLAN_24."</span></td>
		</tr>

		<tr>
			<td>".FORLAN_142.":</td>
			<td>".$e107->user_class->uc_dropdown('forum_postclass', $fInfo['forum_postclass'], 'nobody,public,member,admin,classes')."<span class='field-help'>".FORLAN_143."</span></td>
		</tr>

		<tr>
			<td>".FORLAN_184.":</td>
			<td>".$e107->user_class->uc_dropdown('forum_threadclass', $fInfo['forum_threadclass'], 'nobody,public,member,admin,classes')."<span class='field-help'>".FORLAN_185."</span></td>
		</tr>
		</table>
		
		<div class='buttons-bar center'>";
		if ($sub_action == "edit")
		{
			$text .= $frm->admin_button('update_forum', LAN_UPDATE, 'update');
		}
		else
		{
			$text .= $frm->admin_button('submit_forum', LAN_CREATE, 'submit');
		}
		$text .= "
		</div>
		</form>
";
		$ns->tablerender(FORLAN_28, $text);
	}

	function show_message($message)
	{
		$ns = e107::getRender();
		$ns->tablerender('', "<div style='text-align:center'><b>".$message."</b></div>"); //FIX: v2 style = render?
	}

	function show_tools()
	{
		$sql = e107::getDb();
		$ns = e107::getRender();
		$tp = e107::getParser();
		$frm = e107::getForm();

		$txt = "
		<form method='post' action='".e_SELF."?".e_QUERY."'>
		<table class='table adminlist'>
		<tr style='width:100%'>
		<td>".FORLAN_156."</td>
		</tr>
		<tr>
		<td>
		";
		if($sql->db_Select("forum", "*", "1 ORDER BY forum_order"))
		{
			$fList = $sql->db_getList();
			foreach($fList as $f)
			{
				$txt .= "<input type='checkbox' name='forumlist[{$f['forum_id']}]' value='1' /> ".$tp->toHTML($f['forum_name'])."<br />";
			}
			$txt .= "<input type='checkbox' name='forum_all' value='1' /> <strong>".FORLAN_157."</strong>";
		}
		$txt .= "
		</td>
		</tr>
		<tr>
		<td>".FORLAN_158."</td>
		</tr>
		<tr>
		<td>
		<input type='checkbox' name='lastpost' value='1' /> ".FORLAN_159." <br />&nbsp;&nbsp;&nbsp;&nbsp;
		<input type='checkbox' name='lastpost_nothread' value='1' checked='checked' /> ".FORLAN_160."
		</td>
		</tr>
		<tr>
		<td>".FORLAN_161."</td>
		</tr>
		<tr>
		<td>
			<input type='checkbox' name='counts' value='1' /> ".FORLAN_162."<br />
			&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='counts_threads' value='1' /><span style='text-align: center'> ".FORLAN_182."<br />".FORLAN_183."</span><br />
		</td>
		</tr>
		<tr>
		<td>".FORLAN_163."</td>
		</tr>
		<tr>
		<td>
		<input type='checkbox' name='userpostcounts' value='1' /> ".FORLAN_164."<br />
		</td>
		</tr>
		<tr>
		<td style='text-align:center'>
		".$frm->admin_button('tools', LAN_EXECUTE, 'submit')."
		</td>
		</tr>
		</table>
		</form>
		";
		$ns->tablerender(FORLAN_166, $txt);
	}

	function show_prefs()
	{
		global $fPref;
		$ns = e107::getRender();
		$sql    = e107::getDb(); 
		$e107 = e107::getInstance();
		$emessage = eMessage::getInstance();
		$frm = e107::getForm();

		$poll_installed = plugInstalled('poll');


		if(!$poll_installed)
		{
			if($fPref->get('poll') == 1)
			{
				$fPref['forum_poll'] = e_UC_NOBODY;
				$fPref->save(false, true);
			}
		}

		$text = "
		<form method='post' action='".e_SELF."?".e_QUERY."'>\n
		<table class='table adminform'>

		<tr>
		<td style='width:75%'>".FORLAN_44.":</td>
		<td style='width:25%'>".($fPref->get('enclose') ? "<input type='checkbox' name='forum_enclose' value='1' checked='checked' />" : "<input type='checkbox' name='forum_enclose' value='1' />")."<span class='field-help'>".FORLAN_45."</div></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_65.":</td>
		<td style='width:25%'><input class='tbox' type='text' name='forum_title' size='15' value='".$fPref->get('title')."' maxlength='100' /></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_47.":</td>
		<td style='width:25%'>".($fPref->get('notify') ? "<input type='checkbox' name='email_notify' value='1' checked='checked' />" : "<input type='checkbox' name='email_notify' value='1' />")."<span class='field-help'>".FORLAN_48."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_177.":</td>
		<td style='width:25%'>".($fPref->get('notify_on') ? "<input type='checkbox' name='email_notify_on' value='1' checked='checked' />" : "<input type='checkbox' name='email_notify_on' value='1' />")."<span class='field-help'>".FORLAN_178."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_49.":</td>";
		if($poll_installed)
		{
//			<td>".$e107->user_class->uc_dropdown("mods[{$f['forum_id']}]", $f['forum_moderators'], 'admin,classes')."</td>
			$text .= "<td style='width:25%'>".$e107->user_class->uc_dropdown('forum_poll', $fPref->get('poll'), 'admin,classes').'<span class="field-help">'.FORLAN_50.'</span></td>';
		}
		else
		{
			$text .= "<td style='width:25%'>".FORLAN_66."</td>";
		}
		$text .= "
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_70.":"; 

		if(!$pref['image_post'])
		{
			$text .= "<br /><b>".FORLAN_139."</b>"; // TODO LAN
		}
		if(!is_writable(e_PLUGIN.'forum/attachments'))
		{
			$text .= "<br /><b>Attachment dir (".e_PLUGIN_ABS.'forum/attachments'.") is not writable!</b>"; // TODO LAN
		}

		$text .= "</td>
		<td style='width:25%'>".($fPref->get('attach') ? "<input type='checkbox' name='forum_attach' value='1' checked='checked' />" : "<input type='checkbox' name='forum_attach' value='1' />")."<span class='field-help'>".FORLAN_71." <a href='".e_ADMIN."upload.php'>".FORLAN_130."</a> ". FORLAN_131."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_134.":</td>
		<td style='width:25%'><input class='tbox' type='text' size='3' maxlength='5' name='forum_maxwidth' value='".$fPref->get('maxwidth')."' /><span class='field-help'>".FORLAN_135."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_136.":</td>
		<td style='width:25%'>".($fPref->get('linkimg') ? "<input type='checkbox' name='forum_linkimg' value='1' checked='checked' />" : "<input type='checkbox' name='forum_linkimg' value='1' />")."<span class='field-help'>".FORLAN_137."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_51.":</td>
		<td style='width:25%'>".($fPref->get('track') ? "<input type='checkbox' name='forum_track' value='1' checked='checked' />" : "<input type='checkbox' name='forum_track' value='1' />")."<span class='field-help'>".FORLAN_52."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_112.":</td>
		<td style='width:25%'>".($fPref->get('redirect') ? "<input type='checkbox' name='forum_redirect' value='1' checked='checked' />" : "<input type='checkbox' name='forum_redirect' value='1' />")."<span class='field-help'>".FORLAN_113."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_116.":</td>
		<td style='width:25%'>".($fPref->get('reported_post_email') ? "<input type='checkbox' name='reported_post_email' value='1' checked='checked' />" : "<input type='checkbox' name='reported_post_email' value='1' />")."<span class='field-help'>".FORLAN_122."</span></td>
		</tr>


		<tr>
		<td style='width:75%'>".FORLAN_126.":</td>
		<td style='width:25%'>".($fPref->get('forum_tooltip') ? "<input type='checkbox' name='forum_tooltip' value='1' checked='checked' />" : "<input type='checkbox' name='forum_tooltip' value='1' />")."<span class='field-help'>".FORLAN_127."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_128.":</td>
		<td style='width:25%'><input class='tbox' type='text' name='forum_tiplength' size='15' value='".$fPref->get('tiplength')."' maxlength='20' /><span class='field-help'>".FORLAN_129."</span></td>
		</tr>


		<tr>
		<td style='width:75%'>".FORLAN_53.":</td>
		<td style='width:25%'><input class='tbox' type='text' name='forum_eprefix' size='15' value='".$fPref->get('eprefix')."' maxlength='20' /><span class='field-help'>".FORLAN_54."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_55.":</td>
		<td style='width:25%'><input class='tbox' type='text' name='forum_popular' size='3' value='".$fPref->get('popular')."' maxlength='3' /><span class='field-help'>".FORLAN_56."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_57.":</td>
		<td style='width:25%'><input class='tbox' type='text' name='forum_postspage' size='3' value='".$fPref->get('postspage')."' maxlength='3' /><span class='field-help'>".FORLAN_58."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_186.":</td>
		<td style='width:25%'><input class='tbox' type='text' name='forum_threadspage' size='3' value='".$fPref->get('threadspage')."' maxlength='3' /><span class='field-help'>".FORLAN_187."</span></td>
		</tr>

		<tr>
		<td style='width:75%'>".FORLAN_132.":</td>
		<td style='width:25%'>".($fPref->get('hilightsticky') ? "<input type='checkbox' name='forum_hilightsticky' value='1' checked='checked' />" : "<input type='checkbox' name='forum_hilightsticky' value='1' />")."<span class='field-help'>".FORLAN_133."</span></td>
		</tr>
		</table>
	

		<div class='buttons-bar center'>
			".$frm->admin_button('updateoptions', LAN_UPDATE, 'update')."
		</div>
		</form>
";
		$ns->tablerender(FORLAN_62, $emessage->render().$text);
	}

	function show_reported ($sub_action, $id)
	{
		global $rs; // FIX replace by $frm?
		$sql = e107::getDb();
		$ns = e107::getRender(); 
		$tp = e107::getParser();

		if ($sub_action) {
			$sql -> db_Select("generic", "*", "gen_id='".$sub_action."'");
			$row = $sql -> db_Fetch();
			$sql -> db_Select("user", "*", "user_id='". $row['gen_user_id']."'");
			$user = $sql -> db_Fetch();
			$con = new convert;
			$text = "
			<table class='table adminlist'>
			<tr>
			<td>
			".FORLAN_171.":
			</td>
			<td>
			<a href='".e_PLUGIN."forum/forum_viewtopic.php?".$row['gen_intdata'].".post' rel='external'>#".$row['gen_intdata']."</a>
			</td>
			</tr>
			<tr>
			<td>
			".FORLAN_173.":
			</td>
			<td>
			".$row['gen_ip']."
			</td>
			</tr>
			<tr>
			<td>
			".FORLAN_174.":
			</td>
			<td>
			<a href='".e_BASE."user.php?id.".$user['user_id']."'>".$user['user_name']."</a>
			</td>
			</tr>
			<tr>
			<td>
			".FORLAN_175.":
			</td>
			<td>
			".$con -> convert_date($row['gen_datestamp'], "long")."
			</td>
			</tr>
			<tr>
			<td>
			".FORLAN_176.":
			</td>
			<td>
			".$row['gen_chardata']."
			</td>
			</tr>
			<tr>
			<td style='text-align:center' colspan='2'>
			".$rs->form_open("post", e_SELF."?sr", "", "", "", " onsubmit=\"return confirm_('sr',".$row['gen_datestamp'].")\"")."
			".$rs->form_button("submit", "delete[reported_{$row['gen_id']}]", FORLAN_172)."
			".$rs->form_close()."
			</td>
			</tr>\n";
			$text .= "</table>";
			$text .= "</div>";
			$ns -> tablerender(FORLAN_116, $text);
		} else {
			if ($reported_total = $sql->db_Select("generic", "*", "gen_type='reported_post' OR gen_type='Reported Forum Post'"))
			{
				$text .= "<table class='table adminlist'>
				<tr>
				<td style='width:80%' >".FORLAN_170."</td>
				<td style='width:20%; text-align:center'>".FORLAN_80."</td>
				</tr>";
				while ($row = $sql->db_Fetch())
				{
					$text .= "<tr>
					<td style='width:80%'><a href='".e_SELF."?sr.".$row['gen_id']."'>".FORLAN_171." #".$row['gen_intdata']."</a></td>
					<td style='width:20%; text-align:center; vertical-align:top; white-space: nowrap'>
					".$rs->form_open("post", e_SELF."?sr", "", "", "", " onsubmit=\"return confirm_('sr',".$row['gen_datestamp'].")\"")."
					".$rs->form_button("submit", "delete[reported_{$row['gen_id']}]", FORLAN_172)."
					".$rs->form_close()."
					</td>
					</tr>\n";
				}
				$text .= "</table>";
			}
			else
			{
				$text = "<div style='text-align:center'>".FORLAN_121."</div>";
			}
			$ns->tablerender(FORLAN_116, $text);
		}
	}

	function show_prune()
	{
		$ns = e107::getRender();
		$sql = e107::getDB();
		$frm = e107::getForm();

		//		$sql -> db_Select("forum", "forum_id, forum_name", "forum_parent!=0 ORDER BY forum_order ASC");
		$qry = "
		SELECT f.forum_id, f.forum_name, sp.forum_name AS sub_parent, fp.forum_name AS forum_parent
		FROM #forum AS f
		LEFT JOIN #forum AS sp ON sp.forum_id = f.forum_sub
		LEFT JOIN #forum AS fp ON fp.forum_id = f.forum_parent
		WHERE f.forum_parent != 0
		ORDER BY f.forum_parent ASC, f.forum_sub, f.forum_order ASC
		";
		$sql -> db_Select_gen($qry);
		$forums = $sql -> db_getList();

		$text = "
		<form method='post' action='".e_SELF."?".e_QUERY."'>\n
		<table class='table adminlist'>
		<tr>
		<td>".FORLAN_60."</td>
		</tr>
		<tr>

		<td>".FORLAN_87."
		<input class='tbox' type='text' name='prune_days' size='6' value='' maxlength='3' />
		</td>
		</tr>

		<tr>
		<td>".FORLAN_2."<br />
		".FORLAN_89." <input type='radio' name='prune_type' value='delete' />&nbsp;&nbsp;&nbsp;
		".FORLAN_90." <input type='radio' name='prune_type' value='make_inactive' checked='checked' />
		</td>
		</tr>

		<tr>
		<td>".FORLAN_138.": <br />";

		foreach($forums as $forum)
		{
			$for_name = $forum['forum_parent']." -> ";
			$for_name .= ($forum['sub_parent'] ? $forum['sub_parent']." -> " : "");
			$for_name .= $forum['forum_name'];
			$text .= "<input type='checkbox' name='pruneForum[]' value='".$forum['forum_id']."' /> ".$for_name."<br />";
		}


		$text .= "<tr>
		<td colspan='2'  style='text-align:center'>
		".$frm->admin_button('do_prune', LAN_PRUNE, 'submit')."
		</td>
		</tr>
		</table>
		</form>";
		$ns->tablerender(LAN_PRUNE, $text);
	}


	function show_mods()
	{
		global $for;
		$ns = e107::getRender();
		$sql = e107::getDB();
		$e107 = e107::getInstance(); // FIX: needed?
		$forumList = $for->forum_getforums('all');
		$parentList = $for->forum_getparents('list');
		$subList   = $for->forumGetSubs('bysub');
		$frm = e107::getForm();
		$tp = e107::getParser();

		$txt = "<form method='post' action='".e_SELF."?".e_QUERY."'><table class='table adminlist'>";

		foreach($parentList as $p)
		{
			$txt .= "
			<tr>
			<td colspan='2' ><strong>".$tp->toHTML($p['forum_name'])."</strong></td>
			</tr>
			";

			foreach($forumList[$p['forum_id']] as $f)
			{
				$txt .= "
				<tr>
				<td>{$f['forum_name']}</td>
				<td>".$e107->user_class->uc_dropdown("mods[{$f['forum_id']}]", $f['forum_moderators'], 'admin,classes')."</td>
				</tr>
				";
				foreach($subList[$f['forum_id']] as $s)
				{
					$txt .= "
					<tr>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;{$s['forum_name']}</td>
					<td>".$e107->user_class->uc_dropdown("mods[{$s['forum_id']}]", $s['forum_moderators'], 'admin,classes')."</td>
					</tr>
					";
				}
			}
		}
			$txt .= "
			<tr>
			<td colspan='2'  style='text-align:center'>
			".$frm->admin_button('setMods', LAN_UPDATE, 'update')."
			</td>
			</tr>

			</table></form>";
			$ns->tablerender(FORLAN_33, $txt);  // FIX: LAN button update was WMGLAN_4." ".FORLAN_33)
		}

		function show_rules()
		{
			$pref = e107::getPref();
			$ns = e107::getRender();
			$sql = e107::getDB();
			$tp = e107::getParser();
			$frm = e107::getForm();

			$sql->db_Select("wmessage");
			list($null) = $sql->db_Fetch();
			list($null) = $sql->db_Fetch();
			list($null) = $sql->db_Fetch();
			list($id, $guestrules, $wm_active4) = $sql->db_Fetch();
			list($id, $memberrules, $wm_active5) = $sql->db_Fetch();
			list($id, $adminrules, $wm_active6) = $sql->db_Fetch();

			if($sql->db_Select('generic','*',"gen_type='forum_rules_guest'"))
			{
				$guest_rules = $sql->db_Fetch();
			}
			if($sql->db_Select('generic','*',"gen_type='forum_rules_member'"))
			{
				$member_rules = $sql->db_Fetch();
			}
			if($sql->db_Select('generic','*',"gen_type='forum_rules_admin'"))
			{
				$admin_rules = $sql->db_Fetch();
			}

			$guesttext = $tp->toFORM(vartrue($guest_rules['gen_chardata']));
			$membertext = $tp->toFORM(vartrue($member_rules['gen_chardata']));
			$admintext = $tp->toFORM(vartrue($admin_rules['gen_chardata']));

			$text = "
			<form method='post' action='".e_SELF."?rules'  id='wmform'>
			<table class='table adminform'>
			<tr>";

			$text .= "

			<td style='width:20%'>".WMGLAN_1.": <br />
			".WMGLAN_6.":";
			if (vartrue($guest_rules['gen_intdata']))
			{
				$text .= "<input type='checkbox' name='guest_active' value='1'  checked='checked' />";
			}
			else
			{
				$text .= "<input type='checkbox' name='guest_active' value='1' />";
			}
			$text .= "</td>
			<td>
			<textarea class='tbox' name='guestrules' cols='70' rows='10'>$guesttext</textarea>
			<br />
			<input class='helpbox' type='text' name='helpguest' size='100' />
			<br />
			".display_help('helpb', 1, 'addtext1', 'help1')."
			</td>
			</tr>

			<tr>
			<td style='width:20%'>".WMGLAN_2.": <br />
			".WMGLAN_6.":";
			if (vartrue($member_rules['gen_intdata']))
			{
				$text .= "<input type='checkbox' name='member_active' value='1'  checked='checked' />";
			}
			else
			{
				$text .= "<input type='checkbox' name='member_active' value='1' />";
			}
			$text .= "</td>
			<td>
			<textarea class='tbox' name='memberrules' cols='70' rows='10'>$membertext</textarea>
			<br />
			<input class='helpbox' type='text' name='helpmember' size='100' /> 
			<br />
			".display_help('helpb', 1, 'addtext2', 'help2')."
			</td>
			</tr>

			<tr>
			<td style='width:20%'>".WMGLAN_3.": <br />
			".WMGLAN_6.": ";

			if (vartrue($admin_rules['gen_intdata']))
			{
				$text .= "<input type='checkbox' name='admin_active' value='1'  checked='checked' />";
			}
			else
			{
				$text .= "<input type='checkbox' name='admin_active' value='1' />";
			}

			$text .= "</td>
			<td>
			<textarea class='tbox' name='adminrules' cols='70' rows='10'>$admintext</textarea>
			<br />
			<input class='helpbox' type='text' name='helpadmin' size='100' />
			<br />
			".display_help('helpb', 1, 'addtext3', 'help3')."
			</td>
			</tr>

			<tr style='vertical-align:top'>
			<td>&nbsp;</td>
			<td>
			".$frm->admin_button('frsubmit', WMGLAN_4, 'submit')."
			</td>
			</tr>
			</table>
			</form>";

			$ns->tablerender(WMGLAN_5, $text);

			echo "
			<script type=\"text/javascript\">
			function addtext1(sc){
				document.getElementById('wmform').guestrules.value += sc;
			}
			function addtext2(sc){
				document.getElementById('wmform').memberrules.value += sc;
			}
			function addtext3(sc){
				document.getElementById('wmform').adminrules.value += sc;
			}
			function help1(help){
				document.getElementById('wmform').helpguest.value = help;
			}
			function help2(help){
				document.getElementById('wmform').helpmember.value = help;
			}
			function help3(help){
				document.getElementById('wmform').helpadmin.value = help;
			}
			</script>
			";

		}
	}