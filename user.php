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

if ( (isset($_POST['login']) or isset($_POST['reset_password']) or isset($_POST['register']) ) and !verify_ephemeral_key(@$_POST['key']) )
{
  array_push($page['errors'], 'Invalid/expired form key');
  print_page();
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
if (isset($_POST['reset_password']))
{
  // search user_id
  $query = '
SELECT '.$conf['user_fields']['id'].'
  FROM '.USERS_TABLE.'
  WHERE
    '.$conf['user_fields']['username'].' = "'.mres($_POST['username']).'"
    AND '.$conf['user_fields']['email'].' = "'.mres($_POST['email']).'"
;';
  $result = mysql_query($query);
  
  if (!mysql_num_rows($result))
  {
    array_push($page['errors'], 'Wrong username or email.');
  }
  else
  {
    // generate a new password
    list($user_id) = mysql_fetch_row($result);
    $new_password = hash('crc32', uniqid($user_id.$_POST['username'], true));
    
    $query = '
UPDATE '.USERS_TABLE.'
  SET '.$conf['user_fields']['password'].' = "'.$conf['pass_convert']($new_password).'"
  WHERE '.$conf['user_fields']['id'].' = '.$user_id.'
;';
    mysql_query($query);
    
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
if (isset($_POST['register']))
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
  $page['window_title'] = $page['title'] = 'Login';
  $referer = isset($_POST['referer']) ? $_POST['referer'] : ( isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : get_home_url() );
  
  echo '
  <form action="" method="post">
    <table class="login">
      <tr>
        <td><label for="username">Username :</label></td>
        <td><input type="text" name="username" id="username" size="25" maxlength="32" '.(isset($_POST['username']) ? 'value="'.$_POST['username'].'"':'').'></td>
      </tr>
      <tr>
        <td><label for="password">Password :</label></td>
        <td><input type="password" name="password" id="password" size="25" maxlength="32"></td>
      </tr>
      <tr>
        <td><label for="remember_me">Remember me :</label></td>
        <td><input type="checkbox" name="remember_me" id="remember_me" value="1" checked="checked"></td>
      </tr>
      <tr>
        <td><input type="hidden" name="key" value="'.get_ephemeral_key(0).'"></td>
        <td>
          <input type="hidden" value="'.$referer.'" name="referer">
          <input type="submit" name="login" value="Login" class="blue"> <a href="user.php?password" rel="nofollow">Lost your password?</a>
        </td>
      </tr>
    </table>

  </form>';
}

// +-----------------------------------------------------------------------+
// |                         PASSWORD FORM
// +-----------------------------------------------------------------------+
else if (isset($_GET['password'])) 
{
  echo '
  <form action="" method="post">
    <p class="caption">Password reset</p>

    <table class="login">
      <tr>
        <td><label for="username">Username :</label></td>
        <td><input type="text" name="username" id="username" size="25" maxlength="32" '.(isset($_POST['username']) ? 'value="'.$_POST['username'].'"':'').'></td>
      </tr>
      <tr>
        <td><label for="email">Email address :</label></td>
        <td><input type="text" name="email" id="email" size="25" maxlength="64" '.(isset($_POST['email']) ? 'value="'.$_POST['email'].'"':'').'></td>
      </tr>
      <tr>
        <td><input type="hidden" name="key" value="'.get_ephemeral_key(2).'"></td>
        <td>
          <input type="submit" name="reset_password" value="Submit" class="blue">
          <span class="red">All fields are required</span>
        </td>
      </tr>
    </table>

  </form>';
}

// +-----------------------------------------------------------------------+
// |                         REGISTER FORM
// +-----------------------------------------------------------------------+
else if ( isset($_GET['register']) and $conf['allow_registration'] ) 
{
  $page['window_title'] = $page['title'] = 'Register';
  
  echo '
  <form action="" method="post">    
    <table class="login">
      <tr>
        <td><label for="username">Username :</label></td>
        <td><input type="text" name="username" id="username" size="25" maxlength="32" '.(isset($_POST['username']) ? 'value="'.$_POST['username'].'"':'').'></td>
      </tr>
      <tr>
        <td><label for="password">Password :</label></td>
        <td><input type="password" name="password" id="password" size="25" maxlength="32"></td>
      </tr>
      <tr>
        <td><label for="email">Email address :</label></td>
        <td><input type="text" name="email" id="email" size="25" maxlength="64" '.(isset($_POST['email']) ? 'value="'.$_POST['email'].'"':'').'></td>
      </tr>
      <tr>
        <td><input type="hidden" name="key" value="'.get_ephemeral_key(2).'"></td>
        <td>
          <input type="submit" name="register" value="Submit"  class="blue">
          <span class="red">All fields are required</span>
        </td>
      </tr>
    </table>

  </form>';

}
else
{
  redirect('index.php');
}

$page['script'].= '
$("input[type=\'text\']:first", document.forms[0]).focus();';

print_page();
?>