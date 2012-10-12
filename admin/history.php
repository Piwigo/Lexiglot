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


// +-----------------------------------------------------------------------+
// |                         DELETE ROW
// +-----------------------------------------------------------------------+
if ( isset($_GET['delete_row']) and is_numeric($_GET['delete_row']) )
{
  $query = 'DELETE FROM '.ROWS_TABLE.' WHERE id = '.$_GET['delete_row'].';';
  mysql_query($query);
  array_push($page['infos'], 'Translation deleted.');
}


// +-----------------------------------------------------------------------+
// |                         ACTIONS
// +-----------------------------------------------------------------------+
if ( isset($_POST['apply_action']) and $_POST['selectAction'] != '-1' and !empty($_POST['select']) )
{
  switch ($_POST['selectAction'])
  {
    // DELETE ROWS
    case 'delete_rows':
    {
      if (!isset($_POST['confirm_deletion']))
      {
        array_push($page['errors'], 'For security reasons you must confirm the deletion.');
      }
      else
      {
        $query = '
DELETE FROM '.ROWS_TABLE.' 
  WHERE id IN('.implode(',', $_POST['select']).') 
;';
        mysql_query($query);

        array_push($page['infos'], mysql_affected_rows().' translations deleted.');
      }
      break;
    }
    
    // MARK AS DONE
    case 'mark_as_done':
    {
      $query = '
UPDATE '.ROWS_TABLE.' 
  SET status = "done" 
  WHERE id IN('.implode(',', $_POST['select']).') 
;';
      mysql_query($query);

      array_push($page['infos'], mysql_affected_rows().' translations marked as commited.');
      break;
    }
  }
}


// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$search = array(
  'user_id' =>  array('=', -1),
  'language' => array('=', -1),
  'project' =>  array('=', -1),
  'status' =>   array('=', -1),
  'limit' =>    array('=', 50),
  );

$where_clauses = session_search($search, 'history_search', array('limit'));

$displayed_projects = is_admin() ? $conf['all_projects'] : create_projects_array($user['manage_projects']);
if (is_manager())
{
  array_push($where_clauses, 'project IN("'.implode('","', array_keys($displayed_projects)).'")');
}


// +-----------------------------------------------------------------------+
// |                         PAGINATION
// +-----------------------------------------------------------------------+
$query = '
SELECT COUNT(1)
  FROM '.ROWS_TABLE.'
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
;';
list($total) = mysql_fetch_row(mysql_query($query));

$paging = compute_pagination($total, get_search_value('limit'), 'nav');


// +-----------------------------------------------------------------------+
// |                         GET ROWS
// +-----------------------------------------------------------------------+
$query = '
SELECT 
    r.*,
    u.'.$conf['user_fields']['username'].' AS username,
    l.name AS language_name,
    p.name AS project_name
  FROM '.ROWS_TABLE.' AS r
    INNER JOIN '.USERS_TABLE.' AS u
    ON r.user_id = u.'.$conf['user_fields']['id'].'
    INNER JOIN '.LANGUAGES_TABLE.' AS l
    ON r.language = l.id
    INNER JOIN '.PROJECTS_TABLE.' AS p
    ON r.project = p.id
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
  ORDER BY r.last_edit DESC
  LIMIT '.$paging['Entries'].'
  OFFSET '.$paging['Start'].'
;';
$_ROWS = hash_from_query($query, null);

// get users infos
$_USERS = get_users_list(
  array('i.status IN( "translator", "manager", "admin" )'), null
  );


// +-----------------------------------------------------------------------+
// |                        TEMPLATE
// +-----------------------------------------------------------------------+
foreach ($_ROWS as $row)
{
  $row['time'] = strtotime($row['last_edit']);
  $row['date'] = format_date($row['last_edit'], true, false);
  $row['trucated_value'] = cut_string(htmlspecialchars($row['row_value']), 400);
  $row['delete_uri'] = get_url_string(array('delete_row'=>$row['id']));
  
  $template->append('ROWS', $row);
}

$template->assign(array(
  'SEARCH' => search_to_template($search),
  'PAGINATION' => display_pagination($paging, 'nav'),
  'USERS' => $_USERS,
  'displayed_projects' => $displayed_projects,
  'F_ACTION' => get_url_string(array('page'=>'history'), true),
  ));


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('admin/history');


?>