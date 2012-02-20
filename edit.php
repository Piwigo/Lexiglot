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

$page['header'].= '
<link type="text/css" rel="stylesheet" media="screen" href="template/public.css">';

// +-----------------------------------------------------------------------+
// |                         PAGE OPTIONS 1 (mandatory)
// +-----------------------------------------------------------------------+
// language
if ( !isset($_GET['language']) or !array_key_exists($_GET['language'], $conf['all_languages']) )
{
  array_push($page['errors'], 'Undefined or unknown language. <a href="'.get_url_string(array('section'=>$_GET['section']), 'all', 'section').'">Go Back</a>');
  print_page();
}
// section
if ( !isset($_GET['section']) or !array_key_exists($_GET['section'], $conf['all_sections']) )
{
  array_push($page['errors'], 'Undefined or unknown project. <a href="'.get_url_string(array('language'=>$_GET['language']), 'all', 'language').'">Go Back</a>');
  print_page();
}

$page['language'] = $_GET['language'];
$page['section'] = $_GET['section'];
$page['directory'] = $conf['local_dir'].$page['section'].'/';
$page['files'] = explode(',', $conf['all_sections'][ $page['section'] ]['files']);


// +-----------------------------------------------------------------------+
// |                         PAGE OPTIONS 2 (optionals)
// +-----------------------------------------------------------------------+
// file
if ( isset($_GET['file']) and in_array($_GET['file'], $page['files']) )
{
  $page['file'] = $_GET['file'];
}
else
{
  $page['file'] = $page['files'][0];
}
// mode
if (is_plain_file($page['file']))
{
  $page['mode'] = 'plain';
}
else
{
  $page['mode'] = 'array';
}
// display
if ( isset($_GET['display']) and in_array($_GET['display'], array('all','missing')) )
{
  $page['display'] = $_GET['display'];
}
else
{
  $page['display'] = is_guest() || is_visitor() || is_source_language($page['language']) ? 'all' : 'missing';
}

// title
$page['window_title'] = get_section_name($page['section']).' &raquo; '.get_language_name($page['language']);
$page['title'] = 'Edit';


// +-----------------------------------------------------------------------+
// |                         LOAD ROWS
// +-----------------------------------------------------------------------+
if (!file_exists($page['directory'].'/'.$page['language']))
{
  array_push($page['errors'], 'This language doesn\'t exist in this project, please create it throught the <a href="'.get_url_string(array('section'=>$page['section']), 'all', 'section').'">project page</a>.');
  print_page();
}

if (!file_exists($page['directory'].$conf['default_language'].'/'.$page['file']))
{
  array_push($page['errors'], 'Can\'t find this file for default language.');
  print_page();
}

$_LANG_default = load_language_file($page['directory'].$conf['default_language'].'/'.$page['file']);
$_LANG =         load_language_file($page['directory'].$page['language'].'/'.$page['file']);
$_LANG_db =      load_language_db($page['language'], $page['file'], $page['section']);

if ( $page['mode'] == 'plain' and !empty($_LANG_db) )
{
  $_LANG_db = $_LANG_db[ $page['file'] ];
}


// +-----------------------------------------------------------------------+
// |                         SEND NOTIFICATION
// +-----------------------------------------------------------------------+
if ( isset($_POST['send_notification']) and $_POST['user_id'] != '-1' )
{
  if (!verify_ephemeral_key(@$_POST['key']))
  {
    array_push($page['errors'], 'Invalid/expired form key');
  }
  else
  {
    // get user infos
    $query = '
SELECT
    '.$conf['user_fields']['email'].' as email,
    '.$conf['user_fields']['username'].' as username,
    nb_rows
  FROM '.USERS_TABLE.' as u
    INNER JOIN '.USER_INFOS_TABLE.' as i
    ON i.user_id = u.'.$conf['user_fields']['id'].'
  WHERE '.$conf['user_fields']['id'].' = '.mres($_POST['user_id']).'
;';
    $to = mysql_fetch_assoc(mysql_query($query));

    // mail contents
    $current_url = get_absolute_home_url().get_url_string(array('file'=>$page['file']));

    $subject = '['.strip_tags($conf['install_name']).'] '.$user['username'].' notifies you about the translation of '.get_section_name($page['section']).' in '.$page['language'];

    $content = '
Hi '.$to['username'].',<br>
You receive this mail because you are registered as translator on <a href="'.get_absolute_home_url().'">'.strip_tags($conf['install_name']).'</a>.<br>
<br>
'.$user['username'].' notifies you about the translation of <b>'.get_section_name($page['section']).'</b> in <b>'.$page['language'].'</b> :<br>
<a href="'.$current_url.'">'.$current_url.'</a><br>
<br>';
    if (!empty($_POST['message']))
    {
      $content.= '
<hr>
<h4>Message :</h4>
'.nl2br($_POST['message']);
    }

    // add missing translations
    if (isset($_POST['send_rows']))
    {
      $nb_rows = !empty($_POST['nb_rows']) && is_int($_POST['nb_rows']) ? $_POST['nb_rows'] : $to['nb_rows'];
      
      if ($page['mode'] == 'array')
      {
        $_DIFFS = array();
        $i = 1;
        foreach ($_LANG_default as $key => $row)
        {
          if ($i > $nb_rows) break;
          
          if ( !isset($_LANG[$key]) and !isset($_LANG_db[$key]) )
          {
            $_DIFFS[] = $row['row_value'];
            $i++;
          }
        }
      }
      else
      {
        $_DIFFS[] = $_LANG_default['row_value'];
      }

      if (count($_DIFFS))
      {
        $content.= '
<hr>
<h4>Missing translations :</h4>'; 
        foreach ($_DIFFS as $row)
        {
          $content.= "\n".'<pre>'.$row.'</pre><br>';
        }
      }
    }
    
    // send mail
    $args = array(
      'from' => $user['username'].' <'.$user['email'].'>',
      'content_format' => 'text/html',
      );

    if (send_mail($to['email'], $subject, $content, $args))
    {
      array_push($page['infos'], 'Mail sended to <i>'.$to['username'].'</i>');
    }
    else
    {
      array_push($page['errors'], 'An error occurred');
    }
  }
}


