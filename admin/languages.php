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

$highlight_language = isset($_GET['from_id']) ? $_GET['from_id'] : null;

// +-----------------------------------------------------------------------+
// |                         DELETE LANG
// +-----------------------------------------------------------------------+
if (isset($_GET['delete_lang']))
{
  // delete flag
  $flag = $conf['all_languages'][ $_GET['delete_lang'] ]['flag'];
  @unlink($conf['flags_dir'].$flag);
  
  // delete lang form user infos (such a wonderfull query !)
  $i = $_GET['delete_lang'];
  $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET languages = 
    IF(languages = "'.$i.'", 
      "",
      IF(languages LIKE "'.$i.',%",
        REPLACE(languages, "'.$i.',", ""),
        IF(languages LIKE "%,'.$i.'", 
          REPLACE(languages, ",'.$i.'", ""),
          IF(languages LIKE "%,'.$i.',%", 
            REPLACE(languages, ",'.$i.',", ","),
            languages
      ) ) ) )
;';
  mysql_query($query);
  $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET my_languages = 
    IF(my_languages = "'.$i.'", 
      "",
      IF(my_languages LIKE "'.$i.',%",
        REPLACE(my_languages, "'.$i.',", ""),
        IF(my_languages LIKE "%,'.$i.'", 
          REPLACE(my_languages, ",'.$i.'", ""),
          IF(my_languages LIKE "%,'.$i.',%", 
            REPLACE(my_languages, ",'.$i.',", ","),
            my_languages
      ) ) ) )
;';
  mysql_query($query);
  
  // delete from stats table
  $query = '
DELETE FROM '.STATS_TABLE.'
  WHERE language = "'.$_GET['delete_lang'].'"
;';
  mysql_query($query);
  
  // delete from languages table
  $query = '
DELETE FROM '.LANGUAGES_TABLE.' 
  WHERE id = "'.$_GET['delete_lang'].'"
;';
  mysql_query($query);
  
  array_push($page['infos'], 'Language deleted.');
}

// +-----------------------------------------------------------------------+
// |                         ACTIONS
// +-----------------------------------------------------------------------+
if ( isset($_POST['apply_action']) and $_POST['selectAction'] != '-1' and !empty($_POST['select']) )
{
  include(PATH.'admin/include/languages.actions.php');
}

