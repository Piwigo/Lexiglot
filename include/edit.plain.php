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

if (!empty($_LANG)) $_LANG = $_LANG[ $page['file'] ];

// +-----------------------------------------------------------------------+
// |                         SAVE FILE
// +-----------------------------------------------------------------------+
if ( isset($_POST['submit']) and $is_translator )
{
  // note that the key never expires, translation can take a while!
  if (!verify_ephemeral_key(@$_POST['key'], '', false))
  {
    array_push($page['errors'], 'Invalid/expired form key');
  }
  else
  {  
    $text = $_POST['row_value'];
    clean_eol($text);

    // test if the new value is really new (in file and in database)
    if (  
      !empty($text) and
      ( empty($_LANG) or $text != $_LANG['row_value'] )
    )
    {
    $query = '
INSERT INTO `'.ROWS_TABLE.'`(
    language,
    project,
    file_name,
    row_name,
    row_value,
    user_id,
    last_edit,
    status
  )
  VALUES(
    "'.$page['language'].'",
    "'.$page['project'].'",
    "'.$page['file'].'",
    "'.$page['file'].'",
    "'.mres($text).'",
    "'.$user['id'].'",
    NOW(),
    "'.( !empty($_LANG) ? 'edit' : 'new' ).'" 
  )
  ON DUPLICATE KEY UPDATE
    last_edit = NOW(),
    row_value = "'.mres($text).'",
    status = IF(status="done","edit",status)
;';
      $db->query($query);
    }
    
    make_stats($page['project'], $page['language']);
    $_SESSION['page_infos'][] = 'File saved';
    redirect();
  }
}


// +-----------------------------------------------------------------------+
// |                         COMPUTE FILES
// +-----------------------------------------------------------------------+
$_DIFFS = false;
if (empty($_LANG))
{
  $_DIFFS = true;
}

if ($_DIFFS)
{
  array_push($page['warnings'], 'This file is not translated yet.');
}


// +-----------------------------------------------------------------------+
// |                         DISPLAY FILE
// +-----------------------------------------------------------------------+  
// value, database has priority
$text = !empty($_LANG) ? htmlspecialchars_utf8($_LANG['row_value']) : null;

$template->assign(array(
  'ROW_VALUE' => $text,
  'AREA_HEIGHT' => max(count_lines($text, 126)+3, 10)*1.1,
  'NB_LINES' => count_lines($text, 126),
  ));

?>