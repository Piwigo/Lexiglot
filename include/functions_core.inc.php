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

/**
 * load a language from file and db
 * @param string project
 * @param string language
 * @param string filename
 * @param string row_name
 * @return array
 */
function load_language($project, $language, $filename, $row_name=null)
{
  global $conf;
  
  $file = load_language_file($project, $language, $filename);
  $db = load_language_db($project, $language, $filename, $row_name);
  
  if (!empty($row_name))
  {
    foreach ($file as $id => $row)
    {
      if ($id != $row_name) unset($file[$id]);
    }
  }
  
  $out = array_merge($file, $db);
  uasort($out, create_function('&$a,&$b', 'return strcmp($a["row_name"], $b["row_name"]);'));
  
  return $out;
}

/**
 * load language rows from file
 * @param string project
 * @param string language
 * @param string filename
 * @return array
 */
function load_language_file($project, $language, $filename)
{
  global $conf;
  
  if (is_plain_file($filename))
  {
    return load_language_file_plain($project, $language, $filename);
  }
  
  ${$conf['var_name']} = array();
  $out = array();
  
  if (($file = @file_get_contents($conf['local_dir'].$project.'/'.$language.'/'.$filename)) !== false)
  {
    eval($conf['exec_before_file']);
    $file = preg_replace('#<\?php#', null, $file, 1); // remove first php open tag
    
    @eval($file); 
  }
  
  foreach (${$conf['var_name']} as $row_name => $row_value)
  {
    if (is_array($row_value))
    {
      $out = array_merge($out, pop_sub_array($row_value, $row_name));
    }
    else
    {
      $out[$row_name] = array(
        'row_name' => $row_name,
        'row_value' => $row_value,
        );
    }
  }
  
  if (empty($out))
  {
    $out = array_map(create_function('&$v', 'clean_eol($v["row_value"]);return $v;'), $out);
  }
  
  return $out;
}

function load_language_file_plain($project, $language, $filename)
{
  global $conf;
  
  $out = array();
  if (($file = @file_get_contents($conf['local_dir'].$project.'/'.$language.'/'.$filename)) !== false)
  {
    clean_eol($file);
    
    $out = array(
      $filename => array(
        'row_name' => $filename,
        'row_value' => $file,
        )
      );
  }
  
  return $out;
}

/**
 * load language rows from database
 * @param string project
 * @param string language
 * @param string filename
 * @param string row_name
 * @return array
 */
function load_language_db($project, $language, $filename, $row_name=null)
{
  // must use imbricated query to order before group
  $query = '
SELECT * FROM (
  SELECT
      row_name,
      row_value,
      user_id,
      last_edit,
      status
    FROM `'.ROWS_TABLE.'`
    WHERE 
      language = "'.$language.'" 
      AND file_name = "'.$filename.'"
      AND project = "'.$project.'"
      AND status != "done"
      '.(!empty($row_name) ? 'AND row_name = "'.mres($row_name).'"' : null).'
    ORDER BY last_edit DESC
  ) as t
  GROUP BY t.row_name
;';

  return hash_from_query($query, 'row_name');
}

/**
 * determine if a key is for a sub-array and returns it's components
 * @param string
 * @return mixed false or array
 */
function is_sub_string($string)
{
  if (0 === preg_match('#(.+)\[(.+)\]$#', $string, $matches))
  {
    return false;
  }
  else
  {
    return array($matches[1], $matches[2]);
  }
}

/**
 * determine if the language is the reference one
 * @param string language
 * @return boolean
 */
function is_default_language($lang)
{
  global $conf;
  return $lang == $conf['default_language'];
}

/**
 * determine if the file is plain text (check extension)
 * @param string filename
 * @return boolean
 */
function is_plain_file($file)
{
  global $conf;
  return preg_match('#['.implode('|', $conf['plain_types']).']$#', $file);
}

/**
 * extract language strings from sub-arrays, one level only
 * @param language sub-array
 * @param string language string name
 * @return array
 */
