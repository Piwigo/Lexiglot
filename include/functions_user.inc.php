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

/**
 * get user infos
 * @param int user_id
 * @return array
 */
function build_user($user_id)
{
  global $conf;

  $user['id'] = $user_id;
  
  // retrieve user data
  $query = '
SELECT '.get_db_user_fields().'
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['id'].' = '.$user_id.'
;';
  $result = mysql_query($query);
  
  if (!mysql_num_rows($result))
  {
    return false;
  }
  
  $user_row = mysql_fetch_assoc($result);
  
  // retrieve user infos
  $query = '
SELECT *
  FROM '.USER_INFOS_TABLE.'
  WHERE user_id = '.$user_id.'
;';
  $result = mysql_query($query);
  
  // the user came from an external base, we must register it
  if (!mysql_num_rows($result))
  {
    create_user_infos($user_id);
    
    // redo previous query
    $result = mysql_query($query);
    
    $user['is_new'] = true;
  }
  
  // beautify booleans
  $user_infos_row = mysql_fetch_assoc($result);
  foreach ( array_merge($user_row, $user_infos_row) as $key => $value )
  {
    if (!is_numeric($key))
    {
      if ($value == 'true' or $value == 'false')
        $user[$key] = get_boolean($value);
      else
        $user[$key] = $value;
    }
  }
  
  // if the user is visitor we must get guest permissions
  if ($user['status'] == 'visitor')
  {
    $query = '
SELECT *
  FROM '.USER_INFOS_TABLE.'
  WHERE user_id = '.$conf['guest_id'].'
;';
    $guest = mysql_fetch_assoc(mysql_query($query));
    
    $user['languages'] = $guest['languages'];
    $user['sections'] = $guest['sections'];
  }
  
  // explode languages array
  if (!empty($user['languages']))
  {
    $languages = explode_string($user['languages']);
    $user['languages'] = array();
    $user['main_language'] = null;
    foreach ($languages as $lang => $rank)
    {
      if ($rank == 1) $user['main_language'] = $lang;
      array_push($user['languages'], $lang);
    }
  }
  else
  {
    $user['languages'] = array();
    $user['main_language'] = null;
  }

  $user['my_languages'] = !empty($user['my_languages']) ? explode(',', $user['my_languages']) : array();
  
  // explode sections array
  if (!empty($user['sections']))
  {
    $sections = explode_string($user['sections']);
    $user['sections'] = array();
    $user['manage_sections'] = array();
    foreach ($sections as $section => $rank)
    {
      if ($rank == 1) array_push($user['manage_sections'], $section);
      array_push($user['sections'], $section);
    }
  }
  else
  {
    $user['sections'] = array();
    $user['manage_sections'] = array();
  }
  
  // if the user is manager we must fill management permissions
  if ($user['status'] == 'manager')
  {
    if (!empty($user['manage_perms']))
    {
      $user['manage_perms'] = unserialize($user['manage_perms']);
    }
    else
    {
      $user['manage_perms'] = unserialize($conf['default_manager_perms']);
    }
  }
  else
  {
    $user['manage_sections'] = array();
  }

  return $user;
}

/**
 * create row in user_infos_table
 * @param: int user_id
 * @param: string status
 */
function create_user_infos($user_id, $status='visitor')
{
  $query = '
INSERT IGNORE INTO '.USER_INFOS_TABLE.'(
  user_id,
  status,
  registration_date
  )
VALUES(
  "'.$user_id.'",
  "'.$status.'",
  NOW()
  )
;';
  mysql_query($query);
}

/**
 * get all users infos, same as build_user but for all users
 * @param array where_clauses
 * @param string fields to select into USER_INFOS_TABLE
 * @param string where_clauses mode
 * @param int offset
 * @param int limit
 * @return array
 */
