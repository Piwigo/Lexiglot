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

// check user_id
if ( !isset($_GET['user_id']) or !is_numeric($_GET['user_id']) or !get_username($_GET['user_id']) )
{
  array_push($page['errors'], 'Invalid user id. <a href="'.get_url_string(array('page'=>'users'), true).'">Go Back</a>');
  $template->close('messages');
}


// +-----------------------------------------------------------------------+
// |                         SAVE PERMISSIONS
// +-----------------------------------------------------------------------+
if (isset($_POST['save_perm']))
{
  $sets = array();
  
  // projects
  $query = '
DELETE FROM '.USER_PROJECTS_TABLE.'
  WHERE
    user_id = '.$_POST['user_id'].'
    AND type = "translate"
;';
  mysql_query($query);
  
  $inserts = array();
  if (!empty($_POST['available_projects']))
  {
    foreach ($_POST['available_projects'] as $p)
    {
      array_push($inserts, array('user_id'=>$_POST['user_id'], 'project'=>$p, 'type'=>'translate'));
    }
  }
  
  mass_inserts(
    USER_PROJECTS_TABLE,
    array('user_id', 'project', 'type'),
    $inserts
    );
  
  // only admin can change languages and manage permissions
  if (is_admin())
  {
    // languages
    $query = '
DELETE FROM '.USER_LANGUAGES_TABLE.'
  WHERE
    user_id = '.$_POST['user_id'].'
    AND type = "translate"
;';
    mysql_query($query);
    
    $inserts = array();
    
    if (!empty($_POST['available_languages']))
    {
      foreach ($_POST['available_languages'] as $l)
      {
        array_push($inserts, array('user_id'=>$_POST['user_id'], 'language'=>$l, 'type'=>'translate'));
      }
    }
    
    mass_inserts(
      USER_LANGUAGES_TABLE,
      array('user_id', 'language', 'type'),
      $inserts
      );

    // manager permissions
    if (get_user_status($_POST['user_id']) == 'manager')
    {
      $query = '
DELETE FROM '.USER_PROJECTS_TABLE.'
  WHERE
    user_id = '.$_POST['user_id'].'
    AND type = "manage"
;';
      mysql_query($query);
      
      $inserts = array();
      if (!empty($_POST['manage_projects']))
      {
        foreach ($_POST['manage_projects'] as $p)
        {
          array_push($inserts, array('user_id'=>$_POST['user_id'], 'project'=>$p, 'type'=>'manage'));
        }
      }
      
      mass_inserts(
        USER_PROJECTS_TABLE,
        array('user_id', 'project', 'type'),
        $inserts
        );
      
      foreach (array_keys(unserialize($conf['default_manager_perms'])) as $perm)
      {
        $manage_perms[$perm] = !empty($_POST['manage_perms'][$perm]);
      }
      array_push($sets, 'manage_perms = \''.serialize($manage_perms).'\'');
    }
    
    // translator main language
    if (get_user_status($_POST['user_id']) != 'guest')
    {
      $query = '
DELETE FROM '.USER_LANGUAGES_TABLE.'
  WHERE
    user_id = '.$_POST['user_id'].'
    AND type = "main"
;';
      mysql_query($query);
      
      if ( !empty($_POST['main_language']) and in_array($_POST['main_language'], @$_POST['available_languages']) )
      {
        $main = $_POST['main_language'];
      }
      else if (count(@$_POST['available_languages']) == 1)
      {
        $main = $_POST['available_languages'][0];
      }
      
      if (isset($main))
      {
        single_insert(
          USER_LANGUAGES_TABLE,
          array('user_id'=>$_POST['user_id'], 'language'=>$main, 'type'=>'main'),
          $inserts
          );
      }
    }
  }
  
  if (count($sets))
  {
    $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET
    '.implode(",    \n", $sets).'
  WHERE
    user_id = '.$_POST['user_id'].'
;';
    mysql_query($query);
  }
  
  redirect(get_url_string(array('page'=>'users','from_id'=>$_POST['user_id']), true));
}


// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$local_user = build_user($_GET['user_id']);
$local_user['languages'] = create_languages_array($local_user['languages']);
$local_user['projects'] = create_projects_array($local_user['projects']);
$local_user['is_manager'] = $local_user['status']=='manager' && is_admin();

