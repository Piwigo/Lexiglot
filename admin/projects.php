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

$highlight_project = isset($_GET['from_id']) ? $_GET['from_id'] : null;
$deploy_project = null;

// +-----------------------------------------------------------------------+
// |                         DELETE PROJECT
// +-----------------------------------------------------------------------+
if ( isset($_GET['delete_project']) and ( is_admin() or (is_manager($_GET['delete_project']) and $user['manage_perms']['can_delete_projects']) ) )
{
  if (!array_key_exists($_GET['delete_project'], $conf['all_projects']))
  {
    array_push($page['errors'], 'Unknown project.');
  }
  else
  {
    // delete projects from user infos
    $users = get_users_list(
      array('projects LIKE "%'.$_GET['delete_project'].'%"'), 
      'projects'
      );
    
    foreach ($users as $u)
    {
      unset($u['projects'][ array_search($_GET['delete_project'], $u['projects']) ]);
      unset($u['manage_projects'][ array_search($_GET['delete_project'], $u['manage_projects']) ]);
      $u['projects'] = create_permissions_array($u['projects']);
      $u['manage_projects'] = create_permissions_array($u['manage_projects'], 1);    
      $u['projects'] = implode_array(array_merge($u['projects'], $u['manage_projects']));
      
      $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET projects = '.(!empty($u['projects']) ? '"'.$u['projects'].'"' : 'NULL').'
  WHERE user_id = '.$u['id'].'
;';
      mysql_query($query);
    }
    
    // delete directory
    @rrmdir($conf['local_dir'].$_GET['delete_project']);  
      
    // delete from stats table
    $query = '
DELETE FROM '.STATS_TABLE.'
  WHERE project = "'.$_GET['delete_project'].'"
;';
    mysql_query($query);
    
    // delete from projects table
    $query = '
DELETE FROM '.PROJECTS_TABLE.' 
  WHERE id = "'.$_GET['delete_project'].'"
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
  if (array_key_exists($_GET['make_stats'], $conf['all_projects']))
  {
    make_project_stats($_GET['make_stats']);
    array_push($page['infos'], 'Stats refreshed for project &laquo; '.get_project_name($_GET['make_stats']).' &raquo;');
    $highlight_project = $_GET['make_stats'];
  }
}

// +-----------------------------------------------------------------------+
// |                         SAVE PROJECTS
// +-----------------------------------------------------------------------+
if ( isset($_POST['save_project']) and isset($_POST['active_project']) )
{
  $row = $_POST['projects'][ $_POST['active_project'] ];
  $row['id'] = $_POST['active_project'];
  
  $query = '
SELECT id, directory, files
  FROM '.PROJECTS_TABLE.'
  WHERE id = "'.$row['id'].'"
;';
  $old_values = mysql_fetch_assoc(mysql_query($query));
  
  $regenerate_stats = false;
  // check name
  if (empty($row['name']))
  {
    array_push($page['errors'], 'Name is empty.');
  }
  // check directory
  if ($conf['svn_activated'] and empty($row['directory']))
  {
    array_push($page['errors'], 'Directory is empty.');
  }
  else if (!empty($row['directory']))
  {
    $row['directory'] = rtrim($row['directory'], '/').'/';
  }
  // check files
  $row['files'] = str_replace(' ', null, $row['files']);
  if (!preg_match('#^(([a-zA-Z0-9\._\-/]+)([,]{1}))+$#', $row['files'].','))
  {
    array_push($page['errors'], 'Seperate each file with a comma.');
  }
  else if ($row['files'] != $old_values['files'])
  {
    $regenerate_stats = true;
  }
  // check rank
  if (!is_numeric($row['rank']) or $row['rank'] < 1)
  {
    array_push($page['errors'], 'Rank must be an non null integer.');
  }
  // check category
  if ( !count($page['errors']) and !empty($row['category_id']) and !is_numeric($row['category_id']) )
  {
    $row['category_id'] = add_category($row['category_id'], 'project');
  }
  if (empty($row['category_id']))
  {
    $row['category_id'] = 0;
  }
  // check url
  if (empty($row['url']))
  {
    $row['url'] = null;
  }
  
  // switch directory
  if ( !count($page['errors']) and $conf['svn_activated'] and $old_values['directory'] != $row['directory'] )
  {
    $svn_result = svn_switch($conf['svn_server'].$row['directory'], $conf['local_dir'].$row['id']);
    if ($svn_result['level'] == 'error')
    {
      array_push($page['errors'], $svn_result['msg']);
    }
    else
    {
      $regenerate_stats = true;
    }
  }
  
  // save project
  if (count($page['errors']) == 0)
  {
    $query = '
UPDATE '.PROJECTS_TABLE.'
  SET 
    name = "'.$row['name'].'",
    directory = "'.$row['directory'].'",
    files = "'.$row['files'].'",
    rank = '.$row['rank'].',
    category_id = '.$row['category_id'].',
    url = "'.$row['url'].'"
  WHERE id = "'.$row['id'].'"
;';
    mysql_query($query);
  }
  
  $highlight_project = $row['id'];
  
  // update projects array
  $conf['all_projects'][ $row['id'] ] = array_merge($conf['all_projects'][ $row['id'] ], $row);
  
  // update stats
  if ($regenerate_stats)
  {
    make_project_stats($project_id);
  }

  
  if (count($page['errors']) == 0)
  {
    array_push($page['infos'], 'Modifications saved.');
  }
  else
  {
    array_push($page['errors'], 'Modifications not saved.');
    $deploy_project = $row['id'];
  }
}

// +-----------------------------------------------------------------------+
// |                         ADD PROJECT
// +-----------------------------------------------------------------------+
if ( isset($_POST['add_project']) and ( is_admin() or $user['manage_perms']['can_add_projects'] ) )
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
  FROM '.PROJECTS_TABLE.'
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
    $_POST['category_id'] = add_category($_POST['category_id'], 'project');
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
    $svn_result = 'project created.';
    if (!file_exists($conf['local_dir'].$_POST['id']))
    {
      mkdir($conf['local_dir'].$_POST['id'], 0777, true);
    }
  }
  
  // save project
  if (count($page['errors']) == 0)
  {
    $query = '
INSERT INTO '.PROJECTS_TABLE.'(
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
    
    // add project on user infos
    $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET projects = IF(
    projects="",
    "'.$_POST['id'].','.(is_manager()?'1':'0').'",
    CONCAT(projects, ";'.$_POST['id'].','.(is_manager()?'1':'0').'")
    )
  WHERE status IN( "admin"'.($conf['project_default_user'] == 'all' ? ', "translator", "guest", "manager"' : null).' )
;';
    mysql_query($query);
    
    // update projects array
    $conf['all_projects'][ $_POST['id'] ] = array(
                                            'id' => $_POST['id'],
                                            'name' => $_POST['name'],
                                            'directory' => $_POST['directory'],
                                            'files' => $_POST['files'],
                                            'rank' => $_POST['rank'],
                                            'category_id' => $_POST['category_id'],
                                            'url' => null,
                                            );
    ksort($conf['all_projects']);

    // generate stats
    make_project_stats($_POST['id']);
    
    array_push($page['infos'], '<b>'.$_POST['name'].'</b> : '.$svn_result);
    $highlight_project = $_POST['id'];
    $_POST['erase_search'] = true;
  }
}

// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$search = array(
  'name' =>        array('%', ''),
  'rank' =>        array('=', ''),
  'category_id' => array('=', -1),
  'limit' =>       array('=', 20),
  );

// url input
if (isset($_GET['project_id']))
{
  $_POST['erase_search'] = true;
  $search['name'] = array('%', get_project_name($_GET['project_id']), '');
  unset($_GET['project_id']);
}

$where_clauses = session_search($search, 'project_search', array('limit'));

$displayed_projects = is_admin() ? array_keys($conf['all_projects']) : $user['manage_projects'];

if (is_manager())
{
  array_push($where_clauses, 'id IN("'.implode('","', $displayed_projects).'")');
}

// +-----------------------------------------------------------------------+
// |                         PAGINATION
// +-----------------------------------------------------------------------+
$query = '
SELECT COUNT(1)
  FROM '.PROJECTS_TABLE.'
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
;';
list($total) = mysql_fetch_row(mysql_query($query));

$highlight_pos = null;
if (!empty($highlight_project))
{
  $query = '
SELECT x.pos
  FROM (
    SELECT 
        id,
        @rownum := @rownum+1 AS pos
      FROM '.PROJECTS_TABLE.'
        JOIN (SELECT @rownum := 0) AS r
      WHERE 
        '.implode("\n    AND ", $where_clauses).'
      ORDER BY rank DESC, id ASC
  ) AS x
  WHERE x.id = "'.$highlight_project.'"
;';
  list($highlight_pos) = mysql_fetch_row(mysql_query($query));
}

$paging = compute_pagination($total, get_search_value('limit'), 'nav', $highlight_pos);

// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$query = '
SELECT 
    s.*,
    COUNT(u.user_id) as total_users
  FROM '.PROJECTS_TABLE.' as s
    INNER JOIN '.USER_INFOS_TABLE.' as u
      ON u.projects LIKE CONCAT("%",s.id,"%") AND u.status != "guest"
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
  WHERE type = "project"
;';
$categories = hash_from_query($query, 'id');
$categories_json = implode(',', array_map(create_function('$row', 'return \'{id: "\'.$row["id"].\'", name: "\'.$row["name"].\'"}\';'), $categories));


// +-----------------------------------------------------------------------+
// |                        TEMPLATE
// +-----------------------------------------------------------------------+
// add project
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
      <td><input type="submit" name="add_project" class="blue" value="Add"></td>
    </tr>
  </table>
  
</fieldset>
</form>';
}

