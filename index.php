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

define('LEXIGLOT_PATH', './');
include(LEXIGLOT_PATH . 'include/common.inc.php');

if (!empty($conf['intro_message']))
{
  echo '
  <div class="ui-state-highlight" style="padding: 0.7em;margin-bottom:10px;">
    <span class="ui-icon ui-icon-comment" style="float: left; margin-right: 0.7em;"></span>
    '.$conf['intro_message'].'
  </div>';
}

// +-----------------------------------------------------------------------+
// |                         AVAILABLE LANGUAGES
// +-----------------------------------------------------------------------+
if ( $conf['navigation_type'] == 'both' or $conf['navigation_type'] == 'languages' )
{
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
  $language_translated = hash_from_query($query, 'id');
  
  // languages which this user can edit/view
  foreach (array_keys($language_translated) as $lang)
  {
    if (!in_array($lang, $user['languages']))
    {
      unset($language_translated[$lang]);
    }
  }

  // statistics
  if ($conf['use_stats'])
  {
    $stats = get_cache_stats(null, null, 'language');
  }
  $use_stats = !empty($stats);

  // path
  echo '
  <p class="caption">Choose a language'.($conf['navigation_type']=='both' ? '...' : null).'</p>
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
    <li '.($use_stats && empty($stats[ $row['id'] ]) ? 'class="new"' : null).'>
      <a href="'.get_url_string(array('language'=>$row['id']), true, 'language').'">
        '.$row['name'].' '.get_language_flag($row['id']).'
        '.($row['id'] == $conf['default_language'] ? '<i>(source)</i>': null).'
        '.($use_stats ? display_progress_bar($stats[ $row['id'] ], 150) : null).'
      </a>
    </li>';
  }
  if ( $conf['user_can_add_language'] and is_translator() )
  {
    echo '
    <li class="add">
      <a href="'.get_url_string(array('request_language'=>null), true, 'misc').'"><img src="template/images/bullet_add.png" alt="+"> Request a new language</a>
    </li>';
  }
  echo '
  </ul>';
}

// +-----------------------------------------------------------------------+
// |                         AVAILABLE PROJECTS
// +-----------------------------------------------------------------------+
if ( $conf['navigation_type'] == 'both' or $conf['navigation_type'] == 'projects' )
{
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
  $project_translated = hash_from_query($query, 'id');
  
  // projects which this user can edit/view
  foreach ($conf['all_projects'] as $row)
  {
    if (!in_array($row['id'], $user['projects']))
    {
      unset($project_translated[ $row['id'] ]);
    }
  }
  
  // statistics
  if ($conf['use_stats'])
  {
    $stats = get_cache_stats(null, null, 'project');
  }
  $use_stats = !empty($stats);

  // path
  echo '
  <p class="caption" style="margin-top:15px;">'.($conf['navigation_type']=='both' ? '... or choose a project' : 'Choose a project').'</p>
  <ul id="projects" class="list-cloud '.($use_stats ? 'w-stats' : null).'">';
  
  // projects list
  $category_id = null;
  $use_categories = count(array_unique_deep($project_translated, 'category_id')) > 1;
  foreach ($project_translated as $row)
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
    <li>
      <a href="'.get_url_string(array('project'=>$row['id']), true, 'project').'">
        '.$row['name'].'
        '.($use_stats ? display_progress_bar($stats[ $row['id'] ], 150) : null).'
      </a>
    </li>';
  }
  echo '
  </ul>';
}

print_page();
?>