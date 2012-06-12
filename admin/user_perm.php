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

// check user_id
if ( !isset($_GET['user_id']) or !is_numeric($_GET['user_id']) or !get_username($_GET['user_id']) )
{
  array_push($page['errors'], 'Invalid user id. <a href="'.get_url_string(array('page'=>'users'), true).'">Go Back</a>');
  print_page();
}

// +-----------------------------------------------------------------------+
// |                         SAVE PERMISSIONS
// +-----------------------------------------------------------------------+
if (isset($_POST['save_perm']))
{
  $sets = array();
  
  // sections
  if (!empty($_POST['available_sections']))
  {
    ksort($_POST['available_sections']);
    $sections = create_permissions_array(array_keys($_POST['available_sections']));
  }
  else
  {
    $sections = array();
  }
  
  // only admin can change language and permissions
  if (is_admin())
  {
    // languages
    if (!empty($_POST['available_languages']))
    {
      ksort($_POST['available_languages']);
      $languages = create_permissions_array(array_keys($_POST['available_languages']));
    }
    else
    {
      $languages = array();
    }

    // manager permissions
    if (get_user_status($_POST['user_id']) == 'manager')
    {
      if (!empty($_POST['manage_sections']))
      {
        $manage_sections = create_permissions_array(array_keys($_POST['manage_sections']), 1);
        $sections = array_merge($sections, $manage_sections);
      }
      
      foreach (array_keys(unserialize(DEFAULT_MANAGER_PERMS)) as $perm)
      {
        $manage_perms[$perm] = !empty($_POST['manage_perms'][$perm]);
      }
      array_push($sets, 'manage_perms = \''.serialize($manage_perms).'\'');
    }
    // translator main language
    if (get_user_status($_POST['user_id']) != 'guest')
    {
      if (!empty($_POST['main_language']))
      {
        $languages[ $_POST['main_language'] ] = 1;
      }
      else if (count($languages) == 1)
      {
        $temp_lang = array_keys($languages);
        $languages[ $temp_lang[0] ] = 1;
      }
    }
    
    $languages = implode_array($languages);
    array_push($sets, 'languages = '.(!empty($languages) ? '"'.$languages.'"' : 'NULL').'');
  }
  
  $sections = implode_array($sections);
  array_push($sets, 'sections = '.(!empty($sections) ? '"'.$sections.'"' : 'NULL').'');
  
  $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET
    '.implode(",    \n", $sets).'
  WHERE
    user_id = '.$_POST['user_id'].'
;';
  mysql_query($query);
  
  redirect(get_url_string(array('page'=>'users','from_id'=>$_POST['user_id']), true));
}

// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$local_user = build_user($_GET['user_id']);
if ( $conf['use_stats'] and !empty($local_user['main_language']) )
{
  $stats = get_cache_stats(null, $local_user['main_language'], 'section');
}
$use_stats = !empty($stats);

// manager permissions
if ( is_manager() and !$user['manage_perms']['can_change_users_projects'] )
{
  array_push($page['errors'], 'You are not allowed to edit permissions of this user.');
  print_page();
}

$movable_sections = is_admin() ? array_keys($conf['all_sections']) : $user['manage_sections'];


// +-----------------------------------------------------------------------+
// |                         TEMPLATE
// +-----------------------------------------------------------------------+
// sort sections and languages by rank
function cmp1($a, $b)
{
  if ($a['rank'] == $b['rank']) return strcmp($a['id'], $b['id']);
  return $a['rank'] > $b['rank'] ? -1 : 1;
}
function cmp2_lang($a, $b)
{
  if (get_language_rank($a) == get_language_rank($b)) return strcmp($a, $b);
  return get_language_rank($a) < get_language_rank($b) ? 1 : -1;
}
function cmp2_sect($a, $b)
{
  if (get_section_rank($a) == get_section_rank($b)) return strcmp($a, $b);
  return get_section_rank($a) < get_section_rank($b) ? 1 : -1;
}

uasort($local_user['languages'], 'cmp2_lang');
uasort($conf['all_languages'], 'cmp1');
uasort($local_user['sections'], 'cmp2_sect');
uasort($conf['all_sections'], 'cmp1');

// count number of different ranks, if 1, we consider that we don't use ranks
$use_lang_rank = count(array_unique_deep($conf['all_languages'], 'rank')) > 1;
$use_section_rank = count(array_unique_deep($conf['all_sections'], 'rank')) > 1;

echo '
<p class="caption">Manage permissions for user #'.$local_user['id'].' : '.$local_user['username'].'</p>

