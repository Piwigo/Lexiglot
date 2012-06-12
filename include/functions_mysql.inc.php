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
 * alias of the function mysql_real_escape_string
 */
function mres($v)
{
  return mysql_real_escape_string($v);
}

/**
 * convert a string into a boolean
 * @param string
 * @return bool
 */
function get_boolean($string)
{
  $boolean = true;
  if ('false' == strtolower($string))
  {
    $boolean = false;
  }
  return $boolean;
}

/**
 * convert a boolean into a string
 * @param bool
 * @return string
 */
function set_boolean($bool)
{
  $string = 'false';
  if ($bool)
  {
    $string = 'true';
  }
  return $string;
}

/**
 * creates an associative array based on a query
 * @param text query
 * @param string fieldname
 * @return array
 */
function hash_from_query($query, $fieldname=null)
{
  $array = array();

  $result = mysql_query($query);
  while ($row = mysql_fetch_assoc($result))
  {
    if ($fieldname == null) $array[] = $row;
    else                    $array[ $row[$fieldname] ] = $row;
  }

  return $array;
}

/**
 * creates an simple associative array based on a query
 * @param text query
 * @param string fieldname
 * @param string fieldname
 * @return array
 */
function simple_hash_from_query($query, $key, $value)
{
  $array = array();

  $result = mysql_query($query);
  while ($row = mysql_fetch_assoc($result))
  {
    $array[ $row[$key] ] = $row[$value];
  }

  return $array;
}

/**
 * creates an one-depth array based on a query
 * @param text $query
 * @param string $fieldname
 * @return array
 */
function array_from_query($query, $fieldname)
{
  $array = array();

  $result = mysql_query($query);
  while ($row = mysql_fetch_assoc($result))
  {
    array_push($array, $row[$fieldname]);
  }

  return $array;
}

/**
 * load configuration from database
 * @param array configuration
 * @return void
 */
function load_conf_db(&$conf)
{
  $query = 'SELECT * FROM '.CONFIG_TABLE.';';
  $result = mysql_query($query);
  
  while ($row = mysql_fetch_assoc($result))
  {
    if ($row['value'] == 'true' or $row['value'] == 'false')
    {
      $row['value'] = get_boolean($row['value']);
    }  
    $conf[ $row['param'] ] = $row['value'];
  }
}

?>