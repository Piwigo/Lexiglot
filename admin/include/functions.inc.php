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

/**
 * upload a flag
 * @param $_FILES entry
 * @param destination name
 * @return array errors / string destination name
 */
function upload_flag($file, $name)
{
  global $conf;
  $errors = array();
  
  if ($file['error'] > 0) 
  {
    array_push($errors, 'Error when uploading file.');
  }
  else if ($file['size'] > 10240) 
  {
    array_push($errors, 'The file is too big, max size : 10Kio.');
  }
  else if ( !in_array($file['type'], array('image/jpeg', 'image/png', 'image/gif')) )
  {
    array_push($errors, 'Incorrect file type, jpeg/png/gif.');
  }
  else
  {
    $img_size = getimagesize($file['tmp_name']);
    if ($img_size[0] > 24 or $img_size[1] > 24)
    {
      array_push($errors, 'The flag is too big, max size : 24x24px.');
    }
  }
  
  if (count($errors) == 0)
  {
    $file['dest'] = $name.'_'.uniqid().'.'.strtolower(substr(strrchr($file['name'], '.'), 1));
    move_uploaded_file($file['tmp_name'], $conf['flags_dir'].$file['dest']);
    return $file['dest'];
  }
  else
  {
    return $errors;
  }
}

/**
 * search to the end of the row and delete the row
 * @param array
 * @param int start
 * @return void
 */
function unset_to_eor(&$array, $i)
{
  global $conf;
  
  $temp_file = array_slice($array, $i);
  $eor = $i + array_pos('#(\'|");( *)$#', $temp_file, false, true);
  for ($l=$i+1; $l<=$eor; $l++) unset($array[$l]);
  
  unset($temp_file);
}

/**
 * variation of file_put_contents which create needed subfolders
 * @param filename
 * @param data
 * @param flags
 * @return boolean
 */
function deep_file_put_contents($filename, $data, $flags=0)
{
  global $conf;
  
  $path = str_replace(basename($filename), null, $filename);
  
  if ( !empty($path) and !file_exists($path) )
  {
    if ($conf['svn_activated'])
    {
      $svn_result = svn_mkdir($path, true);
      if ($svn_result['level'] != 'success') return false;
    }
    else
    {
      if (!mkdir($path, 0777, true)) return false;
    }
  }
  
  $out = file_put_contents($filename, $data, $flags);
  chmod($filename, 0777);
  return $out;
}

/**
 * add a category if doesn't exist and return its id
 * @param string name
 * @param string type ('section', 'language')
 * @return int id
 */
function add_category($name, $type)
{
  $query = '
SELECT id
  FROM '.CATEGORIES_TABLE.'
  WHERE 
    name = "'.$name.'"
    AND type = "'.$type.'"
;';
  $result = mysql_query($query);
  if (mysql_num_rows($result))
  {
    list($id) = mysql_fetch_row($result);
    return $id;
  }
  else
  {
    $query = '
INSERT INTO '.CATEGORIES_TABLE.'
  VALUES(NULL,"'.$name.'", "'.$type.'")
;';
    mysql_query($query);
    return mysql_insert_id();
  }
}

function print_user_languages_tooltip(&$user, $max=3)
{
  $languages = $user['languages'];
  
  $out = null;
  if (count($languages) <= $max)
  {
    $out.= '<span style="display:none;">'.count($languages).'</span>';
    foreach ($languages as $lang)
    {
      $out.= '<a title="'.get_language_name($lang).'" class="clean expand">'.get_language_flag($lang, 'default').'</a>';
    }
  }
  else
  {
    $out.= '<a class="expand" title=\'<table class="tooltip"><tr>';
    $i=1; $j=ceil(sqrt(count($languages)/2));
    foreach ($languages as $lang)
    {
      $out.= '<td>'.get_language_flag($lang, 'default').' '.get_language_name($lang).'</td>';
      if($i%$j==0) $out.= '</tr><tr>'; $i++;
    }
    $out.= '</tr></table>\'>
      '.count($languages).' <img src="template/images/bullet_toggle_plus.png" style="vertical-align:middle;"></a>';
  }
  return $out;
}

function print_user_sections_tooltip(&$user, $max=1)
{
  $sections = $user['sections'];
  
  if ( $user['status'] == 'translator' AND !empty($user['main_language']) )
  {
    $stats = get_cache_stats(null, $user['main_language'], 'section');
  }
  $use_stats = !empty($stats);
  
  $out = null;
  if (count($sections) <= $max)
  {
    $out.= '<span style="display:none;">'.count($sections).'</span>';
    foreach ($sections as $section)
    {
      $out.= get_section_name($section).($use_stats ? ' <b style="color:'.get_gauge_color($stats[$section]).';">'.number_format($stats[$section]*100, 0).'%</b>' : null);
    }
  }
  else
  {
    $out.= '<a class="expand" title=\'<table class="tooltip"><tr>';
    $i=1; $j=ceil(sqrt(count($sections)/2));
    foreach ($sections as $section)
    {
      $out.= '<td>'.get_section_name($section).($use_stats ? ' <b style="color:'.get_gauge_color($stats[$section]).';">'.number_format($stats[$section]*100, 0).'%</b>' : null).'</td>';
      if($i%$j==0)$out.= '</tr><tr>'; $i++;
    }
    $out.= '</tr></table>\'>
      '.count($sections).' <img src="template/images/bullet_toggle_plus.png" style="vertical-align:middle;"></a>';
  }
  return $out;
}

?>