if ( $conf['use_stats'] and !empty($local_user['main_language']) )
{
  $stats = get_cache_stats(null, $local_user['main_language'], 'project');
}
$use_project_stats = !empty($stats);

$movable_projects = is_admin() ? array_keys($conf['all_projects']) : $user['manage_projects'];

// count number of different ranks, if 1, we consider that we don't use ranks
$use_language_rank = count(array_unique_deep($conf['all_languages'], 'rank')) > 1;
$use_project_rank = count(array_unique_deep($conf['all_projects'], 'rank')) > 1;


// +-----------------------------------------------------------------------+
// |                         TEMPLATE
// +-----------------------------------------------------------------------+
$template->assign(array(
  'local_user' => $local_user,
  'USE_LANGUAGE_RANK' => $use_language_rank,
  'USE_PROJECT_RANK' => $use_project_rank,
  'USE_PROJECT_STATS' => $use_project_stats,
  'CANCEL_URI' => get_url_string(array('page'=>'users', 'from_id'=>$local_user['id']), true),
  'F_ACTION' => get_url_string(array('page'=>'user_perm', 'user_id'=>$_GET['user_id']), true),
  ));

// LANGUAGES
if (is_admin())
{
  // ellipsis display cant' be "automated" in css so we figure out the size of the name field
  $name_size_1 = $name_size_2 = 140;
  if ($use_language_rank)
  { 
    $name_size_1-= 11; 
    $name_size_2-= 11;
  }
  if (file_exists($conf['flags_dir'].$conf['all_languages'][ $conf['default_language'] ]['flag']))
  { 
    list($w) = getimagesize($conf['flags_dir'].$conf['all_languages'][ $conf['default_language'] ]['flag']); 
    $name_size_1-= $w+8; 
    $name_size_2-= $w+8;
  }
  $name_size_1-= 15;
  
  $template->assign(array(
    'LANGUAGE_SIZE_1' => $name_size_1,
    'LANGUAGE_SIZE_2' => $name_size_2,
    ));
  
  foreach ($local_user['languages'] as $row)
  {
    if (array_key_exists($row['id'], $conf['all_languages']))
    {
      $row['is_main'] = $row['id']==$local_user['main_language'];
        
      $template->append('user_languages', $row);
    }
  }
  
  foreach ($conf['all_languages'] as $row)
  {
    if (!array_key_exists($row['id'], $local_user['languages']))
    {
      $template->append('all_languages', $row);
    }
  }
}

// PROJECTS
$name_size_1 = $name_size_2 = 140;
if ($use_project_rank)
{ 
  $name_size_1-= 11; 
  $name_size_2-= 11;
}
if ($use_project_stats)
{ 
  $name_size_1-= 27;
  $name_size_2-= 27;
}
if ($local_user['is_manager'])
{ 
  $name_size_1-= 15;
}

$template->assign(array(
  'PROJECT_SIZE_1' => $name_size_1,
  'PROJECT_SIZE_2' => $name_size_2,
  ));

foreach ($local_user['projects'] as $row)
{
  if (array_key_exists($row['id'], $conf['all_projects']))
  {
    $row['is_movable'] = in_array($row['id'], $movable_projects);
    $row['is_managed'] = in_array($row['id'], $local_user['manage_projects']);
      
    if ($use_project_stats)
    {
      $row['stats_value'] = number_format($stats[ $row['id'] ]*100, 0);
      $row['stats_color'] = get_gauge_color($stats[ $row['id'] ], 'dark');
    }
      
    $template->append('user_projects', $row);
  }
}

foreach ($conf['all_projects'] as $row)
{
  if (!array_key_exists($row['id'], $local_user['projects']))
  {
    $row['is_movable'] = in_array($row['id'], $movable_projects);
    
    if ($use_project_stats)
    {
      $row['stats_value'] = number_format($stats[ $row['id'] ]*100, 0);
      $row['stats_color'] = get_gauge_color($stats[ $row['id'] ], 'dark');
    }
    
    $template->append('all_projects', $row);
  }
}

// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('admin/user_perm');

?>