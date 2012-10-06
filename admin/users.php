<?php
// +-----------------------------------------------------------------------+
// | Lexiglot - A PHP based translation tool                               |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2011-2012 Damien Sorel       http://www.strangeplanet.fr |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

defined('LEXIGLOT_PATH') or die('Hacking attempt!');

$highlight_user = isset($_GET['from_id']) ? $_GET['from_id'] : null;

// +-----------------------------------------------------------------------+
// |                         DELETE USER
// +-----------------------------------------------------------------------+
if ( isset($_GET['delete_user']) and is_numeric($_GET['delete_user']) and is_admin() )
{
  if (USERS_TABLE == DB_PREFIX.'users')
  {
    $query = 'DELETE FROM '.USERS_TABLE.' WHERE '.$conf['user_fields']['id'].' = '.$_GET['delete_user'].';';
    $done = (bool)mysql_query($query);
  }
  else
  {
    $done = true;
  }
  
  $query = 'DELETE FROM '.USER_INFOS_TABLE.' WHERE user_id = '.$_GET['delete_user'].';';
  $done = $done && (bool)mysql_query($query);
  
  if ($done) array_push($page['infos'], 'User deleted');
}

// +-----------------------------------------------------------------------+
// |                         ACTIONS
// +-----------------------------------------------------------------------+
if ( isset($_POST['apply_action']) and $_POST['selectAction'] != '-1' and !empty($_POST['select']) )
{
  include(LEXIGLOT_PATH . 'admin/include/users.actions.php');
}

// +-----------------------------------------------------------------------+
// |                         SAVE STATUS
// +-----------------------------------------------------------------------+
if ( isset($_POST['save_status']) and is_admin() )
{
  $local_user = build_user($_POST['save_status']);
  $new_status = $_POST['status'][ $_POST['save_status'] ];
  $sets = array('status = "'.$new_status.'"');
  
  // adapt permissions
  include(LEXIGLOT_PATH . 'admin/include/users.change_status.php');

  $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET 
    '.implode(",    \n", $sets).'
  WHERE user_id = '.$_POST['save_status'].'
;';
  mysql_query($query);
  
  unset($local_user);
  
  array_push($page['infos'], 'Status saved.');
  $highlight_user = $_POST['save_status'];
}

// +-----------------------------------------------------------------------+
// |                         ADD USER
// +-----------------------------------------------------------------------+
if ( isset($_POST['add_user']) and is_admin() )
{
  $page['errors'] = register_user(
    $_POST['username'],
    $_POST['password'],
    $_POST['email']
    );

  if (count($page['errors']) == 0)
  {
    array_push($page['infos'], 'User added');
    $highlight_user = get_userid($_POST['username']);
  }
}

if ( isset($_POST['add_external_user']) and is_admin() )
{
  if ( empty($_POST['username']) and empty($_POST['id']) )
  {
    array_push($page['errors'], 'Missing username or user id');
  }
  else if (!empty($_POST['id']))
  {
    if ( ($username = get_username($_POST['id'])) === false )
    {
      array_push($page['errors'], 'Invalid user id');
    }
    else
    {
      create_user_infos($_POST['id']);
      array_push($page['infos'], 'User &laquo; '.$username.' &raquo; added');
      $highlight_user = $_POST['id'];
    }
  }
  else if (!empty($_POST['username']))
  {
    if ( ($user_id = get_userid($_POST['username'])) === false )
    {
      array_push($page['errors'], 'Invalid username');
    }
    else
    {
      create_user_infos($user_id);
      array_push($page['infos'], 'User &laquo; '.$_POST['username'].' &raquo; added');
      $highlight_user = $user_id;
    }
  }
}  

// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$search = array(
  'username' =>  array('%', ''),
  'language' => array('%', -1),
  'project' =>  array('%', -1),
  'status' =>    array('=', -1),
  'limit' =>     array('=', 20),
  );

// url input
if (isset($_GET['user_id']))
{
  $_POST['erase_search'] = true;
  $search['username'] = array('%', get_username($_GET['user_id']), '');
  unset($_GET['user_id']);
}
if (isset($_GET['language_id']))
{
  $_POST['erase_search'] = true;
  $search['language'] = array('%', $_GET['language_id'], -1);
  unset($_GET['language_id']);
}
if (isset($_GET['project_id']))
{
  $_POST['erase_search'] = true;
  $search['project'] = array('%', $_GET['project_id'], -1);
  unset($_GET['project_id']);
}
if (isset($_GET['status']))
{
  $_POST['erase_search'] = true;
  $search['status'] = array('=', $_GET['status'], -1);
  unset($_GET['status']);
}

