<?php
// +-----------------------------------------------------------------------+
// | Lexiglot - A PHP based translation tool                               |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2011-2013 Damien Sorel       http://www.strangeplanet.fr |
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

/**
 * create where clauses from session and POST search
 * @param &array search configuration (type, value, default_value)
 * @param string search name
 * @param array fields to exclude from query
 * @return array where clauses
 */
function session_search(&$search, $name, $exclude_from_query=array(), $prefix='')
{
  global $db;

  $where_clauses = array('1=1');
  
  // erase search
  if (isset($_POST['erase_search']))
  {
    unset_session_var($name);
    unset($_POST);
  }
  // get saved search
  else if (get_session_var($name) != null)
  {
    $search = unserialize(get_session_var($name));
  }
  
  // set default_value
  foreach ($search as &$data)
  {
    if (!isset($data[2])) $data[2] = $data[1];
  }
  unset($data);
  
  // get form search
  if (isset($_POST['search']))
  {
    unset_session_var($name);
    unset($_GET['nav']);
    
    foreach ($search as $field => $data)
    {
      if ($data[0] == '%')
      {
        if (!empty($_POST[$field])) $search[$field][1] = str_replace('*', '%', $_POST[$field]);
      }
      else if ($data[0] == '=')
      {
        if (!empty($_POST[$field])) $search[$field][1] = $_POST[$field];
      }
    }
  }

  // build clauses
  foreach ($search as $field => $data)
  {
    if (in_array($field, $exclude_from_query)) continue;
    if ($data[1] == $data[2]) continue;
    
    $data[1] = $db->real_escape_string($data[1]);
    
    if ($data[0] == '%')
    {
      array_push($where_clauses, 'LOWER('.$prefix.$field.') LIKE LOWER("%'.$data[1].'%")');
    }
    else if ($data[0] == '=')
    {
      array_push($where_clauses, $prefix.$field.' = "'.$data[1].'"');
    }
  }

  // save search
  set_session_var($name, serialize($search));
  
  return $where_clauses;
}

/**
 * format search array for template usage
 * @param: array search
 * @return: array tpl search
 */
function search_to_template($search)
{
  $out = array();
  
  foreach ($search as $field => $data)
  {
    $data[1] = htmlspecialchars($data[1]);
    
    if ($data[0] == '%')
    {
      $out[$field] = str_replace('%', '*', $data[1]);
    }
    else if ($data[0] == '=')
    {
      $out[$field] = $data[1];
    }
  }
  
  return $out;
}

/**
 * simple access to the value of a search field
 * @param: string key
 * @return: mixed
 */
function get_search_value($key)
{
  global $search;
  if (!isset($search[$key])) return null;
  return $search[$key][1];
}

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
 * search if the end of the line if the end of the row
 * @param string
 * @return bool
 */
function is_eor($line)
{
  return preg_match('#(\'|");(\s*)$#', $line);
}

/**
 * search the end of the row and delete the row (doesn't delete the first line of the row)
 * @param array
 * @param int start
 * @return void
 */
function unset_to_eor(&$array, $i)
{
  global $conf;
  
  $temp_file = array_slice($array, $i);
  $eor = $i + array_pos('#(\'|");(\s*)$#', $temp_file, false, true);
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
  
  if ( !empty($path) and !file_exists($path) and !create_directory($path) )
  {
    return false;
  }
  
  $out = file_put_contents($filename, $data, $flags);
  chmod($filename, 0777);
  return $out;
}

/**
 * add a category if doesn't exist and return its id
 * @param string name
 * @param string type ('project', 'language')
 * @return int id
 */
function add_category($name, $type)
{
  global $db;
  
  $query = '
SELECT id
  FROM '.CATEGORIES_TABLE.'
  WHERE 
    name = "'.$name.'"
    AND type = "'.$type.'"
;';
  $result = $db->query($query);
  if ($result->num_rows)
  {
    list($id) = $result->fetch_row();
    return $id;
  }
  else
  {
    $query = '
INSERT INTO '.CATEGORIES_TABLE.'
  VALUES(NULL,"'.$name.'", "'.$type.'")
;';
    $db->query($query);
    return $db->insert_id;
  }
}

/**
 * generate the content of the languages tooltip
 * @param: &array user
 * @param: int max languages to display before generate a tooltip
 * @param: bool display 'my_languages' instead of 'languages'
 * @return: string
 */
function print_user_languages_tooltip(&$user, $max=3, $my=false)
{
  $languages = $my ? $user['my_languages'] : $user['languages'];
  
  $out = null;
  if (count($languages) <= $max)
  {
    foreach ($languages as $lang)
    {
      $out.= '<a title="'.get_language_name($lang).'" class="clean expand '.($user['main_language']==$lang?'main-language':null).'">'.get_language_flag($lang, 'default').'</a>';
    }
  }
  else
  {
    $out.= '<a class="expand" title=\'<table class="tooltip"><tr>';
    $i=1; $j=ceil(sqrt(count($languages)/4));
    foreach ($languages as $lang)
    {
      $out.= '<td '.($user['main_language']==$lang?'class="main-language"':null).'>'.get_language_flag($lang, 'default').' '.get_language_name($lang).'</td>';
      if($i%$j==0) $out.= '</tr><tr>'; $i++;
    }
    $out.= '</tr></table>\'>
      '.count($languages).' <img src="template/images/bullet_toggle_plus.png" style="vertical-align:middle;"></a>';
  }
  
  return $out;
}

/**
 * generate the content of the projects tooltip
 * @param: &array user
 * @param: int max projects to display before generate a tooltip
 * @return: string
 */
function print_user_projects_tooltip(&$user, $max=1)
{
  global $conf;
  $projects = $user['projects'];
  
  if ( $conf['use_stats'] and in_array($user['status'], array('translator','manager')) AND !empty($user['main_language']) )
  {
    $stats = get_cache_stats(null, $user['main_language'], 'project');
  }
  $use_stats = !empty($stats);
  
  $out = null;
  if (count($projects) <= $max)
  {
    foreach ($projects as $project)
    {
      $out.= get_project_name($project).($use_stats ? ' <b style="color:'.get_gauge_color($stats[$project]).';">'.number_format($stats[$project]*100, 0).'%</b>' : null);
    }
  }
  else
  {
    $out.= '<a class="expand" title=\'<table class="tooltip"><tr>';
    $i=1; $j=ceil(sqrt(count($projects)/4));
    foreach ($projects as $project)
    {
      $out.= '<td>'.get_project_name($project).($use_stats ? ' <b style="color:'.get_gauge_color($stats[$project]).';">'.number_format($stats[$project]*100, 0).'%</b>' : null).'</td>';
      if($i%$j==0)$out.= '</tr><tr>'; $i++;
    }
    $out.= '</tr></table>\'>
      '.count($projects).' <img src="template/images/bullet_toggle_plus.png" style="vertical-align:middle;"></a>';
  }
  
  return $out;
}

/**
 * returns full info array for specified languages
 * @param: ids array
 * @return: array
 */
function create_languages_array($ids)
{
  global $conf;
  
  if (!count($ids)) return array();
  
  $intersect = array_combine($ids, 
    array_fill(0, count($ids), null)
    );
  
  return array_intersect_key($conf['all_languages'], $intersect);
}

/**
 * returns full info array for specified projects
 * @param: ids array
 * @return: array
 */
function create_projects_array($ids)
{
  global $conf;
  
  if (!count($ids)) return array();
  
  $intersect = array_combine($ids, 
    array_fill(0, count($ids), null)
    );
  
  return array_intersect_key($conf['all_projects'], $intersect);
}

?>