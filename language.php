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
// |                         LANGUAGE PROPERTIES
// +-----------------------------------------------------------------------+
if ( !isset($_GET['language']) or !array_key_exists($_GET['language'], $conf['all_languages']) )
{
  array_push($page['errors'], 'Undefined or unknown language. <a href="'.get_home_url().'">Go Back</a>');
  print_page();
}

$page['language'] = $_GET['language'];

// page title
$page['window_title'] = get_language_name($page['language']);
$page['title'] = 'Browse';

// get ordered sections with categories names (uses a workaround for sections without categories)
$query = '
SELECT 
    s.*,
    IF(s.category_id = 0, "[999]zzz", c.name) as category_name
  FROM '.SECTIONS_TABLE.' AS s
    LEFT JOIN '.CATEGORIES_TABLE.' AS c
    ON c.id = s.category_id
  ORDER BY
    IF(s.category_id = 0, "[999]zzz", c.name) ASC,
    rank DESC,
    s.name ASC
;';
$conf['all_sections'] = hash_from_query($query, 'id');

// search this language into section directories
foreach ($conf['all_sections'] as &$row)
{
  $row['lang_exists'] = file_exists($conf['local_dir'].$row['id'].'/'.$page['language']);
}
unset($row);

// +-----------------------------------------------------------------------+
// |                         ADD A NEW SECTION
// +-----------------------------------------------------------------------+
if (isset($_POST['add_section']))
{
  if ( $_POST['section'] == '-1' or !array_key_exists($_POST['section'], $conf['all_sections']) )
  {
    array_push($page['errors'], 'Undefined or unknown project.');
  }
  else if ( !$conf['user_can_add_language'] or !is_translator($page['language'], $_POST['section']) )
  {
    array_push($page['errors'], 'You have no rights to add this project.');
  }
  else if ( create_directory($conf['local_dir'].$_POST['section'].'/'.$page['language']) )
  {
    redirect(get_url_string(array('language'=>$page['language'],'section'=>$_POST['section']), true, 'edit'));
  }
  else
  {
    array_push($page['errors'], 'Can\'t create the folder. Please contact administrators.');
  }
}


// +-----------------------------------------------------------------------+
// |                         DISPLAY SECTIONS
// +-----------------------------------------------------------------------+
// statistics (must be computed with a clean rank)
if ($conf['use_stats'])
{
  $old_language_rank = $conf['all_languages'][$page['language']]['rank'];
  $conf['all_languages'][$page['language']]['rank'] = 1;

  if ( time() - strtotime(get_cache_date(null, $page['language'])) > $conf['stats_cache_life'] )
  {
    make_language_stats($page['language']);
  }
  $stats = get_cache_stats(null, $page['language'], 'section');
  $language_stats = get_cache_stats(null, $page['language'], 'all');
  
  $conf['all_languages'][$page['language']]['rank'] = $old_language_rank;
}
$use_stats = !empty($stats);

// sections not translated, translated and editable, not translated and editable
$section_not_translated = $section_translated = $section_available = $conf['all_sections'];
foreach ($conf['all_sections'] as $row)
{
  if ($row['lang_exists'])
  {
    unset($section_not_translated[ $row['id'] ]);
    unset($section_available[ $row['id'] ]);
  }
  else
  {
    unset($section_translated[ $row['id'] ]);
  }
  if (!in_array($row['id'], $user['sections']))
  {
    unset($section_available[ $row['id'] ], $section_translated[ $row['id'] ]);
  }
}

// path
echo '
<p class="caption"><a href="'.get_url_string().'">'.get_language_flag($page['language']).' '.get_language_name($page['language']).'</a></p>
<ul id="sections" class="list-cloud '.($use_stats ? 'w-stats' : null).'">';

// sections list
$category_id = null;
$use_categories = count(array_unique_deep($section_translated, 'category_id')) > 1;
foreach ($section_translated as $row)
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
  
  if ($use_stats and !isset($stats[ $row['id'] ])) $stats[ $row['id'] ] = 0;
  
  echo '
  <li '.( !$row['lang_exists'] || ($use_stats && empty($stats[$row['id']])) ? 'class="new"' : null).'>
    <a href="'.get_url_string(array('language'=>$page['language'],'section'=>$row['id']), true, 'edit').'">
      '.$row['name'].'
      '.($use_stats ? display_progress_bar($stats[$row['id']], 150) : null).'
    </a>
  </li>';
}

// add section button
if ( $conf['user_can_add_language'] and is_translator($page['language'], null) and count($section_not_translated) > 0 )
{
  echo '
  <li class="add">
    <b>'.count($section_not_translated).' projects not translated</b> <a class="add_section" href="#"><img src="template/images/bullet_add.png" alt="+"> Translate another project</a>
  </li>
  
  <div id="dialog-form" title="Translate another project" style="display:none;">
    <div class="ui-state-highlight" style="padding: 0.7em;margin-bottom:10px;">
      <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
      You can only translate project which you have permission to edit.
    </div>
    <form action="" method="post" style="text-align:center;">
      Select a project : 
      <select name="section">
        <option value="-1">----------</option>';
      foreach ($section_not_translated as $row)
      {
        echo '
        <option value="'.$row['id'].'" '.(!array_key_exists($row['id'], $section_available) ? 'disabled' : null).'>'.$row['name'].'</option>';
      }
      echo '
      </select>
      <input type="hidden" name="add_section" value="1">
    </form>
  </div>';
}
echo '
</ul>';

// language progression
if ($use_stats)
{
  echo '
  <div id="displayStats" class="ui-state-highlight" style="padding: 0em;margin-top:10px;">
    <p style="margin:10px;">
      <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
      <b>Language progression :</b> '.display_progress_bar($language_stats, 825, true).'
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
    Cancel: function() { $(this).dialog("close"); }
  }
});

$(".add_section").click(function() {
  $("#dialog-form").dialog("open");
  return false;
});';

print_page();
?>