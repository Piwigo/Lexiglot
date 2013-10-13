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

define('LEXIGLOT_PATH', './');
include(LEXIGLOT_PATH . 'include/common.inc.php');


// +-----------------------------------------------------------------------+
// |                         LANGUAGE PROPERTIES
// +-----------------------------------------------------------------------+
if ( !isset($_GET['language']) or !array_key_exists($_GET['language'], $conf['all_languages']) )
{
  array_push($page['errors'], 'Undefined or unknown language. <a href="'.get_home_url().'">Go Back</a>');
  $template->close('messages');
}

$page['language'] = $_GET['language'];

// page title
$template->assign(array(
  'WINDOWS_TITLE' => get_language_name($page['language']),
  'PAGE_TITLE' => 'Browse',
  'LANGUAGE' => $page['language'],
  ));

// get ordered projects with categories names (uses a workaround for projects without categories)
$query = '
SELECT 
    s.*,
    IF(s.category_id = 0, "[999]zzz", c.name) as category_name
  FROM '.PROJECTS_TABLE.' AS s
    LEFT JOIN '.CATEGORIES_TABLE.' AS c
    ON c.id = s.category_id
  ORDER BY
    category_name ASC,
    s.rank DESC,
    s.name ASC
;';
$conf['all_projects'] = hash_from_query($query, 'id');

// search this language into project directories
foreach ($conf['all_projects'] as &$row)
{
  $row['language_exists'] = file_exists($conf['local_dir'].$row['id'].'/'.$page['language']);
}
unset($row);

// +-----------------------------------------------------------------------+
// |                         ADD A NEW PROJECT
// +-----------------------------------------------------------------------+
if (isset($_POST['add_project']))
{
  if ( $_POST['project'] == '-1' or !array_key_exists($_POST['project'], $conf['all_projects']) )
  {
    array_push($page['errors'], 'Undefined or unknown project.');
  }
  else if ( !$conf['user_can_add_language'] or !is_translator($page['language'], $_POST['project']) )
  {
    array_push($page['errors'], 'You have no rights to add this project.');
  }
  else if ( create_directory($conf['local_dir'].$_POST['project'].'/'.$page['language']) )
  {
    redirect(get_url_string(array('language'=>$page['language'],'project'=>$_POST['project']), true, 'edit'));
  }
  else
  {
    array_push($page['errors'], 'Can\'t create the folder. Please contact administrators.');
  }
}


// +-----------------------------------------------------------------------+
// |                         DISPLAY PROJECTS
// +-----------------------------------------------------------------------+
// update statistics
if ( time() - strtotime(get_cache_date(null, $page['language'])) > $conf['stats_cache_life'] )
{
  // make_language_stats($page['language']);
  $template->assign('MAKE_STATS_KEY', get_ephemeral_key(0));
}
  
// get statistics
if ($conf['use_stats'])
{
  $stats = get_cache_stats(null, $page['language'], 'project');
  $language_stats = get_cache_stats(null, $page['language'], 'all');
  $template->assign('PROGRESS_BAR', display_progress_bar($language_stats, 825, true));
}
$use_stats = !empty($stats);
$template->assign('USE_PROJECT_STATS', $use_stats);

// projects not translated, translated and editable, not translated and editable (translated and not editable doesn't appear)
$project_not_translated = $project_translated = $project_available = $conf['all_projects'];
foreach ($conf['all_projects'] as $row)
{
  if ($row['language_exists'])
  {
    unset($project_not_translated[ $row['id'] ]);
    unset($project_available[ $row['id'] ]);
  }
  else
  {
    unset($project_translated[ $row['id'] ]);
  }
  if (!in_array($row['id'], $user['projects'])) // don't use is_translator() because of read access
  {
    unset($project_available[ $row['id'] ]);
    unset($project_translated[ $row['id'] ]);
  }
}

if ($conf['use_talks'])
{
  $template->assign('TALK_URI', get_url_string(array('talk'=>null,'language'=>$page['language']), true, 'misc'));
}


// projects list
$use_categories = count(array_unique_deep($project_translated, 'category_id')) > 1;
$template->assign('USE_PROJECT_CATS', $use_categories);

foreach ($project_translated as $row)
{
  if ( $use_stats and !isset($stats[ $row['id'] ]) ) $stats[ $row['id'] ] = 0;
    
  $tpl_var = array(
    'ID' => $row['id'],
    'NAME' => $row['name'],
    'URL' => get_url_string(array('language'=>$page['language'],'project'=>$row['id']), true, 'edit'),
    );
    
  if ($use_categories)
  {
    $tpl_var['CATEGORY_ID'] = $row['category_id'];
    $tpl_var['CATEGORY_NAME'] = preg_replace('#^\[([0-9]+)\](.*)#', '$2', $row['category_name']);
  }
  
  if ($use_stats)
  {
    $tpl_var['STATS'] = $stats[ $row['id'] ];
    $tpl_var['PROGRESS_BAR'] = display_progress_bar($stats[ $row['id'] ], 150);
  }
  
  $template->append('projects', $tpl_var);
}


// add project button
if ( $conf['user_can_add_language'] and is_translator($page['language'], null) and count($project_available) > 0 )
{
  $template->assign(array(
    'PROJECT_NOT_TRANSLATED' => count($project_not_translated),
    'project_available' => simple_hash_from_array($project_available, 'id', 'name'),
    ));
}


// +-----------------------------------------------------------------------+
// |                         USERS
// +-----------------------------------------------------------------------+
$users = get_users_list(array(
  'l.language = "'.$page['language'].'"',
  'u.id != '.$conf['guest_id'],
  'status!="admin"',
  ), null);

foreach ($users as $row)
{
  $row['url'] = get_url_string(array('user_id'=>$row['id']), true, 'profile');
  $row['color'] = get_status_color($row['status']);

  $template->append('users', $row);
}


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('language');

?>