function pop_sub_array($array, $array_name)
{
  $new = array();
  
  foreach ($array as $sub_key => $sub_row)
  {
    if (is_array($sub_row))
    {
      continue;
    }
    
    $new[$array_name.'['.$sub_key.']'] = array(
      'row_name' => $array_name.'['.$sub_key.']',
      'row_value' => $sub_row,
      //'sub_key' => $sub_key,
      //'main_key' => $array_name,
      );
  }
  
  return $new;
}

/**
 * catch eval() errors while parsing a language file
 * @param: string filename
 * @return: bool true or array(file state, message, details)
 */
function verify_language_file($filename)
{
  global $conf;
  
  if (($file = @file_get_contents($filename)) !== false)
  {
    if ( strpos($file, '<?php')===false or strpos($file, '?>')===false )
    {
      return array('Warning', 'Missing PHP open and/or close tags');
    }
     
    eval($conf['exec_before_file']);
    $file = preg_replace('#<\?php#', null, $file, 1);
    
    ob_start();
    eval($file);
    $out = ob_get_clean();
    
    if (preg_match('#( *)Parse error#mi', $out))
    {
      return array('Parse error', $out);
    }
    else if (preg_match('#( *)Warning#mi', $out))
    {
      return array('Warning', $out);
    }
    else if (preg_match('#( *)Notice#mi', $out))
    {
      return array('Notice', $out);
    }
    else 
    {
      return true;
    }
  }
  else
  {
    return true;
  }
}

/**
 * send a mail to admins to notify a language file PHP error
 * @param: string filename
 * @param: array returned by verify_language_file()
 * @return: void
 */
function notify_language_file_error($filename, $infos)
{
  // don't notify 'Notice' errors
  if ($infos[0] == 'Notice') return;
  
  // check if the notification was already send
  if (get_session_var('notify_language_file_error.'.$filename) !== null) return;
  
  $query = '
SELECT COUNT(1)
  FROM '.MAIL_HISTORY_TABLE.'
  WHERE subject = "PHP '.$infos[0].' on '.$filename.'"
;';
  if (mysql_num_rows(mysql_query($query))) return;
  
  
  $subject = '['.strip_tags($conf['install_name']).'] PHP '.$infos[0].' on a language file';
  $content = '
Language file : '.$filename.'<br>
Error level : '.$infos[0].'<br>
Full error :<br>
'.nl2br($infos[1]).'
';
  $args = array(
    'content_format' => 'text/html',
  );

  set_session_var('notify_language_file_error.'.$filename, true);
  send_mail(get_admin_email(), $subject, $content, $args, 'PHP '.$infos[0].' on '.$filename);
}

/**
 * get language name
 * @param string lang
 */
function get_language_name($lang)
{
  global $conf;
  if (isset($conf['all_languages'][$lang]))
  {
    return $conf['all_languages'][$lang]['name'];
  }
  return $lang;
}

/**
 * get language rank
 * @param string lang
 */
function get_language_rank($lang)
{
  global $conf;
  return (int)@$conf['all_languages'][$lang]['rank'];
}

/**
 * get language flag img tag
 * @param string lang
 * @param string force (false, 'id', 'name', 'default')
 */
function get_language_flag($lang, $force=false)
{
  global $conf;
  if ( !empty($conf['all_languages'][$lang]['flag']) and file_exists_strict($conf['flags_dir'].$conf['all_languages'][$lang]['flag']) )
  {
    return '<img src="'.$conf['flags_dir'].$conf['all_languages'][$lang]['flag'].'" alt="'.$lang.'" class="flag">';
  }
  else if ($force=='id')
  {
    return $lang;
  }
  else if ($force=='name')
  {
    return get_language_name($lang);
  }
  else if ($force=='default')
  {
    return '<img src="'.$conf['flags_dir'].'default.gif" alt="'.$lang.'" class="flag">';
  }
  
  return null;
}

/**
 * get language reference
 * @param string lang
 */
function get_language_ref($lang)
{
  global $conf;
  if ( !empty($conf['all_languages'][$lang]['ref_id']) and $lang!=$conf['all_languages'][$lang]['ref_id'] and array_key_exists($conf['all_languages'][$lang]['ref_id'], $conf['all_languages']) )
  {
    return $conf['all_languages'][$lang]['ref_id'];
  }
  else
  {
    return $conf['default_language'];
  }
}

