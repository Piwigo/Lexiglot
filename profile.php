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

define('LEXIGLOT_PATH', './');
include(LEXIGLOT_PATH . 'include/common.inc.php');

if ( ( isset($_POST['send_message']) or isset($_POST['save_profile']) ) and !verify_ephemeral_key(@$_POST['key']) )
{
  array_push($page['errors'], 'Invalid/expired form key. <a href="javascript:history.back();">Go Back</a>.');
  $template->close('messages');
}


// +-----------------------------------------------------------------------+
// |                         SEND MESSAGE
// +-----------------------------------------------------------------------+
if (isset($_POST['send_message']))
{ 
  if (strlen($_POST['subject']) < 10) array_push($page['errors'], 'Subject is too short');
  if (strlen($_POST['message']) < 10) array_push($page['errors'], 'Message is too short');
  if (strlen($_POST['message']) > 2000) array_push($page['errors'], 'Message is too long. max: 2000 chars');
  
  if (empty($page['errors']))
  {
    // get user mail
    $query = '
SELECT
  '.$conf['user_fields']['email'].' as email,
  '.$conf['user_fields']['username'].' as username
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['id'].' = '.mres($_POST['user_id']).'
;';
    $to = $db->query($query)->fetch_assoc();

    $content = 
$user['username'].' from '.strip_tags($conf['install_name']).' has sent you a message. You can reply to him by replying to this e-mail.

The message reads as follows:
-----------------------------------------------------------------------

'.$_POST['message'].'

-----------------------------------------------------------------------

Lexiglot - A PHP based translation tool';

    $args = array(
      'from' => format_email($user['email'], $user['username']),
    );

    if (send_mail($to['email'], $_POST['subject'], $content, $args, 'Private message'))
    {
      array_push($page['infos'], 'Mail sended to <i>'.$to['username'].'</i>');
      unset($_POST);
    }
    else
    {
      array_push($page['errors'], 'An error occured');
    }
  }
}

// +-----------------------------------------------------------------------+
// |                         SAVE PROFILE
// +-----------------------------------------------------------------------+
else if (isset($_POST['save_profile']))
{
  $sets_user = $sets_infos = array();
  
  if ($conf['allow_profile'])
  {
    // save mail
    $mail_error = validate_mail_address($_POST['email'], $user['id'], true);
    if ($mail_error !== true)
    {
      array_push($page['errors'], $mail_error);
    }
    else
    {
      array_push($sets_user, $conf['user_fields']['email'].' = "'.mres($_POST['email']).'"');
      $user['email'] = $_POST['email'];
    }
    // save password
    if (!empty($_POST['password_new']))
    {
      if (empty($_POST['password']) or $conf['pass_convert']($_POST['password']) != $user['password'])
      {
        array_push($page['errors'], 'Please enter your current password, password not changed.');
      }
      else if ($_POST['password_new'] != $_POST['password_confirm'])
      {
        array_push($page['errors'], 'Please confirm your new password, password not changed.');
      }
      else
      {
        array_push($sets_user, $conf['user_fields']['password'].' = "'.$conf['pass_convert']($_POST['password_new']).'"');
      }
    }
  }
  
  // save my languages
  $query = '
DELETE FROM '.USER_LANGUAGES_TABLE.'
  WHERE
    user_id = '.$user['id'].'
    AND type = "my"
;';
  $db->query($query);
  
  if (!empty($_POST['my_languages']))
  {
    $inserts = array();
    foreach ($_POST['my_languages'] as $l)
    {
      array_push($inserts, array('user_id'=>$user['id'], 'language'=>$l, 'type'=>'my'));
    }
    
    mass_inserts(
      USER_LANGUAGES_TABLE,
      array('user_id','language','type'),
      $inserts
      );
    
    $user['my_languages'] = $_POST['my_languages'];
  }
  else
  {
    $user['my_languages'] = array();
  }
  
  // save number of rows
  if ( empty($_POST['nb_rows']) or !preg_match('#^[0-9]+$#', $_POST['nb_rows']) )
  {
    array_push($page['errors'], 'The number of rows rows must be a non-null integer.');
  }
  else
  {
    array_push($sets_infos, 'nb_rows = '.$_POST['nb_rows']);
    $user['nb_rows'] = $_POST['nb_rows'];
  }
  // save email privacy
  array_push($sets_infos, 'email_privacy = "'.$_POST['email_privacy'].'"');
  
  // write in db
  if (!empty($sets_user))
  {
    $query = '
UPDATE '.USERS_TABLE.'
  SET
    '.implode(",    \n", $sets_user).'
  WHERE '.$conf['user_fields']['id'].' = '.$user['id'].'
;';
    $db->query($query);
  }
  if (!empty($sets_infos))
  {
    $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET
    '.implode(",    \n", $sets_infos).'
  WHERE user_id = '.$user['id'].'
;';
    $db->query($query);
  }
  
  // reload the profile
  $user = build_user($user['id']);
  unset($_GET['new']);
  array_push($page['infos'], 'Profile saved.');
}