function get_users_list($where_clauses=array('1=1'), $select='i.*', $mode='AND', $offset=0, $limit=9999999999999)
{
  global $conf;
  
  $query = '
SELECT 
    i.status, 
    '.(!empty($select)?$select.',':null).'
    '.get_db_user_fields().'
  FROM '.USERS_TABLE.' AS u
    INNER JOIN '.USER_INFOS_TABLE.' AS i
      ON u.'.$conf['user_fields']['id'].'  = i.user_id
  WHERE 
    '.implode("\n    ".$mode." ", $where_clauses).'
  ORDER BY u.'.$conf['user_fields']['username'].' ASC
  LIMIT '.$limit.'
  OFFSET '.$offset.'
;';
  $users = hash_from_query($query, 'id');
  
  foreach ($users as &$user)
  {
    // beautify booleans
    foreach ($user as $key => $value)
    {
      if (!is_numeric($key))
      {
        if ($value == 'true' or $value == 'false')
          $user[$key] = get_boolean($value);
      }
      else
      {
        unset($user[$key]);
      }
    }
  
    // if the user is visitor we must get guest permissions
    /*if ( $user['status'] == 'visitor' and isset($users[ $conf['guest_id'] ]) )
    {
      $user['languages'] = $users[ $conf['guest_id'] ]['languages'];
      $user['sections'] =  $users[ $conf['guest_id'] ]['sections'];
    }*/
  
    // explode languages array
    if (!empty($user['languages']))
    {
      $languages = explode_string($user['languages']);
      $user['languages'] = array();
      $user['main_language'] = null;
      foreach ($languages as $lang => $rank)
      {
        if ($rank == 1) $user['main_language'] = $lang;
        array_push($user['languages'], $lang);
      }
    }
    else
    {
      $user['languages'] = array();
      $user['main_language'] = null;
    }

    $user['my_languages'] = !empty($user['my_languages']) ? explode(',', $user['my_languages']) : array();
    
    // explode sections array
    if (!empty($user['sections']))
    {
      $sections = explode_string($user['sections']);
      $user['sections'] = array();
      $user['manage_sections'] = array();
      foreach ($sections as $section => $rank)
      {
        if ($rank == 1) array_push($user['manage_sections'], $section);
        array_push($user['sections'], $section);
      }
    }
    else
    {
      $user['sections'] = array();
      $user['manage_sections'] = array();
    }
    
    // if the user is manager we must fill management permissions
    if ( $user['status'] == 'manager' and isset($user['manage_perms']) )
    {
      if (!empty($user['manage_perms']))
      {
        $user['manage_perms'] = unserialize($user['manage_perms']);
      }
      else
      {
        $user['manage_perms'] = unserialize($conf['default_manager_perms']);
      }
    }
    else if ($user['status'] != 'manager')
    {
      $user['manage_sections'] = array();
    }
  }

  return $users;
}

/**
 * generates SELECT for user fields
 */
function get_db_user_fields()
{
  global $conf;
  
  $is_first = true;
  $query = null;
  
  foreach ($conf['user_fields'] as $localfield => $dbfield)
  {
    if ($is_first) $is_first = false;
    else $query.= ', ';
    $query.= $dbfield.' AS '.$localfield;
  }
  
  return $query;
}

/**
 * performs auto-connexion when cookie remember_me exists
 * @return bool
 */
function auto_login() 
{
  global $conf;
  
  if ( isset($_COOKIE[ $conf['remember_me_name'] ]) )
  {
    $cookie = explode('-', stripslashes($_COOKIE[$conf['remember_me_name']]));
    if ( 
      count($cookie) === 3
      and is_numeric(@$cookie[0]) /*user id*/
      and is_numeric(@$cookie[1]) /*time*/
      and time()-$conf['remember_me_length'] <= @$cookie[1]
      and time() >= @$cookie[1] /*cookie generated in the past*/ )
    {
      $key = calculate_auto_login_key($cookie[0], $cookie[1]);
      if ( $key!==false and $key===$cookie[2] )
      {
        log_user($cookie[0], true);
        return true;
      }
    }
    setcookie($conf['remember_me_name'], '', 0);
  }
  return false;
}

/**
 * performs user login
 * @param int user_id
 * @param bool remember_me
 * @return void
 */
function log_user($user_id, $remember_me)
{
  global $conf, $user;

  if ($remember_me)
  {
    $now = time();
    $key = calculate_auto_login_key($user_id, $now);
    if ($key !== false)
    {
      $cookie = $user_id.'-'.$now.'-'.$key;
      setcookie($conf['remember_me_name'], $cookie, time()+$conf['remember_me_length']);
    }
  }
  else
  { // make sure we clean any remember me ...
    setcookie($conf['remember_me_name'], '', 0);
  }
  
  if (session_id() != "")
  { // we regenerate the session for security reasons
    // see http://www.acros.si/papers/session_fixation.pdf
    session_regenerate_id(true);
  }
  else
  {
    session_start();
  }
  
  $user['id'] = $_SESSION['uid'] = (int)$user_id;
}

