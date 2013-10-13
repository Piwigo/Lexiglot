<?php
// +-----------------------------------------------------------------------+
// | Lexiglot - A PHP based translation tool                               |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2011-2013 Damien Sorel       http://www.strangeplanet.fr |
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
    $done = (bool)$db->query($query);
  }
  else
  {
    $done = true;
  }
  
  $query = 'DELETE FROM '.USER_INFOS_TABLE.' WHERE user_id = '.$_GET['delete_user'].';';
  $done = $done && (bool)$db->query($query);
  
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
  $db->query($query);
  
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
list($total) = $db->query($query)->fetch_row();

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
  list($highlight_pos) = $db->query($query)->fetch_row();
}

$paging = compute_pagination($total, get_search_value('limit'), 'nav', $highlight_pos);


// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$_USERS = get_users_list($where_clauses, array(), 'AND', $paging['Start'], $paging['Entries']);

$displayed_projects = is_admin() ? $conf['all_projects'] : create_projects_array($user['manage_projects']);


// +-----------------------------------------------------------------------+
// |                        TEMPLATE
// +-----------------------------------------------------------------------+
foreach ($_USERS as $row)
{
  $row['highlight'] = $highlight_user==$row['id'];
  $row['time'] = strtotime($row['registration_date']);
  $row['date'] = format_date($row['registration_date'], true, false);
  $row['status_disabled'] = in_array($row['id'], array($user['id'], $conf['guest_id']));
  
  if (count($row['my_languages']) > 0)
  {
    $row['my_languages_tooltip'] = print_user_languages_tooltip($row, 3, true);
  }
  if (count($row['languages']) > 0)
  {
    $row['languages_tooltip'] =  print_user_languages_tooltip($row);
  }
  if (count($row['projects']) > 0)
  {
    $row['projects_tooltip'] = print_user_projects_tooltip($row);
  }
  
  if ( !in_array($row['status'], array('admin','visitor')) and $row['id']!=$user['id'] and (!is_manager() or $row['id']!=$conf['guest_id']) )
  {
    $row['manage_uri'] = get_url_string(array('page'=>'user_perm','user_id'=>$row['id']), true);
  }
  if ( !in_array($row['status'], array('admin','guest')) and $row['id']!=$user['id'] and is_admin() )
  {
    $row['delete_uri'] = get_url_string(array('delete_user'=>$row['id']));
  }
  
  $template->append('USERS', $row);
}

$template->assign(array(
  'SEARCH' => search_to_template($search),
  'PAGINATION' => display_pagination($paging, 'nav'),
  'NAV_PAGE' => !empty($_GET['nav']) ? '&amp;nav='.$_GET['nav'] : null,
  'displayed_projects' => $displayed_projects,
  'EXTERNAL_USERS' => USERS_TABLE != DB_PREFIX.'users',
  'F_ACTION' => get_url_string(array('page'=>'users'), true),
  ));


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('admin/users');

?>