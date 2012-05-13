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

if (!svn_check_connection())
{
  array_push($page['errors'], 'Unable to connect to the Subversion server.');
  print_page();
}

// +-----------------------------------------------------------------------+
// |                        GET DATAS
// +-----------------------------------------------------------------------+
$displayed_sections = is_admin() ? $conf['all_sections'] : array_intersect_key($conf['all_sections'], array_combine($user['manage_sections'], $user['manage_sections']));
  
// get users name
$query = '
SELECT 
    u.'.$conf['user_fields']['id'].' as id,
    u.'.$conf['user_fields']['username'].' as username
  FROM '.USERS_TABLE.' as u
    INNER JOIN '.USER_INFOS_TABLE.' as i
    ON u.'.$conf['user_fields']['id'].'  = i.user_id
  WHERE i.status != "guest" AND i.status != "visitor"
  ORDER BY u.'.$conf['user_fields']['username'].' ASC
;';
$_USERS = hash_from_query($query, 'id');

if (isset($_POST['init_commit']))
{
  // build query
  $commit_title = array();;
  $where_clauses = array('1=1');
  
  if ($_POST['mode'] == 'filter')
  {
    if ( !empty($_POST['filter_section']) and $_POST['section_id'] != '-1' )
    {
      array_push($commit_title, 'project : '.get_section_name($_POST['section_id']));
      array_push($where_clauses, 'section = "'.$_POST['section_id'].'"');
    }
    if ( !empty($_POST['filter_language']) and $_POST['language_id'] != '-1' )
    {
      array_push($commit_title, 'language : '.get_language_name($_POST['language_id']));
      array_push($where_clauses, 'lang = "'.$_POST['language_id'].'"');
    }
    if ( !empty($_POST['filter_user']) and $_POST['user_id'] != '-1' )
    {
      array_push($commit_title, 'user : '.get_username($_POST['user_id']));
      array_push($where_clauses, 'user_id = "'.$_POST['user_id'].'"');
    }
  }
  
  $commit_title = implode(' | ', $commit_title);
  
  if (is_manager())
  {
    array_push($where_clauses, 'section IN("'.implode('","', array_keys($displayed_sections)).'")');
  }
  
  if (!empty($_POST['exclude']))
  {
    array_push($where_clauses, 'CONCAT(section, lang) NOT IN ("'.implode('", "', $_POST['exclude']).'")');
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
      last_edit DESC,
      lang ASC,
      section ASC,
      user_id ASC,
      file_name ASC,
      row_name ASC
  ) as t
  GROUP BY CONCAT(t.row_name,t.lang,t.section)
;';
  $result = mysql_query($query);
  
  $_ROWS = array();
  while ($row = mysql_fetch_assoc($result))
  {
    // complicated array usefull for separate each commit
    $_ROWS[ $row['section'].'||'.$row['lang'] ][ $row['file_name'] ][ $row['row_name'] ] = $row;
  }
  
  if (!count($_ROWS))
  {
    array_push($page['warnings'], (!empty($commit_title) ? $commit_title.' > ' : null).'No changes to commit. <a href="'.get_url_string(array('page'=>'commit'), true).'">Go back</a>');
    print_page();
  }
  
  // +-----------------------------------------------------------------------+
  // |                        MAIN PROCESS
  // +-----------------------------------------------------------------------+
  if (isset($_POST['check_commit']))
  {
    include(PATH.'admin/include/commit.preview.php');
  }

  else
  {
    include(PATH.'admin/include/commit.full.php');
  }

}


// +-----------------------------------------------------------------------+
// |                        CONFIGURATION
// +-----------------------------------------------------------------------+
else
{
  echo '
  <form action="admin.php?page=commit" method="post" id="commit_conf">
  <fieldset class="common">
    <!--<legend>What to commit ?</legend>-->
    
    <div id="mode">
      <input type="radio" id="radio1" name="mode" value="all" checked="checked" /><label for="radio1">All</label>
      <input type="radio" id="radio2" name="mode" value="filter" /><label for="radio2">Filter</label>
    </div>
    
    <ul id="filter" style="display:none;">
      <li class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only">
        <span class="ui-button-text">
          <label><span class="ui-icon ui-icon-close" style="float:left;margin:0 5px 0 -5px;"></span>
          <input type="checkbox" name="filter_section" value="1" style="display:none;"> by project</label>
          
          <select name="section_id" style="display:none;">
            <option value="-1">--------</option>';
          foreach ($displayed_sections as $row)
          {
            echo '
            <option value="'.$row['id'].'">'.$row['name'].'</option>';
          }
          echo '
          </select>
        </span>
      </li>
      <br>
      <li class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only">
        <span class="ui-button-text">
          <label><span class="ui-icon ui-icon-close" style="float:left;margin:0 5px 0 -5px;"></span>
          <input type="checkbox" name="filter_language" value="1" style="display:none;"> by language</label>
          
          <select name="language_id" style="display:none;">
            <option value="-1">--------</option>';
          foreach ($conf['all_languages'] as $row)
          {
            echo '
            <option value="'.$row['id'].'">'.$row['name'].'</option>';
          }
          echo '
          </select>
        </span>
      </li>
      <br>
      <li class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only">
        <span class="ui-button-text">
          <label><span class="ui-icon ui-icon-close" style="float:left;margin:0 5px 0 -5px;"></span>
          <input type="checkbox" name="filter_user" value="1" style="display:none;"> by user</label>
          
          <select name="user_id" style="display:none;">
            <option value="-1">--------</option>';
          foreach ($_USERS as $row)
          {
            echo '
            <option value="'.$row['id'].'">'.$row['username'].'</option>';
          }
          echo '
          </select>
        </span>
      </li>
    </ul>
    <ul>
      <li class="ui-button ui-widget ui-state-default ui-state-active ui-corner-all ui-button-text-only">
        <span class="ui-button-text">
          <label><span class="ui-icon ui-icon-check" style="float:left;margin:0 5px 0 -5px;"></span>
          <input type="checkbox" name="delete_obsolete" checked="checked" value="1" style="display:none;"> delete obsolete strings</label>
        </span>
      </li>
    </ul>
    
    <div class="centered">
      <input type="hidden" name="check_commit" value="1">
      <input type="submit" name="init_commit" class="blue big" value="Launch">
    </div>
  </fieldset>
  </form>';
}

$page['script'].= '
$("#mode").buttonset();

$("input[name=\"mode\"]").change(function() {
  if ($(this).val() == "filter" && $("#filter").css("display") == "none") {
    $("#filter").slideDown("slow");
  } else if ($(this).val() == "all" && $("#filter").css("display") != "none") {
    $("#filter").slideUp("slow");
  }
});

$(".ui-button").hover(
  function() { $(this).addClass("ui-state-hover"); },
  function() { $(this).removeClass("ui-state-hover"); }
);

$(".ui-button input[type=\"checkbox\"]").change(function() {
  if ($(this).is(":checked")) {
    $(this).parents(".ui-button").addClass("ui-state-active");
    $(this).parents(".ui-button").find(".ui-icon").removeClass("ui-icon-close").addClass("ui-icon-check");
    $(this).parent("label").next("select").show("slide");
  } else {
    $(this).parents(".ui-button").removeClass("ui-state-active");
    $(this).parents(".ui-button").find(".ui-icon").removeClass("ui-icon-check").addClass("ui-icon-close");
    $(this).parent("label").next("select").hide("slide");
  }
});
';

?>