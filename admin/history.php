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

// +-----------------------------------------------------------------------+
// |                         DELETE ROW
// +-----------------------------------------------------------------------+
if ( isset($_GET['delete_row']) and is_numeric($_GET['delete_row']) )
{
  $query = 'DELETE FROM '.ROWS_TABLE.' WHERE id = '.$_GET['delete_row'].';';
  mysql_query($query);
  array_push($page['infos'], 'Translation deleted.');
}

// +-----------------------------------------------------------------------+
// |                         ACTIONS
// +-----------------------------------------------------------------------+
if ( isset($_POST['apply_action']) and $_POST['selectAction'] != '-1' and !empty($_POST['select']) )
{
  switch ($_POST['selectAction'])
  {
    // DELETE ROWS
    case 'delete_rows':
    {
      $query = '
DELETE FROM '.ROWS_TABLE.' 
  WHERE id IN('.implode(',', $_POST['select']).') 
;';
      mysql_query($query);

      array_push($page['infos'], mysql_affected_rows().' translations deleted.');
      break;
    }
    
    // MARK AS DONE
    case 'mark_as_done':
    {
      $query = '
UPDATE '.ROWS_TABLE.' 
  SET status = "done" 
  WHERE id IN('.implode(',', $_POST['select']).') 
;';
      mysql_query($query);

      array_push($page['infos'], mysql_affected_rows().' translations marked as commited.');
      break;
    }
  }
}

// +-----------------------------------------------------------------------+
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$where_clauses = array('1=1');
$search = array(
  'user_id' => '-1',
  'language' => '-1',
  'section' => '-1',
  'status' => '-1',
  'limit' => 50,
  );

// erase search
if (isset($_POST['erase_search']))
{
  unset_session_var('log_search');
  unset($_POST);
}
// get saved search
else if (get_session_var('log_search') != null)
{
  $search = unserialize(get_session_var('log_search'));
}

// get form search
if (isset($_POST['search']))
{
  unset_session_var('log_search');
  if (!empty($_POST['user_id']))  $search['user_id'] = $_POST['user_id'];
  if (!empty($_POST['language'])) $search['language'] = $_POST['language'];
  if (!empty($_POST['section']))  $search['section'] = $_POST['section'];
  if (!empty($_POST['status']))   $search['status'] = $_POST['status'];
  if (!empty($_POST['limit']))    $search['limit'] = $_POST['limit'];
}

// build query
if ($search['user_id'] != '-1') 
{
  array_push($where_clauses, 'l.user_id = '.$search['user_id']);
}
if ($search['language'] != '-1') 
{
  array_push($where_clauses, 'l.lang = "'.$search['language'].'"');
}
if ($search['section'] != '-1') 
{
  array_push($where_clauses, 'l.section = "'.$search['section'].'"');
}
if ($search['status'] != '-1') 
{
  array_push($where_clauses, 'l.status = "'.$search['status'].'"');
}

// save search
if ($where_clauses != array('1=1'))
{
  set_session_var('log_search', serialize($search));
}


// +-----------------------------------------------------------------------+
// |                         GET ROWS
// +-----------------------------------------------------------------------+
$displayed_sections = is_admin() ? $conf['all_sections'] : array_intersect_key($conf['all_sections'], array_combine($user['manage_sections'], $user['manage_sections']));
array_push($where_clauses, 'l.section IN("'.implode('","', array_keys($displayed_sections)).'")');

$query = '
SELECT 
    l.*,
    u.'.$conf['user_fields']['username'].' as username
  FROM '.ROWS_TABLE.' as l
    INNER JOIN '.USERS_TABLE.' as u
    ON l.user_id = u.'.$conf['user_fields']['id'].'
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
  ORDER BY last_edit DESC
  LIMIT 0,'.$search['limit'].'
;';
$_ROWS = hash_from_query($query, null);

// get users infos
$query = '
SELECT 
    u.'.$conf['user_fields']['id'].' as id,
    u.'.$conf['user_fields']['username'].' as username
  FROM '.USERS_TABLE.' as u
    INNER JOIN '.USER_INFOS_TABLE.' as i
    ON u.'.$conf['user_fields']['id'].'  = i.user_id
  WHERE i.status = "translator" OR i.status = "admin"
  ORDER BY u.'.$conf['user_fields']['username'].' ASC
;';
$_USERS = hash_from_query($query, 'id');


