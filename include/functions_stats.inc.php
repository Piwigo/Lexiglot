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
 * make stats for a language of a section and register it to DB
 * calculate ratio between the numbers of rows in the default language and the number of existing rows in the current language
 * the value returned is between 0 and 1
 *
 * @param string section
 * @param string language
 * @return float stat
 */
function make_stats($section, $language, $save=true)
{
  global $conf;
  
  // language/section doesn't exist
  if (!file_exists($conf['local_dir'].$section.'/'.$language))
  {
    $stat = 0;
  }
  // language/section exists, will count rows
  else
  {
    $total = $translated = 0;
    $directory = $conf['local_dir'].$section.'/';
    $files = explode(',', $conf['all_sections'][$section]['files']);
    
    foreach ($files as $file)
    {
      $_LANG_default = load_language_file($section, $conf['default_language'], $file);
      $_LANG =         load_language($section, $language, $file);
      
      // for plain texts
      if (is_plain_file($file))
      {
        $src_lenght = substr_count($_LANG_default[$file]['row_value'], $conf['eol'])+1;
        $total+= $src_lenght;
        if ( !empty($_LANG) )
        {
          $translated+= $src_lenght;
        }
      }
      // for arrays
      else
      {
        foreach ($_LANG_default as $key => $row)
        {
          if ( isset($_LANG[$key]) )
          {
            $translated++;
          }
          $total++;
        }
      }
    }
    
    $stat = ($total != 0) ? min($translated/$total, 1) : 0; // min is to prevent any error during calculation
  }
  
  if ($save)
  {
    $query = '
DELETE FROM '.STATS_TABLE.'
  WHERE
    section = "'.$section.'"
    AND language = "'.$language.'"
;';
    mysql_query($query);
    
    $query = '
INSERT INTO '.STATS_TABLE.'(
    section,
    language,
    date,
    value
  )
  VALUES (
    "'.$section.'",
    "'.$language.'",
    NOW(),
    '.$stat.'
  )
;';
    mysql_query($query);
  }
  
  return $stat;
}

/**
 * make stats for a section
 * @param string section
 * @return array of floats stats
 */
function make_section_stats($section, $save=true)
{
  global $conf;
  $stats = array();
  
  foreach (array_keys($conf['all_languages']) as $language)
  {
    $stats[$language] = make_stats($section, $language, $save);
  }
  
  return $stats;
}

/**
 * make stats for a language
 * @param string language
 * @return array of floats stats
 */
function make_language_stats($language, $save=true)
{
  global $conf;
  $stats = array();
  
  foreach (array_keys($conf['all_sections']) as $section)
  {
    $stats[$section] = make_stats($section, $language, $save);
  }
  
  return $stats;
}

/**
 * make all stats
 * @return array of floats stats
 */
function make_full_stats($save=true)
{
  global $conf;
  $stats = array();
  
  foreach (array_keys($conf['all_sections']) as $section)
  {
    $stats[$section] = make_section_stats($section, $save);
  }
  
  return $stats;
}

/**
 * get saved stats
 * @param string section
 * @param string language
 * @param string 'language','section','all'
 * @return array
 */