// +-----------------------------------------------------------------------+
// |                         PAGE CONTENTS
// +-----------------------------------------------------------------------+
// only translators and admin can modify files, no one is translator for source language
$is_translator = true;
if (is_source_language($page['language']))
{
  $is_translator = false;
  array_push($page['warnings'], 'The source language can\'t be modified.');
}
else if (!is_translator($page['language'], $page['section']))
{
  $is_translator = false;
  if (is_visitor())
  {
    array_push($page['errors'], 'You don\'t have the necessary rights to edit this file.');
  }
  else
  {
    array_push($page['warnings'], 'You <a href="user.php?login">have to login</a> to edit this translation.');
  }
}

// tabsheet
$tabsheet['param'] = 'file';
$tabsheet['selected'] = $page['file'];
foreach ($page['files'] as $file)
{
  $tabsheet['tabs'][$file] = array(basename($file), "Edit the file '".$file."'", array('page','ks'));
}

// path
$page['caption'].= '
<a href="'.get_url_string(array('section'=>$page['section']), 'all', 'section').'">'.get_section_name($page['section']).'</a> &raquo; 
<a href="'.get_url_string(array('language'=>$page['language']), 'all', 'language').'">'.get_language_flag($page['language']).' '.get_language_name($page['language']).'</a>
'.($is_translator ? '<a class="floating_link notification" style="cursor:pointer;">Send a notification</a> <span class="floating_link">&nbsp;|&nbsp;</span>' : null);

// MAIN PROCESS
include(PATH.'include/edit.'.$page['mode'].'.php');

// notification popup
if ($is_translator)
{
  // search users that can receive notifications (status, persmissions, preferences)
  $query = '
SELECT 
    '.$conf['user_fields']['id'].' as user_id,
    '.$conf['user_fields']['username'].' as username,
    i.status,
    i.nb_rows
  FROM '.USERS_TABLE.' as u
  INNER JOIN '.USER_INFOS_TABLE.' as i
    ON u.'.$conf['user_fields']['id'].' = i.user_id
  WHERE
    i.status = "admin" OR (
      i.sections LIKE "%'.$page['section'].'%"
      AND i.languages LIKE "%'.$page['language'].'%"
    )
    AND i.status != "guest"
    '.(!is_admin() ? 'AND i.email_privacy != "private"' : null).'
 ;';
  $users = hash_from_query($query);

  $page['caption'].= '
<div id="dialog-form" title="Send a notification by mail" style="display:none;">
	<div class="ui-state-highlight" style="padding: 0.7em;margin-bottom:10px;">
    <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
    You can only send mails to an admin or a translator of this language/section.
  </div>
  <form action="" method="post">
    <table class="login" style="text-align:left;margin:0 auto;">
      <tr><td>
        Send to :
        <select name="user_id">
          <option value="-1" alt="15">---------</option>';
        foreach ($users as $row)
        {
          $page['caption'].= '
          <option value="'.$row['user_id'].'" alt="'.$row['nb_rows'].'">'.$row['username'].($row['status']=='admin' ? ' (admin)' : null).'</option>';
        }
        $page['caption'].= '
        </select>
      </td></tr>
      <tr><td>';
      if ($page['mode'] == 'array')
      {
        $page['caption'].= '
        <input type="checkbox" name="send_rows" value="1"> include <input type="text" name="nb_rows" size=2" maxlength="3" value="15"> first missing rows of current file in the mail';
      }
      else
      {
        $page['caption'].= '
        <label><input type="checkbox" name="send_rows" value="1"> include the contents of current file in the mail</label>';
      }
      $page['caption'].= '
      </td></tr>
      <tr><td>
        <textarea name="message" rows="6" cols="50"></textarea>
        <input type="hidden" name="key" value="'.get_ephemeral_key(3).'">
        <input type="hidden" name="send_notification" value="1">
      </td></tr>
    </table>
  </form>
</div>';
}


// +-----------------------------------------------------------------------+
// |                         SCRIPTS
// +-----------------------------------------------------------------------+
if ($is_translator)
{
  $page['script'].= '
  // notification popup
  $("#dialog-form").dialog({
    autoOpen: false, modal: true, resizable: false,
    height: 320, width: 520,
    show: "clip", hide: "clip",
    buttons: {
      "Send": function() { $("#dialog-form form").submit(); },
      Cancel: function() { $(this).dialog("close"); }
    }
  });
  $(".notification").click(function() {
    $("#dialog-form").dialog("open");
  });
  $("select[name=\'user_id\']").change(function() {
    $("input[name=\'nb_rows\']").val($(this).children("option:selected").attr("alt"));
  });';
}
else
{
  $page['script'].= '
  $("textarea").prop("disabled", true);';
}

// Can't use autoResize plugin with too many textarea (browser crashes) and incompatible with highlightTextarea
if ( count($_DIFFS) <= 30 and !isset($block_autoresize) )
{
  $page['header'].= '
  <script type="text/javascript" src="template/js/jquery.autoresize.min.js"></script>';
  
  $page['script'].= '
  $("#diffs textarea").autoResize({
    maxHeight:2000,
    extraSpace:11
  });';
}

print_page();
?>