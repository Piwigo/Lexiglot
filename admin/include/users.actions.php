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
$forbid_ids = array_from_query($query, 'user_id');
array_push($forbid_ids, $conf['guest_id'], $user['id']);

switch ($_POST['selectAction'])
{
  // DELETE USERS (forbid)
  case 'delete_users':
  {
    if (!isset($_POST['confirm_deletion']))
    {
      array_push($page['errors'], 'For security reasons you must confirm the deletion.');
    }
    else
    {
      $query = '
DELETE FROM '.USERS_TABLE.' 
  WHERE 
    '.$conf['user_fields']['id'].' IN('.implode(',', $selection).') 
    AND '.$conf['user_fields']['id'].' NOT IN ('.implode(',', $forbid_ids).')
;';
      mysql_query($query);
      $query = '
DELETE FROM '.USER_INFOS_TABLE.' 
  WHERE 
    user_id IN('.implode(',', $selection).')
    AND user_id NOT IN ('.implode(',', $forbid_ids).')
;';
      mysql_query($query);
      
      array_push($page['infos'], mysql_affected_rows().' users deleted.');
    }
    break;
  }
  
  // CHANGE STATUS (forbid)
  case 'change_status':
  {
    $s = $_POST['batch_status'];
    if ( $s == '-1' )
    {
      array_push($page['errors'], 'Wrong status !');
    }
    else
    {
      $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET 
    status = "'.$s.'"
  WHERE 
    user_id IN('.implode(',', $selection).')
    AND user_id NOT IN ('.implode(',', $forbid_ids).')
;';
      mysql_query($query);
      
      array_push($page['infos'], 'Status changed to &laquo; '.ucfirst($s).' &raquo; for <b>'.mysql_affected_rows().'</b> users.');
    }
    break;
  }
  
  // ASSIGN LANGUAGE
  case 'add_lang':
  {
    $l = $_POST['language_add'];
    if ( $l == '-1' or !array_key_exists($l, $conf['all_languages']) )
    {
      array_push($page['errors'], 'Wrong language !');
    }
    else
    {
      $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET 
    languages = IF( 
      languages="", 
      "'.$l.'", 
      IF( 
        languages LIKE("%'.$l.'%"), 
        languages, 
        CONCAT(languages, ",'.$l.'")
      )
    )
  WHERE 
    user_id IN('.implode(',', $selection).')
    AND status != "visitor"
;';
      mysql_query($query);
      
      array_push($page['infos'], 'Language &laquo; '.get_language_name($l).' &raquo; assigned to <b>'.mysql_affected_rows().'</b> users.');
    }
    break;
  }
    
  // UNASSIGN LANGUAGE
  case 'remove_lang':
  {
    $l = $_POST['language_remove'];
    if ( $l == '-1' or !array_key_exists($l, $conf['all_languages']) )
    {
      array_push($page['errors'], 'Wrong language !');
    }
    else
    {
      $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET languages = 
    IF(languages = "'.$l.'", 
      "",
      IF(languages LIKE "'.$l.',%",
        REPLACE(languages, "'.$l.',", ""),
        IF(languages LIKE "%,'.$l.'", 
          REPLACE(languages, ",'.$l.'", ""),
          IF(languages LIKE "%,'.$l.',%", 
            REPLACE(languages, ",'.$l.',", ","),
            languages
      ) ) ) )
  WHERE 
    user_id IN('.implode(',', $selection).')
    AND status != "visitor"
;';
      mysql_query($query);
      
      array_push($page['infos'], 'Language &laquo; '.get_language_name($l).' &raquo; unassigned from <b>'.mysql_affected_rows().'</b> users.');
    }
    break;
  }
  
  // ASSIGN PROJECT
  case 'add_section':
  {
    $s = $_POST['section_add'];
    if ( $s == '-1' or !array_key_exists($s, $conf['all_sections']) )
    {
      array_push($page['errors'], 'Wrong project !');
    }
    else
    {
      $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET 
    sections = IF( 
      sections="", 
      "'.$s.'", 
      IF( 
        sections LIKE("%'.$s.'%"), 
        sections, 
        CONCAT(sections, ",'.$s.'")
      )
    )
  WHERE 
    user_id IN('.implode(',', $selection).')
    AND status != "visitor"
;';
      mysql_query($query);
      
      array_push($page['infos'], 'Project &laquo; '.get_section_name($s).' &raquo; assigned to <b>'.mysql_affected_rows().'</b> users.');
    }
    break;
  }
  
  // UNASSIGN PROJECT
  case 'remove_section':
  {
    $s = $_POST['section_remove'];
    if ( $s == '-1' or !array_key_exists($s, $conf['all_sections']) )
    {
      array_push($page['errors'], 'Wrong project !');
    }
    else
    {
      $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET sections = 
    IF(sections = "'.$s.'", 
      "",
      IF(sections LIKE "'.$s.',%",
        REPLACE(sections, "'.$s.',", ""),
        IF(sections LIKE "%,'.$s.'", 
          REPLACE(sections, ",'.$s.'", ""),
          IF(sections LIKE "%,'.$s.',%", 
            REPLACE(sections, ",'.$s.',", ","),
            sections
      ) ) ) )
  WHERE 
    user_id IN('.implode(',', $selection).')
    AND status != "visitor"
;';
      mysql_query($query);
      
      array_push($page['infos'], 'Project &laquo; '.get_section_name($s).' &raquo; unassigned from <b>'.mysql_affected_rows().'</b> users.');
    }
    break;
  }
}

?>