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
  // DELETE LANGUAGES
  case 'delete_languages':
  {
    if (!isset($_POST['confirm_deletion']))
    {
      array_push($page['errors'], 'For security reasons you must confirm the deletion.');
    }
    else
    {
      // delete flags
      foreach ($selection as $language)
      {
        @unlink($conf['flags_dir'].$conf['all_languages'][$language]['flag']);
      }
      
      // delete language form user infos
      $query = '
SELECT
    user_id,
    status,
    languages,
    my_languages,
    spacial_perms
  FROM '.USER_INFOS_TABLE.'
;';
      $users = hash_from_query($query, 'user_id');
      
      $update_query = null;
      foreach ($users as &$row)
      {
        $row['languages'] = explode(',', $row['languages']);
        $row['my_languages'] = explode(',', $row['my_languages']);
        $row['special_perms'] = explode(',', $row['special_perms']);
        foreach ($selection as $language)
        {
          unset($row['languages'][ array_search($language, $row['languages']) ]);
          unset($row['my_languages'][ array_search($language, $row['my_languages']) ]);
          if ($row['status'] == 'translator') unset($row['special_perms'][ array_search($language, $row['special_perms']) ]);
        }
        $update_query.= '
UPDATE '.USER_INFOS_TABLE.' SET languages = "'.implode(',', $row['languages']).'", my_languages = "'.implode(',', $row['my_languages']).'", special_perms = "'.implode(',', $row['special_perms']).'" WHERE user_id = '.$row['user_id'].';';
      }
      mysql_query($update_query);
      
      // delete from stats table
      $query = '
DELETE FROM '.STATS_TABLE.'
  WHERE language IN("'.implode('","', $selection).'")
;';
      mysql_query($query);
      
      // delete from languages table
      $query = '
DELETE FROM '.LANGUAGES_TABLE.' 
  WHERE id IN("'.implode('","', $selection).'")
;';
      mysql_query($query);
      
      array_push($page['infos'], '<b>'.mysql_affected_rows().'</b> languages deleted.');
    }
    break;
  }
  
  // REFRESH STATS
  case 'make_stats':
  {
    foreach ($selection as $language)
    {
      make_language_stats($language);
    }
    
    array_push($page['infos'], 'Stats refreshed for <b>'.count($selection).'</b> languages.');
    break;
  }
    
  // CHANGE RANK
  case 'change_rank':
  {
    $query = '
UPDATE '.LANGUAGES_TABLE.'
  SET rank = '.intval(@$_POST['batch_rank']).'
  WHERE id IN("'.implode('","', $selection).'")
;';
    mysql_query($query);
    
    array_push($page['infos'], 'Rank changed for <b>'.mysql_affected_rows().'</b> languages.');
    break;
  }
  
  // CHANGE CATEGORY
  case 'change_category':
  {
    if ( !empty($_POST['batch_category_id']) and !is_numeric($_POST['batch_category_id']) )
    {
      $_POST['batch_category_id'] = add_category($_POST['batch_category_id'], 'language');
    }
    if (empty($_POST['batch_category_id']))
    {
      $_POST['batch_category_id'] = 0;
    }
    
    $query = '
UPDATE '.LANGUAGES_TABLE.'
  SET category_id = '.$_POST['batch_category_id'].'
  WHERE id IN("'.implode('","', $selection).'")
;';
    mysql_query($query);
    
    array_push($page['infos'], 'Category changed for <b>'.mysql_affected_rows().'</b> languages.');
    break;
  }
}

?>