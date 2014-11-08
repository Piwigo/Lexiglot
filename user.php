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

if ( ( isset($_POST['login']) or isset($_POST['reset_password']) or isset($_POST['register']) ) and !verify_ephemeral_key(@$_POST['key']) )
{
  array_push($page['errors'], 'Invalid/expired form key. <a href="javascript:history.back();">Go Back</a>.');
  $template->close('messages');
}


// +-----------------------------------------------------------------------+
// |                         PERFORM LOGIN
// +-----------------------------------------------------------------------+
if (isset($_POST['login']))
{
  if (!isset($_COOKIE[session_name()]))
  {
    array_push($page['errors'], 'Cookies are blocked or not supported by your browser. You must enable cookies to connect.');
  }
  else
  { 
    if ( try_log_user($_POST['username'], $_POST['password'], !empty($_POST['remember_me'])) )
    {
      redirect($_POST['referer']);
    }
    else
    {
      array_push($page['errors'], 'Invalid password.');
    }
  }
}

// +-----------------------------------------------------------------------+
// |                        RESET PASSWORD
// +-----------------------------------------------------------------------+
else if (isset($_POST['reset_password']))
{
  // search user_id
  $query = '
SELECT '.$conf['user_fields']['id'].'
  FROM '.USERS_TABLE.'
  WHERE
    '.$conf['user_fields']['username'].' = "'.mres($_POST['username']).'"
    AND '.$conf['user_fields']['email'].' = "'.mres($_POST['email']).'"
;';
  $result = $db->query($query);
  
  if (!$result->num_rows)
  {
    array_push($page['errors'], 'Wrong username and/or email.');
  }
  else
  {
    // generate a new password
    list($user_id) = $result->fetch_row();
    $new_password = hash('crc32', uniqid($user_id.$_POST['username'], true));
    
    $query = '
UPDATE '.USERS_TABLE.'
  SET '.$conf['user_fields']['password'].' = "'.$conf['pass_convert']($new_password).'"
  WHERE '.$conf['user_fields']['id'].' = '.$user_id.'
;';
    $db->query($query);
    
    // send mail
    $subject = '['.strip_tags($conf['install_name']).'] Password reset';
    
    $content = '
Hi '.$_POST['username'].'<br>
You asked for reset your password on <a href="'.get_absolute_home_url().'">'.strip_tags($conf['install_name']).'</a>.<br>
<br>
Here is your new password : '.$new_password.'<br>
<br>
Modifications take effect immediately.<br>
This is an automated email, please do not answer !';

    $args = array(
      'content_format' => 'text/html',
      );
    
    if (send_mail($_POST['email'], $subject, $content, $args))
    {
      array_push($page['infos'], 'A new password has been sended to your email adress, once logged you can modify it from your profile page.');
    }
    else
    {
      array_push($page['errors'], 'An error occurred');
    }
    
    unset($_POST, $_GET);
    $_GET['login'] = true;
  }
}

// +-----------------------------------------------------------------------+
// |                         PERFORM REGISTER
// +-----------------------------------------------------------------------+
else if (isset($_POST['register']))
{
  $page['errors'] = register_user(
    $_POST['username'],
    $_POST['password'],
    $_POST['email']
    );

  if (count($page['errors']) == 0)
  {
    $user_id = get_userid($_POST['username']);
    log_user($user_id, false);
    redirect('profile.php?new');
  }
}


// +-----------------------------------------------------------------------+
// |                         LOGIN FORM
// +-----------------------------------------------------------------------+
if (isset($_GET['login'])) 
{
  $hooks->do_action('before_login');
  
  $template->assign(array(
    'IN_LOGIN' => true,
    'WINDOW_TITLE' => 'Login',
    'REFERER' => isset($_POST['referer']) ? $_POST['referer'] : ( isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : get_home_url() ),
    'KEY' => get_ephemeral_key(0),
    'user' => array(
      'USERNAME' => @$_POST['username'],
      'REMEMBER' => isset($_POST['username']) ? !empty($_POST['remember_me']) : true,
      ),
    ));
    
  $hooks->do_action('after_login');
}

// +-----------------------------------------------------------------------+
// |                         PASSWORD FORM
// +-----------------------------------------------------------------------+
else if (isset($_GET['password'])) 
{
  $hooks->do_action('before_password');
  
  $template->assign(array(
    'IN_PASSWORD' => true,
    'WINDOW_TITLE' => 'Password reset',
    'KEY' => get_ephemeral_key(2),
    'user' => array(
      'USERNAME' => @$_POST['username'],
      'EMAIL' => @$_POST['email'],
      ),
    ));
    
  $hooks->do_action('after_password');
}

// +-----------------------------------------------------------------------+
// |                         REGISTER FORM
// +-----------------------------------------------------------------------+
else if ( isset($_GET['register']) and $conf['allow_registration'] ) 
{
  $hooks->do_action('before_register');
  
  $template->assign(array(
    'IN_REGISTER' => true,
    'WINDOW_TITLE' => 'Register',
    'KEY' => get_ephemeral_key(2),
    'user' => array(
      'USERNAME' => @$_POST['username'],
      'EMAIL' => @$_POST['email'],
      ),
    ));
    
  $hooks->do_action('after_register');
}
else
{
  redirect('index.php');
}


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('user');

?>