$where_clauses = session_search($search, 'user_search', array('limit','project','language'));

// special for 'projects' and 'languages'
if (get_search_value('project') != -1) 
{
  if (get_search_value('project') == 'n/a/m') 
  {
    foreach ($user['manage_projects'] as $project)
      array_push($where_clauses, 'p.project != "'.$project.'"');
  }
  else if (get_search_value('project') == 'n/a') 
  {
    array_push($where_clauses, 'p.project IS NULL');
  }
  else
  {
    array_push($where_clauses, 'p.project = "'.get_search_value('project').'"');
  }
}
if (get_search_value('language') != -1) 
{
  if (get_search_value('language') == 'n/a') 
  {
    array_push($where_clauses, 'l.language IS NULL');
  }
  else
  {
    array_push($where_clauses, 'l.language = "'.get_search_value('language').'"');
  }
}

set_session_var('user_search', serialize($search));

// +-----------------------------------------------------------------------+
// |                         PAGINATION
// +-----------------------------------------------------------------------+
$join = null;
if (array_pos('l.language', $where_clauses) !== false)
{
  $join.= '
  LEFT JOIN '.USER_LANGUAGES_TABLE.' AS l
  ON u.'.$conf['user_fields']['id'].' = l.user_id';
}
if (array_pos('p.project', $where_clauses) !== false)
{
  $join.= '
  LEFT JOIN '.USER_PROJECTS_TABLE.' AS p
  ON u.'.$conf['user_fields']['id'].' = p.user_id';
}
  
$query = '
SELECT COUNT(1)
  FROM '.USER_INFOS_TABLE.' AS i
    INNER JOIN '.USERS_TABLE.' AS u
    ON u.'.$conf['user_fields']['id'].' = i.user_id
    '.$join.'
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
;';
list($total) = mysql_fetch_row(mysql_query($query));

$highlight_pos = null;
if (!empty($highlight_user))
{
  $query = '
SELECT x.pos
  FROM (
    SELECT 
        u.id,
        @rownum := @rownum+1 AS pos
      FROM '.USER_INFOS_TABLE.' AS i
        INNER JOIN '.USERS_TABLE.' AS u
        ON u.'.$conf['user_fields']['id'].' = i.user_id
        '.$join.'
        JOIN (SELECT @rownum := 0) AS r
      WHERE 
        '.implode("\n    AND ", $where_clauses).'
      ORDER BY u.'.$conf['user_fields']['username'].' ASC
  ) AS x
  WHERE x.id = "'.$highlight_user.'"
;';
  list($highlight_pos) = mysql_fetch_row(mysql_query($query));
}

$paging = compute_pagination($total, get_search_value('limit'), 'nav', $highlight_pos);

// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$_USERS = get_users_list($where_clauses, array(), 'AND', $paging['Start'], $paging['Entries']);


// +-----------------------------------------------------------------------+
// |                        TEMPLATE
// +-----------------------------------------------------------------------+
$displayed_projects = is_admin() ? $conf['all_projects'] : array_intersect_key($conf['all_projects'], create_permissions_array($user['manage_projects']));

// create a new user
if (is_admin())
{
  echo '
  <form action="admin.php?page=users" method="post" '.(USERS_TABLE != DB_PREFIX.'users' ? 'style="float:left;width:70%;"' : null).'>
  <fieldset class="common">
    <legend>Create a new user</legend>
    
    <table class="search">
      <tr>
        <th>Username <span class="red">*</span></th>
        <th>Password <span class="red">*</span></th>
        <th>Email <span class="red">*</span></th>
        <th></th>
      </tr>
      <tr>
        <td><input type="text" name="username"></td>
        <td><input type="password" name="password"></td>
        <td><input type="text" name="email"></td>
        <td><input type="submit" name="add_user" class="blue" value="Add"></td>
      </tr>
    </table>
    
  </fieldset>
  </form>';

  // add user from external table
  if (USERS_TABLE != DB_PREFIX.'users')
  {
  echo '
  <form action="admin.php?page=users" method="post" style="float:left;width:30%;">
  <fieldset class="common">
    <legend>Add an user from external table</legend>
    
    <table class="search">
      <tr>
        <th>Username</th>
        <th></th>
        <th>Id</th>
        <th></th>
      </tr>
      <tr>
        <td><input type="text" name="username"></td>
        <td>or</td>
        <td><input type="text" name="id" size="5"></td>
        <td><input type="submit" name="add_external_user" class="blue" value="Add"></td>
      </tr>
    </table>
    
  </fieldset>
  </form>
  <div style="clear:both;"></div>';
  }
}

