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

define('PATH', './');
include(PATH.'include/common.inc.php');

// +-----------------------------------------------------------------------+
// |                         SECTION PROPERTIES
// +-----------------------------------------------------------------------+
if ( !isset($_GET['section']) or !array_key_exists($_GET['section'], $conf['all_sections']) )
{
  array_push($page['errors'], 'Undefined or unknown section. <a href="'.get_home_url().'">Go Back</a>');
  print_page();
}

$page['section'] = $_GET['section'];

// page title
$page['window_title'] = get_section_name($page['section']);
$page['title'] = 'Browse';

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
    rank DESC,
    l.id ASC
;';
$conf['all_languages'] = hash_from_query($query, 'id');

// search languages into this section
foreach ($conf['all_languages'] as &$row)
{
  $row['section_exists'] = file_exists($conf['local_dir'].$page['section'].'/'.$row['id']);
}
unset($row);

// +-----------------------------------------------------------------------+
// |                         ADD A NEW LANG
// +-----------------------------------------------------------------------+
if (isset($_POST['add_lang']))
{
  if ( $_POST['language'] == '-1' or !array_key_exists($_POST['language'], $conf['all_languages']) )
  {
    array_push($page['errors'], 'Undefined or unknown language.');
  }
  else if ( !$conf['user_can_add_language'] or !is_translator($_POST['language'], $page['section']) )
  {
    array_push($page['errors'], 'You have no rights to add this language.');
  }
  else if ( create_directory($conf['local_dir'].$page['section'].'/'.$_POST['language']) )
  {
    redirect(get_url_string(array('language'=>$_POST['language'],'section'=>$page['section']), true, 'edit'));
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
if ( time() - strtotime(get_cache_date($page['section'], null)) > $conf['stats_cache_life'] )
{
  make_section_stats($page['section']);
}
  
// get statistics (must be computed with a clean rank)
if ($conf['use_stats'])
{
  $stats = get_cache_stats($page['section'], null, 'language');
  $section_stats = get_cache_stats($page['section'], null, 'all');
}
$use_stats = !empty($stats);

// languages not translated, translated and editable, not translated and editable
$language_not_translated = $language_translated = $language_available = $conf['all_languages'];
foreach ($conf['all_languages'] as $row)
{
  if ($row['section_exists'])
  {
    unset($language_not_translated[ $row['id'] ]);
    unset($language_available[ $row['id'] ]);
  }
  else
  {
    unset($language_translated[ $row['id'] ]);
  }
  if (!in_array($row['id'], $user['languages']))
  {
    unset($language_available[ $row['id'] ], $language_translated[ $row['id'] ]);
  }
}

// path
echo '
<p class="caption"><a href="'.get_url_string().'">'.get_section_name($page['section']).'</a></p>
<ul id="languages" class="list-cloud '.($use_stats ? 'w-stats' : null).'">';

// languages list
$category_id = null;
$use_categories = count(array_unique_deep($language_translated, 'category_id')) > 1;
foreach ($language_translated as $row)
{
  if ($use_categories)
  {
    if ( !empty($row['category_id']) and $category_id != $row['category_id'] )
    {
      $category_id = $row['category_id'];
      echo '<h3>'.preg_replace('#^\[([0-9]+)\](.*)#', '$2', $row['category_name']).' :</h3>';
    }
    else if ( empty($row['category_id']) and $category_id != 0 )
    {
      $category_id = 0;
      echo '<h3>Other :</h3>';
    }
  }
  
  if ( $use_stats and !isset($stats[ $row['id'] ]) ) $stats[ $row['id'] ] = 0;
  
  echo '
  <li '.( !$row['section_exists'] || ($use_stats && empty($stats[ $row['id'] ])) ? 'class="new"' : null).'>
    <a href="'.get_url_string(array('language'=>$row['id'],'section'=>$page['section']), true, 'edit').'">
      '.$row['name'].' '.get_language_flag($row['id']).'
      '.(is_default_language($row['id']) ? '<i>(source)</i>': null).'
      '.($use_stats ? display_progress_bar($stats[ $row['id'] ], 150) : null).'
    </a>
  </li>';
}

// add language button
if ( $conf['user_can_add_language'] and is_translator(null, $page['section']) and count($language_available) > 0 )
{
  echo '
  <li class="add">
    <b>'.count($language_not_translated).' languages not translated</b> <a class="add_lang" href="#"><img src="template/images/bullet_add.png" alt="+"> Add a new language</a>
  </li>
  
  <div id="dialog-form" title="Add a new language" style="display:none;">
    <div class="ui-state-highlight" style="padding: 0.7em;margin-bottom:10px;">
      <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
      You can only add languages you have permission to translate.<br>
      Can\'t see the language you wish to translate ? Please <a href="'.get_url_string(array('request_language'=>null), true, 'misc').'">send us a request</a>.
    </div>
    <form action="" method="post" style="text-align:center;">
      Select a language : 
      <select name="language">
        <option value="-1">----------</option>';
      foreach ($language_available as $row)
      {
        echo '
        <option value="'.$row['id'].'">'.$row['name'].'</option>';
      }
      echo '
      </select>
      <input type="hidden" name="add_lang" value="1">
    </form>
  </div>';
}
echo '
</ul>';

// project progression
if ($use_stats)
{
  echo '
  <div id="displayStats" class="ui-state-highlight" style="padding: 0em;margin-top:10px;">
    <p style="margin:10px;">
      <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
      <b>Project progression :</b> '.display_progress_bar($section_stats, 835, true).'
    </p>
  </div>';
}


$page['script'].= '
$("#dialog-form").dialog({
  autoOpen: false, modal: true, resizable: false,
  height: 200, width: 440,
  show: "clip", hide: "clip",
  buttons: {
    "Add": function() { $("#dialog-form form").submit(); },
    "Cancel": function() { $(this).dialog("close"); }
  }
});

$(".add_lang").click(function() {
  $("#dialog-form").dialog("open");
  return false;
});';

print_page();
?>