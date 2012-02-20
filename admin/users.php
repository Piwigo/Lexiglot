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

defined('PATH') or die('Hacking attempt!');

// +-----------------------------------------------------------------------+
// |                         DELETE USER
// +-----------------------------------------------------------------------+
if ( isset($_GET['delete_user']) and is_numeric($_GET['delete_user']) )
{
  $query = 'DELETE FROM '.USERS_TABLE.' WHERE '.$conf['user_fields']['id'].' = '.$_GET['delete_user'].';';
  $done = (bool)mysql_query($query);
  
  $query = 'DELETE FROM '.USER_INFOS_TABLE.' WHERE user_id = '.$_GET['delete_user'].';';
  mysql_query($query);
  
  if ($done) array_push($page['infos'], 'User deleted');
}

// +-----------------------------------------------------------------------+
// |                         ACTIONS
// +-----------------------------------------------------------------------+
if ( isset($_POST['apply_action']) and $_POST['selectAction'] != '-1' and !empty($_POST['select']) )
{
  include(PATH.'admin/include/users.actions.php');
}

// +-----------------------------------------------------------------------+
// |                         SAVE STATUS
// +-----------------------------------------------------------------------+
if (isset($_POST['save_status']))
{
  $query = '
SELECT 
    status as old_status,
    my_languages,
    user_id
  FROM '.USER_INFOS_TABLE.'
;';
  $users = hash_from_query($query, 'user_id');
  
  foreach ($_POST['status'] as $user_id => $new_status)
  {
    $sets = array('status = "'.$new_status.'"');
    
    switch ($users[$user_id]['old_status'].'->'.$new_status)
    {
      // to translator (languages/sections depend on config)
      case 'visitor->translator':
        if ($conf['user_default_language'] == 'all')
        {
          array_push($sets, 'languages = "'.implode(',', array_keys($conf['all_languages'])).'"');
        }
        else if ($conf['user_default_language'] == 'own')
        {
          array_push($sets, 'languages = "'.$users[$user_id]['my_languages'].'"');
        }
        if ($conf['user_default_section'] == 'all')
        {
          array_push($sets, 'sections = "'.implode(',', array_keys($conf['all_sections'])).'"');
        }
        break;
      
      // to manager (all languages, sections depend on config)
      case 'visitor->manager':
        if ($conf['user_default_section'] == 'all')
        {
          array_push($sets, 'sections = "'.implode(',', array_keys($conf['all_sections'])).'"');
        }
      case 'translator->manager':
        array_push($sets, 'languages = "'.implode(',', array_keys($conf['all_languages'])).'"');
        break;
        
      // to admin (all languages/sections)
      case 'visitor->admin':
      case 'translator->admin':
      case 'manager->admin':
        array_push($sets, 'languages = "'.implode(',', array_keys($conf['all_languages'])).'"');
        array_push($sets, 'sections = "'.implode(',', array_keys($conf['all_sections'])).'"');
        break;
        
      // to visitor (none languages/sections)
      case 'translator->visitor':
      case 'manager->visitor':
      case 'admin->visitor':
        array_push($sets, 'languages = ""');
        array_push($sets, 'sections = ""');
        break;
    }
  
    $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET 
    '.implode(",    \n", $sets).'
  WHERE user_id = '.$user_id.'
;';
    mysql_query($query);
  }
  
  unset($users);
  array_push($page['infos'], 'Status saved.');
}

// +-----------------------------------------------------------------------+
// |                         ADD USER
// +-----------------------------------------------------------------------+
if (isset($_POST['add_user']))
{
  $page['errors'] = register_user(
    $_POST['username'],
    $_POST['password'],
    $_POST['email']
    );

  if (count($page['errors']) == 0)
  {
    array_push($page['infos'], 'User added');
  }
}

// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$where_clauses = array('1=1');
$search = array(
  'username' => null,
  'status' => '-1',
  'language' => '-1',
  'section' => '-1',
  );

// url input
if (isset($_GET['user_id']))
{
  $_POST['erase_search'] = true;
  array_push($where_clauses, 'u.id = '.$_GET['user_id']);
}
if (isset($_GET['lang_id']))
{
  $_POST['erase_search'] = true;
  $search['language'] = $_GET['lang_id'];
}
if (isset($_GET['section_id']))
{
  $_POST['erase_search'] = true;
  $search['section'] = $_GET['section_id'];
}
if (isset($_GET['status']))
{
  $_POST['erase_search'] = true;
  $search['status'] = $_GET['status'];
}

