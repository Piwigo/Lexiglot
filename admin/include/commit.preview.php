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


// +-----------------------------------------------------------------------+
// |                        PREVIEW COMMIT
// +-----------------------------------------------------------------------+
// FOREACH COMMIT
foreach ($_ROWS as $props => $commit_content)
{
  // commit infos
  $commit = array();
  list($commit['project'], $commit['language']) = explode('||', $props);
  $commit['path'] = $conf['local_dir'].$commit['project'].'/'.$commit['language'].'/';
  $commit['is_new'] = dir_is_empty($commit['path']);
  $commit['users'] = $commit['files'] = array();
  
  // FOREACH FILE
  foreach ($commit_content as $filename => $file_content)
  {
    $file = array();
    $file['name'] = $filename;
    $file['path'] = $commit['path'].$file['name'];
    $file['is_new'] = !file_exists($file['path']);
    $file['is_plain'] = is_plain_file($file['name']);
    $file['rows'] = array();
    
    ## plain file ##
    if ($file['is_plain'])
    {
      $row = $file_content[ $file['name'] ];
      array_merge_ref($commit['users'], array_unique_deep($row, 'user_id'));
      $row[0]['status'] = $row[ count($row)-1 ]['status'];
      $row[0]['row_value'] = htmlspecialchars($row[0]['row_value']);
      
      $file['rows'][] = $row[0];
    }
    ## array file ##
    else
    {
      $_LANG =         load_language_file($commit['project'], $commit['language'], $file['name']);
      $_LANG_default = load_language_file($commit['project'], $conf['default_language'], $file['name']);
      
      // rows from database (new/edited) we skip obsolete
      foreach ($file_content as $key => $row)
      {
        if (!isset($_LANG_default[$key])) continue;
        
        array_merge_ref($commit['users'], array_unique_deep($row, 'user_id'));
        $row[0]['status'] = $row[ count($row)-1 ]['status'];
        $row[0]['row_value'] = htmlspecialchars($row[0]['row_value']);
        $row[0]['key'] = htmlspecialchars($key);
        
        $file['rows'][] = $row[0];
      }
      
      // obsolete rows from file
      if (isset($_POST['delete_obsolete']))
      {
        foreach ($_LANG as $key => $row)
        {
          if (!isset($_LANG_default[$key]))
          {
            $row['status'] = 'obsolete';
            $row['row_value'] = htmlspecialchars($row['row_value']);
            $row['key'] = htmlspecialchars($key);
            
            $file['rows'][] = $row;
          }
        }
      }
    }
    
    $commit['files'][] = $file;
  }
  
  $commit['users'] = array_unique($commit['users']);
  array_walk($commit['users'], 'print_username');
  
  $template->append('DATA', $commit);
}


// +-----------------------------------------------------------------------+
// |                        CONFIGURATION
// +-----------------------------------------------------------------------+
$tpl_var = array(
  'delete_obsolete' => isset($_POST['delete_obsolete']),
  'mode' => $_POST['mode'],
  );
  
if ($_POST['mode'] != 'all')
{
  foreach(array('project','language','user') as $mode)
  {
    if ( !empty($_POST['filter_'.$mode]) and $_POST[$mode.'_id'] != '-1' )
    {
      $tpl_var['filters'][$mode] = $_POST[ $mode.'_id' ];
    }
  }
}
  
$template->assign('commit_config', $tpl_var);


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('admin/commit_preview');

?>