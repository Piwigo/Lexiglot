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
 * load language rows from file
 * @param string file
 * @return array
 */
function load_language_file($filename)
{
  global $conf;
  
  if (is_plain_file($filename))
  {
    return load_language_file_plain($filename);
  }
  
  ${$conf['var_name']} = array();
  $out = array();
  
  if (($file = @file_get_contents($filename)) !== false)
  {
    eval($conf['exec_before_file']);
    $file = str_replace(
      array('<?php', '?>'), // eval fails with php tags
      null, $file
      );
    eval($file); 
  }
  
  foreach (${$conf['var_name']} as $row_name => $row_value)
  {
    $out[$row_name] = array(
      'row_name' => $row_name,
      'row_value' => $row_value,
      );
  }
  
  return $out;
}

function load_language_file_plain($filename)
{
  $out = array();
  if (($file = @file_get_contents($filename)) !== false)
  {
    $out = array('row_value' => $file);
  }
  return $out;
}

/**
 * load language rows from database
 * @param string language
 * @param string file
 * @param string section
 * @param string row_name=null
 * @return array
 */
function load_language_db($language, $file, $section, $row_name=null)
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
      lang = "'.$language.'" 
      AND file_name = "'.$file.'"
      AND section = "'.$section.'"
      '.(!empty($row_name) ? 'AND row_name = "'.mres($row_name).'"' : null).'
    ORDER BY last_edit DESC
  ) as t
  GROUP BY t.row_name
;';

  return hash_from_query($query, 'row_name');
}

/**
 * determine if the language is the reference
 * @param string language
 * @return boolean
 */
function is_source_language($lang)
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
 * get language flag img tag
 * @param string lang
 * @param string force (false, 'id', 'name', 'default')
 */
function get_language_flag($lang, $force=false)
{
  global $conf;
  if ( isset($conf['all_languages'][$lang]) and file_exists_strict($conf['flags_dir'].$conf['all_languages'][$lang]['flag']) )
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
 * get section name
 * @param string section
 */
function get_section_name($section)
{
  global $conf;
  if (isset($conf['all_sections'][$section]))
  {
    return $conf['all_sections'][$section]['name'];
  }
  return $section;
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
 * performs a string search in a language array
 * search the string and each sub-words weighted by the lenght
 *
 * @param array language array
 * @param string needle
 * @param string 'row_value' or 'row_name'
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
    $haystack[$id]['search_rank']+= substr_count($search_in, ' '.$needle.' ')*$needle_lenght;
    
    // complete string with partial word
    $haystack[$id]['search_rank']+= substr_count($search_in, $needle)*$needle_lenght*3/4;
    
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

function get_fulltext_words($needle)
{
  $str = preg_replace('#[\&\#\"\'\{\(\[\-\|\_\\\@\)\]\+\=\}\*\,\?\;\.\:\/\!]#', ' ', $needle);
  $str = preg_replace('#[\s]+#',' ', $str);
  $words = explode(' ', $str);
  return $words;
}

function highlight_search_result($text, $words, $color="#ff0")
{
  $replace = implode('|', $words);
  $text = preg_replace('#('.$replace.')#i', '<span class="highlight" style="background-color:'.$color.';">$1</span>', $text);
  return $text;
}

?>