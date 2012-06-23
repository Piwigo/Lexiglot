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
    $users = get_users_list(
      array('languages LIKE "%'.$_GET['delete_language'].'%" OR my_languages LIKE "%'.$_GET['delete_language'].'%"'), 
      'languages, my_languages'
      );
    
    foreach ($users as $u)
    {
      unset($u['languages'][ array_search($_GET['delete_language'], $u['languages']) ]);
      unset($u['my_languages'][ array_search($_GET['delete_language'], $u['my_languages']) ]);
      $u['languages'] = create_permissions_array($u['languages']);
      
      if      ($u['main_language'] == $_GET['delete_language'])   $u['main_language'] = null;
      else if ($u['main_language'] != null) $u['languages'][ $u['main_language'] ] = 1;
      
      $u['languages'] = implode_array($u['languages']);
      $u['my_languages'] = implode(',', $u['my_languages']);
      
      $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET
    languages = '.(!empty($u['languages']) ? '"'.$u['languages'].'"' : 'NULL').',
    my_languages = '.(!empty($u['my_languages']) ? '"'.$u['my_languages'].'"' : 'NULL').'
  WHERE user_id = '.$u['id'].'
;';
      mysql_query($query);
    }
    
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
  include(PATH.'admin/include/languages.actions.php');
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
    
    // add lang on user infos
    $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET languages = IF(
    languages="",
    "'.$_POST['id'].',0",
    CONCAT(languages, ";'.$_POST['id'].',0")
    )
  WHERE status IN ( "admin"'.($conf['language_default_user'] == 'all' ? ', "translator", "guest", "manager"' : null).' )
;';
    mysql_query($query);
    
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
  if (get_search_value('flag') == 'with') array_push($where_clauses, 'flag != "" AND flag IS NOT NULL');
  if (get_search_value('flag') == 'without') array_push($where_clauses, 'flag = "" OR flag IS NULL');
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
    COUNT(u.user_id) as total_users
  FROM '.LANGUAGES_TABLE.' as l
    INNER JOIN '.USER_INFOS_TABLE.' as u
      ON u.languages LIKE CONCAT("%",l.id,"%") AND u.status != "guest"
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
  GROUP BY l.id
  ORDER BY l.rank DESC, l.id ASC
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
      <th>Reference</th>
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
      <td>
        <select name="ref_id">
          <option value="" selected="selected">(default)</option>';
          foreach ($conf['all_languages'] as $lang)
          {
            echo '
          <option value="'.$lang['id'].'">'.$lang['name'].'</option>';
          }
        echo '
        </select>
      </td>
      <td><input type="text" name="rank" size="2" value="1"></td>
      <td><input type="text" name="category_id" class="category"></td>
      <td><input type="submit" name="add_language" class="blue" value="Add"></td>
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
      <th>Name</th>
      <th>Flag</th>
      <th>Priority</th>
      <th>Category</th>
      <th>Entries</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="name" size="20" value="'.get_search_value('name').'"></td>
      <td>
        <select name="flag">
          <option value="-1" '.(-1==get_search_value('flag')?'selected="selected"':'').'>-------</option>
          <option value="with" '.('with'==get_search_value('flag')?'selected="selected"':'').'>With flag</option>
          <option value="without" '.('without'==get_search_value('flag')?'selected="selected"':'').'>Without flag</option>
        </select>
      </td>
      <td><input type="text" name="rank" size="2" value="'.get_search_value('rank').'"></td>
      <td>
        <select name="category">
          <option value="-1" '.(-1==get_search_value('category')?'selected="selected"':'').'>-------</option>';
          foreach ($categories as $row)
          {
            echo '
          <option value="'.$row['id'].'" '.($row['id']==get_search_value('category')?'selected="selected"':'').'>'.$row['name'].'</option>';
          }
        echo '
        </select>
      </td>
      <td><input type="text" name="limit" size="3" value="'.get_search_value('limit').'"></td>
      <td>
        <input type="submit" name="search" class="blue" value="Search">
        <input type="submit" name="erase_search" class="red tiny" value="Reset">
      </td>
    </tr>
  </table>
</fieldset>
</form>';

