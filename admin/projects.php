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

$highlight_section = isset($_GET['from_id']) ? $_GET['from_id'] : null;

// +-----------------------------------------------------------------------+
// |                         DELETE SECTION
// +-----------------------------------------------------------------------+
if ( isset($_GET['delete_section']) and ( is_admin() or (is_manager($_GET['delete_section']) and $user['manage_perms']['can_delete_projects']) ) )
{
  if (!array_key_exists($_GET['delete_section'], $conf['all_sections']))
  {
    array_push($page['errors'], 'Unknown project.');
  }
  else
  {
    // delete sections from user infos
    $users = get_users_list(
      array('sections LIKE "%'.$_GET['delete_section'].'%"'), 
      'sections'
      );
    
    foreach ($users as $u)
    {
      unset($u['sections'][ array_search($_GET['delete_section'], $u['sections']) ]);
      unset($u['manage_sections'][ array_search($_GET['delete_section'], $u['manage_sections']) ]);
      $u['sections'] = create_permissions_array($u['sections']);
      $u['manage_sections'] = create_permissions_array($u['manage_sections'], 1);    
      $u['sections'] = implode_array(array_merge($u['sections'], $u['manage_sections']));
      
      $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET sections = '.(!empty($u['sections']) ? '"'.$u['sections'].'"' : 'NULL').'
  WHERE user_id = '.$u['id'].'
;';
      mysql_query($query);
    }
    
    // delete directory
    @rrmdir($conf['local_dir'].$_GET['delete_section']);  
      
    // delete from stats table
    $query = '
DELETE FROM '.STATS_TABLE.'
  WHERE section = "'.$_GET['delete_section'].'"
;';
    mysql_query($query);
    
    // delete from sections table
    $query = '
DELETE FROM '.SECTIONS_TABLE.' 
  WHERE id = "'.$_GET['delete_section'].'"
;';
    mysql_query($query);
    
    array_push($page['infos'], 'Project deleted.');
  }
}

// +-----------------------------------------------------------------------+
// |                         ACTIONS
// +-----------------------------------------------------------------------+
if ( isset($_POST['apply_action']) and $_POST['selectAction'] != '-1' and !empty($_POST['select']) )
{
  include(PATH.'admin/include/projects.actions.php');
}

// +-----------------------------------------------------------------------+
// |                         MAKE STATS
// +-----------------------------------------------------------------------+
if (isset($_GET['make_stats']))
{
  if (array_key_exists($_GET['make_stats'], $conf['all_sections']))
  {
    make_section_stats($_GET['make_stats']);
    array_push($page['infos'], 'Stats refreshed for project &laquo; '.get_section_name($_GET['make_stats']).' &raquo;');
    $highlight_section = $_GET['make_stats'];
  }
}