// erase search
if (isset($_POST['erase_search']))
{
  unset_session_var('user_search');
  unset($_POST);
}
// get saved search
else if (get_session_var('user_search') != null)
{
  $search = unserialize(get_session_var('user_search'));
}

// get form search
if (isset($_POST['search']))
{
  unset_session_var('user_search');
  if (isset($_GET['user_id']))    unset($_GET['user_id']);
  if (!empty($_POST['username'])) $search['username'] = str_replace('*', '%', $_POST['username']);
  if (!empty($_POST['status']))   $search['status'] =   $_POST['status'];
  if (!empty($_POST['language'])) $search['language'] = $_POST['language'];
  if (!empty($_POST['section']))  $search['section'] =  $_POST['section'];
}

// build query
if (!empty($search['username']))
{
  array_push($where_clauses, 'LOWER(u.'.$conf['user_fields']['username'].') LIKE LOWER("%'.$search['username'].'%")');
}
if ($search['status'] != '-1') 
{
  array_push($where_clauses, 'i.status = "'.$search['status'].'"');
}
if ($search['language'] != '-1') 
{
  array_push($where_clauses, 'i.languages LIKE "%'.$search['language'].'%"');
}
if ($search['section'] != '-1') 
{
  array_push($where_clauses, 'i.sections LIKE "%'.$search['section'].'%"');
}

// save search
if ( !isset($_GET['user_id']) and $where_clauses != array('1=1') )
{
  set_session_var('user_search', serialize($search));
}

// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
// get users infos
$query = '
SELECT u.*, i.*
  FROM '.USERS_TABLE.' as u
    INNER JOIN '.USER_INFOS_TABLE.' as i
    ON u.'.$conf['user_fields']['id'].'  = i.user_id
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
  ORDER BY u.'.$conf['user_fields']['username'].' ASC
;';
$_USERS = hash_from_query($query, 'id');


// +-----------------------------------------------------------------------+
// |                        TEMPLATE
// +-----------------------------------------------------------------------+
// add user
echo '
<form action="admin.php?page=users" method="post">
<fieldset class="common">
  <legend>Add a user</legend>
  
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

// search users
if (count($_USERS) or count($where_clauses) > 1)
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
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="username" value="'.$search['username'].'"></td>
      <td>
        <select name="status">
          <option value="-1" '.('-1'==$search['status']?'selected="selected"':null).'>-------</option>
          <option value="guest" '.('guest'==$search['status']?'selected="selected"':null).'>Guest</option>
          <option value="visitor" '.('visitor'==$search['status']?'selected="selected"':null).'>Visitor</option>
          <option value="translator" '.('translator'==$search['status']?'selected="selected"':null).'>Translator</option>
          <option value="manager" '.('manager'==$search['status']?'selected="selected"':null).'>Manager</option>
          <option value="admin" '.('admin'==$search['status']?'selected="selected"':null).'>Admin</option>
        </select>
      </td>
      <td>
        <select name="language">
          <option value="-1" '.('-1'==$search['language']?'selected="selected"':null).'>-------</option>';
        foreach ($conf['all_languages'] as $row)
        {
          echo '
          <option value="'.$row['id'].'" '.($row['id']==$search['language']?'selected="selected"':null).'>'.$row['name'].'</option>';
        }
        echo '
        </select>
      </td>
      <td>
        <select name="section">
          <option value="-1" '.('-1'==$search['section']?'selected="selected"':null).'>-------</option>';
        foreach ($conf['all_sections'] as $row)
        {
          echo '
          <option value="'.$row['id'].'" '.($row['id']==$search['section']?'selected="selected"':null).'>'.$row['name'].'</option>';
        }
        echo '
        </select>
      </td>
      <td>
        <input type="submit" name="search" class="blue" value="Search">
        <input type="submit" name="erase_search" class="red tiny" value="Erase">
      </td>
    </tr>
  </table>
</fieldset>
</form>';

