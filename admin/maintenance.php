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

// +-----------------------------------------------------------------------+
// |                         ACTIONS
// +-----------------------------------------------------------------------+
if (!empty($_GET['action']))
{
  switch ($_GET['action'])
  {
    case 'optimize_db' :
    {
      $result = mysql_query("SHOW TABLE STATUS FROM ". DB_NAME ."");
      while ($table = mysql_fetch_assoc($result))
      {
        if (strstr($table['Name'], DB_PREFIX) != false)
        {
          mysql_query("OPTIMIZE TABLE ".$table['Name']);
        }
      }
      array_push($page['infos'], 'Database cleaned');
      break;
    }
    
    case 'make_stats' :
    {
      make_full_stats();
      array_push($page['infos'], 'Statistics updated');
      break;
    }
    
    case 'delete_unused_categories' :
    {
      $query = '
DELETE FROM '.CATEGORIES_TABLE.'
  WHERE 
    id NOT IN (
      SELECT category_id as id
        FROM '.SECTIONS_TABLE.'
        GROUP BY category_id
      )
    AND id NOT IN (
      SELECT category_id as id
        FROM '.LANGUAGES_TABLE.'
        GROUP BY category_id
      )
;';
      mysql_query($query);
      array_push($page['infos'], mysql_affected_rows().' unused categories deleted');
      break;
    }
    
    case 'delete_done_rows' :
    {
      $query = '
DELETE FROM '.ROWS_TABLE.'
  WHERE status = "done"
;';
      mysql_query($query);
      array_push($page['infos'], mysql_affected_rows().' commited strings deleted');
      break;
    }
  }
}


// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
// database time
list($db_current_date) = mysql_fetch_row(mysql_query('SELECT NOW();'));

// database space and tables size
$db_tables = array();
$db_size = $db_free = 0;
$result = mysql_query("SHOW TABLE STATUS FROM ". DB_NAME ."");
while ($table = mysql_fetch_assoc($result))
{
  if (strstr($table['Name'], DB_PREFIX) != false)
  {
    $db_size += $table['Data_length'] + $table['Index_length'] + $table['Data_free'];
    $db_free += $table['Data_free'];
    $db_tables[ str_replace(DB_PREFIX, null, $table['Name']) ] = $table['Rows'];
  }
}

// unused categories
$query = '
SELECT COUNT(*) as total
  FROM '.CATEGORIES_TABLE.'
  WHERE 
    id NOT IN (
      SELECT category_id as id
        FROM '.SECTIONS_TABLE.'
        GROUP BY category_id
      )
    AND id NOT IN (
      SELECT category_id as id
        FROM '.LANGUAGES_TABLE.'
        GROUP BY category_id
      )
;';
list($nb_unused_categories) = mysql_fetch_row(mysql_query($query));


// +-----------------------------------------------------------------------+
// |                         TEMPLATE
// +-----------------------------------------------------------------------+
echo '
<div id="maintenance">
  <ul style="float:left;">
    <h5>Environement</h5>
    <li><b>Lexiglot version :</b> '.$conf['version'].'</li>
    <li><b>PHP :</b> '.phpversion().' ['.date("Y-m-d H:i:s").']</li>
    <li><b>MySQL :</b> '.mysql_get_server_info().' ['.$db_current_date.']</li>
  </ul>

  <ul style="float:right;">
    <h5>Database</h5>
    <li><b>Used space :</b> '.round($db_size/1024,2).' Kio
      '.(round($db_free/1024,2) != 0 ? '(waste : '.round($db_free/1024,2).' Kio <a href="'.get_url_string(array('action'=>'optimize_db')).'">Clean</a>)' : null).'</li>
    <li><b>Users :</b> '.$db_tables['user_infos'].'</li>
    <li><b>Projects :</b> '.$db_tables['sections'].'</li>
    <li><b>Languages :</b> '.$db_tables['languages'].'</li>
    '.(!$conf['delete_done_rows'] ? '<li><b>Translations :</b> '.$db_tables['rows'].'</li>' : null).'
    <li><b>Categories :</b> '.$db_tables['categories'].'
      '.($nb_unused_categories !=0 ? '('.$nb_unused_categories.' unused <a href="'.get_url_string(array('action'=>'delete_unused_categories')).'">Delete</a>)' : null).'</li>
  </ul>
  
  <ul style="float:left;">
    <h5>Maintenance</h5>
    '.($conf['use_stats'] ? '<li><a href="'.get_url_string(array('action'=>'make_stats')).'">Update all statistics</a></li>' : null).'
    '.(!$conf['delete_done_rows'] ? '<li><a href="'.get_url_string(array('action'=>'delete_done_rows')).'">Delete all commited strings</a></li>' : null).'
  </ul>
  
  <div style="clear:both;"></div>
</div>';

?>