// +-----------------------------------------------------------------------+
// |                         SAVE SECTIONS
// +-----------------------------------------------------------------------+
if (isset($_POST['save_section']))
{
  $query = '
SELECT id, directory, files
  FROM '.SECTIONS_TABLE.'
  WHERE id IN("'.implode('","', array_keys($_POST['sections'])).'")
;';
  $old_values = hash_from_query($query, 'id');
  
  foreach ($_POST['sections'] as $section_id => $row)
  {
    $regenerate_stats = false;
    $errors = array();
    // check name
    if (empty($row['name']))
    {
      array_push($errors, 'Name is empty for project &laquo;'.$section_id.'&raquo;.');
    }
    // check directory
    if ($conf['svn_activated'] and empty($row['directory']))
    {
      array_push($errors, 'Directory is empty for project &laquo;'.$section_id.'&raquo;.');
    }
    else if (!empty($row['directory']))
    {
      $row['directory'] = rtrim($row['directory'], '/').'/';
    }
    // check files
    $row['files'] = str_replace(' ', null, $row['files']);
    if (!preg_match('#^(([a-zA-Z0-9\._\-/]+)([,]{1}))+$#', $row['files'].','))
    {
      array_push($errors, 'Seperate each file with a comma for project &laquo;'.$section_id.'&raquo;.');
    }
    else if ($row['files'] != $old_values[$section_id]['files'])
    {
      $regenerate_stats = true;
    }
    // check rank
    if (!is_numeric($row['rank']) or $row['rank'] < 1)
    {
      array_push($errors, 'Rank must be an non null integer for project &laquo;'.$section_id.'&raquo;.');
    }
    // check category
    if ( !count($errors) and !empty($row['category_id']) and !is_numeric($row['category_id']) )
    {
      $row['category_id'] = add_category($row['category_id'], 'section');
    }
    if (empty($row['category_id']))
    {
      $row['category_id'] = 0;
    }
    
    // switch directory
    if ( !count($errors) and $conf['svn_activated'] and $old_values[$section_id]['directory'] != $row['directory'] )
    {
      $svn_result = svn_switch($conf['svn_server'].$row['directory'], $conf['local_dir'].$section_id);
      if ($svn_result['level'] == 'error')
      {
        array_push($errors, $svn_result['msg']);
      }
      else
      {
        $regenerate_stats = true;
      }
    }
    
    // save section
    if (count($errors) == 0)
    {
      $query = '
UPDATE '.SECTIONS_TABLE.'
  SET 
    name = "'.$row['name'].'",
    directory = "'.$row['directory'].'",
    files = "'.$row['files'].'",
    rank = '.$row['rank'].',
    category_id = '.$row['category_id'].'
  WHERE id = "'.$section_id.'"
;';
      mysql_query($query);
      
      // update stats
      if ($regenerate_stats)
      {
        make_section_stats($section_id);
      }
    }
    else
    {
      $page['errors'] = array_merge($page['errors'], $errors);
    }
  }
  
  // reload sections array
  $query = 'SELECT * FROM '.SECTIONS_TABLE.' ORDER BY id;';
  $conf['all_sections'] = hash_from_query($query, 'id');
  ksort($conf['all_sections']);

  
  if (count($page['errors']) == 0)
  {
    array_push($page['infos'], 'Modifications saved.');
  }
}

