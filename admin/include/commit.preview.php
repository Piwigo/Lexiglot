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
// |                        PREVIEW COMMIT
// +-----------------------------------------------------------------------+

// only use this with 'array_walk'
function print_username(&$item, $key)
{
  global $_USERS;
  $item = $_USERS[$item]['username'];
}

// legend
echo '
<form id="check_commit" action="admin.php?page=commit" method="post">
<fieldset class="common">
  <legend>'.count($_ROWS).' commit(s) '.(!empty($commit_title) ? '• '.$commit_title : null).'</legend>
  <table class="main" style="margin-bottom:10px;">';
  $i=0;
  // FOREACH COMMIT
  foreach ($_ROWS as $props => $files)
  {
    // commit infos
    list($commit['section'], $commit['language']) = explode('||', $props);
    $commit['path'] = $conf['local_dir'].$commit['section'].'/'.$commit['language'].'/';
    $commit['is_new'] = dir_is_empty($commit['path']);
    $commit['users'] = array();
    
    echo '
    <tr><td class="title commit" colspan="2">
      <b>Section :</b> '.$commit['section'].' — <b>Language :</b> '.$commit['language'].'
      <span style="float:right;"><label><input type="checkbox" name="exclude[]" value="'.$commit['section'].$commit['language'].'"> Exlude</label></span>
    </td></tr>
    <tr>
      <td class="marge"></td>
      <td>
      <table class="files">';
      // FOREACH FILE
      foreach ($files as $filename => $file_content)
      {
        $file_infos['name'] = $filename;
        $file_infos['path'] = $commit['path'].$file_infos['name'];
        $file_infos['is_new'] = !file_exists($file_infos['path']);
        
        echo '
          <tr><td class="title file" colspan="2"><b>File :</b> '.$file_infos['name'].($file_infos['is_new'] ? ' <span class="green">(new file)</span>' : null).'</td></tr>';
        
        ## plain file ##
        if (is_plain_file($file_infos['name']))
        {
          $row = $file_content[ $file_infos['name'] ];
          array_push($commit['users'], $row['user_id']);
          
          echo '
          <tr>
            <td class="marge"></td>
            <td><table class="rows">
              <tr class="'.$row['status'].' '.($i%2==0?'odd':'even').'">
                <td colspan="2"><pre>'.htmlspecialchars($row['row_value']).'</pre></td>
              </tr>
            </table></td>
          </tr>';
          $i++;
        }
        ## array file ##
        else
        {
          $_LANG =         load_language_file($commit['section'], $commit['language'], $file_infos['name']);
          $_LANG_default = load_language_file($commit['section'], $conf['default_language'], $file_infos['name']);
          
          echo '
          <tr>
            <td class="marge"></td>
            <td><table class="rows">';
            // rows from database (new/edited) we skip obsolete
            foreach ($file_content as $key => $row)
            {
              if (!isset($_LANG_default[$key])) continue;
              
              array_push($commit['users'], $row['user_id']);
              
              echo '
              <tr class="'.$row['status'].' '.($i%2==0?'odd':'even').'">
                <td><pre>'.htmlspecialchars($key).'</pre></td>
                <td><pre>'.htmlspecialchars($row['row_value']).'</pre></td>
              </tr>';
              $i++;
            }
            // obsolete rows from file
            if (isset($_POST['delete_obsolete']))
            {
              foreach ($_LANG as $key => $row)
              {
                if (!isset($_LANG_default[$key]))
                {
                  echo '
                  <tr class="obsolete '.($i%2==0?'odd':'even').'">
                    <td>'.htmlspecialchars($key).'</td>
                    <td>'.htmlspecialchars($row['row_value']).'</td>
                  </tr>';
                  $i++;
                }
              }
            }
            echo '
            </table></td>
          </tr>';
        }
      }
      echo '
      </table>
    </td>
    </tr>';
    
    $commit['users'] = array_unique($commit['users']);
    array_walk($commit['users'], 'print_username');
  
    echo '
    <tr><td class="message" colspan="2"><b>Message :</b> ['.get_section_name($commit['section']).'] '.($commit['is_new']?'Add':'Update').' language '.get_language_name($commit['language']).', thanks to : '.implode(' & ', $commit['users']).'</td></tr>';
    
    unset($commit);
  }
  echo '
  </table>
  
  <div class="centered">';
    // repeat some form inputs
    if (isset($_POST['delete_obsolete']))
    {
      echo '
      <input type="hidden" name="delete_obsolete" value="1">';
    }
    if ($_POST['mode'] != 'all')
    {
      foreach(array('section','language','user') as $mode)
      {
        if ( !empty($_POST['filter_'.$mode]) and $_POST[$mode.'_id'] != '-1' )
        {
          echo '
          <input type="hidden" name="filter_'.$mode.'" value="1">
          <input type="hidden" name="'.$mode.'_id" value="'.$_POST[ $mode.'_id' ].'">';
        }
      }
    }
    echo '
    <input type="hidden" name="mode" value="'.$_POST['mode'].'">
    <input type="submit" name="init_commit" class="blue big" value="Launch">
  </div>
</fieldset>
</form>

<table class="legend">
  <tr>
    <td><span class="new">&nbsp;</span> Added strings</td>
    <td><span class="edit">&nbsp;</span> Modified strings</td>
    <td><span class="missing">&nbsp;</span> Obsolete strings</td>
  </tr>
</table>';

?>