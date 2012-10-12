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

$highlight_language = isset($_GET['from_id']) ? $_GET['from_id'] : null;
$deploy_language = null;


// +-----------------------------------------------------------------------+
// |                         DELETE LANG
// +-----------------------------------------------------------------------+
if (isset($_GET['delete_language']))
{
  if (!array_key_exists($_GET['delete_language'], $conf['all_languages']))
  {
    array_push($page['errors'], 'Unknown language.');
  }
  else
  {
    // delete lang from user infos
    $query = '
DELETE FROM '.USER_LANGUAGES_TABLE.'
  WHERE language = "'.$_GET['delete_language'].'"
;';
    mysql_query($query);
    
    // delete flag
    @unlink($conf['flags_dir'].$conf['all_languages'][ $_GET['delete_language'] ]['flag']);
    
    // delete from stats table
    $query = '
DELETE FROM '.STATS_TABLE.'
  WHERE language = "'.$_GET['delete_language'].'"
;';
    mysql_query($query);
    
    // delete from languages table
    $query = '
DELETE FROM '.LANGUAGES_TABLE.' 
  WHERE id = "'.$_GET['delete_language'].'"
;';
    mysql_query($query);
    
    array_push($page['infos'], 'Language deleted.');
  }
}


// +-----------------------------------------------------------------------+
// |                         ACTIONS
// +-----------------------------------------------------------------------+
if ( isset($_POST['apply_action']) and $_POST['selectAction'] != '-1' and !empty($_POST['select']) )
{
  include(LEXIGLOT_PATH . 'admin/include/languages.actions.php');
}


// +-----------------------------------------------------------------------+
// |                         DELETE FLAG
// +-----------------------------------------------------------------------+
if (isset($_GET['delete_flag']))
{
  @unlink($conf['flags_dir'].$conf['all_languages'][ $_GET['delete_flag'] ]['flag']);
  
  $query = '
UPDATE '.LANGUAGES_TABLE.'
  SET flag = NULL
  WHERE id = "'.$_GET['delete_flag'].'"
;';
  mysql_query($query);
  
  array_push($page['infos'], 'Flag deleted.');
  $highlight_language = $_GET['delete_flag'];
}


// +-----------------------------------------------------------------------+
// |                         MAKE STATS
// +-----------------------------------------------------------------------+
if (isset($_GET['make_stats']))
{
  if (array_key_exists($_GET['make_stats'], $conf['all_languages']))
  {
    make_language_stats($_GET['make_stats']);
    array_push($page['infos'], 'Stats refreshed for language &laquo; '.get_language_name($_GET['make_stats']).' &raquo;');
    $highlight_language = $_GET['make_stats'];
  }
}


// +-----------------------------------------------------------------------+
// |                         SAVE LANGS
// +-----------------------------------------------------------------------+
if ( isset($_POST['save_language']) and isset($_POST['active_language']) )
{
  $row = $_POST['languages'][ $_POST['active_language'] ];
  $row['id'] = $_POST['active_language'];
  
  // check name
  if (empty($row['name']))
  {
    array_push($page['errors'], 'Name is empty.');
  }
  // check rank
  if (!is_numeric($row['rank']) or $row['rank'] < 1)
  {
    array_push($page['errors'], 'Rank must be an non null integer.');
  }
  // check category
  if ( !count($page['errors']) and !empty($row['category_id']) and !is_numeric($row['category_id']) )
  {
    $row['category_id'] = add_category($row['category_id'], 'language');
  }
  if (empty($row['category_id']))
  {
    $row['category_id'] = 0;
  }
  // check reference
  if (empty($row['ref_id']))
  {
    $row['ref_id'] = null;
  }
  
  // check flag
  if ( !count($page['errors']) and !empty($_FILES['flags-'.$row['id']]['tmp_name']) )
  {
    $row['flag'] = upload_flag($_FILES['flags-'.$row['id']], $row['id']);
    if (is_array($row['flag']))
    {
      $page['errors'] = array_merge($page['errors'], $row['flag']);
    }
    else
    {
      @unlink($conf['flags_dir'].$conf['all_languages'][ $row['id'] ]['flag']);
    }
  }
  
  // save lang
  if (count($page['errors']) == 0)
  {
    $query = '
UPDATE '.LANGUAGES_TABLE.'
  SET
    name = "'.$row['name'].'",
    rank = '.$row['rank'].',
    category_id = '.$row['category_id'].',
    ref_id = "'.$row['ref_id'].'"
    '.(isset($row['flag']) ? ',flag = "'.$row['flag'].'"' : null).'
  WHERE id = "'.$row['id'].'"
;';
    mysql_query($query);
  }

  $highlight_language = $row['id'];
  
  // update languages array
  $conf['all_languages'][ $row['id'] ] = array_merge($conf['all_languages'][ $row['id'] ], $row);
    
  if (count($page['errors']) == 0)
  {
    array_push($page['infos'], 'Modifications saved.');
  }
  else
  {
    array_push($page['errors'], 'Modifications not saved.');
    $deploy_language = $row['id'];
  }
}