// +-----------------------------------------------------------------------+
// |                         ADD SECTION
// +-----------------------------------------------------------------------+
if ( isset($_POST['add_section']) and ( is_admin() or $user['manage_perms']['can_add_projects'] ) )
{
  // check name and id
  if (empty($_POST['name']))
  {
    array_push($page['errors'], 'Name is empty.');
  }
  else
  {
    $_POST['id'] = str2url($_POST['name']);
    $query ='
SELECT id
  FROM '.SECTIONS_TABLE.'
  WHERE id = "'.$_POST['id'].'"
';
    $result = mysql_query($query);
    if (mysql_num_rows($result))
    {
      array_push($page['errors'], 'A project with this name already exists.');
    }
  }
  // check files
  $_POST['files'] = str_replace(' ', null, $_POST['files']);
  if (!preg_match('#^(([a-zA-Z0-9\._\-/]+)([,]{1}))+$#', $_POST['files'].','))
  {
    array_push($page['errors'], 'Seperate each file with a comma.');
  }
  // check rank
  if (!is_numeric($_POST['rank']) or $_POST['rank'] < 1)
  {
    array_push($page['errors'], 'Rank must be an non null integer.');
  }
  // check category
  if ( !count($page['errors']) and !empty($_POST['category_id']) and !is_numeric($_POST['category_id']) )
  {
    $_POST['category_id'] = add_category($_POST['category_id'], 'section');
  }
  if (empty($_POST['category_id']))
  {
    $_POST['category_id'] = 0;
  }
  // check directory
  if ( $conf['svn_activated'] and empty($_POST['directory']) )
  {
    array_push($page['errors'], 'Directory is empty');
  }
  else if ( !count($page['errors']) and $conf['svn_activated'] )
  {
    $_POST['directory'] = rtrim($_POST['directory'], '/').'/';
    if (file_exists($conf['local_dir'].$_POST['id']))
    {
      array_push($page['errors'], 'A local directory with the name &laquo;'.$_POST['id'].'&raquo; already exists, I can\'t do a checkout to the SVN server.');
    }
    else
    {
      $svn_result = svn_checkout($conf['svn_server'].$_POST['directory'], $conf['local_dir'].$_POST['id']);
      if ($svn_result['level'] == 'error')
      {
        array_push($page['errors'], $svn_result['msg']);
      }
      else
      {
        $svn_result = $svn_result['msg'];
        chmod($conf['local_dir'].$_POST['id'], 0777);
      }
    }
  }
  else if (!count($page['errors']))
  {
    $svn_result = 'section created.';
    if (!file_exists($conf['local_dir'].$_POST['id']))
    {
      mkdir($conf['local_dir'].$_POST['id'], 0777, true);
    }
  }
  
  // save section
  if (count($page['errors']) == 0)
  {
    $query = '
INSERT INTO '.SECTIONS_TABLE.'(
    id, 
    name, 
    directory, 
    files,
    rank,
    category_id
  )
  VALUES(
    "'.$_POST['id'].'",
    "'.$_POST['name'].'",
    "'.$_POST['directory'].'",
    "'.$_POST['files'].'",
    '.$_POST['rank'].',
    '.$_POST['category_id'].'
  )
;';
    mysql_query($query);
    
    // add section on user infos
    $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET sections = IF(
    sections="",
    "'.$_POST['id'].','.(is_manager()?'1':'0').'",
    CONCAT(sections, ";'.$_POST['id'].','.(is_manager()?'1':'0').'")
    )
  WHERE status IN( "admin"'.($conf['section_default_user'] == 'all' ? ', "translator", "guest", "manager"' : null).' )
;';
    mysql_query($query);
    
    // update sections array
    $conf['all_sections'][ $_POST['id'] ] = array(
                                              'id' => $_POST['id'],
                                              'name' => $_POST['name'],
                                              'directory' => $_POST['directory'],
                                              'files' => $_POST['files'],
                                              'rank' => $_POST['rank'],
                                              'category_id' => $_POST['category_id'],
                                              );
    ksort($conf['all_sections']);

    // generate stats
    make_section_stats($_POST['id']);
    
    array_push($page['infos'], '<b>'.$_POST['name'].'</b> : '.$svn_result);
    $highlight_section = $_POST['id'];
    $_POST['erase_search'] = true;
  }
}

// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$search = array(
  'id' =>          array('%', ''),
  'name' =>        array('%', ''),
  'rank' =>        array('=', ''),
  'category_id' => array('=', -1),
  'limit' =>       array('=', 20),
  );

// url input
if (isset($_GET['section_id']))
{
  $_POST['erase_search'] = true;
  $search['id'] = array('%', $_GET['section_id'], '');
  unset($_GET['section_id']);
}

$where_clauses = session_search($search, 'section_search', array('limit'));

// +-----------------------------------------------------------------------+
// |                         PAGINATION
// +-----------------------------------------------------------------------+
$query = '
SELECT COUNT(1)
  FROM '.SECTIONS_TABLE.'
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
;';
list($total) = mysql_fetch_row(mysql_query($query));

$highlight_pos = null;
if (!empty($highlight_section))
{
  $query = '
SELECT x.pos
  FROM (
    SELECT 
        id,
        @rownum := @rownum+1 AS pos
      FROM '.SECTIONS_TABLE.'
        JOIN (SELECT @rownum := 0) AS r
      ORDER BY rank DESC, id ASC
  ) AS x
  WHERE x.id = "'.$highlight_section.'"
;';
  list($highlight_pos) = mysql_fetch_row(mysql_query($query));
}

$paging = compute_pagination($total, get_search_value('limit'), 'nav', $highlight_pos);

// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$displayed_sections = is_admin() ? array_keys($conf['all_sections']) : $user['manage_sections'];

if (is_manager())
{
  array_push($where_clauses, 'id IN("'.implode('","', $displayed_sections).'")');
}

