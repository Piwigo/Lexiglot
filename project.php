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


$hooks->do_action('before_project');


// +-----------------------------------------------------------------------+
// |                         PROJECT PROPERTIES
// +-----------------------------------------------------------------------+
if ( !isset($_GET['project']) or !array_key_exists($_GET['project'], $conf['all_projects']) )
{
  array_push($page['errors'], 'Undefined or unknown project. <a href="'.get_home_url().'">Go Back</a>');
  $template->close('messages');
}

$page['project'] = $_GET['project'];

// page title
$template->assign(array(
  'WINDOWS_TITLE' => get_project_name($page['project']),
  'PAGE_TITLE' => 'Browse',
  'PROJECT' => $page['project'],
  'PROJECT_WEBSITE' => get_project_website($page['project']),
  ));

// get ordered languages with categories names (uses a workaround for languages without categories)
$query = '
SELECT 
    l.*,
    IF(l.category_id = 0, "[999]zzz", c.name) as category_name
  FROM '.LANGUAGES_TABLE.' AS l
    LEFT JOIN '.CATEGORIES_TABLE.' AS c
    ON c.id = l.category_id
  ORDER BY
    category_name ASC,
    l.rank DESC,
    l.id ASC
;';
$conf['all_languages'] = hash_from_query($query, 'id');

// search languages into this project
foreach ($conf['all_languages'] as &$row)
{
  $row['project_exists'] = file_exists($conf['local_dir'].$page['project'].'/'.$row['id']);
}
unset($row);

// +-----------------------------------------------------------------------+
// |                         ADD A NEW LANG
// +-----------------------------------------------------------------------+
if (isset($_POST['add_language']))
{
  if ( $_POST['language'] == '-1' or !array_key_exists($_POST['language'], $conf['all_languages']) )
  {
    array_push($page['errors'], 'Undefined or unknown language.');
  }
  else if ( !$conf['user_can_add_language'] or !is_translator($_POST['language'], $page['project']) )
  {
    array_push($page['errors'], 'You have no rights to add this language.');
  }
  else if ( create_directory($conf['local_dir'].$page['project'].'/'.$_POST['language']) )
  {
    redirect(get_url_string(array('language'=>$_POST['language'],'project'=>$page['project']), true, 'edit'));
  }
  else
  {
    array_push($page['errors'], 'Can\'t create the folder. Please contact administrators.');
  }
}

// +-----------------------------------------------------------------------+
// |                         DISPLAY LANGUAGES
// +-----------------------------------------------------------------------+
// update statistics
if ( time() - strtotime(get_cache_date($page['project'], null)) > $conf['stats_cache_life'] )
{
  // make_project_stats($page['project']);
  $template->assign('MAKE_STATS_KEY', get_ephemeral_key(0));
}
  
// get statistics (must be computed with a clean rank)
if ($conf['use_stats'])
{
  $stats = get_cache_stats($page['project'], null, 'language');
  $project_stats = get_cache_stats($page['project'], null, 'all');
  $template->assign('PROGRESS_BAR', display_progress_bar($project_stats, 835, true));
}
$use_stats = !empty($stats);
$template->assign('USE_LANGUAGE_STATS', $use_stats);

// languages not translated, translated and editable, not translated and editable (translated and not editable doesn't appear)
$language_not_translated = $language_translated = $language_available = $conf['all_languages'];
foreach ($conf['all_languages'] as $row)
{
  if ($row['project_exists'])
  {
    unset($language_not_translated[ $row['id'] ]);
    unset($language_available[ $row['id'] ]);
  }
  else
  {
    unset($language_translated[ $row['id'] ]);
  }
  if (!in_array($row['id'], $user['languages'])) // don't use is_translator() because of read access
  {
    unset($language_available[ $row['id'] ]);
    unset($language_translated[ $row['id'] ]);
  }
}

if ($conf['use_talks'])
{
  $template->assign('TALK_URI', get_url_string(array('talk'=>null,'project'=>$page['project']), true, 'misc'));
}


// languages list
$use_categories = count(array_unique_deep($language_translated, 'category_id')) > 1;
$template->assign('USE_LANGUAGE_CATS', $use_categories);

foreach ($language_translated as $row)
{
  if ( $use_stats and !isset($stats[ $row['id'] ]) ) $stats[ $row['id'] ] = 0;
    
  $tpl_var = array(
    'ID' => $row['id'],
    'NAME' => $row['name'],
    'FLAG' => get_language_flag($row['id']),
    'URL' => get_url_string(array('language'=>$row['id'],'project'=>$page['project']), true, 'edit'),
    'IS_NEW' => !$row['project_exists'] || ($use_stats && empty($stats[ $row['id'] ])),
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
  
  $template->append('languages', $tpl_var);
}

// add language button
if ( $conf['user_can_add_language'] and is_translator(null, $page['project']) and count($language_available) > 0 )
{
  $template->assign(array(
    'LANGUAGE_NOT_TRANSLATED' => count($language_not_translated),
    'REQUEST_LANGUAGE_URL' => get_url_string(array('request_language'=>null), true, 'misc'),
    'language_available' => simple_hash_from_array($language_available, 'id', 'name'),
    ));
}


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$hooks->do_action('after_project');
$template->close('project');

?>