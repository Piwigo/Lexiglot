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
  
  // projects
  $query = '
DELETE FROM '.USER_PROJECTS_TABLE.'
  WHERE
    user_id = '.$_POST['user_id'].'
    AND type = "translate"
;';
  mysql_query($query);
  
  $inserts = array();
  if (!empty($_POST['available_projects']))
  {
    foreach ($_POST['available_projects'] as $p)
    {
      array_push($inserts, array('user_id'=>$_POST['user_id'], 'project'=>$p, 'type'=>'translate'));
    }
  }
  
  mass_inserts(
    USER_PROJECTS_TABLE,
    array('user_id', 'project', 'type'),
    $inserts
    );
  
  // only admin can change languages and manage permissions
  if (is_admin())
  {
    // languages
    $query = '
DELETE FROM '.USER_LANGUAGES_TABLE.'
  WHERE
    user_id = '.$_POST['user_id'].'
    AND type = "translate"
;';
    mysql_query($query);
    
    $inserts = array();
    
    if (!empty($_POST['available_languages']))
    {
      foreach ($_POST['available_languages'] as $l)
      {
        array_push($inserts, array('user_id'=>$_POST['user_id'], 'language'=>$l, 'type'=>'translate'));
      }
    }
    
    mass_inserts(
      USER_LANGUAGES_TABLE,
      array('user_id', 'language', 'type'),
      $inserts
      );

    // manager permissions
    if (get_user_status($_POST['user_id']) == 'manager')
    {
      $query = '
DELETE FROM '.USER_PROJECTS_TABLE.'
  WHERE
    user_id = '.$_POST['user_id'].'
    AND type = "manage"
;';
      mysql_query($query);
      
      $inserts = array();
      if (!empty($_POST['manage_projects']))
      {
        foreach ($_POST['manage_projects'] as $p)
        {
          array_push($inserts, array('user_id'=>$_POST['user_id'], 'project'=>$p, 'type'=>'manage'));
        }
      }
      
      mass_inserts(
        USER_PROJECTS_TABLE,
        array('user_id', 'project', 'type'),
        $inserts
        );
      
      foreach (array_keys(unserialize($conf['default_manager_perms'])) as $perm)
      {
        $manage_perms[$perm] = !empty($_POST['manage_perms'][$perm]);
      }
      array_push($sets, 'manage_perms = \''.serialize($manage_perms).'\'');
    }
    
    // translator main language
    if (get_user_status($_POST['user_id']) != 'guest')
    {
      $query = '
DELETE FROM '.USER_LANGUAGES_TABLE.'
  WHERE
    user_id = '.$_POST['user_id'].'
    AND type = "main"
;';
      mysql_query($query);
      
      if ( !empty($_POST['main_language']) and in_array($_POST['main_language'], @$_POST['available_languages']) )
      {
        $main = $_POST['main_language'];
      }
      else if (count(@$_POST['available_languages']) == 1)
      {
        $main = $_POST['available_languages'][0];
      }
      
      if (isset($main))
      {
        single_insert(
          USER_LANGUAGES_TABLE,
          array('user_id'=>$_POST['user_id'], 'language'=>$main, 'type'=>'main'),
          $inserts
          );
      }
    }
  }
  
  if (count($sets))
  {
    $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET
    '.implode(",    \n", $sets).'
  WHERE
    user_id = '.$_POST['user_id'].'
;';
    mysql_query($query);
  }
  
  redirect(get_url_string(array('page'=>'users','from_id'=>$_POST['user_id']), true));
}

// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$local_user = build_user($_GET['user_id']);

if ( $conf['use_stats'] and !empty($local_user['main_language']) )
{
  $stats = get_cache_stats(null, $local_user['main_language'], 'project');
}
$use_project_stats = !empty($stats);

$movable_projects = is_admin() ? array_keys($conf['all_projects']) : $user['manage_projects'];


// +-----------------------------------------------------------------------+
// |                         TEMPLATE
// +-----------------------------------------------------------------------+
// sort projects and languages by rank
uasort($local_user['languages'], 'cmp_language');
uasort($conf['all_languages'], 'cmp_default');
uasort($local_user['projects'], 'cmp_project');
uasort($conf['all_projects'], 'cmp_default');

// count number of different ranks, if 1, we consider that we don't use ranks
$use_language_rank = !(count(array_unique_deep($conf['all_languages'], 'rank')) > 1);
$use_project_rank = count(array_unique_deep($conf['all_projects'], 'rank')) > 1;

echo '
<p class="caption">Manage permissions for user #'.$local_user['id'].' : '.$local_user['username'].'</p>