$query = '
SELECT 
    s.*,
    COUNT(u.user_id) as total_users
  FROM '.SECTIONS_TABLE.' as s
    INNER JOIN '.USER_INFOS_TABLE.' as u
      ON u.sections LIKE CONCAT("%",s.id,"%") AND u.status != "guest"
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
  GROUP BY s.id
  ORDER BY s.rank DESC, s.id ASC
  LIMIT '.$paging['Entries'].'
  OFFSET '.$paging['Start'].'
;'; 
$_DIRS = hash_from_query($query, 'id');

$query = '
SELECT id, name
  FROM '.CATEGORIES_TABLE.'
  WHERE type = "section"
;';
$categories = hash_from_query($query, 'id');
$categories_json = implode(',', array_map(create_function('$row', 'return \'{id: "\'.$row["id"].\'", name: "\'.$row["name"].\'"}\';'), $categories));


// +-----------------------------------------------------------------------+
// |                        TEMPLATE
// +-----------------------------------------------------------------------+
// add section
if ( is_admin() or $user['manage_perms']['can_add_projects'] )
{
echo '
<form action="admin.php?page=projects" method="post">
<fieldset class="common">
  <legend>Add a project</legend>
  
  <table class="search">
    <tr>
      <th>Name <span class="red">*</span></th>
      '.($conf['svn_activated'] ? '<th>Directory (on Subversion server) <span class="red">*</span></th>' : null).'
      <th>Files <span class="red">*</span></th>
      <th>Priority</th>
      <th>Category</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="name" size="20"></td>
      '.($conf['svn_activated'] ? '<td><input type="text" name="directory" size="30"></td>' : null).'
      <td><input type="text" name="files" size="50"></td>
      <td><input type="text" name="rank" size="2" value="1"></td>
      <td><input type="text" name="category_id" class="category"></td>
      <td><input type="submit" name="add_section" class="blue" value="Add"></td>
    </tr>
  </table>
  
</fieldset>
</form>';
}

