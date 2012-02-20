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
// |                         SEARCH
// +-----------------------------------------------------------------------+
// default search
$where_clauses = array('1=1');
$search = array(
  'username' => null,
  'status' => '-1',
  'language' => '-1',
  'section' => '-1',
  );

// url input
if (isset($_GET['user_id']))
{
  $_POST['erase_search'] = true;
  array_push($where_clauses, 'u.id = '.$_GET['user_id']);
}
if (isset($_GET['lang_id']))
{
  $_POST['erase_search'] = true;
  $search['language'] = $_GET['lang_id'];
}
if (isset($_GET['section_id']))
{
  $_POST['erase_search'] = true;
  $search['section'] = $_GET['section_id'];
}
if (isset($_GET['status']))
{
  $_POST['erase_search'] = true;
  $search['status'] = $_GET['status'];
}

// erase search
if (isset($_POST['erase_search']))
{
  unset_session_var('user_search');
  unset($_POST);
}
// get saved search
else if (get_session_var('user_search') != null)
{
  $search = unserialize(get_session_var('user_search'));
}

// get form search
if (isset($_POST['search']))
{
  unset_session_var('user_search');
  if (isset($_GET['user_id']))    unset($_GET['user_id']);
  if (!empty($_POST['username'])) $search['username'] = str_replace('*', '%', $_POST['username']);
  if (!empty($_POST['status']))   $search['status'] =   $_POST['status'];
  if (!empty($_POST['language'])) $search['language'] = $_POST['language'];
  if (!empty($_POST['section']))  $search['section'] =  $_POST['section'];
}

// build query
if (!empty($search['username']))
{
  array_push($where_clauses, 'LOWER(u.'.$conf['user_fields']['username'].') LIKE LOWER("%'.$search['username'].'%")');
}
if ($search['status'] != '-1') 
{
  array_push($where_clauses, 'i.status = "'.$search['status'].'"');
}
if ($search['language'] != '-1') 
{
  array_push($where_clauses, 'i.languages LIKE "%'.$search['language'].'%"');
}
if ($search['section'] != '-1') 
{
  if ($search['section'] == 'none') 
  {
    foreach ($user['sections'] as $section)
      array_push($where_clauses, 'i.sections NOT LIKE "%'.$section.'%"');
  }
  else
  {
    array_push($where_clauses, 'i.sections LIKE "%'.$search['section'].'%"');
  }
}

// save search
if ( !isset($_GET['user_id']) and $where_clauses != array('1=1') )
{
  set_session_var('user_search', serialize($search));
}

// +-----------------------------------------------------------------------+
// |                         GET INFOS
// +-----------------------------------------------------------------------+
// get users infos
$query = '
SELECT u.*, i.*
  FROM '.USERS_TABLE.' as u
    INNER JOIN '.USER_INFOS_TABLE.' as i
    ON u.'.$conf['user_fields']['id'].'  = i.user_id
  WHERE 
    '.implode("\n    AND ", $where_clauses).'
  ORDER BY u.'.$conf['user_fields']['username'].' ASC
;';
$_USERS = hash_from_query($query, 'id');