// +-----------------------------------------------------------------------+
// |                         DELETE FLAG
// +-----------------------------------------------------------------------+
if (isset($_GET['delete_flag']))
{
  $query = '
SELECT flag FROM '.LANGUAGES_TABLE.' 
  WHERE id = "'.$_GET['delete_flag'].'"
;';
  list($flag) = mysql_fetch_row(mysql_query($query));
  @unlink($conf['flags_dir'].$flag);
  
  $query = '
UPDATE '.LANGUAGES_TABLE.'
  SET flag = NULL
  WHERE "'.$_GET['delete_flag'].'"
;';
  mysql_query($query);
  
  $conf['all_languages'][$_GET['delete_flag']]['flag'] = null;
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
if (isset($_POST['save_lang']))
{
  foreach ($_POST['langs'] as $id => $row)
  {
    $errors = array();
    // check name
    if (empty($row['name']))
    {
      array_push($errors, 'Name is empty for language &laquo;'.$id.'&raquo;.');
    }
    // check file
    if ( !empty($_FILES['flags-'.$id]['tmp_name']) and count($errors) == 0 )
    {
      $row['flag'] = upload_flag($_FILES['flags-'.$id], $id);
      if (is_array($row['flag']))
      {
        $errors = array_merge($errors, $row['flag']);
      }
      else
      {
        // delete old file
        $query = '
SELECT flag FROM '.LANGUAGES_TABLE.' 
  WHERE id = "'.$id.'"
;';
        list($flag) = mysql_fetch_row(mysql_query($query));
        @unlink($conf['flags_dir'].$flag);
        
        $conf['all_languages'][$id]['flag'] = $row['flag'];
      }
    }
    // check rank
    if (!is_numeric($row['rank']))
    {
      array_push($errors, 'Rank must be an integer for language &laquo;'.$id.'&raquo;.');
    }
    // check category
    if ( !empty($row['category_id']) and !count($errors) and !is_numeric($row['category_id']) )
    {
      $row['category_id'] = add_category($row['category_id'], 'language');
    }
    if (empty($row['category_id']))
    {
      $row['category_id'] = 0;
    }
    
    // save lang
    if (count($errors) == 0)
    {
      $query = '
UPDATE '.LANGUAGES_TABLE.'
  SET
    name = "'.$row['name'].'",
    rank = '.$row['rank'].',
    category_id = '.$row['category_id'].''
    .(isset($row['flag']) ? ',flag = "'.$row['flag'].'"' : null).'
  WHERE id = "'.$id.'"
;';
      mysql_query($query);
    }
    else
    {
      $page['errors'] = array_merge($page['errors'], $errors);
    }
  }
  
  if (count($page['errors']) == 0)
  {
    array_push($page['infos'], 'Modifications saved.');
  }
}

// +-----------------------------------------------------------------------+
// |                         ADD LANG
// +-----------------------------------------------------------------------+
if (isset($_POST['add_lang']))
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
  // check file
  if ( !empty($_FILES['flag']['tmp_name']) and count($page['errors']) == 0 )
  {
    $_POST['flag'] = upload_flag($_FILES['flag'], $_POST['id']);
    if (is_array($_POST['flag']))
    {
      $page['errors'] = array_merge($page['errors'], $_POST['flag']);
    }
    else
    {
      $conf['all_languages'][$_POST['id']]['flag'] = $_POST['flag'];
    }
  }
  else
  {
    $_POST['flag'] = null;
  }
  // check rank
  if (!is_numeric($_POST['rank']))
  {
    array_push($page['errors'], 'Rank must be an integer.');
  }
  // check category
  if ( !empty($_POST['category_id']) and !count($page['errors']) and !is_numeric($_POST['category_id']) )
  {
    $row['category_id'] = add_category($_POST['category_id'], 'language');
  }
  if (empty($_POST['category_id']))
  {
    $_POST['category_id'] = 0;
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
    category_id
  )
  VALUES(
    "'.$_POST['id'].'",
    "'.$_POST['name'].'",
    "'.$_POST['flag'].'",
    '.$_POST['rank'].',
    '.$_POST['category_id'].'
  )
;';
    mysql_query($query);
    
    // add lang on user infos
    $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET languages = IF( languages="", "'.$_POST['id'].'", CONCAT(languages, ",'.$_POST['id'].'") )
  WHERE '.($conf['language_default_user'] == 'all' ? 'status = "translator" OR status = "guest" OR status = "manager" OR ' : null).'status = "admin"
;';
    mysql_query($query);
    
    // generate stats
    if ($conf['use_stats'])
    {
      $query = 'SELECT * FROM '.LANGUAGES_TABLE.' ORDER BY id;';
      $conf['all_languages'] = hash_from_query($query, 'id');
      ksort($conf['all_languages']);
      
      make_language_stats($_POST['id']);
    }
    
    array_push($page['infos'], 'Language added');
    $highlight_language = $_POST['id'];
  }
}

// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$where_clauses = array('1=1');
$search = array(
  'id' => null,
  'name' => null,
  'flag' => '-1',
  'rank' => null,
  'category' => '-1',
  );

// url input
if (isset($_GET['lang_id']))
{
  $_POST['erase_search'] = true;
  $search['id'] = $_GET['lang_id'];
}

// erase search
if (isset($_POST['erase_search']))
{
  unset_session_var('lang_search');
  unset($_POST);
}
// get saved search
else if (get_session_var('lang_search') != null)
{
  $search = unserialize(get_session_var('lang_search'));
}

// get form search
if (isset($_POST['search']))
{
  unset_session_var('lang_search');
  if (isset($_GET['lang_id']))    unset($_GET['lang_id']);
  if (!empty($_POST['id']))       $search['id'] =       str_replace('*', '%', $_POST['id']);
  if (!empty($_POST['name']))     $search['name'] =     str_replace('*', '%', $_POST['name']);
  if (!empty($_POST['flag']))     $search['flag'] =     $_POST['flag'];
  if (!empty($_POST['rank']))     $search['rank'] =     $_POST['rank'];
  if (!empty($_POST['category'])) $search['category'] = $_POST['category'];
}

// build query
if (!empty($search['id']))
{
  array_push($where_clauses, 'LOWER(id) LIKE LOWER("%'.$search['id'].'%")');
}
if (!empty($search['name']))
{
  array_push($where_clauses, 'LOWER(name) LIKE LOWER("%'.$search['name'].'%")');
}
if ($search['flag'] != '-1')
{
  if ($search['flag'] == 'with') array_push($where_clauses, 'flag != ""');
  if ($search['flag'] == 'without') array_push($where_clauses, 'flag = ""');
}
if (!empty($search['rank']))
{
  array_push($where_clauses, 'rank = '.$search['rank']);
}
if ($search['category'] != '-1')
{
  array_push($where_clauses, 'category_id = '.$search['category']);
}

