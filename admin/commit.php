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


$template->assign('F_ACTION', get_url_string(array('page'=>'commit'), true));


// +-----------------------------------------------------------------------+
// |                        GET DATAS
// +-----------------------------------------------------------------------+
$displayed_projects = is_admin() ? $conf['all_projects'] : create_projects_array($user['manage_projects']);
  
// get users name
$_USERS = get_users_list(
  array('i.status NOT IN("guest", "visitor")'), null
  );

if (isset($_POST['init_commit']))
{
  // build query
  $commit_title = array();;
  $where_clauses = array('1=1');
  
  if ($_POST['mode'] == 'filter')
  {
    if ( !empty($_POST['filter_project']) and $_POST['project_id'] != '-1' )
    {
      array_push($commit_title, 'project : '.get_project_name($_POST['project_id']));
      array_push($where_clauses, 'project = "'.$_POST['project_id'].'"');
    }
    if ( !empty($_POST['filter_language']) and $_POST['language_id'] != '-1' )
    {
      array_push($commit_title, 'language : '.get_language_name($_POST['language_id']));
      array_push($where_clauses, 'language = "'.$_POST['language_id'].'"');
    }
    if ( !empty($_POST['filter_user']) and $_POST['user_id'] != '-1' )
    {
      array_push($commit_title, 'user : '.get_username($_POST['user_id']));
      array_push($where_clauses, 'user_id = "'.$_POST['user_id'].'"');
    }
  }
  
  $commit_title = implode(' | ', $commit_title);
  $template->assign('COMMIT_TITLE', $commit_title);
  
  if (is_manager())
  {
    array_push($where_clauses, 'project IN("'.implode('","', array_keys($displayed_projects)).'")');
  }
  
  if (!empty($_POST['exclude']))
  {
    array_push($where_clauses, 'CONCAT(project, language) NOT IN ("'.implode('", "', $_POST['exclude']).'")');
  }
  
  // must use imbricated query to order before group
  $query = '
SELECT * FROM (
  SELECT * 
    FROM '.ROWS_TABLE.'
    WHERE
      '.implode("\n      AND ", $where_clauses).'
      AND status != "done"
    ORDER BY
      language ASC,
      project ASC,
      file_name ASC,
      row_name ASC,
      last_edit DESC,
      user_id ASC
  ) as t
  GROUP BY CONCAT(t.row_name,t.language,t.project,t.user_id)
  ORDER BY last_edit DESC
;';
  $result = $db->query($query);
  
  $_ROWS = array();
  while ($row = $result->fetch_assoc())
  {
    // complicated array usefull to separate each commit
    $_ROWS[ $row['project'].'||'.$row['language'] ][ $row['file_name'] ][ $row['row_name'] ][] = $row;
  }
  
  if (!count($_ROWS))
  {
    array_push($page['warnings'], (!empty($commit_title) ? $commit_title.' > ' : null).'No changes to commit. <a href="'.get_url_string(array('page'=>'commit'), true).'">Go back</a>');
    $template->close('messages');
  }
  
  // +-----------------------------------------------------------------------+
  // |                        MAIN PROCESS
  // +-----------------------------------------------------------------------+
  if (isset($_POST['check_commit']))
  {
    include(LEXIGLOT_PATH . 'admin/include/commit.preview.php');
  }

  else
  {
    include(LEXIGLOT_PATH . 'admin/include/commit.full.php');
  }
}


// +-----------------------------------------------------------------------+
// |                        CONFIGURATION
// +-----------------------------------------------------------------------+
else
{
  $template->assign(array(
    'displayed_projects' => $displayed_projects,
    'USERS' => $_USERS,
    ));
  
  $template->close('admin/commit_config');
}


function print_username(&$item, $key)
{
  global $_USERS;
  $item = $_USERS[$item]['username'];
}

?>