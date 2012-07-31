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

$selection = $_POST['select'];

// to prevent some queries to modify admins and current user properties
$query = 'SELECT user_id FROM '.USER_INFOS_TABLE.' WHERE status = "admin"';
$admin_ids = array_from_query($query, 'user_id');
$query = 'SELECT user_id FROM '.USER_INFOS_TABLE.' WHERE status = "visitor"';
$visitor_ids = array_from_query($query, 'user_id');
if (empty($visitor_ids)) $visitor_ids = array(0);
array_push($admin_ids, $user['id']);

switch ($_POST['selectAction'])
{
  // DELETE USERS
  case 'delete_users':
  {
    if (!isset($_POST['confirm_deletion']))
    {
      array_push($page['errors'], 'For security reasons you must confirm the deletion.');
    }
    else if (is_admin())
    {
      if (USERS_TABLE == DB_PREFIX.'users')
      {
        $query = '
DELETE FROM '.USERS_TABLE.' 
  WHERE 
    '.$conf['user_fields']['id'].' IN('.implode(',', $selection).') 
    AND '.$conf['user_fields']['id'].' NOT IN ('.implode(',', $admin_ids).')
    AND '.$conf['user_fields']['id'].' != '.$conf['guest_id'].'
;';
        mysql_query($query);
      }
      
      $query = '
DELETE FROM '.USER_LANGUAGES_TABLE.' 
  WHERE 
    user_id IN('.implode(',', $selection).')
    AND user_id NOT IN ('.implode(',', $admin_ids).')
    AND user_id != '.$conf['guest_id'].'
;';
      mysql_query($query);  
      
      $query = '
DELETE FROM '.USER_PROJECTS_TABLE.' 
  WHERE 
    user_id IN('.implode(',', $selection).')
    AND user_id NOT IN ('.implode(',', $admin_ids).')
    AND user_id != '.$conf['guest_id'].'
;';
      mysql_query($query);
      
      $query = '
DELETE FROM '.USER_INFOS_TABLE.' 
  WHERE 
    user_id IN('.implode(',', $selection).')
    AND user_id NOT IN ('.implode(',', $admin_ids).')
    AND user_id != '.$conf['guest_id'].'
;';
      mysql_query($query);
      
      array_push($page['infos'], mysql_affected_rows().' users deleted.');
    }
    break;
  }
  
  // CHANGE STATUS
  case 'change_status':
  {
    $s = $_POST['batch_status'];
    if ($s == '-1')
    {
      array_push($page['errors'], 'Wrong status !');
    }
    else if (is_admin())
    {
      $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET 
    status = "'.$s.'"
  WHERE 
    user_id IN('.implode(',', $selection).')
    AND user_id NOT IN ('.implode(',', $admin_ids).')
    AND user_id != '.$conf['guest_id'].'
;';
      mysql_query($query);
      
      array_push($page['infos'], 'Status changed to &laquo; '.ucfirst($s).' &raquo; for <b>'.mysql_affected_rows().'</b> users.');
    }
    break;
  }
  
  // ASSIGN LANGUAGE
  case 'add_language':
  {
    $l = $_POST['language_add'];
    if ( $l == '-1' or !array_key_exists($l, $conf['all_languages']) )
    {
      array_push($page['errors'], 'Wrong language !');
    }
    else if (is_admin())
    {
      $inserts = array();
      foreach ($selection as $user_id)
      {
        if (in_array($user_id, array_merge($admin_ids,$visitor_ids))) continue;
        array_push($inserts, array('user_id'=>$user_id, 'language'=>$l, 'type'=>'translate'));
      }
      
      mass_inserts(
        USER_LANGUAGES_TABLE,
        array('user_id', 'language', 'type'),
        $inserts,
        array('ignore'=>true)
        );
      
      array_push($page['infos'], 'Language &laquo; '.get_language_name($l).' &raquo; assigned to <b>'.count($inserts).'</b> users.');
    }
    break;
  }
    
  // UNASSIGN LANGUAGE
  case 'remove_language':
  {
    $l = $_POST['language_remove'];
    if ( $l == '-1' or !array_key_exists($l, $conf['all_languages']) )
    {
      array_push($page['errors'], 'Wrong language !');
    }
    else if (is_admin())
    {
      $query = '
DELETE FROM '.USER_LANGUAGES_TABLE.'
  WHERE
    language = "'.$l.'"
    AND user_id IN('.implode(',', $selection).')
    AND user_id NOT IN ('.implode(',', $admin_ids).')
    AND user_id NOT IN ('.implode(',', $visitor_ids).')
;';
      mysql_query($query);
      
      array_push($page['infos'], 'Language &laquo; '.get_language_name($l).' &raquo; unassigned from <b>'.mysql_affected_rows().'</b> users.');
    }
    break;
  }
  
  // ASSIGN PROJECT
  case 'add_project':
  {
    $p = $_POST['project_add'];
    if ( $p == '-1' or !array_key_exists($p, $conf['all_projects']) )
    {
      array_push($page['errors'], 'Wrong project !');
    }
    else
    {
      $inserts = array();
      foreach ($selection as $user_id)
      {
        if (in_array($user_id, array_merge($admin_ids,$visitor_ids))) continue;
        array_push($inserts, array('user_id'=>$user_id, 'project'=>$p, 'type'=>'translate'));
      }
      
      mass_inserts(
        USER_PROJECTS_TABLE,
        array('user_id', 'project', 'type'),
        $inserts,
        array('ignore'=>true)
        );
      
      array_push($page['infos'], 'Project &laquo; '.get_project_name($p).' &raquo; assigned to <b>'.count($inserts).'</b> users.');
    }
    break;
  }
  
  // UNASSIGN PROJECT
  case 'remove_project':
  {
    $p = $_POST['project_remove'];
    if ( $p == '-1' or !array_key_exists($p, $conf['all_projects']) )
    {
      array_push($page['errors'], 'Wrong project !');
    }
    else
    {
      $query = '
DELETE FROM '.USER_PROJECTS_TABLE.'
  WHERE
    project = "'.$p.'"
    AND user_id IN('.implode(',', $selection).')
    AND user_id NOT IN ('.implode(',', $admin_ids).')
    AND user_id NOT IN ('.implode(',', $visitor_ids).')
;';
      mysql_query($query);
      
      array_push($page['infos'], 'Project &laquo; '.get_project_name($p).' &raquo; unassigned from <b>'.mysql_affected_rows().'</b> users.');
    }
    break;
  }
}

?>