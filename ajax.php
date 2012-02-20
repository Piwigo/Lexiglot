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

/**
 * This page is called by jQuery ro perform some AJAX actions
 * a ephemeral key is mandatory for security
 */

define('PATH', './');
define('IN_AJAX', 1);
include(PATH.'include/common.inc.php');

if (!isset($_POST['action']))
{
  echo 'Undefined action';
  close_page();
}

if (!verify_ephemeral_key(@$_POST['key']))
{
  echo 'Stop spam!';
  close_page();
}

switch ($_POST['action'])
{
  // SAVE A ROW
  case 'save_row':
  {    
    if (empty($_POST['row_value']))
    {
      echo 'String is empty';
      break;
    }
    
    if (empty($_POST['row_name']) or empty($_POST['section']) or empty($_POST['language']) or empty($_POST['file']))
    {
      echo 'Bad parameters';
      break;
    }
    
    $key = utf8_decode($_POST['row_name']);
    $text = utf8_decode($_POST['row_value']);
    $_POST['directory'] = $conf['local_dir'].$_POST['section'].'/';
    $_LANG =         load_language_file($_POST['directory'].$_POST['language'].'/'.$_POST['file']);
    $_LANG_db =      load_language_db($_POST['language'], $_POST['file'], $_POST['section'], $key);
    
    if (  
      ( 
        ( !isset($_LANG[$key]) or $text != $_LANG[$key]['row_value'] ) 
        and ( !isset($_LANG_db[$key]) or $text != $_LANG_db[$key]['row_value'] ) 
      )
      or ( isset($_LANG_db[$key]) and $text != $_LANG_db[$key]['row_value'] )
    )
    {
      $query = '
INSERT INTO `'.ROWS_TABLE.'`(
    lang,
    section,
    file_name,
    row_name,
    row_value,
    user_id,
    last_edit,
    status
  )
  VALUES(
    "'.mres($_POST['language']).'",
    "'.mres($_POST['section']).'",
    "'.mres($_POST['file']).'",
    "'.mres($key).'",
    "'.mres($text).'",
    "'.$user['id'].'",
    NOW(),
    "'.( isset($_LANG[$key]) ? 'edit' : 'new' ).'" 
  )
  ON DUPLICATE KEY UPDATE
    last_edit = NOW(),
    row_value = "'.$text.'",
    status = IF(status="done","edit",status)
;';
      mysql_query($query);
      
      echo 'Saved';
      break;
    }
    
    echo 'Already up-to-date';
    break;
  }
  
  
  // HISTORY OF A ROW
  case 'row_log':
  {
    if (empty($_POST['row_name']) or empty($_POST['section']) or empty($_POST['language']) or empty($_POST['file']))
    {
      echo 'Bad parameters';
      break;
    }
    
    $query = '
SELECT
    row_value,
    user_id,
    '.$conf['user_fields']['username'].' AS username,
    last_edit,
    status
  FROM '.ROWS_TABLE.' AS r
    INNER JOIN '.USERS_TABLE.' AS u
    ON u.id = r.user_id
  WHERE
    row_name = "'.mres(utf8_decode($_POST['row_name'])).'"
    AND section = "'.mres($_POST['section']).'"
    AND lang = "'.mres($_POST['language']).'"
    AND file_name = "'.mres($_POST['file']).'"
  ORDER BY last_edit DESC
;';
    $result = mysql_query($query);
    
    $out = array();
    while ($entry = mysql_fetch_assoc($result))
    {
      array_push($out, '<li><pre>'.$entry['row_value'].'</pre> <span>by <a href="'.get_url_string(array('user_id'=>$entry['user_id']),'all','profile').'">'.$entry['username'].'</a> on '.format_date($entry['last_edit']).'</span></li>');
    }
    if (!count($out))
    {
      array_push($out, '<li><i>No data</i></li>');
    }
    
    echo '<h5>Past translations :</h5>
    <ul class="row_log">
      '.implode('', $out).'
    </ul>';
    break;
    
  }
}

close_page();

?>