/** 
 * performs all the cleanup on user logout 
 */
function logout_user()
{
  global $conf;
  $_SESSION = array();
  session_unset();
  session_destroy();
  setcookie(session_name(), '', 0);
  setcookie($conf['remember_me_name'], '', 0);
}

/**
 * tries to login a user given username and password
 * @return bool
 */
function try_log_user($username, $password, $remember_me)
{
  global $conf;
  
  // retrieving the encrypted password of the login submitted
  $query = '
SELECT 
    '.$conf['user_fields']['id'].' AS id,
    '.$conf['user_fields']['password'].' AS password
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['username'].' = "'.mres($username).'"
;';
  $row = mysql_fetch_assoc(mysql_query($query));
  
  if ($row['password'] == $conf['pass_convert']($password))
  {
    log_user($row['id'], $remember_me);
    return true;
  }
  
  return false;
}

/**
 * returns the auto login key or false on error
 * @param int user_id
 * @param time_t time
 */
function calculate_auto_login_key($user_id, $time)
{
  global $conf;
  
  $query = '
SELECT 
  '.$conf['user_fields']['username'].' AS username,
  '.$conf['user_fields']['password'].' AS password
FROM '.USERS_TABLE.'
WHERE '.$conf['user_fields']['id'].' = '.$user_id;
  $result = mysql_query($query);
  
  if (mysql_num_rows($result) > 0)
  {
    $row = mysql_fetch_assoc($result);
    $username = stripslashes($row['username']);
    $data = $time.$user_id.$username;
    $key = base64_encode( hash_hmac('sha1', $data, SALT_KEY.$row['password'], true) );
    return $key;
  }
  
  return false;
}

/**
 * returns user identifier thanks to his name, false if not found
 * @param string username
 * @param int user identifier
 */
function get_userid($username)
{
  global $conf;

  $query = '
SELECT '.$conf['user_fields']['id'].'
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['username'].' = "'.mres($username).'"
;';
  $result = mysql_query($query);

  if (mysql_num_rows($result) == 0)
  {
    return false;
  }
  else
  {
    list($user_id) = mysql_fetch_row($result);
    return $user_id;
  }
}

/**
 * returns user name thanks to his identifier, false if not found
 * @param int identifier
 * @param string username
 */
function get_username($id)
{
  global $conf;

  $query = '
SELECT '.$conf['user_fields']['username'].'
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['id'].' = "'.mres($id).'"
;';
  $result = mysql_query($query);

  if (mysql_num_rows($result) == 0)
  {
    return false;
  }
  else
  {
    list($username) = mysql_fetch_row($result);
    return $username;
  }
} 

/**
 * validate_mail_address
 * @param string mail_adress
 * @param int user_id
 * @param bool check if already exists
 * @return string message
 */
function validate_mail_address($mail_address, $user_id, $check_user=true)
{
  global $conf;

  $atom   = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';   // before arobase
  $domain = '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)'; // domain name
  $regex = '/^' . $atom . '+' . '(\.' . $atom . '+)*' . '@' . '(' . $domain . '{1,63}\.)+' . $domain . '{2,63}$/i';

  if ( !preg_match( $regex, $mail_address ) )
  {
    return 'Mail address must be like xxx@yyy.eee (example : jack@altern.org)';
  }

  if ( !empty($mail_address) and $check_user )
  {
    $query = '
SELECT count(*)
  FROM '.USERS_TABLE.'
  WHERE 
    UPPER('.$conf['user_fields']['email'].') = UPPER("'.$mail_address.'")
    '.(is_numeric($user_id) ? 'AND '.$conf['user_fields']['id'].' != "'.$user_id.'"' : '').'
;';
    
    list($count) = mysql_fetch_row(mysql_query($query));
    if ($count != 0)
    {
      return 'This email address is already in use';
    }
  }
  
  return true;
}