// +-----------------------------------------------------------------------+
// |                        TEMPLATE
// +-----------------------------------------------------------------------+
// search rows
echo '
<form action="admin.php?page=log" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>User</th>
      <th>Language</th>
      <th>Project</th>
      <th>Status</th>
      <th>Limit</th>
      <th></th>
    </tr>
    <tr>
      <td>
        <select name="user_id">
          <option value="-1" '.('-1'==$search['user_id']?'selected="selected"':'').'>-------</option>';
          foreach ($_USERS as $row)
          {
            echo '
          <option value="'.$row['id'].'" '.($row['id']==$search['user_id']?'selected="selected"':'').'>'.$row['username'].'</option>';
          }
        echo '
        </select>
      </td>
      <td>
        <select name="language">
          <option value="-1" '.('-1'==$search['language']?'selected="selected"':'').'>-------</option>';
          foreach ($conf['all_languages'] as $row)
          {
            echo '
          <option value="'.$row['id'].'" '.($row['id']==$search['language']?'selected="selected"':'').'>'.$row['name'].'</option>';
          }
        echo '
        </select>
      </td>
      <td>
        <select name="section">
          <option value="-1" '.('-1'==$search['section']?'selected="selected"':'').'>-------</option>';
          foreach ($displayed_sections as $row)
          {
            echo '
          <option value="'.$row['id'].'" '.($row['id']==$search['section']?'selected="selected"':'').'>'.$row['name'].'</option>';
          }
        echo '
        </select>
      </td>
      <td>
        <select name="status">
          <option value="-1" '.('-1'==$search['status']?'selected="selected"':'').'>-------</option>
          <option value="new" '.('new'==$search['status']?'selected="selected"':'').'>Added</option>
          <option value="edit" '.('edit'==$search['status']?'selected="selected"':'').'>Modified</option>
          <option value="done" '.('done'==$search['status']?'selected="selected"':'').'>Commited</option>
        </select>
      </td>
      <td>
        <input type="text" size="3" name="limit" value="'.$search['limit'].'">
      </td>
      <td>
        <input type="submit" name="search" class="blue" value="Search">
        <input type="submit" name="erase_search" class="red tiny" value="Erase">
      </td>
    </tr>
  </table>
</fieldset>
</form>';

// rows list
echo '
<form action="admin.php?page=log" method="post" id="last_modifs">
<fieldset class="common">
  <legend>History</legend>
  
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="chkb"></th>
        <th class="lang">Lang</th>
        <th class="section">Project</th>
        <th class="file">File</th>
        <th class="user">User</th>
        <th class="date">Date</th>
        <th class="value">Content</th>
        <th class="actions"></th>
      </tr>
    </thead>
    <tbody>';
    foreach ($_ROWS as $row)
    {
      echo '
      <tr class="'.$row['status'].'">
        <td class="chkb"><input type="checkbox" name="select[]" value="'.$row['id'].'"></td>
        <td class="lang">
          <a href="'.get_url_string(array('page'=>'languages','lang_id'=>$row['lang']), true).'">'.get_language_name($row['lang']).'</a>
        </td>
        <td class="section">
          <a href="'.get_url_string(array('page'=>'projects','section_id'=>$row['section']), true).'">'.get_section_name($row['section']).'</a>
        </td>
        <td class="file">'.$row['file_name'].'</td>
        <td class="user">
          <a href="'.get_url_string(array('page'=>'users','user_id'=>$row['user_id']), true).'">'.$row['username'].'</a>
        </td>
        <td class="date"><span style="display:none;">'.strtotime($row['last_edit']).'</span>'.format_date($row['last_edit'], true, false).'</td>
        <td class="value">
          <pre class="row_value" title="'.str_replace('"',"'",$row['row_name']).'">'.cut_string(htmlspecialchars($row['row_value']), 400).'</pre>
        </td>
        <td class="actions">
          <a href="'.get_url_string(array('delete_row'=>$row['id'])).'" title="Delete this row">
            <img src="template/images/cross.png" alt="[x]"></a>
        </td>
      </tr>';
    }
    if (count($_ROWS) == 0)
    {
      echo '
      <tr>
        <td colspan="8"><i>No results</i></td>
      </tr>';
    }
    echo '
    </tbody>
  </table>
  <a href="#" class="selectAll">Select All</a> / <a href="#" class="unselectAll">Unselect all</a>
</fieldset>

<fieldset id="permitAction" class="common" style="display:none;margin-bottom:20px;">
  <legend>Global action <span class="unselectAll">[close]</span></legend>
  
  <select name="selectAction">
    <option value="-1">Choose an action...</option>
    <option disabled="disabled">------------------</option>
    <option value="delete_rows">Delete</option>
    <option value="mark_as_done">Mark as commited</option>
  </select>
  
  <span id="action_apply" class="action-container">
    <input type="submit" name="apply_action" class="blue" value="Apply">
  </span>
</fieldset>

</form>

<table class="legend">
  <tr>
    <td><span>&nbsp;</span> Commited strings</td>
    <td><span class="new">&nbsp;</span> Added strings</td>
    <td><span class="edit">&nbsp;</span> Modified strings</td>
  </tr>
</table>';


// +-----------------------------------------------------------------------+
// |                        SCRIPTS
// +-----------------------------------------------------------------------+
load_jquery('tablesorter');
load_jquery('tiptip');

$page['script'].= '
$(".row_value").tipTip({ 
  maxWidth:"600px",
  delay:200,
  defaultPosition:"left"
});

$("#last_modifs table").tablesorter({
  sortList: [[5,1]],
  headers: { 0: {sorter: false}, 6: {sorter: false}, 7: {sorter: false} },
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
  } else {
    $("#permitAction").show();
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

  if ($(this).val() != -1) {
    $("#action_apply").show();
  } else {
    $("#action_apply").hide();
  }
});';

?>