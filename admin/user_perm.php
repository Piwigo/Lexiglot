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
  array_push($page['errors'], 'Invalid user id. <a href="'.get_url_string(array('page'=>'users'),'all').'">Go Back</a>');
  print_page();
}

// +-----------------------------------------------------------------------+
// |                         SAVE PERMISSIONS
// +-----------------------------------------------------------------------+
if (isset($_POST['save_perm']))
{
  eval($_POST['languages']);
  eval($_POST['sections']);
  if (isset($available_languages)) sort($available_languages);
  if (isset($available_sections)) sort($available_sections);
  
  $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET
    languages = "'.(isset($available_languages) ? implode(',', $available_languages) : null).'",
    sections = "'.(isset($available_sections) ? implode(',', $available_sections) : null).'"
  WHERE
    user_id = '.$_POST['user_id'].'
;';
  mysql_query($query);
  redirect(get_url_string(array('page'=>'users'), 'all'));
}

// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$this_user = build_user($_GET['user_id']);

// manager permissions
if ( is_manager() and !in_array($this_user['status'], array('translator','guest')) )
{
  array_push($page['errors'], 'You are not allowed to edit permissions of this user.');
  print_page();
}

$movable_sections = is_manager() ? $user['sections'] : array_keys($conf['all_sections']);


// +-----------------------------------------------------------------------+
// |                         TEMPLATE
// +-----------------------------------------------------------------------+
echo '
<p class="caption">Manage permissions for the user #'.$this_user['id'].' : '.$this_user['username'].'</p>

<form action="" method="post" onsubmit="save_datas(this);" id="permissions">

  <fieldset class="common" style="'.($this_user['status']=='manager' ? 'display:none;' : null).'">
    <legend>Languages</legend>
    <ul id="available_languages" class="lang-container">
      <h5>Authorized languages <span id="authorizeAllLang" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($this_user['languages'] as $lang)
    {
      if (array_key_exists($lang, $conf['all_languages']))
      {
        echo '
        <li id="'.$lang.'" class="lang">'.get_language_flag($lang).' '.get_language_name($lang).'</li>';
      }
    }
    echo '
    </ul>

    <ul id="unavailable_languages" class="lang-container">
      <h5>Forbidden languages <span id="forbidAllLang" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($conf['all_languages'] as $row)
    {
      if (!in_array($row['id'], $this_user['languages']))
      {
        echo '
        <li id="'.$row['id'].'" class="lang">'.get_language_flag($row['id']).' '.$row['name'].'</li>';
      }
    }
    echo '
    </ul>
  </fieldset>

  <fieldset class="common">
    <legend>Projects</legend>
    <ul id="available_sections" class="section-container">
      <h5>Authorized projects <span id="authorizeAllSection" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($this_user['sections'] as $section)
    {
      if (array_key_exists($section, $conf['all_sections']))
      {
        echo '
        <li id="'.$section.'" class="section" '.(!in_array($section,$movable_sections) ? 'style="display:none;"' : null).'>'.get_section_name($section).'</li>';
      }
    }
    echo '
    </ul>

    <ul id="unavailable_sections" class="section-container">
      <h5>Forbidden projects <span id="forbidAllSection" class="uniq-action" style="margin-left:-40px;">[all]</span></h5>';
    foreach ($conf['all_sections'] as $row)
    {
      if (!in_array($row['id'], $this_user['sections']))
      {
        echo '
        <li id="'.$row['id'].'" class="section" '.(!in_array($row['id'],$movable_sections) ? 'style="display:none;"' : null).'>'.$row['name'].'</li>';
      }
    }
    echo '
    </ul>
  </fieldset>

  <input type="hidden" name="user_id" value="'.$this_user['id'].'">
  <input type="hidden" name="languages">
  <input type="hidden" name="sections">
  <input type="submit" name="save_perm" class="blue big" value="Save">
</form>';
    
if ($this_user['status'] != 'manager')
{
  $page['script'].= '
  $("li.lang").draggable({
    revert: "invalid",
    helper: "clone",
    cursor: "move"
  });
  $(".lang-container").droppable({
    accept: "li.lang",
    hoverClass: "active",
    drop: function(event, ui) {
      var $gallery = this;
      ui.draggable.fadeOut(function() {
        $(this).appendTo($gallery).fadeIn();
      });      
    }
  });
  $("#authorizeAllLang").click(function() {
    $("#unavailable_languages li").each(function() {
      $(this).fadeOut(function() {
        $(this).appendTo($("#available_languages")).fadeIn();
      });
    });
  });
  $("#forbidAllLang").click(function() {
    $("#available_languages li").each(function() {
      $(this).fadeOut(function() {
        $(this).appendTo($("#unavailable_languages")).fadeIn();
      });
    });
  });';
}

$page['script'].= '  
  $("li.section").draggable({
    revert: "invalid",
    helper: "clone",
    cursor: "move"
  });
  $(".section-container").droppable({
    accept: "li.section",
    hoverClass: "active",
    drop: function(event, ui) {
      var $gallery = this;
      ui.draggable.fadeOut(function() {
        $(this).appendTo($gallery).fadeIn();
      });      
    }
  });
  $("#authorizeAllSection").click(function() {
    $("#unavailable_sections li").each(function() {
      if ($(this).css("display") != "none")
        $(this).fadeOut(function() {
          $(this).appendTo($("#available_sections")).fadeIn();
        });
    });
  });
  $("#forbidAllSection").click(function() {
    $("#available_sections li").each(function() {
      if ($(this).css("display") != "none")
        $(this).fadeOut(function() {
          $(this).appendTo($("#unavailable_sections")).fadeIn();
        });
    });
  });';

$page['header'].= '
<script type="text/javascript">
  function save_datas(form) {
    var languages = "";
    var sections = "";
    
    $(".lang-container").each(function() {
      var id = $(this).attr("id");
      $("> li", this).each(function() { 
        languages += "$" + id + "[]=\'" + $(this).attr("id") + "\';";
      });
    });
    $(".section-container").each(function() {
      var id = $(this).attr("id");
      $("> li", this).each(function() { 
        sections += "$" + id + "[]=\'" + $(this).attr("id") + "\';";
      });
    });
    
    jQuery("input[name=\'languages\']").val(languages);
    jQuery("input[name=\'sections\']").val(sections);
    submit(form);
  }
</script>';

?>