<form action="" method="post" onSubmit="save_datas(this);" id="permissions">';
if (is_admin())
{
  // ellipsis display cant' be "automated" in css so we figure out the size of the name field
  $name_size_1 = $name_size_2 = 140;
  if ($use_language_rank)
  { 
    $name_size_1-= 11; 
    $name_size_2-= 11;
  }
  if (file_exists($conf['flags_dir'].$conf['all_languages'][ $conf['default_language'] ]['flag']))
  { 
    list($w) = getimagesize($conf['flags_dir'].$conf['all_languages'][ $conf['default_language'] ]['flag']); 
    $name_size_1-= $w+8; 
    $name_size_2-= $w+8;
  }
  $name_size_1-= 15;
  
  echo '
  <fieldset class="common">
    <legend>Languages</legend>
    '.($local_user['status']!='guest' ? '<p class="caption">Check the main language of this user</p>' : null).'
    
    <ul id="available_languages" class="language-container">
      <h5>Authorized languages <span id="authorizeAllLanguage" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($local_user['languages'] as $lang)
    {
      if (array_key_exists($lang, $conf['all_languages']))
      {
        echo '
        <li id="list_'.$lang.'" class="language">
          '.($local_user['status']!='guest' ? '<input type="radio" name="main_language" value="'.$lang.'" '.($lang==$local_user['main_language']?'checked="checked"':null).'>' : null).'
          '.get_language_flag($lang).' <span style="width:'.$name_size_1.'px;">'.get_language_name($lang).'</span>
          '.($use_language_rank ? '<i>'.get_language_rank($lang).'</i>' : null).'
        </li>';
      }
    }
    echo '
    </ul>

    <ul id="unavailable_languages" class="language-container">
      <h5>Forbidden languages <span id="forbidAllLanguage" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($conf['all_languages'] as $row)
    {
      if (!in_array($row['id'], $local_user['languages']))
      {
        echo '
        <li id="list_'.$row['id'].'" class="language">
          '.($local_user['status']!='guest' ? '<input type="radio" name="main_language" value="'.$row['id'].'" style="display:none;">' : null).'
          '.get_language_flag($row['id']).' <span style="width:'.$name_size_2.'px;">'.$row['name'].'</span>
          '.($use_language_rank ? '<i>'.$row['rank'].'</i>' : null).'
        </li>';
      }
    }
    echo '
    </ul>
  </fieldset>';
}


$name_size_1 = $name_size_2 = 140;
if ($use_project_rank)
{ 
  $name_size_1-= 11; 
  $name_size_2-= 11;
}
if ($use_project_stats)
{ 
  $name_size_1-= 27;
  $name_size_2-= 27;
}
if ($local_user['status']=='manager' && is_admin())
{ 
  $name_size_1-= 15;
}