/**
 * register an user in the database
 * @param string username
 * @param string password
 * @param string mail_adress
 * @return array errors
 */
function register_user($login, $password, $mail_address)
{
  global $conf;
  
  $errors = array();
  if ($login == '')
  {
    array_push($errors, 'Please, enter a login');
  }
  if ( preg_match('/^.* $/', $login) or preg_match('/^ .*$/', $login) )
  {
    array_push($errors, 'Login mustn\'t begin or start with a space character');
  }
  if (get_userid($login))
  {
    array_push($errors, 'This login is already used');
  }
  if ($login != strip_tags($login))
  {
    array_push($errors, 'HTML tags are not allowed in login');
  }
  $mail_error = validate_mail_address($mail_address, null, true);
  if ($mail_error !== true)
  {
    array_push($errors, $mail_error);
  }

  // if no error until here, registration of the user
  if (count($errors) == 0)
  {
    // if used with a external users table we can specify additional fields to fill while add an user
    if (USERS_TABLE != DB_PREFIX.'users')
    {
      $insert_names = array_keys($conf['additional_user_infos']);
      $insert_values = array_values($conf['additional_user_infos']);
    }
    else
    {
      $insert_names = $insert_values = array();
    }
    array_push($insert_names, $conf['user_fields']['username'], $conf['user_fields']['password'], $conf['user_fields']['email']);
    array_push($insert_values, mres($login), $conf['pass_convert']($password), $mail_address);
  
    $query = '
INSERT INTO '.USERS_TABLE.'(
    '.implode(',', $insert_names).'
    )
  VALUES(
    "'.implode('","', $insert_values).'"
    )
;';
    mysql_query($query);
    
    create_user_infos(mysql_insert_id(), 'visitor');
  }

  return $errors;
}

/**
 * return admins emails
 * @return string
 */
function get_admin_email()
{
  global $conf;
  
  $query = '
SELECT
  '.$conf['user_fields']['email'].' as email,
  '.$conf['user_fields']['username'].' as username
  FROM '.USERS_TABLE.' as u
    INNER JOIN '.USER_INFOS_TABLE.' as i
      ON u.'.$conf['user_fields']['id'].' = i.user_id
  WHERE i.status = "admin"
;';
  $to = hash_from_query($query);
  
  array_walk($to, create_function('&$k,$v', '$k=format_email($k["email"],$k["username"]);'));

  return implode(',', $to);
}

/**
 * return user status
 * @return string
 */
function get_user_status($user_id=null)
{
  global $user;

  if ($user_id == null)
  {
    if (isset($user['status']))
    {
      return $user['status'];
    }
    else
    {
      return 'guest';
    }
  }
  else
  {
    $query = '
SELECT status
  FROM '.USER_INFOS_TABLE.'
  WHERE user_id = '.mres($user_id).'
;';
    list($status) = mysql_fetch_row(mysql_query($query));
    return $status;
  }
}

/**
 * search if current user is guest
 * @return bool
 */
function is_guest()
{
  return get_user_status() == 'guest';
}

/**
 * search if current user is a visitor (logged user)
 * @return bool
 */
function is_visitor()
{
  return get_user_status() == 'visitor';
}

/**
 * search if current user is translator
 *   - admins are always translators
 *   - managers have same rights of translators
 * @param string language
 * @param string section
 * @return bool
 */
function is_translator($lang=null, $section=null)
{
  if (is_admin()) return true;
  
  global $user;
  
  $cond = get_user_status() == 'translator' || get_user_status() == 'manager'; // status
  if ($lang != null) // access to language
  {
    $cond = $cond && in_array($lang, $user['languages']);
  }
  if ($section != null) // access to section
  {
    $cond = $cond && in_array($section, $user['sections']);
  }
  
  return $cond;
}

/**
 * search if current user is manager
 * @param string section
 * @return bool
 */
function is_manager($section=null)
{
  global $user;
  
  $cond = get_user_status() == 'manager'; // status
  if ($section != null) // access to section
  {
    $cond = $cond && in_array($section, $user['manage_sections']);
  }
  
  return $cond;
}

/**
 * search if current user is an admin
 * @return bool
 */
function is_admin()
{
  return get_user_status() == 'admin';
}

?>