<form action="" method="post" onSubmit="save_datas(this);" id="permissions">';
if (is_admin())
{
  echo '
  <fieldset class="common">
    <legend>Languages</legend>
    '.($local_user['status']!='guest' ? '<p class="caption">Check the main language of this user</p>' : null).'
    <ul id="available_languages" class="lang-container">
      <h5>Authorized languages <span id="authorizeAllLang" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($local_user['languages'] as $lang)
    {
      if (array_key_exists($lang, $conf['all_languages']))
      {
        echo '
        <li id="list_'.$lang.'" class="lang">
          '.($local_user['status']!='guest' && is_admin() ? '<input type="radio" name="main_language" value="'.$lang.'" '.($lang==$local_user['main_language']?'checked="checked"':null).'>' : null).'
          '.get_language_flag($lang).' '.get_language_name($lang).'
          '.($use_lang_rank ? '<i>'.get_language_rank($lang).'</i>' : null).'
        </li>';
      }
    }
    echo '
    </ul>

    <ul id="unavailable_languages" class="lang-container">
      <h5>Forbidden languages <span id="forbidAllLang" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($conf['all_languages'] as $row)
    {
      if (!in_array($row['id'], $local_user['languages']))
      {
        echo '
        <li id="list_'.$row['id'].'" class="lang">
          '.($local_user['status']!='guest' && is_admin() ? '<input type="radio" name="main_language" value="'.$row['id'].'" style="display:none;">' : null).'
          '.get_language_flag($row['id']).' '.$row['name'].'
          '.($use_lang_rank ? '<i>'.$row['rank'].'</i>' : null).'
        </li>';
      }
    }
    echo '
    </ul>
  </fieldset>';
}

echo '
  <fieldset class="common">
    <legend>Projects</legend>
    '.($local_user['status']=='manager' && is_admin() ? '<p class="caption">Check projects this user can manage</p>' : null).'
    <ul id="available_sections" class="section-container">
      <h5>Authorized projects <span id="authorizeAllSection" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($local_user['sections'] as $section)
    {
      if (array_key_exists($section, $conf['all_sections']))
      {
        echo '
        <li id="list_'.$section.'" class="section" '.(!in_array($section,$movable_sections) ? 'style="display:none;"' : null).' title="'.get_section_name($section).'">
          '.($local_user['status']=='manager' && is_admin() ? '<input type="checkbox" name="manage_sections['.$section.']" value="1" '.(in_array($section,$local_user['manage_sections'])?'checked="checked"':null).'>' : null).'
          '.cut_string(get_section_name($section), 12, false).'
          '.($use_stats ? ' <b style="color:'.get_gauge_color($stats[$section],'dark').';font-size:0.8em;">'.number_format($stats[$section]*100, 0).'%</b>' : null).'
          '.($use_section_rank ? '<i>'.get_section_rank($section).'</i>' : null).'
        </li>';
      }
    }
    echo '
    </ul>

    <ul id="unavailable_sections" class="section-container">
      <h5>Forbidden projects <span id="forbidAllSection" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($conf['all_sections'] as $row)
    {
      if (!in_array($row['id'], $local_user['sections']))
      {
        echo '
        <li id="list_'.$row['id'].'" class="section" '.(!in_array($row['id'],$movable_sections) ? 'style="display:none;"' : null).' title="'.$row['name'].'">
          '.($local_user['status']=='manager' && is_admin() ? '<input type="checkbox" name="manage_sections['.$row['id'].']" value="1" style="display:none;">' : null).'
          '.cut_string($row['name'], 12, false).'
          '.($use_stats ? ' <b style="color:'.get_gauge_color($stats[$row['id']],'dark').';font-size:0.8em;">'.number_format($stats[$row['id']]*100, 0).'%</b>' : null).'
          '.($use_section_rank ? '<i>'.$row['rank'].'</i>' : null).'
        </li>';
      }
    }
    echo '
    </ul>
  </fieldset>';
  
  if ( $local_user['status']=='manager' and is_admin() )
  {
    echo '
  <fieldset class="common">
    <legend>Manager permissions</legend>
    
    <label><input type="checkbox" name="manage_perms[can_add_projects]" value="1" '.($local_user['manage_perms']['can_add_projects'] ? 'checked="checked"' : null).'> Can add projects</label><br>
    <label><input type="checkbox" name="manage_perms[can_delete_projects]" value="1" '.($local_user['manage_perms']['can_delete_projects'] ? 'checked="checked"' : null).'> Can delete projects</label><br>
    <label><input type="checkbox" name="manage_perms[can_change_users_projects]" value="1" '.($local_user['manage_perms']['can_change_users_projects'] ? 'checked="checked"' : null).'> Can change users projects</label><br>
  </fieldset>';
  }

  echo '
  <div class="centered">
    <input type="hidden" name="user_id" value="'.$local_user['id'].'">
    <input type="submit" name="save_perm" class="blue big" value="Save">
    <input type="reset" onClick="location.href=\''.get_url_string(array('page'=>'users','from_id'=>$local_user['id']), true).'\';" class="red" value="Cancel">
  </div>
