<?php
// +-----------------------------------------------------------------------+
// | Lexiglot - A PHP based translation tool                               |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2011-2013 Damien Sorel       http://www.strangeplanet.fr |
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

// +-----------------------------------------------------------------------+
// |                         PAGE OPTIONS 1 (mandatory)
// +-----------------------------------------------------------------------+
// language
if ( !isset($_GET['language']) or !array_key_exists($_GET['language'], $conf['all_languages']) )
{
  array_push($page['errors'], 'Undefined or unknown language. <a href="javascript:history.back();">Go Back</a>.');
  $template->close('messages');
}
// project
if ( !isset($_GET['project']) or !array_key_exists($_GET['project'], $conf['all_projects']) )
{
  array_push($page['errors'], 'Undefined or unknown project. <a href="javascript:history.back();">Go Back</a>.');
  $template->close('messages');
}

$page['language'] = $_GET['language'];
$page['project'] = $_GET['project'];
$page['files'] = explode(',', $conf['all_projects'][ $page['project'] ]['files']);


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
if ( isset($_GET['display']) and in_array($_GET['display'], array('all','missing','translated')) )
{
  $page['display'] = $_GET['display'];
}
else
{
  $page['display'] = ( is_guest() || is_visitor() || is_default_language($page['language']) ) ? 'all' : 'missing';
}
// reference
if ( 
  isset($_GET['ref']) 
  and array_key_exists($_GET['ref'], $conf['all_languages']) 
  and !is_default_language($page['language']) 
  and $page['language']!=$_GET['ref']
) {
  $page['ref'] = $_GET['ref'];
}
else
{
  $page['ref'] = get_language_ref($page['language']);
}

$page['file_uri'] = $conf['local_dir'].$page['project'].'/'.$page['language'].'/'.$page['file'];

// title
$template->assign(array(
  'WINDOW_TITLE' => get_project_name($page['project']).' &raquo; '.get_language_name($page['language']),
  'PAGE_TITLE' => 'Edit',
  'LANGUAGE' => $page['language'],
  'PROJECT' => $page['project'],
  'MODE' => $page['mode'],
  'FILE' => $page['file'],
  'SECRET_KEY' => get_ephemeral_key(0),
  ));


// +-----------------------------------------------------------------------+
// |                         LOAD ROWS
// +-----------------------------------------------------------------------+
if (!file_exists($conf['local_dir'].$page['project'].'/'.$page['language']))
{
  array_push($page['errors'], 'This language doesn\'t exist in this project, please create it throught the <a href="'.get_url_string(array('project'=>$page['project']), true, 'project').'">project page</a>.');
  $template->close('messages');
}

if (!file_exists($conf['local_dir'].$page['project'].'/'.$conf['default_language'].'/'.$page['file']))
{
  array_push($page['errors'], 'Can\'t find this file for default language. <a href="javascript:history.back();">Go Back</a>.');
  $template->close('messages');
}

// for php files we check the validity of the file
if ( $page['mode'] == 'array' and ($fileinfos = verify_language_file($page['file_uri'])) !== true )
{
  notify_language_file_error($page['file_uri'], $fileinfos);
  
  if ($fileinfos[0] == 'Parse error')
  {
    array_push($page['errors'], 'The language file is corrupted. Administrators have been notified. <a href="javascript:history.back();">Go back</a>.');
    $template->close('messages');
  }
}

$_LANG_default = load_language($page['project'], $page['ref'], $page['file']);
$_LANG =         load_language($page['project'], $page['language'], $page['file']);


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
    u.'.$conf['user_fields']['email'].' as email,
    u.'.$conf['user_fields']['username'].' as username,
    i.nb_rows
  FROM '.USERS_TABLE.' as u
    INNER JOIN '.USER_INFOS_TABLE.' as i
    ON i.user_id = u.'.$conf['user_fields']['id'].'
  WHERE u.'.$conf['user_fields']['id'].' = '.mres($_POST['user_id']).'
;';
    $to = $db->query($query)->fetch_assoc();

    // mail contents
    $current_url = get_absolute_home_url().get_url_string(array('file'=>$page['file']));

    $subject = '['.strip_tags($conf['install_name']).'] '.$user['username'].' notifies you about the translation of '.get_project_name($page['project']).' in '.get_language_name($page['language']);

    $content = '
