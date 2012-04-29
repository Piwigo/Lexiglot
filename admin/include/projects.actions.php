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

switch ($_POST['selectAction'])
{
  // DELETE PROJECTS
  case 'delete_projects':
  {
    if (!isset($_POST['confirm_deletion']))
    {
      array_push($page['errors'], 'For security reasons you must confirm the deletion.');
    }
    else
    {
      // delete directories
      foreach ($selection as $section)
      {
        @rrmdir($conf['local_dir'].$section);
      }
      
      // delete section form user infos
      $query = '
SELECT
    user_id,
    status,
    sections,
    special_perms
  FROM '.USER_INFOS_TABLE.'
;';
      $users = hash_from_query($query, 'user_id');
      
      $update_query = null;
      foreach ($users as &$row)
      {
        $row['sections'] = explode(',', $row['sections']);
        $row['special_perms'] = explode(',', $row['special_perms']);
        foreach ($selection as $section)
        {
          unset($row['sections'][ array_search($section, $row['sections']) ]);
          if ($row['status'] == 'manager') unset($row['special_perms'][ array_search($section, $row['special_perms']) ]);
        }
        $update_query.= '
UPDATE '.USER_INFOS_TABLE.' SET sections = "'.implode(',', $row['sections']).'", special_perms = "'.implode(',', $row['special_perms']).'" WHERE user_id = '.$row['user_id'].';';
      }
      mysql_query($update_query);
      
      // delete from stats table
      $query = '
DELETE FROM '.STATS_TABLE.'
  WHERE section IN("'.implode('","', $selection).'")
;';
      mysql_query($query);
      
      // delete from sections table
      $query = '
DELETE FROM '.SECTIONS_TABLE.' 
  WHERE id IN("'.implode('","', $selection).'")
;';
      mysql_query($query);
      
      array_push($page['infos'], '<b>'.mysql_affected_rows().'</b> projects deleted.');
    }
    break;
  }
  
  // REFRESH STATS
  case 'make_stats':
  {
    foreach ($selection as $section)
    {
      make_section_stats($section);
    }
    
    array_push($page['infos'], 'Stats refreshed for <b>'.count($selection).'</b> projects.');
    break;
  }
    
  // CHANGE RANK
  case 'change_rank':
  {
    $query = '
UPDATE '.SECTIONS_TABLE.'
  SET rank = '.intval(@$_POST['batch_rank']).'
  WHERE id IN("'.implode('","', $selection).'")
;';
    mysql_query($query);
    
    array_push($page['infos'], 'Rank changed for <b>'.mysql_affected_rows().'</b> projects.');
    break;
  }
  
  // CHANGE CATEGORY
  case 'change_category':
  {
    if ( !empty($_POST['batch_category_id']) and !is_numeric($_POST['batch_category_id']) )
    {
      $_POST['batch_category_id'] = add_category($_POST['batch_category_id'], 'section');
    }
    if (empty($_POST['batch_category_id']))
    {
      $_POST['batch_category_id'] = 0;
    }
    
    $query = '
UPDATE '.SECTIONS_TABLE.'
  SET category_id = '.$_POST['batch_category_id'].'
  WHERE id IN("'.implode('","', $selection).'")
;';
    mysql_query($query);
    
    array_push($page['infos'], 'Category changed for <b>'.mysql_affected_rows().'</b> projects.');
    break;
  }
}

?>