// +-----------------------------------------------------------------------+
// |                         ADD LANG
// +-----------------------------------------------------------------------+
if (isset($_POST['add_language']))
{
  // check id
  if (empty($_POST['id']))
  {
    array_push($page['errors'], 'Id. is empty.');
  }
  else
  {
    $query ='
SELECT id
  FROM '.LANGUAGES_TABLE.'
  WHERE id = "'.$_POST['id'].'"
';
    $result = mysql_query($query);
    if (mysql_num_rows($result))
    {
      array_push($page['errors'], 'A language with this Id already exists.');
    }
  }
  // check name
  if (empty($_POST['name']))
  {
    array_push($page['errors'], 'Name is empty.');
  }
  // check rank
  if (!is_numeric($_POST['rank']) or $_POST['rank'] < 1)
  {
    array_push($page['errors'], 'Rank must be an non null integer.');
  }
  // check category
  if ( !count($page['errors']) and !empty($_POST['category_id']) and !is_numeric($_POST['category_id']) )
  {
    $row['category_id'] = add_category($_POST['category_id'], 'language');
  }
  if (empty($_POST['category_id']))
  {
    $_POST['category_id'] = 0;
  }
  // check reference
  if (empty($_POST['ref_id']))
  {
    $_POST['ref_id'] = null;
  }
  
  // check flag
  if ( !count($page['errors']) and !empty($_FILES['flag']['tmp_name']) )
  {
    $_POST['flag'] = upload_flag($_FILES['flag'], $_POST['id']);
    if (is_array($_POST['flag']))
    {
      $page['errors'] = array_merge($page['errors'], $_POST['flag']);
    }
  }
  else
  {
    $_POST['flag'] = null;
  }
  
  // save lang
  if (count($page['errors']) == 0)
  {
    $query = '
INSERT INTO '.LANGUAGES_TABLE.'(
    id, 
    name,
    flag,
    rank,
    category_id,
    ref_id
  )
  VALUES(
    "'.$_POST['id'].'",
    "'.$_POST['name'].'",
    "'.$_POST['flag'].'",
    '.$_POST['rank'].',
    '.$_POST['category_id'].',
    "'.$_POST['ref_id'].'"
  )
;';
    mysql_query($query);
    
    // add project on user infos
    $query = '
SELECT user_id 
  FROM '.USER_INFOS_TABLE.' 
  WHERE status IN("admin"'.($conf['project_default_user'] == 'all' ? ', "translator", "guest", "manager"' : null).')
;';
    $user_ids = array_from_query($query, 'user_id');
    
    $inserts = array();
    foreach ($user_ids as $uid)
    {
      array_push($inserts, array('user_id'=>$uid, 'language'=>$_POST['id'], 'type'=>'translate'));
    }
    
    mass_inserts(
      USER_LANGUAGES_TABLE,
      array('user_id', 'language', 'type'),
      $inserts,
      array('ignore'=>true)
      );
    
    
    // update languages array
    $conf['all_languages'][ $_POST['id'] ] = array(
                                              'id' => $_POST['id'],
                                              'name' => $_POST['name'],
                                              'flag' => $_POST['flag'],
                                              'rank' => $_POST['rank'],
                                              'category_id' => $_POST['category_id'],
                                              'ref_id' => $_POST['ref_id'],
                                              );
    ksort($conf['all_languages']);
      
    // generate stats
    make_language_stats($_POST['id']);
    
    array_push($page['infos'], 'Language added');
    $highlight_language = $_POST['id'];
    $_POST['erase_search'] = true;
  }
}


// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$search = array(
  'name' =>     array('%', ''),
  'rank' =>     array('%', ''),
  'flag' =>     array('=', -1),
  'category' => array('=', -1),
  'limit' =>    array('=', 20),
  );

// url input
if (isset($_GET['lang_id']))
{
  $_POST['erase_search'] = true;
  $search['name'] = array('%', get_language_name($_GET['language_id']), '');
  unset($_GET['language_id']);
}

$where_clauses = session_search($search, 'language_search', array('limit','flag'));

// special for 'flag'
if (get_search_value('flag') != -1)
{
  if (get_search_value('flag') == 'with') array_push($where_clauses, '(flag != "" AND flag IS NOT NULL)');
  if (get_search_value('flag') == 'without') array_push($where_clauses, '(flag = "" OR flag IS NULL)');
}

set_session_var('language_search', serialize($search));


// +-----------------------------------------------------------------------+
// |                         PAGINATION
// +-----------------------------------------------------------------------+
$query = '
SELECT COUNT(1)
  FROM '.LANGUAGES_TABLE.'
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
;';
list($total) = mysql_fetch_row(mysql_query($query));

$highlight_pos = null;
if (!empty($highlight_language))
{
  $query = '
SELECT x.pos
  FROM (
    SELECT 
        id,
        @rownum := @rownum+1 AS pos
      FROM '.LANGUAGES_TABLE.'
        JOIN (SELECT @rownum := 0) AS r
      WHERE 
        '.implode("\n    AND ", $where_clauses).'
      ORDER BY rank DESC, id ASC
  ) AS x
  WHERE x.id = "'.$highlight_language.'"
;';
  list($highlight_pos) = mysql_fetch_row(mysql_query($query));
}

$paging = compute_pagination($total, get_search_value('limit'), 'nav', $highlight_pos);


// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$query = '
SELECT 
    l.*,
    COUNT(DISTINCT(u.user_id)) as total_users
  FROM '.LANGUAGES_TABLE.' as l
    LEFT JOIN '.USER_LANGUAGES_TABLE.' as u
    ON u.language = l.id
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
  GROUP BY l.id
  ORDER BY 
    l.rank DESC, 
    l.id ASC
  LIMIT '.$paging['Entries'].'
  OFFSET '.$paging['Start'].'
;';
$_LANGS = hash_from_query($query, 'id');

$query = '
SELECT id, name
  FROM '.CATEGORIES_TABLE.'
  WHERE type = "language"
;';
$categories = hash_from_query($query, 'id');
$categories_json = implode(',', array_map(create_function('$row', 'return \'{id: "\'.$row["id"].\'", name: "\'.$row["name"].\'"}\';'), $categories));


// +-----------------------------------------------------------------------+
// |                        TEMPLATE
// +-----------------------------------------------------------------------+
foreach ($_LANGS as $row)
{
  $row['highlight'] = $highlight_language==$row['id'];
  $row['category_name'] = @$categories[ @$row['category_id'] ]['name'];
  $row['users_uri'] = get_url_string(array('page'=>'users','language_id'=>$row['id']), true);
  $row['make_stats_uri'] = get_url_string(array('make_stats'=>$row['id']));
  
  if (!is_default_language($row['id']))
  {
    $row['delete_uri'] = get_url_string(array('delete_language'=>$row['id']));
  }
  
  $template->append('LANGS', $row);
}

$template->assign(array(
  'SEARCH' => search_to_template($search),
  'PAGINATION' => display_pagination($paging, 'nav'),
  'CATEGORIES' => $categories,
  'CATEGORIES_JSON' => $categories_json,
  'NAV_PAGE' => !empty($_GET['nav']) ? '&amp;nav='.$_GET['nav'] : null,
  'DEPLOY_LANGUAGE' => $deploy_language,
  'F_ACTION' => get_url_string(array('page'=>'languages'), true),
  ));


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('admin/languages');

?>