// +-----------------------------------------------------------------------+
// |                        TEMPLATE
// +-----------------------------------------------------------------------+
// search users
if (count($_USERS) or count($where_clauses) > 1)
{
echo '
<form action="admin.php?page=users_lite" method="post">
<fieldset class="common">
  <legend>Search</legend>
  
  <table class="search">
    <tr>
      <th>Username</th>
      <th>Status</th>
      <th>Language</th>
      <th>Project</th>
      <th></th>
    </tr>
    <tr>
      <td><input type="text" name="username" value="'.$search['username'].'"></td>
      <td>
        <select name="status">
          <option value="-1" '.('-1'==$search['status']?'selected="selected"':null).'>-------</option>
          <option value="guest" '.('guest'==$search['status']?'selected="selected"':null).'>Guest</option>
          <option value="visitor" '.('visitor'==$search['status']?'selected="selected"':null).'>Visitor</option>
          <option value="translator" '.('translator'==$search['status']?'selected="selected"':null).'>Translator</option>
          <option value="manager" '.('manager'==$search['status']?'selected="selected"':null).'>Manager</option>
          <option value="admin" '.('admin'==$search['status']?'selected="selected"':null).'>Admin</option>
        </select>
      </td>
      <td>
        <select name="language">
          <option value="-1" '.('-1'==$search['language']?'selected="selected"':null).'>-------</option>';
        foreach ($conf['all_languages'] as $row)
        {
          echo '
          <option value="'.$row['id'].'" '.($row['id']==$search['language']?'selected="selected"':null).'>'.$row['name'].'</option>';
        }
        echo '
        </select>
      </td>
      <td>
        <select name="section">
          <option value="-1" '.('-1'==$search['section']?'selected="selected"':null).'>-------</option>
          <option value="none" '.('none'==$search['section']?'selected="selected"':null).'>-- none --</option>';
        foreach ($user['sections'] as $section)
        {
          echo '
          <option value="'.$section.'" '.($section==$search['section']?'selected="selected"':null).'>'.get_section_name($section).'</option>';
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

// users list
echo '
<form id="users" action="admin.php?page=users_lite" method="post">
<fieldset class="common">
  <legend>Manage</legend>
  <table class="common tablesorter">
    <thead>
      <tr>
        <th class="user">Username</th>
        <th class="email">Email</th>
        <th class="date">Registration date</th>
        <th class="lang tiptip" title="Spoken">Languages</th>
        <th class="status">Status</th>
        <th class="lang tiptip" title="Assigned">Languages</th>
        <th class="section">Projects</th>
        <th class="actions">Actions</th>
      </tr>
    </thead>
    <tbody>';
    foreach ($_USERS as $row)
    {
      $row['languages'] = !empty($row['languages']) ? explode(',', $row['languages']) : array();
      $row['my_languages'] = !empty($row['my_languages']) ? explode(',', $row['my_languages']) : array();
      $row['sections'] = !empty($row['sections']) ? explode(',', $row['sections']) : array();
      
      echo '
      <tr class="'.$row['status'].'">
        <td class="user">'.$row['username'].'</td>
        <td class="email">'.((!empty($row['email']) and $row['id']!=$conf['guest_id']) ? '<a href="mailto:'.$row['email'].'">'.$row['email'].'</a>' : null).'</td>
        <td class="date">'.format_date($row['registration_date'], true, false).'</td>
        <td class="lang">';
        if (count($row['my_languages']) > 0)
        {
          echo '<a class="expand" title=\'<table class="tooltip"><tr>';
          $i=1; $j=ceil(sqrt(count($row['my_languages'])/2));
          foreach ($row['my_languages'] as $lang)
          {
            echo '<td>'.get_language_flag($lang).' '.get_language_name($lang).'</td>';
            if($i%$j==0) echo '</tr><tr>'; $i++;
          }
          echo '</tr></table>\'>
            '.count($row['my_languages']).' <img src="template/images/bullet_toggle_plus.png" style="margin:5px 0 -5px 0;"></a>';
        }
        echo '
        </td>
        <td class="status">
          '.$row['status'].'
        </td>
        <td class="lang">';
        if (count($row['languages']) > 0)
        {
          echo '<a class="expand" title=\'<table class="tooltip"><tr>';
          $i=1; $j=ceil(sqrt(count($row['languages'])/2));
          foreach ($row['languages'] as $lang)
          {
            echo '<td>'.get_language_flag($lang).' '.get_language_name($lang).'</td>';
            if($i%$j==0) echo '</tr><tr>'; $i++;
          }
          echo '</tr></table>\'>
            '.count($row['languages']).' <img src="template/images/bullet_toggle_plus.png" style="margin:5px 0 -5px 0;"></a>';
        }
        echo '
        </td>
        <td class="section">';
        if (count($row['sections']) > 0)
        {
          echo '<a class="expand" title=\'<table class="tooltip"><tr>';
          $i=1; $j=ceil(sqrt(count($row['sections'])/2));
          foreach ($row['sections'] as $section)
          {
            echo '<td>'.get_section_name($section).'</td>';
            if($i%$j==0) echo '</tr><tr>'; $i++;
          }
          echo '</tr></table>\'>
            '.count($row['sections']).' <img src="template/images/bullet_toggle_plus.png" style="margin:5px 0 -5px 0;"></a>';
        }
        echo '
        </td>
        <td class="actions">
          <img src="template/images/blank.png">';
        if ( in_array($row['status'], array('translator','guest')) )
        {
          echo '
          <a href="'.get_url_string(array('page'=>'user_perm','user_id'=>$row['id']), 'all').'" title="Manage permissions"><img src="template/images/user_edit.png"></a>';
        }
        echo '
        </td>
      </tr>';
    }
    if (count($_USERS) == 0)
    {
      echo '
      <tr>
        <td colspan="9"><i>No results</i></td>
      </tr>';
    }
    echo '
    </tbody>
  </table>
</fieldset>';

$global_mail = 'mailto:'; $f = 1;
foreach ($_USERS as $row)
{
  if (!empty($row['email']) and $row['id']!=$conf['guest_id'])
  {
    if(!$f) $global_mail.= ';'; else $f = 0;
    $global_mail.= $row['email'];
  }
}

echo '
<a href="'.$global_mail.'">Send a mail to all users displayed</a>';
}

$page['header'].= '
<link type="text/css" rel="stylesheet" media="screen" href="template/js/jquery.tablesorter.css">
<script type="text/javascript" src="template/js/jquery.tablesorter.min.js"></script>';
$page['script'].= '
  $(".expand").tipTip({ 
    maxWidth:"800px",
    delay:200,
    defaultPosition:"left"
  });
  
  $(".tiptip").tipTip({
    delay:200,
    defaultPosition:"top"
  });

  $("#users table").tablesorter({
    sortList: [[0,0]],
    widgets: ["zebra"]
  });';

?>