// save search
if ( !isset($_GET['lang_id']) and $where_clauses != array('1=1') )
{
  set_session_var('lang_search', serialize($search));
}

// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
$query = '
SELECT 
    l.*,
    COUNT(u.user_id) as total_users
  FROM '.LANGUAGES_TABLE.' as l
    INNER JOIN '.USER_INFOS_TABLE.' as u
    ON u.languages LIKE CONCAT("%",l.id,"%") OR u.status = "admin"
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
  GROUP BY l.id
  ORDER BY l.id ASC
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
// add lang
echo '
<form action="admin.php?page=languages" method="post" enctype="multipart/form-data">
<fieldset class="common">
  <legend>Add a language</legend>
  
  <table class="search">
    <tr>
      <th>Id. (folder name) <span class="red">*</span></th>
      <th>Name <span class="red">*</span></th>
      <th>Flag</th>
      <th>Priority</th>
      <th>Category</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="id" size="15"></td>
      <td><input type="text" name="name" size="20"></td>
      <td>
        <input type="file" name="flag">
        <input type="hidden" name="MAX_FILE_SIZE" value="10240">
      </td>
      <td><input type="text" name="rank" size="2" value="1"></td>
      <td><input type="text" name="category_id" class="category"></td>
      <td><input type="submit" name="add_lang" class="blue" value="Add"></td>
    </tr>
  </table>
  
</fieldset>
</form>';

// search langs
if (count($_LANGS) or count($where_clauses) > 1)
{
echo '
<form action="admin.php?page=languages" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>Id.</th>
      <th>Name</th>
      <th>Flag</th>
      <th>Priority</th>
      <th>Category</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="id" size="15" value="'.$search['id'].'"></td>
      <td><input type="text" name="name" size="20" value="'.$search['name'].'"></td>
      <td>
        <select name="flag">
          <option value="-1" '.('-1'==$search['flag']?'selected="selected"':'').'>-------</option>
          <option value="with" '.('with'==$search['flag']?'selected="selected"':'').'>With flag</option>
          <option value="without" '.('without'==$search['flag']?'selected="selected"':'').'>Without flag</option>
        </select>
      </td>
      <td><input type="text" name="rank" size="2" value="'.$search['rank'].'"></td>
      <td>
        <select name="category">
          <option value="-1" '.('-1'==$search['category']?'selected="selected"':'').'>-------</option>';
          foreach ($categories as $row)
          {
            echo '
          <option value="'.$row['id'].'" '.($row['id']==$search['category']?'selected="selected"':'').'>'.$row['name'].'</option>';
          }
        echo '
        </select>
      </td>
      <td>
        <input type="submit" name="search" class="blue" value="Search">
        <input type="submit" name="erase_search" class="red tiny" value="Erase">
      </td>
    </tr>
  </table>
</fieldset>
</form>';

// langs list
echo '
<form id="langs" action="admin.php?page=languages" method="post" enctype="multipart/form-data">
<fieldset class="common">
  <legend>Manage</legend>
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="chkb"></th>
        <th class="id">Id.</th>
        <th class="name">Name</th>
        <th class="flag">Flag</th>
        <th class="rank">Priority</th>
        <th class="category">Category</th>
        <th class="users">Translators</th>
        <th class="actions"></th>
      </tr>
    </thead>
    <tbody>';
    foreach ($_LANGS as $row)
    {
      echo '
      <tr class="'.($highlight_language==$row['id'] ? 'highlight' : null).'">
        <td class="chkb"><input type="checkbox" name="select[]" value="'.$row['id'].'"></td>
        <td class="id">'.$row['id'].'</td>
        <td class="name">
          <input type="text" name="langs['.$row['id'].'][name]" value="'.$row['name'].'" size="20">
          <span style="display:none;">'.$row['name'].'</span>
        </td>
        <td class="flag">
          '.get_language_flag($row['id'], 'default');
        if (get_language_flag($row['id']) != null)
        {
          echo '
          <a href="'.get_url_string(array('delete_flag'=>$row['id'])).'" title="Delete the flag" style="margin-right:10px;">
            <img src="template/images/bullet_delete.png" alt="x"></a>';
        }
        else
        {
          echo '
          <span style="display:inline-block;margin-right:10px;width:16px;">&nbsp;</span>';
        }
        echo '
          Change : <input type="file" name="flags-'.$row['id'].'" size="15">
        </td>
        <td class="rank">
          <input type="text" name="langs['.$row['id'].'][rank]" value="'.$row['rank'].'" size="2">
          <span style="display:none;">'.$row['rank'].'</span>
        </td>
        <td class="category">
          <input type="text" name="langs['.$row['id'].'][category_id]" class="category" '.(!empty($row['category_id']) ? 'value=\'[{"id": '.$row['category_id'].'}]\'' : null).'>
        </td>
        <td class="users">
          <a href="'.get_url_string(array('lang_id'=>$row['id'],'page'=>'users'), true).'">'.$row['total_users'].'</a>
        </td>
        <td class="actions">
          '.($conf['use_stats'] ? '<a href="'.get_url_string(array('make_stats'=>$row['id'])).'" title="Refresh stats"><img src="template/images/arrow_refresh.png"></a>' : null).'
          '.($conf['default_language'] != $row['id'] ? '<a href="'.get_url_string(array('delete_lang'=>$row['id'])).'" title="Delete this language" onclick="return confirm(\'Are you sure?\');">
            <img src="template/images/cross.png" alt="[x]"></a>' : null).'
        </td>
      </tr>';
    }
    if (count($_LANGS) == 0)
    {
      echo '
      <tr>
        <td colspan="5"><i>No results</i></td>
      </tr>';
    }
    echo '
    </tbody>
  </table>
  <a href="#" class="selectAll">Select All</a> / <a href="#" class="unselectAll">Unselect all</a>
  
  <div class="centered">
    <input type="hidden" name="MAX_FILE_SIZE" value="10240">
    <input type="submit" name="save_lang" class="blue big" value="Save">
  </div>
