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

function init_db()
{
  $db = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);

  if ($db->connect_error)
  {
    die('MySQLi connection error (' . $db->connect_errno . ') ' . $db->connect_error);
  }
  
  $db->query('SET names utf8;');
  
  return $db;
}

/**
 * alias of the function mysql_real_escape_string
 */
function mres($v)
{
  global $db;
  
  return $db->real_escape_string($v);
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
  global $db;
  
  $array = array();

  $result = $db->query($query);
  while ($row = $result->fetch_assoc())
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
  global $db;
  $array = array();

  $result = $db->query($query);
  while ($row = $result->fetch_assoc())
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
  global $db;
  $array = array();

  $result = $db->query($query);
  while ($row = $$result->fetch_assoc())
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
  global $db;
  
  $result = $db->query('SELECT * FROM '.CONFIG_TABLE.';');
  
  while ($row = $result->fetch_assoc())
  {
    if ($row['value'] == 'true' or $row['value'] == 'false')
    {
      $row['value'] = get_boolean($row['value']);
    }  
    $conf[ $row['param'] ] = $row['value'];
  }
}

/**
 * updates multiple lines in a table
 *
 * @param string table_name
 * @param array dbfields
 * @param array datas
 * @param bool empty values do not overwrite existing ones
 * @return void
 */
function mass_updates($tablename, $dbfields, $datas, $skip_empty=false)
{
  if (count($datas) == 0)
  {
    return;
  }
  
  global $db;
  
  // depending the number of updates, we use the multi table update or N update queries
  if (count($datas) < 10)
  {
    foreach ($datas as $data)
    {
      $query = '
UPDATE '.$tablename.'
  SET ';
      $is_first = true;
      foreach ($dbfields['update'] as $key)
      {
        $separator = $is_first ? '' : ",\n    ";

        if (isset($data[$key]) and $data[$key] != '')
        {
          $query.= $separator.$key.' = \''.$data[$key].'\'';
        }
        else
        {
          if ($skip_empty) continue; // next field
          $query.= $separator.$key.' = NULL';
        }
        $is_first = false;
      }
      if (!$is_first)
      {// only if one field at least updated
        $query.= '
  WHERE ';
        $is_first = true;
        foreach ($dbfields['primary'] as $key)
        {
          if (!$is_first)
          {
            $query.= ' AND ';
          }
          if ( isset($data[$key]) )
          {
            $query.= $key.' = \''.$data[$key].'\'';
          }
          else
          {
            $query.= $key.' IS NULL';
          }
          $is_first = false;
        }
        $db->query($query);
      }
    } // foreach update
  } // if count<X
  else
  {
    // creation of the temporary table
    $result = $db->query('SHOW FULL COLUMNS FROM '.$tablename.';');
    $columns = array();
    $all_fields = array_merge($dbfields['primary'], $dbfields['update']);
    
    while ($row = $result->fetch_assoc())
    {
      if (in_array($row['Field'], $all_fields))
      {
        $column = $row['Field'];
        $column.= ' '.$row['Type'];

        $nullable = true;
        if (!isset($row['Null']) or $row['Null'] == '' or $row['Null']=='NO')
        {
          $column.= ' NOT NULL';
          $nullable = false;
        }
        if (isset($row['Default']))
        {
          $column.= " default '".$row['Default']."'";
        }
        elseif ($nullable)
        {
          $column.= " default NULL";
        }
        if (isset($row['Collation']) and $row['Collation'] != 'NULL')
        {
          $column.= " collate '".$row['Collation']."'";
        }
        array_push($columns, $column);
      }
    }

    $temporary_tablename = $tablename.'_'.micro_seconds();

    // fill temporary table
    $query = '
CREATE TABLE '.$temporary_tablename.'
(
  '.implode(",\n  ", $columns).',
  UNIQUE KEY the_key ('.implode(',', $dbfields['primary']).')
)';

    $db->query($query);
    mass_inserts($temporary_tablename, $all_fields, $datas);
    
    if ($skip_empty)
      $func_set = create_function('$s', 'return "t1.$s = IFNULL(t2.$s, t1.$s)";');
    else
      $func_set = create_function('$s', 'return "t1.$s = t2.$s";');

    // update of table by joining with temporary table
    $query = '
UPDATE '.$tablename.' AS t1, '.$temporary_tablename.' AS t2
  SET '.
      implode(
        "\n    , ",
        array_map($func_set, $dbfields['update'])
        ).'
  WHERE '.
      implode(
        "\n    AND ",
        array_map(
          create_function('$s', 'return "t1.$s = t2.$s";'),
          $dbfields['primary']
          )
        );
    $db->query($query);
    
    // delete temporary table
    $db->query('DROP TABLE '.$temporary_tablename.';');
  }
}

