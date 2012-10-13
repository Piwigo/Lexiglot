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

// commit states :
// +--------------+---------------+------------++--------------------------+
// |   modified   |   done_rows   |   errors   ||   state                  |
// +--------------+---------------+------------++--------------------------+
// |    false     |      =0       |     =0     ||   IMPOSSIBLE             |
// |    false     |      =0       |     >0     ||   fatal error            |
// |    false     |      >0       |     =0     ||   commit aborded         |
// |    false     |      >0       |     >0     ||   IMPOSSIBLE             |
// |    true      |      =0       |     =0     ||   IMPOSSIBLE             |
// |    true      |      =0       |     >0     ||   IMPOSSIBLE             |
// |    true      |      >0       |     =0     ||   commit                 |
// |    true      |      >0       |     >0     ||   commit with errors     |
// +--------------+---------------+------------++--------------------------+
//
// "commit" and "commit with errors" can lead up to "fatal error" in case of SVN error


// +-----------------------------------------------------------------------+
// |                        UPDATE FILES
// +-----------------------------------------------------------------------+
// FOREACH COMMIT
foreach ($_ROWS as $props => $commit_content)
{
  // commit infos
  $commit = array();
  list($commit['project'], $commit['language']) = explode('||', $props);
  $commit['project_name'] = get_project_name($commit['project']);
  $commit['language_name'] = get_language_name($commit['language']);
  $commit['path'] = $conf['local_dir'].$commit['project'].'/'.$commit['language'].'/';
  $commit['is_new'] = dir_is_empty($commit['path']);
  $commit['users'] = $commit['done_rows'] = $commit['errors'] = array();
  $commit['modified'] = false;
  
  // FOREACH FILE
  foreach ($commit_content as $filename => $file_content)
  {
    // file infos
    $file = array();
    $file['name'] = $filename;
    $file['path'] = $commit['path'].$file['name'];
    $file['is_new'] = !file_exists($file['path']);
    $file['users'] = $file['done_rows'] = $file['errors'] = array();
    $file['modified'] = false;
    
    ## plain file ##
    if (is_plain_file($file['name']))
    {
      $row = $file_content[ $file['name'] ];
      
      // try to put the content in the file
      if (deep_file_put_contents($file['path'], $row[0]['row_value']))
      {
        // keep trace of added row(s), user(s) and mark the file as modified
        array_merge_ref($file['done_rows'], array_unique_deep($row, 'id'));
        array_merge_ref($file['users'], array_unique_deep($row, 'user_id'));
        $file['modified'] = true;
      }
      else
      {
        array_push($file['errors'], 'Can\'t update/create file \''.$file['path'].'\'');
      }
    }
    ## array file ##
    else
    {
      // load language files
      $_LANG =         load_language_file($commit['project'], $commit['language'], $file['name']);
      $_LANG_default = load_language_file($commit['project'], $conf['default_language'], $file['name']);
      
      // update the file
      if (!$file['is_new'])
      {
        $_FILE = file($file['path'], FILE_IGNORE_NEW_LINES);
        unset($_FILE[ array_search('?>', $_FILE) ]); // remove PHP end tag
      }
      // create the file
      else
      {
        $_FILE = array('<?php', $conf['new_file_content']);
      }
      
      // FOREACH ROW
      foreach ($file_content as $key => $row)
      {
        $sub_string = is_sub_string($key);
        
        /* we search for
          $lang['a key']
          $lang["a key"]
          $lang[a key]
        */
        /*if ($sub_string !== false)
        {
          $sub = $sub_string;
          $search = '#\$'.$conf['var_name'].'\[(\''.str_replace("'","\\'",$sub[0]).'\'|"'.str_replace('"','\\"',$sub[0]).'"|'.$sub[0].')\]\[(\''.str_replace("'","\\'",$sub[1]).'\'|"'.str_replace('"','\\"',$sub[1]).'"|'.$sub[1].')\]#';
        }
        else
        {
          $search = '#\$'.$conf['var_name'].'\[(\''.str_replace("'","\\'",$key).'\'|"'.str_replace('"','\\"',$key).'"|'.$key.')\]#';

        }*/
        
        // supposing how the file line should looks like
        switch ($conf['quote'])
        {
          case "'":
            if ($sub_string !== false)
              $row[0]['search'] = "$".$conf['var_name']."['".str_replace("'","\'",$sub_string[0])."']['".str_replace("'","\'",$sub_string[1])."']";
            else
              $row[0]['search'] = "$".$conf['var_name']."['".str_replace("'","\'",$key)."']";
            $row[0]['content'] = $row[0]['search']." = '".str_replace("'","\'",$row[0]['row_value'])."';";
            break;
          case '"':
            if ($sub_string !== false)
              $row[0]['search'] = '$'.$conf['var_name'].'["'.str_replace('"','\"',$sub_string[0]).'"]["'.str_replace('"','\"',$sub_string[1]).'"]';
            else
              $row[0]['search'] = '$'.$conf['var_name'].'["'.str_replace('"','\"',$key).'"]';
            $row[0]['content'] = $row[0]['search'].' = "'.str_replace('"','\"',$row[0]['row_value']).'";';
            break;
        }
        
        // obsolete rows, if translated then removed from the source, must be marked as commited
        if (!isset($_LANG_default[$key]))
        {
          array_merge_ref($file['done_rows'], array_unique_deep($row, 'id'));
          continue;
        }
        
        // update existing line
        if (
          !$file['is_new'] and 
          ($i = array_pos($row[0]['search'], $_FILE)) !== false
        )
        {
          // if the end of the line is not the end of the row, we search the end into lines bellow
          if (!is_eor($_FILE[$i]))
          {
            unset_to_eor($_FILE, $i);
          }
          $_FILE[$i] = $row[0]['content'];
        }
        // add new line at the end
        else
        {
          $_FILE[] = $row[0]['content'];
        }
        
        // keep trace of added row(s), user(s) and mark the file as modified
        array_merge_ref($file['done_rows'], array_unique_deep($row, 'id'));
        array_merge_ref($file['users'], array_unique_deep($row, 'user_id'));
        $file['modified'] = true;
      }
      
      // obsolete rows from file
      if ( isset($_POST['delete_obsolete']) and !$file['is_new'] )
      {
        foreach ($_LANG as $key => $row)
        {
          if (!isset($_LANG_default[$key]))
          {
            $sub_string = is_sub_string($key);
            
            // supposing how the file line should looks like
            switch ($conf['quote'])
            {
              case "'":
                if ($sub_string !== false)
                  $row['search'] = "$".$conf['var_name']."['".str_replace("'","\'",$sub_string[0])."']['".str_replace("'","\'",$sub_string[1])."']";
                else
                  $row['search'] = "$".$conf['var_name']."['".str_replace("'","\'",$key)."']";
                break;
              case '"':
                if ($sub_string !== false)
                  $row['search'] = '$'.$conf['var_name'].'["'.str_replace('"','\"',$sub_string[0]).'"]["'.str_replace('"','\"',$sub_string[1]).'"]';
                else
                  $row['search'] = '$'.$conf['var_name'].'["'.str_replace('"','\"',$key).'"]';
                break;
            }
          
            $i = array_pos($row['search'], $_FILE);
            
            // if the end of the line is not the end of the row, we search the end into lines bellow
            if (!is_eor($_FILE[$i]))
            {
              unset_to_eor($_FILE, $i);
            }
            
            unset($_FILE[$i]);
            $file['modified'] = true;
          }
        }
      }
      
      $_FILE[] = '?>'; // don't forget to close PHP tag
      
      // try to put the content in the file
      if (!deep_file_put_contents($file['path'], implode($conf['eol'], $_FILE)))
      {
        $file['done_rows'] = array();
        $file['users'] = array();
        $file['modified'] = false;
        
        array_push($file['errors'], 'Can\'t update/create file\''.$file['path'].'\'');
      }
    }
    
    // try to svn_add the file if it's new
    if ( $conf['svn_activated'] and $file['modified'] and $file['is_new'] ) 
    {
      $svn_result = svn_add($file['path'], true);
      if ($svn_result['level'] == 'error')
      {
        $file['done_rows'] = array();
        $file['users'] = array();
        $file['modified'] = false;
        
        unlink($file['path']);
        array_push($file['errors'], 'svn: '.$svn_result['msg']);
      }
    }
    
    // the file was successfully modified/created
    if ($file['done_rows'] > 0)
    {
      array_merge_ref($commit['done_rows'], $file['done_rows']);
    }
    if ($file['modified'])
    {
      array_merge_ref($commit['users'], $file['users']);
      $commit['modified'] = true;
    }
    
    // errors occured
    if (count($file['errors']) > 0)
    {
      array_merge_ref($commit['errors'], $file['errors']);
    }
  }
  
  // users
  $commit['users'] = array_unique($commit['users']);
  array_walk($commit['users'], 'print_username');
  
  
  // +-----------------------------------------------------------------------+
  // |                        SEND COMMIT
  // +-----------------------------------------------------------------------+
  // state: aborded
  if ( !$commit['modified'] and count($commit['done_rows'])>0 and count($commit['errors'])==0 )
  {
    array_push($page['warnings'], '['.$commit['project_name'].'] '.$commit['language_name'].': aborded, nothing modified.');
  }
  else
  {
    // state: commit/commit with errors
    if ( $commit['modified'] and count($commit['done_rows'])>0 )
    {
      if ( $conf['svn_activated'] )
      {
        $svn_result = svn_commit($commit['path'], 
          '['.$commit['project'].'] '.($commit['is_new']?'Add':'Update').' '.$commit['language'].', thanks to : '.implode(' & ', $commit['users'])
          );
        
        // => fatal error
        if ($svn_result['level'] == 'error')
        {
          svn_revert($commit['path']);
          $commit['done_rows'] = array();
          $commit['modified'] = false;
          $commit['errors'] = array('['.$commit['project_name'].'] '.$commit['language_name'].': '.$svn_result['msg']);
        }
        // state: commit
        else if (count($commit['errors'])==0)
        {
          array_push($page['infos'], '['.$commit['project_name'].'] '.$commit['language_name'].': '.$svn_result['msg']);
        }
        // state: commit with errors
        else if (count($commit['errors'])>0)
        {
          array_push($page['warnings'], '['.$commit['project_name'].'] '.$commit['language_name'].': '.$svn_result['msg'].', partialy commited, see errors bellow<br>'.implode('<br>', $commit['errors']));
        }
      }
      else
      {
        // state: commit
        if (count($commit['errors'])==0)
        {
          array_push($page['infos'], '['.$commit['project_name'].'] '.$commit['language_name'].': done');
        }
        // state: commit with errors
        else if (count($commit['errors'])>0)
        {
          array_push($page['warnings'], '['.$commit['project_name'].'] '.$commit['language_name'].': partialy done, see errors bellow<br>'.implode('<br>', $commit['errors']));
        }
      }
    }
    
    // state: fatal error
    if ( !$commit['modified'] and count($commit['done_rows'])==0 and count($commit['errors'])>0 )
    {
      array_push($page['errors'], '['.$commit['project_name'].'] '.$commit['language_name'].': failed<br>'.implode('<br>', $commit['errors']));
    }
  }
  
  
  // update database
  if (count($commit['done_rows']) > 0)
  {
    // delete rows
    if ($conf['delete_done_rows'])
    {
      $query = '
DELETE FROM '.ROWS_TABLE.'
  WHERE id IN('.implode(',', $commit['done_rows']).')
;';
      mysql_query($query);
    }
    // set rows as done
    else
    {
      $query = '
UPDATE '.ROWS_TABLE.'
  SET status = "done"
  WHERE id IN('.implode(',', $commit['done_rows']).')
;';
      mysql_query($query);
    }
  }
  
  make_stats($commit['project'], $commit['language']);  
}


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('messages');

?>