Hi '.$to['username'].',<br>
You receive this mail because you are registered as translator on <a href="'.get_absolute_home_url().'">'.strip_tags($conf['install_name']).'</a>.<br>
<br>
'.$user['username'].' notifies you about the translation of <b>'.get_project_name($page['project']).'</b> in <b>'.get_language_name($page['language']).'</b> :<br>
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
      $nb_rows = ( !empty($_POST['nb_rows']) && is_int($_POST['nb_rows']) ) ? $_POST['nb_rows'] : $to['nb_rows'];
      
      if ($page['mode'] == 'array')
      {
        $_DIFFS = array();
        $i = 1;
        foreach ($_LANG_default as $key => $row)
        {
          if ($i > $nb_rows) break;
          
          if (!isset($_LANG[$key]))
          {
            $_DIFFS[] = $row['row_value'];
            $i++;
          }
        }
      }
      else
      {
        $_DIFFS[] = $_LANG_default[ $page['file'] ]['row_value'];
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
      'from' => format_email($user['email'], $user['username']),
      'content_format' => 'text/html',
      );
    if (isset($_POST['notification']))
    {
      $args['notification'] = $user['email'];
    }
      
    $result = send_mail(
      format_email($to['email'], $to['username']),
      $subject, $content, $args, 
      'Notification on '.get_project_name($page['project']).' in '.get_language_name($page['language'])
      );

    if ($result)
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
// only translators and admin can modify files
$is_translator = true;
if ( is_default_language($page['language']) and !$conf['allow_edit_default'] )
{
  $is_translator = false;
  array_push($page['warnings'], 'The source language can\'t be modified.');
}
else if (!is_translator($page['language'], $page['project']))
{
  $is_translator = false;
  if (is_guest())
  {
    array_push($page['warnings'], 'You <a href="user.php?login">have to login</a> to edit this translation.');
  }
  else
  {
    array_push($page['errors'], 'You don\'t have the necessary rights to edit this file.');
  }
}
$template->assign('IS_TRANSLATOR', $is_translator);

// tabsheet
include_once(LEXIGLOT_PATH . 'include/tabsheet.inc.php');
$tabsheet = new Tabsheet('FILES', 'file');
foreach ($page['files'] as $file)
{
  $tabsheet->add($file, basename($file), "Edit the file '".$file."'", array('page'));
}
$tabsheet->select($page['file']);
$tabsheet->render(true);

// popu to reference file
if (!is_default_language($page['language']))
{
  $template->assign('REFERENCE_POPUP_LINK', js_popup(
      get_url_string(
        array(
          'project'=>$page['project'],
          'language'=>$conf['default_language'],
          'file'=>$page['file'],
          ),
        true,
        ($page['mode'] == 'array' ? 'simple_view' : 'simple_view_plain')
        ),
      'Reference page', 
      800, 650
    ));
}


// MAIN PROCESS
include(LEXIGLOT_PATH . 'include/edit.'.$page['mode'].'.php');


// notification popup
if ($is_translator)
{
  // search users that can receive notifications (status, persmissions, preferences)
  $where_clauses = array('( 
    i.status = "admin"
    OR ( 
      p.project = "'.$page['project'].'" 
      AND (( 
          l.language = "'.$page['language'].'" 
          AND l.type = "translate" )
        OR (
          p.type = "manage"
          AND i.status = "manager" )
      )))',
    'i.status != "guest"',
    );
  if (!is_admin()) array_push($where_clauses, 'i.email_privacy != "private"');
  
  $users = get_users_list($where_clauses, array('i.nb_rows', 'projects'));
  
  foreach ($users as $row)
  {
    $status = null;
    if ($row['status']=='admin') $status = ' (admin)';
    if (in_array($page['project'], $row['manage_projects'])) $status = ' (manager)';
    
    $template->append('notifications_users', array(
      'ID' => $row['id'],
      'USERNAME' => $row['username'],
      'STATUS' => $status,
      'NB_ROWS' => $row['nb_rows'],
      ));
  }
}


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('edit');

?>