</form>';


// +-----------------------------------------------------------------------+
// |                        JAVASCRIPT
// +-----------------------------------------------------------------------+    
$page['script'].= '
$("li.lang input").bind("click", function (e) {
  e.stopPropagation();
});
$("#available_languages").delegate("li.lang", "click", function() {
  $(this).fadeOut("fast", function() {
    $(this).children("input").hide();
    $(this).appendTo("#unavailable_languages").fadeIn("fast");
  });
});
$("#unavailable_languages").delegate("li.lang", "click", function() {
  $(this).fadeOut("fast", function() {
    $(this).children("input").show();
    $(this).appendTo("#available_languages").fadeIn("fast");
  });
});

$("li.section input").bind("click", function (e) {
  e.stopPropagation();
});
$("#available_sections").delegate("li.section", "click", function() {
  $(this).fadeOut("fast", function() {
    $(this).children("input").hide();
    $(this).appendTo("#unavailable_sections").fadeIn("fast");
  });
});
$("#unavailable_sections").delegate("li.section", "click", function() {
  $(this).fadeOut("fast", function() {
    $(this).children("input").show();
    $(this).appendTo("#available_sections").fadeIn("fast");
  });
});

/*$("li.lang").draggable({
  revert: "invalid",
  helper: "clone",
  cursor: "move"
});
$(".lang-container").droppable({
  accept: "li.lang",
  hoverClass: "active",
  drop: function(event, ui) {
    var $gallery = this;
    ui.draggable.fadeOut("fast", function() {
      if ($($gallery).attr("id") == "available_languages") {
        $(this).children("input").show();
      } else {
        $(this).children("input").hide();
      }
      $(this).appendTo($gallery).fadeIn("fast");
      update_height("languages");
    });      
  }
});*/
$("#authorizeAllLang").click(function() {
  $("#unavailable_languages li").each(function() {
    $(this).fadeOut("fast", function() {
      $(this).children("input").show();
      $(this).appendTo($("#available_languages")).fadeIn("fast");
    });
  }).promise().done(function() { 
    update_height("languages");
  });
});
$("#forbidAllLang").click(function() {
  $("#available_languages li").each(function() {
    $(this).fadeOut("fast", function() {
      $(this).children("input").hide();
      $(this).appendTo($("#unavailable_languages")).fadeIn("fast");
    });
  }).promise().done(function() { 
    update_height("languages");
  });
});

/*$("li.section").draggable({
  revert: "invalid",
  helper: "clone",
  cursor: "move"
});
$(".section-container").droppable({
  accept: "li.section",
  hoverClass: "active",
  drop: function(event, ui) {
    var $gallery = this;
    ui.draggable.fadeOut("fast", function() {
      if ($($gallery).attr("id") == "available_sections") {
        $(this).children("input").show();
      } else {
        $(this).children("input").hide();
      }
      $(this).appendTo($gallery).fadeIn("fast");
      update_height("sections");
    });      
  }
});*/
$("#authorizeAllSection").click(function() {
  $("#unavailable_sections li").each(function() {
    if ($(this).css("display") != "none") {
      $(this).fadeOut("fast", function() {
        $(this).children("input").show();
        $(this).appendTo($("#available_sections")).fadeIn("fast");
      });
    }
  }).promise().done(function() { 
    update_height("sections");
  });
});
$("#forbidAllSection").click(function() {
  $("#available_sections li").each(function() {
    if ($(this).css("display") != "none") {
      $(this).fadeOut("fast", function() {
        $(this).children("input").hide();
        $(this).appendTo($("#unavailable_sections")).fadeIn("fast");
      });
    }
  }).promise().done(function() { 
    update_height("sections");
  });
});

update_height("languages");
update_height("sections");';

$page['header'].= '
<script type="text/javascript">
  function save_datas(form) {    
    $("#available_languages > li").each(function() {
      $(form).append("<input type=\"hidden\" name=\"available_languages["+ $(this).attr("id").replace("list_","") +"]\" value=\"1\">");
    });
    $("#available_sections > li").each(function() {
      $(form).append("<input type=\"hidden\" name=\"available_sections["+ $(this).attr("id").replace("list_","") +"]\" value=\"1\">");
    });
  }
  function update_height(row) {
    $("#available_"+ row).css("height", "auto");
    $("#unavailable_"+ row).css("height", "auto");
    var max = Math.max($("#available_"+ row).height(), $("#unavailable_"+ row).height());
    $("#available_"+ row).css("height", max);
    $("#unavailable_"+ row).css("height", max);
  }
</script>';

?>