/**
 * updates one line in a table
 *
 * @param string table_name
 * @param array set_fields
 * @param array where_fields
 * @param bool empty values do not overwrite existing ones
 * @return void
 */
function single_update($tablename, $set_fields, $where_fields, $skip_empty=false)
{
  if (count($set_fields) == 0)
  {
    return;
  }
  
  global $db;

  $query = '
UPDATE '.$tablename.'
  SET ';
  $is_first = true;
  foreach ($set_fields as $key => $value)
  {
    $separator = $is_first ? '' : ",\n    ";

    if (isset($value) and $value !== '')
    {
      $query.= $separator.$key.' = \''.$value.'\'';
    }
    else
    {
      if ($skip_empty) continue; // next field
      $query.= $separator.$key.' = NULL';
    }
    $is_first = false;
  }
  if (!$is_first)
  {// only if one field at least updated
    $query.= '
  WHERE ';
    $is_first = true;
    foreach ($where_fields as $key => $value)
    {
      if (!$is_first)
      {
        $query.= ' AND ';
      }
      if ( isset($value) )
      {
        $query.= $key.' = \''.$value.'\'';
      }
      else
      {
        $query.= $key.' IS NULL';
      }
      $is_first = false;
    }
    $db->query($query);
  }
}


/**
 * inserts multiple lines in a table
 *
 * @param string table_name
 * @param array dbfields
 * @param array inserts
 * @return void
 */
function mass_inserts($table_name, $dbfields, $datas, $options=array())
{
  global $db;
  
  $ignore = '';
  if (isset($options['ignore']) and $options['ignore'])
  {
    $ignore = 'IGNORE';
  }
  
  if (count($datas) != 0)
  {
    $first = true;

    list(, $packet_size) = $db->query('SHOW VARIABLES LIKE \'max_allowed_packet\';')->fetch_row();
    $packet_size = $packet_size - 2000; // The last list of values MUST not exceed 2000 character*/
    $query = '';

    foreach ($datas as $insert)
    {
      if (strlen($query) >= $packet_size)
      {
        $db->query($query);
        $first = true;
      }

      if ($first)
      {
        $query = '
INSERT '.$ignore.' INTO '.$table_name.'
  ('.implode(',', $dbfields).')
  VALUES';
        $first = false;
      }
      else
      {
        $query .= '
  , ';
      }

      $query .= '(';
      foreach ($dbfields as $field_id => $dbfield)
      {
        if ($field_id > 0)
        {
          $query .= ',';
        }

        if (!isset($insert[$dbfield]) or $insert[$dbfield] === '')
        {
          $query .= 'NULL';
        }
        else
        {
          $query .= "'".$insert[$dbfield]."'";
        }
      }
      $query .= ')';
    }
    $db->query($query);
  }
}

/**
 * inserts one line in a table
 *
 * @param string table_name
 * @param array dbfields
 * @param array insert
 * @return void
 */
function single_insert($table_name, $data, $options=array())
{
  global $db;
  
  $ignore = '';
  if (isset($options['ignore']) and $options['ignore'])
  {
    $ignore = 'IGNORE';
  }
  
  if (count($data) != 0)
  {
    $query = '
INSERT '.$ignore.' INTO '.$table_name.'
  ('.implode(',', array_keys($data)).')
  VALUES';

    $query .= '(';
    $is_first = true;
    foreach ($data as $key => $value)
    {
      if (!$is_first)
      {
        $query .= ',';
      }
      else
      {
        $is_first = false;
      }
      
      if ($value === '')
      {
        $query .= 'NULL';
      }
      else
      {
        $query .= "'".$value."'";
      }
    }
    $query .= ')';
    
    $db->query($query);
  }
}

?>