/**
 * get project name
 * @param string project
 */
function get_project_name($project)
{
  global $conf;
  if (isset($conf['all_projects'][$project]))
  {
    return $conf['all_projects'][$project]['name'];
  }
  return $project;
}

/**
 * get project rank
 * @param string project
 */
function get_project_rank($project)
{
  global $conf;
  return (int)@$conf['all_projects'][$project]['rank'];
}

/**
 * get project url
 * @param string project
 */
function get_project_url($project)
{
  global $conf;
  return (string)@$conf['all_projects'][$project]['url'];
}

/**
 * get category name
 * @param int id
 */
function get_category_name($id)
{
  $query = '
SELECT name
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$id.'
;';
  list($name) = mysql_fetch_row(mysql_query($query));
  return $name;
}

/**
 * create a directory with(out) SVN and add optional file (advanced conf)
 * @param: string path
 * @return: bool
 */
function create_directory($path)
{
  global $conf;
  
  // create the directory
  if ($conf['svn_activated'])
  {
    $svn_result = svn_mkdir($path, true);
    $result = ($svn_result['level'] == 'success');
  }
  else
  {
    $result = mkdir($path, 0777, true);
  }
  
  // add the optional file
  if ( $result and file_exists($conf['copy_file_to_repo']) )
  {
    $destination = $path.'/'.basename($conf['copy_file_to_repo']);
    copy($conf['copy_file_to_repo'], $destination);
    
    if ($conf['svn_activated'])
    {
      $svn_result = svn_add($destination, true);
      if ($svn_result['level'] == 'error') unlink($destination);
    }
  }
  
  return $result;
}

/**
 * performs a string search in a language array
 * search the string and each sub-words weighted by the lenght
 *
 * @param array language array
 * @param string needle
 * @param string 'row_value' or 'row_name' or 'original'
 * @return array sorted by 'search_rank'
 */
function search_fulltext($haystack, $needle, $where='row_value')
{
  // format string
  $needle = trim(strtolower($needle));
  $needle_lenght = strlen($needle);
  
  // extract words
  $words = get_fulltext_words($needle);
  
  foreach ($words as $i => $word)
  {
    unset($words[$i]);
    if (!empty($word))
    {
      $words[$word] = strlen($word);
    }
  }
  
  foreach ($haystack as $id => $row)
  {
    if (empty($row[$where]))
    {
      unset($haystack[$id]);
      continue;
    }
    
    $haystack[$id]['search_rank'] = 0;
    $search_in = strtolower($row[$where]);
    
    // complete string
    $haystack[$id]['search_rank']+= substr_count($search_in, $needle)*$needle_lenght;
    
    foreach ($words as $word => $lenght)
    {
      // complete word
      $haystack[$id]['search_rank']+= substr_count($search_in, ' '.$word.' ')*$lenght;
      
      // partial word
      $haystack[$id]['search_rank']+= substr_count($search_in, $word)*$lenght*1/2;
    }
    
    // remove unmatching rows
    if ($haystack[$id]['search_rank'] == 0)
    {
      unset($haystack[$id]);
    }
  }
  
  // sort results
  function cmp($a, $b)
  {
    if ($a['search_rank'] == $b['search_rank']) return 0;
    return ($a['search_rank'] > $b['search_rank']) ? -1 : 1;
  }

  uasort($haystack, 'cmp');
  
  return $haystack;
}

/**
 * slice a search query into words, remove all special chars
 * @param string
 * @return array
 */
function get_fulltext_words($needle)
{
  $str = preg_replace('#[\&\#\"\{\(\[\-\|\_\\\@\)\]\+\=\}\*\,\?\;\.\:\/\!]#', ' ', $needle);
  $str = preg_replace('#[\s]+#',' ', $str);
  $words = explode(' ', $str);
  return $words;
}

/**
 * highlight searched words with a span
 * @param string
 * @param array words
 * @param string hex color
 * @return string
 */
function highlight_search_result($text, $words, $color="#ff0")
{
  $replace = implode('|', $words);
  $text = preg_replace('#('.$replace.')#i', '<span class="highlight" style="background-color:'.$color.';">$1</span>', $text);
  return $text;
}

?>