</fieldset>

<fieldset id="permitAction" class="common" style="display:none;margin-bottom:20px;">
  <legend>Global action <span class="unselectAll">[close]</span></legend>
  
  <select name="selectAction">
    <option value="-1">Choose an action...</option>
    <option disabled="disabled">------------------</option>
    '.($conf['use_stats'] ? '<option value="make_stats">Refresh stats</option>' : null).'
    <option value="delete_languages">Delete languages</option>
    <option value="change_rank">Change priority</option>
    <option value="change_category">Change category</option>
  </select>
  
  <span id="action_delete_languages" class="action-container">
    <label><input type="checkbox" name="confirm_deletion" value="1"> Are you sure ?</label>
  </span>
  
  <span id="action_change_rank" class="action-container">
    <input type="text" name="batch_rank" size="2">
  </span>
  
  <span id="action_change_category" class="action-container" style="position:relative;top:8px;"> <!-- manually correct the mispositionning of tokeninput block -->
    <input type="text" name="batch_category_id" class="category">
  </span>
  
  <span id="action_apply" class="action-container">
    <input type="submit" name="apply_action" class="blue" value="Apply">
  </span>
</fieldset>
</form>';
}


// +-----------------------------------------------------------------------+
// |                        JAVASCRIPT
// +-----------------------------------------------------------------------+
load_jquery('tablesorter');
load_jquery('tokeninput');

$page['script'].= '
/* token input for categories */
$("input.category").tokenInput(['.$categories_json.'], {
  tokenLimit: 1,
  allowCreation: true,
  hintText: ""
});

/* tablesorter */
$("#langs table").tablesorter({
  sortList: [[4,1],[1,0]],
  headers: { 0: {sorter: false}, 3: {sorter: false}, 7: {sorter: false} },
  widgets: ["zebra"]
});

/* actions */
function checkPermitAction() {
  var nbSelected = 0;

  $("td.chkb input[type=checkbox]").each(function() {
     if ($(this).is(":checked")) {
       nbSelected++;
     }
  });

  if (nbSelected == 0) {
    $("#permitAction").hide();
    $("#save_status").show();
  } else {
    $("#permitAction").show();
    $("#save_status").hide();
  }
}

$("[id^=action_]").hide();

$("td.chkb input[type=checkbox]").change(function () {
  checkPermitAction();
});

$(".selectAll").click(function() {
  $("td.chkb input[type=checkbox]").each(function() {
     $(this).attr("checked", true);
  });
  checkPermitAction();
  return false;
});
$(".unselectAll").click(function() {
  $("td.chkb input[type=checkbox]").each(function() {
     $(this).attr("checked", false);
  });
  checkPermitAction();
  return false;
});

$("select[name=selectAction]").change(function() {
  $("[id^=action_]").hide();
  $("#action_"+$(this).attr("value")).show();

  if ($(this).val() != -1) {
    $("#action_apply").show();
  } else {
    $("#action_apply").hide();
  }
});

$("td.id").click(function() {
  $checkbox = $(this).prev("td.chkb").children("input");
  $checkbox.attr("checked", !$checkbox.attr("checked"));
});';

?>