// langs list
echo '
<form id="languages" action="admin.php?page=languages'.(!empty($_GET['nav']) ? '&amp;nav='.$_GET['nav'] : null).'" method="post" enctype="multipart/form-data">
<fieldset class="common">
  <legend>Manage</legend>
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="chkb"></th>
        <th class="name">Name</th>
        <th class="rank">Priority</th>
        <th class="category">Category</th>
        <th class="users">Translators</th>
        <th class="actions">Actions</th>
      </tr>
    </thead>
    <tbody>';
    foreach ($_LANGS as $row)
    {
      echo '
      <tr class="main '.($highlight_language==$row['id'] ? 'highlight' : null).'">
        <td class="chkb">
          <input type="checkbox" name="select[]" value="'.$row['id'].'">
        </td>
        <td class="name">
          <a href="'.get_url_string(array('language'=>$row['id']), true, 'language').'">'.get_language_flag($row['id'], 'default').' '.$row['name'].'</a>
        </td>
        <td class="rank">
          '.$row['rank'].'
        </td>
        <td class="category">
          '.(!empty($row['category_id']) ? get_category_name($row['category_id']) : null).'
        </td>
        <td class="users">
          <a href="'.get_url_string(array('lang_id'=>$row['id'],'page'=>'users'), true).'">'.$row['total_users'].'</a>
        </td>
        <td class="actions">
          <a href="#" class="expand" data="'.$row['id'].'" title="Edit this language"><img src="template/images/page_white_edit.png" alt="edit"></a>
          <a href="'.get_url_string(array('make_stats'=>$row['id'])).'" title="Refresh stats"><img src="template/images/arrow_refresh.png"></a>';
          if ($conf['default_language'] != $row['id'])
          {
            echo ' <a href="'.get_url_string(array('delete_language'=>$row['id'])).'" title="Delete this language" onclick="return confirm(\'Are you sure?\');">
            <img src="template/images/cross.png" alt="[x]"></a>';
          }
          else
          {
            echo ' <span style="display:inline-block;margin-left:5px;width:16px;">&nbsp;</span>';
          }
        echo '
        </td>
      </tr>';
    }
    if (count($_LANGS) == 0)
    {
      echo '
      <tr>
        <td colspan="6"><i>No results</i></td>
      </tr>';
    }
    echo '
    </tbody>
  </table>
  <a href="#" class="selectAll">Select All</a> / <a href="#" class="unselectAll">Unselect all</a>
  <div class="pagination">'.display_pagination($paging, 'nav').'</div>
  
  <input type="hidden" name="MAX_FILE_SIZE" value="10240">
</fieldset>

<fieldset id="permitAction" class="common" style="display:none;margin-bottom:20px;">
  <legend>Global action <span class="unselectAll">[close]</span></legend>
  
  <select name="selectAction">
    <option value="-1">Choose an action...</option>
    <option disabled="disabled">------------------</option>
    <option value="make_stats">Refresh stats</option>
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

$page['header'].= '
<script type="text/javascript" src="template/js/functions.js"></script>';

$page['script'].= '
/* perform ajax request for language edit */
$("a.expand").click(function() {
  $trigger = $(this);
  language_id = $trigger.attr("data");
  $parent_row = $trigger.parents("tr.main");
  $details_row = $parent_row.next("tr.details");
  
  if (!$details_row.length) {
    $("a.expand img").attr("src", "template/images/page_white_edit.png");
    $("tr.details").remove();
    
    $trigger.children("img").attr("src", "template/images/page_edit.png");
    $parent_row.after(\'<tr class="details" id="details\'+ language_id +\'"><td class="chkb"></td><td colspan="5"><img src="template/images/load16.gif"> <i>Loading...</i></td></tr>\');
    
    $container = $parent_row.next("tr.details").children("td:last-child");

    $.ajax({
      type: "POST",
      url: "admin/ajax.php",
      data: { "action":"get_language_form", "language_id": language_id }
    }).done(function(msg) {
      msg = $.parseJSON(msg);
      
      if (msg.errcode == "success") {
        $container.html(msg.data);
        $container.find("input.category").tokenInput(json_categories, {
          tokenLimit: 1,
          allowCreation: true,
          hintText: ""
        });
      }  else {
        overlayMessage(msg.data, msg.errcode, $trigger);
      }
    });
  } else {
    $details_row.remove();
    $trigger.children("img").attr("src", "template/images/page_white_edit.png");
  }
  
  return false;
});

/* token input for categories */
var json_categories = ['.$categories_json.'];
$("input.category").tokenInput(json_categories, {
  tokenLimit: 1,
  allowCreation: true,
  hintText: ""
});

/* tablesorter */
$("#languages table").tablesorter({
  sortList: [[2,1],[1,0]],
  headers: { 0: {sorter: false}, 5: {sorter: false} },
  widgets: ["zebra"]
})
.bind("sortStart", function() { 
  $("tr.details").remove();
  $("a.expand img").attr("src", "template/images/page_white_edit.png");
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

if (!empty($deploy_language))
{
  $page['script'].= '
  $("a.expand[data=\''.$deploy_language.'\']").trigger("click");';
}

?>