// search users
if ( count($_USERS) or count($where_clauses) > 1 )
{
echo '
<form action="admin.php?page=users" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>Username</th>
      <th>Status</th>
      <th>Language</th>
      <th>Project</th>
      <th>Entries</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="username" value="'.get_search_value('username').'"></td>
      <td>
        <select name="status">
          <option value="-1" '.(-1==get_search_value('status')?'selected="selected"':null).'>-------</option>
          <option value="guest" '.('guest'==get_search_value('status')?'selected="selected"':null).'>Guest</option>
          <option value="visitor" '.('visitor'==get_search_value('status')?'selected="selected"':null).'>Visitor</option>
          <option value="translator" '.('translator'==get_search_value('status')?'selected="selected"':null).'>Translator</option>
          <option value="manager" '.('manager'==get_search_value('status')?'selected="selected"':null).'>Manager</option>
          <option value="admin" '.('admin'==get_search_value('status')?'selected="selected"':null).'>Admin</option>
        </select>
      </td>
      <td>
        <select name="language">
          <option value="-1" '.(-1==get_search_value('language')?'selected="selected"':null).'>-------</option>
          <option value="n/a" '.('n/a'==get_search_value('language')?'selected="selected"':null).'>-- none assigned --</option>';
        foreach ($conf['all_languages'] as $row)
        {
          echo '
          <option value="'.$row['id'].'" '.($row['id']==get_search_value('language')?'selected="selected"':null).'>'.$row['name'].'</option>';
        }
        echo '
        </select>
      </td>
      <td>
        <select name="project">
          <option value="-1" '.(-1==get_search_value('project')?'selected="selected"':null).'>-------</option>
          <option value="n/a" '.('n/a'==get_search_value('project')?'selected="selected"':null).'>-- none assigned --</option>
          '.(is_manager() ? '<option value="n/a/m" '.('n/a/m'==get_search_value('project')?'selected="selected"':null).'>-- none of mine --</option>' : null);
        foreach ($displayed_projects as $row)
        {
          echo '
          <option value="'.$row['id'].'" '.($row['id']==get_search_value('project')?'selected="selected"':null).'>'.$row['name'].'</option>';
        }
        echo '
        </select>
      </td>
      <td><input type="text" name="limit" size="3" value="'.get_search_value('limit').'"></td>
      <td>
        <input type="submit" name="search" class="blue" value="Search">
        <input type="submit" name="erase_search" class="red tiny" value="Reset">
      </td>
    </tr>
  </table>
</fieldset>
</form>';

// users list
echo '
<form id="users" action="admin.php?page=users'.(!empty($_GET['nav']) ? '&amp;nav='.$_GET['nav'] : null).'" method="post">
<fieldset class="common">
  <legend>Manage</legend>
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="chkb"></th>
        <th class="user">Username</th>
        <th class="email">Email</th>
        <th class="date">Registration date</th>
        <th class="language lang-tip" title="Spoken">Languages</th>
        <th class="status">Status</th>
        <th class="lang lang-tip" title="Assigned">Languages</th>
        <th class="project">Projects</th>
        <th class="actions"></th>
      </tr>
    </thead>
    <tbody>';
    foreach ($_USERS as $row)
    {      
      echo '
      <tr class="'.$row['status'].' '.($highlight_user==$row['id'] ? 'highlight' : null).'">
        <td class="chkb">
          <input type="checkbox" name="select[]" value="'.$row['id'].'">
        </td>
        <td class="user">
          '.($row['id']!=$conf['guest_id'] ? '<a href="'.get_url_string(array('user_id'=>$row['id']), true, 'profile').'">'.$row['username'].'</a>' : $row['username']).'
        </td>
        <td class="email">
          '.(!empty($row['email']) ? '<a href="mailto:'.$row['email'].'">'.$row['email'].'</a>' : null).'
        </td>
        <td class="date">
          <span style="display:none;">'.strtotime($row['registration_date']).'</span>'.format_date($row['registration_date'], true, false).'
        </td>
        <td class="language">';
        if (count($row['my_languages']) > 0)
        {
          echo print_user_languages_tooltip($row, 3, true);
        }
        echo '
        </td>
        <td class="status">';
        if (is_admin())
        {
          echo'
          <span style="display:none;">'.$row['status'].'</span>
          <select name="status['.$row['id'].']" data="'.$row['id'].'" '.(in_array($row['id'], array($user['id'], $conf['guest_id'])) ? 'disabled="disabled"' : null).'>
            '.($row['id']==$conf['guest_id'] ? '<option value="guest" selected="selected">Guest</option>' : null).'
            <option value="visitor" '.('visitor'==$row['status']?'selected="selected"':null).'>Visitor</option>
            <option value="translator" '.('translator'==$row['status']?'selected="selected"':null).'>Translator</option>
            <option value="manager" '.('manager'==$row['status']?'selected="selected"':null).'>Manager</option>
            <option value="admin" '.('admin'==$row['status']?'selected="selected"':null).'>Admin</option>
          </select>';
        }
        else
        {
          echo $row['status'];
        }
        echo '
        </td>
        <td class="language">';
        if (count($row['languages']) > 0)
        {
          echo print_user_languages_tooltip($row);
        }
        echo '
        </td>
        <td class="project">';
        if (count($row['projects']) > 0)
        {
          echo print_user_projects_tooltip($row);
        }
        echo '
        </td>  
        <td class="actions">';
        if ( !in_array($row['status'], array('admin','visitor')) and $row['id']!=$user['id'] and (!is_manager() or $row['id']!=$conf['guest_id']) )
        {
          echo ' <a href="'.get_url_string(array('page'=>'user_perm','user_id'=>$row['id']), true).'" title="Manage permissions"><img src="template/images/user_edit.png"></a>';
        }
        if ( !in_array($row['status'], array('admin','guest')) and $row['id']!=$user['id'] and is_admin() )
        {
          echo ' <a href="'.get_url_string(array('delete_user'=>$row['id'])).'" title="Delete this user" onclick="return confirm(\'Are you sure?\');"><img src="template/images/cross.png" alt="[x]"></a>';
        }
        else
        {
          echo ' <span style="display:inline-block;width:16px;">&nbsp;</span>';
        }
        echo '
        </td>
      </tr>';
    }
    if (count($_USERS) == 0)
    {
      echo '
      <tr>
        <td colspan="9"><i>No results</i></td>
      </tr>';
    }
    echo '
    </tbody>
  </table>
  <a href="#" class="selectAll">Select All</a> / <a href="#" class="unselectAll">Unselect all</a>
  <div class="pagination">'.display_pagination($paging, 'nav').'</div>
</fieldset>

<fieldset id="permitAction" class="common" style="display:none;margin-bottom:20px;">
  <legend>Global action <span class="unselectAll">[close]</span></legend>
  
  <select name="selectAction">
    <option value="-1">Choose an action...</option>
    <option disabled="disabled">------------------</option>
    <option value="send_email">Send email</option>
    '.(is_admin() ? '<option value="delete_users">Delete users</option>
    <!--<option value="change_status">Change status</option>-->
    <option value="add_language">Assign a language</option>
    <option value="remove_language">Unassign a language</option>' : null).'
    <option value="add_project">Assign a project</option>
    <option value="remove_project">Unassign a project</option>
  </select>
  
  <span id="action_send_email" class="action-container">
    <a href="mailto:">Send</a>
  </span>
  
  <span id="action_delete_users" class="action-container">
    <label><input type="checkbox" name="confirm_deletion" value="1"> Are you sure ?</label>
  </span>
  
  <span id="action_change_status" class="action-container">
    <select name="batch_status">
      <option value="-1">-------</option>
      <option value="visitor">Visitor</option>
      <option value="translator">Translator</option>
      <option value="manager">Manager</option>
    </select>
  </span>
  
  <span id="action_add_language" class="action-container">
    <select name="language_add">
      <option value="-1">-------</option>';
    foreach ($conf['all_languages'] as $row)
    {
      echo '
      <option value="'.$row['id'].'">'.$row['name'].'</option>';
    }
    echo '
    </select>
  </span>
  
  <span id="action_remove_language" class="action-container">
    <select name="language_remove">
      <option value="-1">-------</option>';
    foreach ($conf['all_languages'] as $row)
    {
      echo '
      <option value="'.$row['id'].'">'.$row['name'].'</option>';
    }
    echo '
    </select>
  </span>
  
  <span id="action_add_project" class="action-container">
    <select name="project_add">
      <option value="-1">-------</option>';
    foreach ($conf['all_projects'] as $row)
    {
      echo '
      <option value="'.$row['id'].'">'.$row['name'].'</option>';
    }
    echo '
    </select>
  </span>
  
  <span id="action_remove_project" class="action-container">
    <select name="project_remove">
      <option value="-1">-------</option>';
    foreach ($conf['all_projects'] as $row)
    {
      echo '
      <option value="'.$row['id'].'">'.$row['name'].'</option>';
    }
    echo '
    </select>
  </span>
  
  <span id="action_apply" class="action-container">
    <input type="submit" name="apply_action" class="blue" value="Apply">
  </span>
</fieldset>

</form>';
}


