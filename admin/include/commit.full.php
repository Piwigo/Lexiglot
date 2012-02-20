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

// +-----------------------------------------------------------------------+
// |                        SEND COMMIT
// +-----------------------------------------------------------------------+

$infos = array(); // output message array
$ids = array(); // rows treated

// FOREACH COMMIT
foreach ($_ROWS as $props => $files)
{
  // commit infos
  list($commit['section'], $commit['language']) = explode('||', $props);
  $commit['users'] = array();
  $commit['path'] = $conf['local_dir'].$commit['section'].'/'.$commit['language'].'/';
  
  // if the folder is empty it's a new language
  $commit['new_lang']= false;
  if (dir_is_empty($commit['path']))
  {
    $commit['new_lang']= true;
  }
  
  // FOREACH FILE
  foreach ($files as $filename => $file_content)
  {
    // file infos
    $file_infos['name'] = $filename;
    $file_infos['path'] = $commit['path'].$file_infos['name'];
    
    ## plain file ##
    if (is_plain_file($file_infos['name']))
    {           
      $file_infos['is_new'] = !file_exists($file_infos['path']);
      $file_content = array_values($file_content);
      $row = $file_content[0];
      $commit['users'][$row['user_id']] = $_USERS[$row['user_id']]['username'];
      deep_file_put_contents($file_infos['path'], html_special_chars($row['row_value']));
    }
    ## array file ##
    else
    {
      // load language files
      $_LANG =         load_language_file($file_infos['path']);
      $_LANG_default = load_language_file($conf['local_dir'].$commit['section'].'/'.$conf['default_language'].'/'.$file_infos['name']);
      
      // update the file
      if (file_exists($file_infos['path']))
      {
        $_FILE = file($file_infos['path'], FILE_IGNORE_NEW_LINES);
        unset($_FILE[array_search('?>', $_FILE)]); // remove PHP tag
        $file_infos['is_new'] = false;
      }
      // create the file
      else
      {
        $_FILE = array('<?php', $conf['new_file_content']);
        $file_infos['is_new'] = true;
      }
      
      // FOREACH ROW
      // rows from database (new/edit) we skip/remove obsolete
      foreach ($file_content as $key => $row)
      {
        // append the user who edit the row to users list
        $commit['users'][ $row['user_id'] ] = $_USERS[$row['user_id']]['username'];
        
        // remove obsolete row, and continue to the next
        if (!isset($_LANG_default[$key]))
        {
          if ( !$file_infos['is_new'] and isset($_POST['delete_obsolete']) and ($i = array_pos($key, $_FILE)) !== false )
          {
            // if the end of the line is not the end of the row, we search the end into lines bellow
            if ( !preg_match('#(\'|");( *)$#', $_FILE[$i]) )
            {
              unset_to_eor($_FILE, $i);
            }
            unset($_FILE[$i]);
          }
          continue;
        }
        
        switch ($conf['quote'])
        {
          case "'":
            $row['search'] = "$".$conf['var_name']."['".str_replace("'","\'",$key)."']";
            $row['content'] = $row['search']." = '".str_replace("'","\'",$row['row_value'])."';";
            break;
          case '"':
            $row['search'] = '$'.$conf['var_name'].'["'.str_replace('"','\"',$key).'"]';
            $row['content'] = $row['search'].' = "'.str_replace('"','\"',$row['row_value']).'";';
            break;
        }
          
        // update existing line (don't use the "heavy" search function if this is a new file)
        if ( !$file_infos['is_new'] and ($i = array_pos($row['search'], $_FILE)) !== false )
        {
          // if the end of the line is not the end of the row, we search the end into lines bellow
          if ( !preg_match('#(\'|");( *)$#', $_FILE[$i]) )
          {
            unset_to_eor($_FILE, $i);
          }
          $_FILE[$i] = $row['content'];
        }
        // add new line at the end
        else
        {
          $_FILE[] = $row['content'];
        }
        
        array_push($ids, $row['id']);
      }
      // obsolete rows from file
      if (isset($_POST['delete_obsolete']) and !$file_infos['is_new'])
      {
        foreach ($_LANG as $key => $row)
        {
          // here we skip rows than were in the database, already deleted
          if (!isset($_LANG_default[$key]) and !isset($file_content[$key]))
          {
            $i = array_pos($key, $_FILE);
            // if the end of the line is not the end of the row, we search the end into lines bellow
            if ( !preg_match('#(\'|");( *)$#', $_FILE[$i]) )
            {
              unset_to_eor($_FILE, $i);
            }
            unset($_FILE[$i]);
          }
        }
      }
      
      $_FILE[] = '?>'; // don't forget to close PHP tag
      deep_file_put_contents($file_infos['path'], implode($conf['eol'], $_FILE));
    }
    
    if ($file_infos['is_new'] and $conf['svn_activated']) svn_add($file_infos['path']); // ask subversion to add the file
  }
      
  // send to svn server
  if ($conf['svn_activated'])
  {
    $svn_result = svn_commit($commit['path'], 
      '['.get_section_name($commit['section']).'] '.($commit['new_lang']?'Add':'Update').' '.$commit['language'].', thanks to : '.implode(' & ', $commit['users'])
      );
    if ($svn_result === false)
    {
      svn_revert($commit['path']);
      break;
    }
  }
  else
  {
    $svn_result = 'done.';
  }
  
  array_push($infos, '<b>'.$commit['section'].'</b> (<i>'.$commit['language'].'</i>) : '.$svn_result);
  unset($commit);
}

if (count($page['errors']) == 0)
{
  // delete rows
  if ($conf['delete_done_rows'])
  {
    $query = '
DELETE FROM '.ROWS_TABLE.'
  WHERE
    '.implode("\n    AND ", $where_clauses).'
    AND status != "done"
;';
    mysql_query($query);
  }
  // set rows as done
  else
  {
    $query = '
UPDATE '.ROWS_TABLE.'
  SET 
    status = "done"
  WHERE
    '.implode("\n    AND ", $where_clauses).'
    AND status != "done"
;';
    mysql_query($query);

    $query = '
UPDATE '.ROWS_TABLE.'
  SET 
    last_edit = NOW()
  WHERE
    id IN('.implode(',', $ids).')
;';
    mysql_query($query);
  }
  

  array_push($page['infos'], implode('<br>', $infos));
  if ($conf['use_stats']) make_full_stats();
}

?>