// users list
echo '
<form id="users" action="admin.php?page=users" method="post">
<fieldset class="common">
  <legend>Manage</legend>
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="chkb"></th>
        <th class="user">Username</th>
        <th class="email">Email</th>
        <th class="date">Registration date</th>
        <th class="lang tiptip" title="Spoken">Languages</th>
        <th class="status">Status</th>
        <th class="lang tiptip" title="Assigned">Languages</th>
        <th class="section">Projects</th>
        <th class="actions"></th>
      </tr>
    </thead>
    <tbody>';
    foreach ($_USERS as $row)
    {
      $row['languages'] = !empty($row['languages']) ? explode(',', $row['languages']) : array();
      $row['my_languages'] = !empty($row['my_languages']) ? explode(',', $row['my_languages']) : array();
      $row['sections'] = !empty($row['sections']) ? explode(',', $row['sections']) : array();
      
      echo '
      <tr class="'.$row['status'].'">
        <td class="chkb"><input type="checkbox" name="select[]" value="'.$row['id'].'"></td>
        <td class="user">'.$row['username'].'</td>
        <td class="email">'.((!empty($row['email']) and $row['id']!=$conf['guest_id']) ? '<a href="mailto:'.$row['email'].'">'.$row['email'].'</a>' : null).'</td>
        <td class="date">'.format_date($row['registration_date'], true, false).'</td>
        <td class="lang">';
        if (count($row['my_languages']) > 0)
        {
          echo '<a class="expand" title=\'<table class="tooltip"><tr>';
          $i=1; $j=ceil(sqrt(count($row['my_languages'])/2));
          foreach ($row['my_languages'] as $lang)
          {
            echo '<td>'.get_language_flag($lang).' '.get_language_name($lang).'</td>';
            if($i%$j==0) echo '</tr><tr>'; $i++;
          }
          echo '</tr></table>\'>
            '.count($row['my_languages']).' <img src="template/images/bullet_toggle_plus.png" style="margin:5px 0 -5px 0;"></a>';
        }
        echo '
        </td>
        <td class="status">
          <span style="display:none;">'.$row['status'].'</span>
          <select name="status['.$row['id'].']" '.(($row['id']==$user['id'] or $row['id']==$conf['guest_id'])?'disabled="disabled"':null).'>
            '.($row['id']==$conf['guest_id'] ? '<option value="guest" selected="selected">Guest</option>' : null).'
            <option value="visitor" '.('visitor'==$row['status']?'selected="selected"':null).'>Visitor</option>
            <option value="translator" '.('translator'==$row['status']?'selected="selected"':null).'>Translator</option>
            <option value="manager" '.('manager'==$row['status']?'selected="selected"':null).'>Manager</option>
            <option value="admin" '.('admin'==$row['status']?'selected="selected"':null).'>Admin</option>
          </select>
        </td>
        <td class="lang">';
        if (count($row['languages']) > 0)
        {
          echo '<a class="expand" title=\'<table class="tooltip"><tr>';
          $i=1; $j=ceil(sqrt(count($row['languages'])/2));
          foreach ($row['languages'] as $lang)
          {
            echo '<td>'.get_language_flag($lang).' '.get_language_name($lang).'</td>';
            if($i%$j==0) echo '</tr><tr>'; $i++;
          }
          echo '</tr></table>\'>
            '.count($row['languages']).' <img src="template/images/bullet_toggle_plus.png" style="margin:5px 0 -5px 0;"></a>';
        }
        echo '
        </td>
        <td class="section">';
        if (count($row['sections']) > 0)
        {
          echo '<a class="expand" title=\'<table class="tooltip"><tr>';
          $i=1; $j=ceil(sqrt(count($row['sections'])/2));
          foreach ($row['sections'] as $section)
          {
            echo '<td>'.get_section_name($section).'</td>';
            if($i%$j==0) echo '</tr><tr>'; $i++;
          }
          echo '</tr></table>\'>
            '.count($row['sections']).' <img src="template/images/bullet_toggle_plus.png" style="margin:5px 0 -5px 0;"></a>';
        }
        echo '
        </td>
        <td class="actions">
          <img src="template/images/blank.png">';
        if ( in_array($row['status'], array('translator','manager','guest')) )
        {
          echo '
          <a href="'.get_url_string(array('page'=>'user_perm','user_id'=>$row['id']), 'all').'" title="Manage permissions"><img src="template/images/user_edit.png"></a>';
        }
        if ( !in_array($row['status'], array('admin','guest')) and $row['id'] != $user['id'] )
        {
          echo '
          <a href="'.get_url_string(array('delete_user'=>$row['id'])).'" title="Delete this user" onclick="return confirm(\'Are you sure?\');"><img src="template/images/cross.png" alt="[x]"></a>';
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
</fieldset>

<fieldset id="permitAction" class="common" style="display:none;margin-bottom:20px;">
  <legend>Global action <span class="unselectAll">[close]</span></legend>
  
  <select name="selectAction">
    <option value="-1">Choose an action...</option>
    <option disabled="disabled">------------------</option>
    <option value="delete_users">Delete users</option>
    <option value="change_status">Change Status</option>
    <option value="add_lang">Assign a language</option>
    <option value="add_section">Assign a project</option>
    <option value="remove_lang">Unassign a language</option>
    <option value="remove_section">Unassign a project</option>
  </select>
  
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
  
  <span id="action_add_lang" class="action-container">
    <select name="language_add">
      <option value="-1" '.('-1'==$search['language']?'selected="selected"':null).'>-------</option>';
    foreach ($conf['all_languages'] as $row)
    {
      echo '
      <option value="'.$row['id'].'" '.($row['id']==$search['language']?'selected="selected"':null).'>'.$row['name'].'</option>';
    }
    echo '
    </select>
  </span>
  
  <span id="action_add_section" class="action-container">
    <select name="section_add">
      <option value="-1" '.('-1'==$search['section']?'selected="selected"':null).'>-------</option>';
    foreach ($conf['all_sections'] as $row)
    {
      echo '
      <option value="'.$row['id'].'" '.($row['id']==$search['section']?'selected="selected"':null).'>'.$row['name'].'</option>';
    }
    echo '
    </select>
  </span>
  
  <span id="action_remove_lang" class="action-container">
    <select name="language_remove">
      <option value="-1" '.('-1'==$search['language']?'selected="selected"':null).'>-------</option>';
    foreach ($conf['all_languages'] as $row)
    {
      echo '
      <option value="'.$row['id'].'" '.($row['id']==$search['language']?'selected="selected"':null).'>'.$row['name'].'</option>';
    }
    echo '
    </select>
  </span>
  
  <span id="action_remove_section" class="action-container">
    <select name="section_remove">
      <option value="-1" '.('-1'==$search['section']?'selected="selected"':null).'>-------</option>';
    foreach ($conf['all_sections'] as $row)
    {
      echo '
      <option value="'.$row['id'].'" '.($row['id']==$search['section']?'selected="selected"':null).'>'.$row['name'].'</option>';
    }
    echo '
    </select>
  </span>
  
  <span id="action_apply" class="action-container">
    <input type="submit" name="apply_action" class="blue" value="Apply">
  </span>
</fieldset>
  
<input id="save_status" type="submit" name="save_status" class="blue big" value="Save status">
</form>';

$global_mail = 'mailto:'; $f = 1;
foreach ($_USERS as $row)
{
  if (!empty($row['email']) and $row['id']!=$conf['guest_id'])
  {
    if(!$f) $global_mail.= ';'; else $f = 0;
    $global_mail.= $row['email'];
  }
}

echo '
<a href="'.$global_mail.'">Send a mail to all users displayed</a>';
}

$page['header'].= '
<link type="text/css" rel="stylesheet" media="screen" href="template/js/jquery.tablesorter.css">
<script type="text/javascript" src="template/js/jquery.tablesorter.min.js"></script>';

$page['script'].= '
$(".expand").tipTip({ 
  maxWidth:"800px",
  delay:200,
  defaultPosition:"left"
});

$(".tiptip").tipTip({
  delay:200,
  defaultPosition:"top"
});

$("#users table").tablesorter({
  sortList: [[1,0]],
  headers: { 0: {sorter: false}, 8: {sorter: false} },
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

  if (nbSelected == 0) {
    $("#permitAction").hide();
    $("#save_status").show();
  } else {
    $("#permitAction").show();
    $("#save_status").hide();
  }
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

  if ($(this).val() != -1) {
    $("#action_apply").show();
  } else {
    $("#action_apply").hide();
  }
});';

?>