echo '
  <fieldset class="common">
    <legend>Projects</legend>
    '.($local_user['status']=='manager' && is_admin() ? '<p class="caption">Check projects this user can manage</p>' : null).'
    
    <ul id="available_projects" class="project-container">
      <h5>Authorized projects <span id="authorizeAllProject" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($local_user['projects'] as $project)
    {
      if (array_key_exists($project, $conf['all_projects']))
      {
        echo '
        <li id="list_'.$project.'" class="project" '.(!in_array($project,$movable_projects) ? 'style="display:none;"' : null).' title="'.get_project_name($project).'">
          '.($local_user['status']=='manager' && is_admin() ? '<input type="checkbox" name="manage_projects[]" value="'.$project.'" '.(in_array($project,$local_user['manage_projects'])?'checked="checked"':null).'>' : null).'
          <span style="width:'.$name_size_1.'px;">'.get_project_name($project).'</span>
          '.($use_project_stats ? '<b style="color:'.get_gauge_color($stats[$project],'dark').';">'.number_format($stats[$project]*100, 0).'%</b>' : null).'
          '.($use_project_rank ? '<i>'.get_project_rank($project).'</i>' : null).'
        </li>';
      }
    }
    echo '
    </ul>

    <ul id="unavailable_projects" class="project-container">
      <h5>Forbidden projects <span id="forbidAllProject" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
      
    foreach ($conf['all_projects'] as $row)
    {
      if (!in_array($row['id'], $local_user['projects']))
      {
        echo '
        <li id="list_'.$row['id'].'" class="project" '.(!in_array($row['id'],$movable_projects) ? 'style="display:none;"' : null).' title="'.$row['name'].'">
          '.($local_user['status']=='manager' && is_admin() ? '<input type="checkbox" name="manage_projects[]" value="'.$row['id'].'" style="display:none;">' : null).'
          <span style="width:'.$name_size_2.'px;">'.$row['name'].'</span>
          '.($use_project_stats ? '<b style="color:'.get_gauge_color($stats[$row['id']],'dark').';">'.number_format($stats[$row['id']]*100, 0).'%</b>' : null).'
          '.($use_project_rank ? '<i>'.$row['rank'].'</i>' : null).'
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
if (is_admin())
{
  $page['script'].= '
  /* move languages */
  $("li.language input").bind("click", function (e) {
    e.stopPropagation();
  });
  $("#available_languages").delegate("li.language", "click", function() {
    $(this).fadeOut("fast", function() {
      $(this).children("input").hide();
      $(this).children("span").css("width", $(this).children("span").width() + 15);
      $(this).appendTo("#unavailable_languages").fadeIn("fast", function(){
        update_height("languages");
      });
    });
  });
  $("#unavailable_languages").delegate("li.language", "click", function() {
    $(this).fadeOut("fast", function() {
      $(this).children("input").show();
      $(this).children("span").css("width", $(this).children("span").width() - 15);
      $(this).appendTo("#available_languages").fadeIn("fast", function(){
        update_height("languages");
      });
    });
  });
  
  /* all languages */
  $("#authorizeAllLanguage").click(function() {
    $("#unavailable_languages li").each(function() {
      $(this).fadeOut("fast", function() {
        $(this).children("input").show();
        $(this).children("span").css("width", $(this).children("span").width() - 15);
        $(this).appendTo($("#available_languages")).fadeIn("fast");
      });
    }).promise().done(function() { 
      update_height("languages");
    });
  });
  $("#forbidAllLanguage").click(function() {
    $("#available_languages li").each(function() {
      $(this).fadeOut("fast", function() {
        $(this).children("input").hide();
        $(this).children("span").css("width", $(this).children("span").width() + 15);
        $(this).appendTo($("#unavailable_languages")).fadeIn("fast");
      });
    }).promise().done(function() { 
      update_height("languages");
    });
  });
  
  update_height("languages");';
}

$page['script'].= '
/* move projects */
$("li.project input").bind("click", function (e) {
  e.stopPropagation();
});
$("#available_projects").delegate("li.project", "click", function() {
  $(this).fadeOut("fast", function() {
    $(this).children("input").hide();
    '.($local_user['status']=='manager' && is_admin() ? '$(this).children("span").css("width", $(this).children("span").width() + 15);' : null ).'
    $(this).appendTo("#unavailable_projects").fadeIn("fast", function(){
      update_height("projects");
    });
  });
});
$("#unavailable_projects").delegate("li.project", "click", function() {
  $(this).fadeOut("fast", function() {
    $(this).children("input").show();
    '.($local_user['status']=='manager' && is_admin() ? '$(this).children("span").css("width", $(this).children("span").width() - 15);' : null ).'
    $(this).appendTo("#available_projects").fadeIn("fast", function(){
      update_height("projects");
    }); 
  });
});

/* all projects */
$("#authorizeAllProject").click(function() {
  $("#unavailable_projects li").each(function() {
    if ($(this).css("display") != "none") {
      $(this).fadeOut("fast", function() {
        $(this).children("input").show();
        '.($local_user['status']=='manager' && is_admin() ? '$(this).children("span").css("width", $(this).children("span").width() - 15);' : null ).'
        $(this).appendTo($("#available_projects")).fadeIn("fast");
      });
    }
  }).promise().done(function() { 
    update_height("projects");
  });
});
$("#forbidAllProject").click(function() {
  $("#available_projects li").each(function() {
    if ($(this).css("display") != "none") {
      $(this).fadeOut("fast", function() {
        $(this).children("input").hide();
        '.($local_user['status']=='manager' && is_admin() ? '$(this).children("span").css("width", $(this).children("span").width() + 15);' : null ).'
        $(this).appendTo($("#unavailable_projects")).fadeIn("fast");
      });
    }
  }).promise().done(function() { 
    update_height("projects");
  });
});

update_height("projects");';

$page['header'].= '
<script type="text/javascript">
  function save_datas(form) {    
    $("#available_languages > li").each(function() {
      $(form).append("<input type=\"hidden\" name=\"available_languages[]\" value=\""+ $(this).attr("id").replace("list_","") +"\">");
    });
    $("#available_projects > li").each(function() {
      $(form).append("<input type=\"hidden\" name=\"available_projects[]\" value=\""+ $(this).attr("id").replace("list_","") +"\">");
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


function cmp_default($a, $b)
{
  if ($a['rank'] == $b['rank']) return strcmp($a['id'], $b['id']);
  return $a['rank'] > $b['rank'] ? -1 : 1;
}
function cmp_language($a, $b)
{
  if (get_language_rank($a) == get_language_rank($b)) return strcmp($a, $b);
  return get_language_rank($a) < get_language_rank($b) ? 1 : -1;
}
function cmp_project($a, $b)
{
  if (get_project_rank($a) == get_project_rank($b)) return strcmp($a, $b);
  return get_project_rank($a) < get_project_rank($b) ? 1 : -1;
}

?>