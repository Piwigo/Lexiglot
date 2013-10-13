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
isset($local_user) or die('Hacking attempt!');


$delete_languages = $delete_projects = false;
$insert_all_languages = $insert_my_languages = $insert_all_projects = false;

switch ($local_user['status'].'->'.$new_status)
{
  // * to manager (check manage_perms)
  case 'translator->manager':
  case 'admin->manager':
    array_push($sets, 'manage_perms = IFNULL(manage_perms, \''.$conf['default_manager_perms'].'\')');
    break;
    
  // visitor to manager (languages/projects depend on config & check manage_perms)
  case 'visitor->manager':
    array_push($sets, 'manage_perms = \''.$conf['default_manager_perms'].'\'');
    
  // visitor to translator (languages/projects depend on config)
  case 'visitor->translator':
    $delete_languages = $delete_projects = true;
    if ($conf['user_default_language'] == 'all')
    {
      $insert_all_languages = true;
    }
    else if ($conf['user_default_language'] == 'own')
    {
      $insert_my_languages = true;
    }
    if ($conf['user_default_project'] == 'all')
    {
      $insert_all_projects = true;
    }
    break;
    
  // * to admin (all languages/projects)
  case 'visitor->admin':
  case 'translator->admin':
  case 'manager->admin':
    $delete_languages = $delete_projects = true;
    $insert_all_languages = $insert_all_projects = true;
    break;
    
  // * to visitor (none languages/projects)
  case 'translator->visitor':
  case 'manager->visitor':
  case 'admin->visitor':
    $delete_languages = $delete_projects = true;
    break;
    
  // others, do nothing
  case 'manager->translator':
  case 'admin->translator':
    break;
}

if ($delete_languages)
{
  $query = '
DELETE FROM '.USER_LANGUAGES_TABLE.'
  WHERE 
    user_id = '.$local_user['id'].'
    AND type = "translate"
;';
  $db->query($query);
}

if ($delete_projects)
{
  $query = '
DELETE FROM '.USER_PROJECTS_TABLE.'
  WHERE 
    user_id = '.$local_user['id'].'
    AND type = "translate"
;';
  $db->query($query);
}

if ($insert_all_languages)
{
  $inserts = array();
  foreach (array_keys($conf['all_languages']) as $l)
  {
    array_push($inserts, array('user_id'=>$local_user['id'], 'language'=>$l, 'type'=>'translate'));
  }
  
  mass_inserts(
    USER_LANGUAGES_TABLE,
    array('user_id','language','type'),
    $inserts
    );
}
else if ($insert_my_languages and !empty($local_user['my_languages']))
{
  $inserts = array();
  foreach ($local_user['my_languages'] as $l)
  {
    array_push($inserts, array('user_id'=>$local_user['id'], 'language'=>$l, 'type'=>'translate'));
  }
  
  mass_inserts(
    USER_LANGUAGES_TABLE,
    array('user_id','language','type'),
    $inserts
    );
}

if ($insert_all_projects)
{
  $inserts = array();
  foreach (array_keys($conf['all_projects']) as $p)
  {
    array_push($inserts, array('user_id'=>$local_user['id'], 'project'=>$p, 'type'=>'translate'));
  }
  
  mass_inserts(
    USER_PROJECTS_TABLE,
    array('user_id','project','type'),
    $inserts
    );
}

?>