function get_cache_stats($Ssection=null, $Slanguage=null, $Ssum=null)
{
  global $conf;
 if (!$conf['use_stats']) return array();
  
  $where_clauses = array('1=1');
  if (!empty($Slanguage))
  {
    $where_clauses[] = 'language = "'.$Slanguage.'"';
  }
  if (!empty($Ssection))
  {
    $where_clauses[] = 'section = "'.$Ssection.'"';
  }
  
  $query = '
SELECT * FROM (
  SELECT
      section,
      language,
      value
    FROM '.STATS_TABLE.'
    WHERE
      '.implode("\n      AND ", $where_clauses).'
    ORDER BY 
      date DESC, 
      section ASC, 
      language ASC
  ) as t
  GROUP BY CONCAT(t.section, t.language)
;';
  $result = mysql_query($query);
  $out = array();
  
  while ($row = mysql_fetch_assoc($result))
  {
    $out[ $row['section'] ][ $row['language'] ] = $row['value'];
  }
  
  switch ($Ssum)
  {
    // sum sections progressions by language
    case 'language':
      $out = reverse_2d_array($out);
      foreach ($out as $language => $row)
      {
        $num = $denom = 0;
        $from = !empty($Ssection) ? array_keys($row) : array_keys($conf['all_sections']);
        foreach ($from as $section)
        {
          $num+= @$row[$section] * get_section_rank($section);
          $denom+= get_section_rank($section);
        }
        $out[ $language ] = ($num == 0) ? 0 : $num/$denom;
      }
      break;
      
    // sum languages progressions by section
    case 'section':
      foreach ($out as $section => $row)
      {
        $num = $denom = 0;
        $from = !empty($Slanguage) ? array_keys($row) : array_keys($conf['all_languages']);
        foreach ($from as $language)
        {
          $num+= @$row[$language] * get_language_rank($language);
          $denom+= get_language_rank($language);
        }
        $out[ $section ] = ($num == 0) ? 0 : $num/$denom;
      }
      break;
      
    // sum all progressions
    case 'all' :
      $num = $denom = 0;
      $from = !empty($Ssection) ? array_keys($out) : array_keys($conf['all_sections']);
      foreach ($from as $section)
      {
        $sub_num = $sub_denom = 0;
        $from = !empty($Slanguage) ? array_keys($out[$section]) : array_keys($conf['all_languages']);
        foreach ($from as $language)
        {
          $sub_num+= @$out[$section][$language] * get_language_rank($language);
          $sub_denom+= get_language_rank($language);
        }
        $num+= ($sub_num == 0 ? 0 : $sub_num/$sub_denom) * get_section_rank($section);
        $denom+= get_section_rank($section);
      }
      $out = ($num == 0) ? 0 : $num/$denom;
      break;
  }
  
  return $out;
}

/**
 * get the oldest generation date of a set of the cache
 * @param string section
 * @param string language
 * @return string
 */
function get_cache_date($section=null, $language=null)
{
  global $conf;
  if (!$conf['use_stats']) return '0000-00-00 00:00:00';
  
  $where_clauses = array('1=1');
  if (!empty($language))
  {
    $where_clauses[] = 'language = "'.$language.'"';
  }
  if (!empty($section))
  {
    $where_clauses[] = 'section = "'.$section.'"';
  }
  
  $query = '
SELECT MIN(date)
  FROM '.STATS_TABLE.'
  WHERE
    '.implode("\n    AND ", $where_clauses).'
;';
  $result = mysql_query($query);

  if (!mysql_num_rows($result))
  {
    return '0000-00-00 00:00:00';
  }
  
  list($date) = mysql_fetch_row($result);
  return $date;
}

/** 
 * generate progression bar
 * @param float value
 * @param int width
 * @param bool display percentage inside the bar
 * @return string html
 */
function display_progress_bar($value, $width, $inside=true)
{
  if (!$inside) $width-=48;
  return '
  <span class="progressBar '.($value==1?'full':null).'" style="width:'.$width.'px;">
    <span class="bar" style="background-color:'.get_gauge_color($value).';width:'.floor($value*$width).'px;">'.($inside?'&nbsp;&nbsp;'.number_format($value*100,2).'%&nbsp;&nbsp;':null).'</span>
  </span>
  '.(!$inside?'&nbsp;&nbsp;'.number_format($value*100,2).'%':null);
}

/**
 * return a color according to value (gradient is red-orange-green)
 * @param float value between 0 and 1
 * @return sring hex color
 */
function get_gauge_color($value, $color='light')
{
  $light = array("F88C8C","F9A88C","FBC58C","FDE28C","FFFF8C","E2FF8C","C5FF8C","A8FF8C","8CFF8C");
  $dark = array("FF0000","FF3600","FF6C00","FFA200","FFD900","C0D601","81D402","42D103","04CF04");
  $index = floor($value*(count(${$color})-1));
  return '#'.${$color}[$index];
}
?>