// search projects
if ( count($_DIRS) or count($where_clauses) > 1 )
{
echo '
<form action="admin.php?page=projects" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>Name</th>
      <th>Priority</th>
      <th>Category</th>
      <th>Entries</th>
      <th></th>
    </tr>
    <tr>
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

// projects list
echo '
<form id="projects" action="admin.php?page=projects'.(!empty($_GET['nav']) ? '&amp;nav='.$_GET['nav'] : null).'" method="post">
<fieldset class="common">
  <legend>Manage</legend>
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="chkb"></th>
        <th class="name">Name</th>
        <th class="rank">Priority</th>
        <th class="category">Category</th>
        <th class="users">Translators</th>
        <th class="actions">Actions</th>
      </tr>
    </thead>
    <tbody>';
    foreach ($_DIRS as $row)
    {
      echo '
      <tr class="main '.($highlight_project==$row['id'] ? 'highlight' : null).'">
        <td class="chkb">
          <input type="checkbox" name="select[]" value="'.$row['id'].'">
        </td>
        <td class="name">
          <a href="'.get_url_string(array('project'=>$row['id']), true, 'project').'">'.$row['name'].'</a>
        </td>
        <td class="rank">
          '.$row['rank'].'
        </td>
        <td class="category">
          '.(!empty($row['category_id']) ? get_category_name($row['category_id']) : null).'
        </td>
        <td class="users">
          <a href="'.get_url_string(array('project_id'=>$row['id'],'page'=>'users'), true).'">'.$row['total_users'].'</a>
        </td>
        <td class="actions">
          <a href="#" class="expand" data="'.$row['id'].'" title="Edit this project"><img src="template/images/page_white_edit.png" alt="edit"></a>
          <a href="'.get_url_string(array('make_stats'=>$row['id'])).'" title="Refresh stats"><img src="template/images/arrow_refresh.png" alt="refresh"></a>';
          if ( is_admin() || $user['manage_perms']['can_delete_projects'] )
          {
            echo ' <a href="'.get_url_string(array('delete_project'=>$row['id'])).'" title="Delete this project" onclick="return confirm(\'Are you sure?\');"><img src="template/images/cross.png" alt="delete"></a>';
          }
        echo '
        </td>
      </tr>';
    }
    if (count($_DIRS) == 0)
    {
      echo '
      <tr>
        <td colspan="6"><i>No results</i></td>
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

$page['header'].= '
<script type="text/javascript" src="template/js/functions.js"></script>';

$page['script'].= '
/* perform ajax request for project edit */
$("a.expand").click(function() {
  $trigger = $(this);
  project_id = $trigger.attr("data");
  $parent_row = $trigger.parents("tr.main");
  $details_row = $parent_row.next("tr.details");
  
  if (!$details_row.length) {
    $("a.expand img").attr("src", "template/images/page_white_edit.png");
    $("tr.details").remove();
    
    $trigger.children("img").attr("src", "template/images/page_edit.png");
    $parent_row.after(\'<tr class="details" id="details\'+ project_id +\'"><td class="chkb"></td><td colspan="5"><img src="template/images/load16.gif"> <i>Loading...</i></td></tr>\');
    
    $container = $parent_row.next("tr.details").children("td:last-child");

    $.ajax({
      type: "POST",
      url: "admin/ajax.php",
      data: { "action":"get_project_form", "project_id": project_id }
    }).done(function(msg) {
      msg = $.parseJSON(msg);
      
      if (msg.errcode == "success") {
        $container.html(msg.data);
        $container.find("input.category").tokenInput(json_categories, {
          tokenLimit: 1,
          allowCreation: true,
          hintText: ""
        });
      }  else {
        overlayMessage(msg.data, msg.errcode, $trigger);
      }
    });
  } else {
    $details_row.remove();
    $trigger.children("img").attr("src", "template/images/page_white_edit.png");
  }
  
  return false;
});

/* linked table rows follow hover state */
$("tr.main").hover(
  function() { $(this).next("tr.details").addClass("hover"); },
  function() { $(this).next("tr.details").removeClass("hover"); }
);
// this is a live version of above trigger, as "tr.details" are created on the fly
$(document).on("mouseenter", "tr.details", function() { $(this).prev("tr.main").addClass("hover"); });
$(document).on("mouseleave", "tr.details", function() { $(this).prev("tr.main").removeClass("hover"); });

/* token input for categories */
var json_categories = ['.$categories_json.'];
$("input.category").tokenInput(json_categories, {
  tokenLimit: 1,
  allowCreation: true,
  hintText: ""
});

/* tablesorter */
$("#projects table").tablesorter({
  sortList: [[2,1],[1,0]],
  headers: { 0: {sorter:false}, 5: {sorter: false} },
  widgets: ["zebra"]
})
.bind("sortStart", function() { 
  $("tr.details").remove();
  $("a.expand img").attr("src", "template/images/page_white_edit.png");
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

if (!empty($deploy_project))
{
  $page['script'].= '
  $("a.expand[data=\''.$deploy_project.'\']").trigger("click");';
}

?>