// search sections
if ( count($_DIRS) or count($where_clauses) > 1 )
{
echo '
<form action="admin.php?page=projects" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>Id.</th>
      <th>Name</th>
      <th>Priority</th>
      <th>Category</th>
      <th>Entries</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="id" size="15" value="'.get_search_value('id').'"></td>
      <td><input type="text" name="name" size="20" value="'.get_search_value('name').'"></td>
      <td><input type="text" name="rank" size="2" value="'.get_search_value('rank').'"></td>
      <td>
        <select name="category_id">
          <option value="-1" '.(-1==get_search_value('category_id')?'selected="selected"':'').'>-------</option>';
          foreach ($categories as $row)
          {
            echo '
          <option value="'.$row['id'].'" '.($row['id']==get_search_value('category_id')?'selected="selected"':'').'>'.$row['name'].'</option>';
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

// sections list
echo '
<form id="sections" action="admin.php?page=projects'.(!empty($_GET['nav']) ? '&amp;nav='.$_GET['nav'] : null).'" method="post">
<fieldset class="common">
  <legend>Manage</legend>
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="chkb"></th>
        <th class="id">Id.</th>
        <th class="name">Name</th>
        <th class="dir">Directory</th>
        <th class="files">Files</th>
        <th class="rank">Priority</th>
        <th class="category">Category</th>
        <th class="users">Translators</th>
        <th class="actions"></th>
      </tr>
    </thead>
    <tbody>';
    foreach ($_DIRS as $row)
    {
      echo '
      <tr class="'.($highlight_section==$row['id'] ? 'highlight' : null).'">
        <td class="chkb">
          <input type="checkbox" name="select[]" value="'.$row['id'].'">
        </td>
        <td class="id">
          <a href="'.get_url_string(array('section'=>$row['id']), true, 'section').'">'.$row['id'].'</a>
        </td>
        <td class="name">
          <span style="display:none;">'.$row['name'].'</span>
          <input type="text" name="sections['.$row['id'].'][name]" value="'.$row['name'].'" size="20">
        </td>
        <td class="dir">
          <span style="display:none;">'.$row['directory'].'</span>
          <input type="text" name="sections['.$row['id'].'][directory]" value="'.$row['directory'].'" size="35">
        </td>
        <td class="files">
          <a href="#" class="show-files" data="'.$row['id'].'">Edit</a>
          <div id="textarea-'.$row['id'].'" title="'.$row['name'].' files">
            <textarea name="sections['.$row['id'].'][files]" style="width:370px;height:145px;">'.$row['files'].'</textarea>
          </div>    
        </td>
        <td class="rank">
          <span style="display:none;">'.$row['rank'].'</span>
          <input type="text" name="sections['.$row['id'].'][rank]" value="'.$row['rank'].'" size="2">
        </td>
        <td class="category">
          <input type="text" name="sections['.$row['id'].'][category_id]" class="category" '.(!empty($row['category_id']) ? 'value=\'[{"id": '.$row['category_id'].'}]\'' : null).'>
        </td>
        <td class="users">
          <a href="'.get_url_string(array('section_id'=>$row['id'],'page'=>'users'), true).'">'.$row['total_users'].'</a>
        </td>
        <td class="actions">
          <a href="'.get_url_string(array('make_stats'=>$row['id'])).'" title="Refresh stats"><img src="template/images/arrow_refresh.png"></a>
          '.(is_admin() || $user['manage_perms']['can_delete_projects'] ? '<a href="'.get_url_string(array('delete_section'=>$row['id'])).'" title="Delete this project" onclick="return confirm(\'Are you sure?\');">
            <img src="template/images/cross.png" alt="[x]"></a>' : null).'
        </td>
      </tr>';
    }
    if (count($_DIRS) == 0)
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
  
  <div class="centered">
    <input type="submit" name="save_section" class="blue big" value="Save">
  </div>
</fieldset>

<fieldset id="permitAction" class="common" style="display:none;margin-bottom:20px;">
  <legend>Global action <span class="unselectAll">[close]</span></legend>
  
  <select name="selectAction">
    <option value="-1">Choose an action...</option>
    <option disabled="disabled">------------------</option>
    <option value="make_stats">Refresh stats</option>
    '.(is_admin() || $user['manage_perms']['can_delete_projects'] ? '<option value="delete_projects">Delete projects</option>' : null).'
    <option value="change_rank">Change priority</option>
    <option value="change_category">Change category</option>
  </select>
  
  <span id="action_delete_projects" class="action-container">
    <label><input type="checkbox" name="confirm_deletion" value="1"> Are you sure ?</label>
  </span>
  
  <span id="action_change_rank" class="action-container">
    <input type="text" name="batch_rank" size="2">
  </span>
  
  <span id="action_change_category" class="action-container" style="position:relative;top:8px;"> <!-- manually correct the mispositionning of tokeninput block -->
    <input type="text" name="batch_category_id" class="category">
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
load_jquery('tokeninput');

$page['script'].= '
/* token input for categories */
$("input.category").tokenInput(['.$categories_json.'], {
  tokenLimit: 1,
  allowCreation: true,
  hintText: ""
});

/* tablesorter */
$("#sections table").tablesorter({
  sortList: [[5,1],[1,0]],
  headers: { 0: {sorter:false}, 4: {sorter: false}, 8: {sorter: false} },
  widgets: ["zebra"]
});

/* files dialog */
$("div[id^=\'textarea\']").dialog({
  autoOpen: false, resizable: false,
  show: "clip", hide: "clip",
  height: 250, width: 400,
  buttons: {
    "OK": function() { $(this).dialog("close"); }
  },
  create: function() { // jQuery.dialog moves the textarea away the form, me must set it back
    $(this).parent().appendTo($("form#sections"));
  }
});

$("a.show-files").click(function() {
  $("div#textarea-"+ $(this).attr("data")).dialog("open");
  return false;
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
});

$("td.id").click(function() {
  $checkbox = $(this).prev("td.chkb").children("input");
  $checkbox.attr("checked", !$checkbox.attr("checked"));
});';

?>