// +-----------------------------------------------------------------------+
// |                        JAVASCRIPT
// +-----------------------------------------------------------------------+
load_jquery('tablesorter');
load_jquery('tiptip');

if (is_admin())
{
$page['script'].= '
$("#users select[name^=\"status\"]").change(function() {
  $("#users").append("<input type=\"hidden\" name=\"save_status\" value=\""+ $(this).attr("data") +"\">").submit();
});';
}

$page['script'].= '
$(".expand").css("cursor", "help").tipTip({ 
  maxWidth:"800px",
  delay:200,
  defaultPosition:"left"
});

$(".lang-tip").css("cursor", "help").tipTip({
  delay:200,
  defaultPosition:"top"
});

$("#users table").tablesorter({
  sortList: [[1,0]],
  headers: { 0: {sorter: false}, 4: {sorter: false}, 6: {sorter: false}, 7: {sorter: false}, 8: {sorter: false} },
  widgets: ["zebra"]
});

/* actions */
function checkPermitAction() {
  var nbSelected = 0;

  $("td.chkb input[type=checkbox]").each(function() {
     if ($(this).is(":checked")) {
       nbSelected++;
     }
  });
  
  if ($("select[name=selectAction]").attr("value") == "send_email") {
    updateEmailLink();
  }

  if (nbSelected == 0) {
    $("#permitAction").hide();
    $("#save_status").show();
  } else {
    $("#permitAction").show();
    $("#save_status").hide();
  }
}

