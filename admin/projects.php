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
    $query = '
DELETE FROM '.USER_PROJECTS_TABLE.'
  WHERE project = "'.$_GET['delete_project'].'"
;';
    $db->query($query);
    
    // delete directory
    @rrmdir($conf['local_dir'].$_GET['delete_project']);  
      
    // delete from stats table
    $query = '
DELETE FROM '.STATS_TABLE.'
  WHERE project = "'.$_GET['delete_project'].'"
;';
    $db->query($query);
    
    // delete from projects table
    $query = '
DELETE FROM '.PROJECTS_TABLE.' 
  WHERE id = "'.$_GET['delete_project'].'"
;';
    $db->query($query);
    
    array_push($page['infos'], 'Project deleted.');
  }
}


// +-----------------------------------------------------------------------+
// |                         ACTIONS
// +-----------------------------------------------------------------------+
if ( isset($_POST['apply_action']) and $_POST['selectAction'] != '-1' and !empty($_POST['select']) )
{
  include(LEXIGLOT_PATH . 'admin/include/projects.actions.php');
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
SELECT *
  FROM '.PROJECTS_TABLE.'
  WHERE id = "'.$row['id'].'"
;';
  $old_values = $db->query($query)->fetch_assoc();
  
  $regenerate_stats = false;
  // check name
  if (empty($row['name']))
  {
    array_push($page['errors'], 'Name is empty.');
  }
  // check svn_url
  if (empty($row['svn_url']) || empty($row['svn_user']) || empty($row['svn_password']))
  {
    array_push($page['errors'], 'Missing SVN information.');
  }
  else if (!empty($row['svn_url']))
  {
    $row['svn_url'] = rtrim($row['svn_url'], '/').'/';
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
  if (!is_numeric($row['rank']))
  {
    array_push($page['errors'], 'Rank must be an integer.');
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
  
  // switch svn_url
  if ( !count($page['errors']) and $old_values['svn_url'] != $row['svn_url'] )
  {
    $svn_result = svn_switch($row['svn_url'], $conf['local_dir'].$row['id'], $row, $old_values['svn_url']);
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
    svn_url = "'.$row['svn_url'].'",
    svn_user = "'.$row['svn_user'].'",
    svn_password = "'.$row['svn_password'].'",
    files = "'.$row['files'].'",
    rank = '.$row['rank'].',
    category_id = '.$row['category_id'].',
    url = "'.$row['url'].'"
  WHERE id = "'.$row['id'].'"
;';
    $db->query($query);
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
    $result = $db->query($query);
    if ($result->num_rows)
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
  if (!is_numeric($_POST['rank']))
  {
    array_push($page['errors'], 'Rank must be an integer.');
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
  // check svn_url
  if (empty($_POST['svn_url']) || empty($_POST['svn_user']) || empty($_POST['svn_password']))
  {
    array_push($page['errors'], 'Missing SVN information.');
  }
  if (!count($page['errors']))
  {
    $_POST['svn_url'] = rtrim($_POST['svn_url'], '/').'/';
    if (file_exists($conf['local_dir'].$_POST['id']))
    {
      array_push($page['errors'], 'A local directory with the name &laquo;'.$_POST['id'].'&raquo; already exists, I can\'t do a checkout to the SVN server.');
    }
    else
    {
      $svn_result = svn_checkout($_POST['svn_url'], $conf['local_dir'].$_POST['id'], $_POST);
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
  
  // save project
  if (count($page['errors']) == 0)
  {
    $query = '
INSERT INTO '.PROJECTS_TABLE.'(
    id, 
    name, 
    svn_url, 
    svn_user, 
    svn_password, 
    files,
    rank,
    category_id
  )
  VALUES(
    "'.$_POST['id'].'",
    "'.$_POST['name'].'",
    "'.$_POST['svn_url'].'",
    "'.$_POST['svn_user'].'",
    "'.$_POST['svn_password'].'",
    "'.$_POST['files'].'",
    '.$_POST['rank'].',
    '.$_POST['category_id'].'
  )
;';
    $db->query($query);
    
    
    // add project on user infos
    $query = '
SELECT user_id 
  FROM '.USER_INFOS_TABLE.' 
  WHERE status IN("admin"'.($conf['project_default_user'] == 'all' ? ', "translator", "guest", "manager"' : null).')
;';
    $user_ids = array_from_query($query, 'user_id');
    
    $inserts = array();
    foreach ($user_ids as $uid)
    {
      array_push($inserts, array('user_id'=>$uid, 'project'=>$_POST['id'], 'type'=>'translate'));
    }
    
    mass_inserts(
      USER_PROJECTS_TABLE,
      array('user_id', 'project', 'type'),
      $inserts,
      array('ignore'=>true)
      );
      
    if (is_manager())
    {
      single_insert(
        USER_PROJECTS_TABLE,
        array('user_id'=>$user['id'], 'project'=>$_POST['id'], 'type'=>'manage'),
        array('ignore'=>true)
        );
    }
    
    
    // update projects array
    $conf['all_projects'][ $_POST['id'] ] = array(
                                            'id' => $_POST['id'],
                                            'name' => $_POST['name'],
                                            'svn_url' => $_POST['svn_url'],
                                            'svn_user' => $_POST['svn_user'],
                                            'svn_password' => $_POST['svn_password'],
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

$displayed_projects = is_admin() ? $conf['all_projects'] : create_projects_array($user['manage_projects']);

if (is_manager())
{
  array_push($where_clauses, 'id IN("'.implode('","', array_keys($displayed_projects)).'")');
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
list($total) = $db->query($query)->fetch_row();

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
  list($highlight_pos) = $db->query($query)->fetch_row();
}

$paging = compute_pagination($total, get_search_value('limit'), 'nav', $highlight_pos);


// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$query = '
SELECT 
    s.*,
    COUNT(DISTINCT(u.user_id)) as total_users
  FROM '.PROJECTS_TABLE.' as s
    LEFT JOIN '.USER_PROJECTS_TABLE.' as u
    ON u.project = s.id
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
  GROUP BY s.id
  ORDER BY 
    s.rank DESC, 
    s.id ASC
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
foreach ($_DIRS as $row)
{
  $row['highlight'] = $highlight_project==$row['id'];
  $row['category_name'] = @$categories[ @$row['category_id'] ]['name'];
  $row['users_uri'] = get_url_string(array('page'=>'users','project_id'=>$row['id']), true);
  $row['make_stats_uri'] = get_url_string(array('make_stats'=>$row['id']));
  
  if ( is_admin() or $user['manage_perms']['can_delete_projects'] )
  {
    $row['delete_uri'] = get_url_string(array('delete_project'=>$row['id']));
  }
  
  $template->append('DIRS', $row);
}

$template->assign(array(
  'SEARCH' => search_to_template($search),
  'PAGINATION' => display_pagination($paging, 'nav'),
  'CATEGORIES' => $categories,
  'CATEGORIES_JSON' => $categories_json,
  'NAV_PAGE' => !empty($_GET['nav']) ? '&amp;nav='.$_GET['nav'] : null,
  'DEPLOY_PROJECT' => $deploy_project,
  'F_ACTION' => get_url_string(array('page'=>'projects'), true),
  ));

if ( is_admin() or $user['manage_perms']['can_add_projects'] )
{
  $template->assign('CAN_ADD_PROJECT', true);
}


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('admin/projects');

?>