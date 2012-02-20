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
    mkdir($path, 0777, true);
    if ($conf['svn_activated'])
    {
      svn_add($path);
    }
  }
  
  return file_put_contents($filename, $data, $flags);
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
?>