function updateEmailLink() {
  var link = "mailto:";
  $("td.chkb input[type=checkbox]:checked").each(function() {
    mail = $(this).parent("td.chkb").nextAll("td.email").children("a").html();
    if (mail) link+= mail+";";
  });
  $("#action_send_email").children("a").attr("href", link);
}

$("[id^=action_]").hide();

$("td.chkb input[type=checkbox]").change(function () {
  checkPermitAction();
});

$(".selectAll").click(function() {
  $("td.chkb input[type=checkbox]").each(function() {
     $(this).attr("checked", true);
  });
  checkPermitAction();
  return false;
});
$(".unselectAll").click(function() {
  $("td.chkb input[type=checkbox]").each(function() {
     $(this).attr("checked", false);
  });
  checkPermitAction();
  return false;
});

$("select[name=selectAction]").change(function() {
  $("[id^=action_]").hide();
  $("#action_"+$(this).attr("value")).show();
  
  if ($(this).attr("value") == "send_email") {
    updateEmailLink();
  }

  if ($(this).val() != -1 && $(this).val() != "send_email") {
    $("#action_apply").show();
  } else {
    $("#action_apply").hide();
  }
});

$("td.user").click(function() {
  $checkbox = $(this).prev("td.chkb").children("input");
  $checkbox.attr("checked", !$checkbox.attr("checked"));
});';

?>