// +-----------------------------------------------------------------------+
// |                         PROFILE PAGE
// +-----------------------------------------------------------------------+
if ( isset($_GET['user_id']) )
{
  if ($_GET['user_id'] == $conf['guest_id'])
  {
    $_GET['user_id'] = 0;
  }
  
  $local_user = build_user($_GET['user_id']);
  
  if (!$local_user)
  {
    array_push($page['errors'], 'Wrong user id! <a href="javascript:history.back();">Go Back</a>.');
    $template->close('messages');
  }
  else
  {
    $template->assign(array(
      'WINDOW_TITLE' => $local_user['username'],
      'PAGE_TITLE' => $local_user['username'],
      ));
  }
}
else if (!is_guest())
{
  $local_user = $user;
  define('PRIVATE_PROFILE', true);
  
  $template->assign(array(
      'WINDOW_TITLE' => 'Profile',
      'PAGE_TITLE' => 'Profile',
      'IS_PRIVATE' => true,
      ));

  if (isset($_GET['new']))
  {
    array_push($page['infos'], '<b>Welcome on '.strip_tags($conf['install_name']).' !</b> Your registration is almost complete...<br>
      Before all please say us what languages you can speak, this way your registration as translator will be faster.
      ');
    $template->assign('IS_NEW', true);
  }
}
else
{
  redirect('index.php');
}


/* public profile */
foreach ($local_user['languages'] as $lang)
{
  $template->append('languages', array(
    'NAME' => get_language_name($lang),
    'FLAG' => get_language_flag($lang, 'name'),
    'URL' => get_url_string(array('language'=>$lang), true, 'language'),
    ));
}

foreach (array_diff($local_user['projects'], $local_user['manage_projects']) as $project)
{
  $template->append('projects', array(
    'NAME' => get_project_name($project),
    'URL' => get_url_string(array('project'=>$project), true, 'project'),
    ));
}

foreach ($local_user['manage_projects'] as $project)
{
  $template->append('manage_projects', array(
    'NAME' => get_project_name($project),
    'URL' => get_url_string(array('project'=>$project), true, 'project'),
    ));
}

/* stats */
$query = '
SELECT 
    COUNT(*) AS total,
    LEFT(last_edit, 10) as day
  FROM '.ROWS_TABLE.' 
  WHERE user_id = '.$local_user['id'].'
  GROUP BY day
  ORDER BY last_edit ASC
;';
$plot = hash_from_query($query);

if (count($plot) > 0)
{    
  $json = array();
  foreach ($plot as $row)
  {
    list($year, $month, $day) = explode('-', $row['day']);
    if (!isset($json[0])) $json[0] = null;
    $json[0].= '['.mktime(0, 0, 0, $month, $day, $year).'000, '.$row['total'].'],';
  }
  
  // version displaying separated languages
  // $json = array();
  // foreach ($plot as $row)
  // {
    // list($year, $month, $day) = explode('-', $row['day']);
    // if (!isset($json[ $row['language'] ])) $json[ $row['language'] ] = null;
    // $json[ $row['language'] ].= '['.mktime(0, 0, 0, $month, $day, $year).'000, '.$row['total'].'],';
  // }
  
  $template->assign('stats', $json);
}

/*$query = '
SELECT 
    language,
    project, 
    LEFT(last_edit, 10) as date,
    COUNT(CONCAT(language, project, LEFT(last_edit, 10))) as count
  FROM '.ROWS_TABLE.'
  WHERE 
    user_id = '.$local_user['id'].'
  GROUP BY count
  ORDER BY last_edit DESC
  LIMIT 0,10
;';
$recent = hash_from_query($query, null);

echo '
<fieldset class="common">
  <legend>Activity</legend>
  <table class="common">';
    foreach ($recent as $row)
    {
      echo '
      <tr>
        <td>'.format_date($row['date'],0,0).'</td>
        <td><b>'.$row['count'].'</b> string(s)</td>
        <td><i>'.get_project_name($row['project']).'</i></td>
        <td><i>'.get_language_name($row['language']).'</i></td>
      </tr>';
    }
    if (!count($recent))
    {
      echo '
      <tr><td>No recent activity</td></tr>';
    }
  echo '
  </table>
</fieldset>';*/
    
/* contact */
if ( 
  !defined('PRIVATE_PROFILE') and 
  (
    is_admin() or 
    ( !is_guest() and $local_user['email_privacy'] != 'private' ) 
  )
)
{
  $template->assign('contact', array(
    'SUBJECT' => @$_POST['subject'],
    'CONTENT' => @$_POST['message'],
    ));
}


$template->assign(array(
  'all_languages' => simple_hash_from_array($conf['all_languages'], 'id', 'name'),
  'user' => $local_user,
  'KEY' => get_ephemeral_key(2),
  ));


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('profile');

?>