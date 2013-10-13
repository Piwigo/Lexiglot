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
 
defined('LEXIGLOT_PATH') or die('Hacking attempt!'); 

/**
 * get user infos
 * @param int user_id
 * @return array
 */
function build_user($user_id)
{
  global $conf, $db;
  
  if (empty($user_id))
  {
    return false;
  }
  
  // retrieve user data
  $query = '
SELECT '.get_db_user_fields().'
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['id'].' = '.$user_id.'
;';
  $result = $db->query($query);
  
  if (!$result->num_rows)
  {
    return false;
  }
  
  $user_row = $result->fetch_assoc();
  
  // retrieve user infos
  $query = '
SELECT *
  FROM '.USER_INFOS_TABLE.'
  WHERE user_id = '.$user_id.'
;';
  $result = $db->query($query);
  
  // the user came from an external base, we must register it
  if (!$result->num_rows)
  {
    create_user_infos($user_id, 'visitor');
    
    // redo previous query
    $result = $db->query($query);
    
    $user['is_new'] = true;
  }
  
  // beautify booleans
  $user_infos_row = $result->fetch_assoc();
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
  
  $user['languages'] = $user['my_languages'] = $user['projects'] = $user['manage_projects'] = array();
  $user['main_language'] = null;
  
  // if the user is visitor we must get guest permissions
  if ($user['status'] == 'visitor')
  {
    $uid_for_lp = $conf['guest_id'];
  }
  else
  {
    $uid_for_lp = $user['id'];
  }
  
  // get languages
  $query = '
SELECT language, type
  FROM '.USER_LANGUAGES_TABLE.'
  WHERE user_id = '.$uid_for_lp.'
';
  $result = $db->query($query);
  
  while ($row = $result->fetch_assoc())
  {
    switch ($row['type'])
    {
      case 'translate':
        array_push($user['languages'], $row['language']);
        break;
      case 'main':
        $user['main_language'] = $row['language'];
        break;
      case 'my':
        array_push($user['my_languages'], $row['language']);
        break;
    }
  }
  
  // get projects
  $query = '
SELECT project, type
  FROM '.USER_PROJECTS_TABLE.'
  WHERE user_id = '.$uid_for_lp.'
';
  $result = $db->query($query);
  
  while ($row = $result->fetch_assoc())
  {
    switch ($row['type'])
    {
      case 'translate':
        array_push($user['projects'], $row['project']);
        break;
      case 'manage':
        array_push($user['manage_projects'], $row['project']);
        break;
    }
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
    
    // manager have access to all languages in its projects (must find a better way to do that)
    if ( isset($_GET['project']) and in_array($_GET['project'], $user['manage_projects']) )
    {
      $user['languages'] = array_keys($conf['all_languages']);
    }
  }
  else
  {
    $user['manage_projects'] = array();
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
  $db->query($query);
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
function get_users_list($where_clauses=array(), $select=array(), $mode='AND', $offset=0, $limit=9999999999999)
{
  global $conf, $db;
  
  $get_languages = $get_projects = false;
  if ($select === array())
  {
    $select = array('i.*');
    $get_languages = $get_projects = true;
  }
  else if (is_array($select))
  {
    if ( ($i = array_search('languages', $select)) !== false)
    {
      $get_languages = true;
      unset($select[$i]);
    }
    if ( ($i = array_search('projects', $select)) !== false)
    {
      $get_projects = true;
      unset($select[$i]);
    }
  }
  
  $join_languages = $join_projects = false;
  if (array_pos('l.language', $where_clauses) !== false)
  {
    $join_languages = true;
  }
  if (array_pos('p.project', $where_clauses) !== false)
  {
    $join_projects = true;
  }
  
  $query = '
SELECT 
    i.status,';
    
  if (!empty($select))
  {
    $query.= '
    '.implode(",\n    ", $select).',';
  }
  
  $query.= '
    '.get_db_user_fields().'
  FROM '.USERS_TABLE.' AS u
    INNER JOIN '.USER_INFOS_TABLE.' AS i
    ON u.'.$conf['user_fields']['id'].' = i.user_id';
    
  if ($join_languages)
  {
    $query.= '
    LEFT JOIN '.USER_LANGUAGES_TABLE.' AS l
    ON u.'.$conf['user_fields']['id'].' = l.user_id';
  }
  if ($join_projects)
  {
    $query.= '
    LEFT JOIN '.USER_PROJECTS_TABLE.' AS p
    ON u.'.$conf['user_fields']['id'].' = p.user_id';
  }
      
  if (!empty($where_clauses))
  {
    $query.= '
  WHERE 
    '.implode("\n    ".$mode." ", $where_clauses);
  }
  
  $query.= '
  GROUP BY u.'.$conf['user_fields']['id'].'
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
    
    if ($get_languages)
    {
      $user['languages'] = $user['my_languages'] = array();
      $user['main_language'] = null;
    }
    if ($get_projects)
    {     
      $user['projects'] = $user['manage_projects'] = array();
    }
  }
  unset($user);
  
  // get languages
  if ($get_languages and !empty($users))
  {
    $query = '
SELECT user_id, language, type
  FROM '.USER_LANGUAGES_TABLE.'
  WHERE user_id IN('.implode(',', array_keys($users)).')
';
    $result = $db->query($query);
    
    while ($row = $result->fetch_assoc())
    {
      switch ($row['type'])
      {
        case 'translate':
          array_push($users[ $row['user_id'] ]['languages'], $row['language']);
          break;
        case 'main':
          $users[ $row['user_id'] ]['main_language'] = $row['language'];
          break;
        case 'my':
          array_push($users[ $row['user_id'] ]['my_languages'], $row['language']);
          break;
      }
    }
  }
   
  // get projects
  if ($get_projects and !empty($users))
  {
    $query = '
SELECT user_id, project, type
  FROM '.USER_PROJECTS_TABLE.'
  WHERE user_id IN('.implode(',', array_keys($users)).')
';
    $result = $db->query($query);
    
    while ($row = $result->fetch_assoc())
    {
      switch ($row['type'])
      {
        case 'translate':
          array_push($users[ $row['user_id'] ]['projects'], $row['project']);
          break;
        case 'manage':
          array_push($users[ $row['user_id'] ]['manage_projects'], $row['project']);
          break;
      }
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
  global $conf, $db;
  
  $db->query('SET names '.$conf['users_table_encoding'].';');
  // retrieving the encrypted password of the login submitted
  $query = '
SELECT 
    '.$conf['user_fields']['id'].' AS id,
    '.$conf['user_fields']['password'].' AS password
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['username'].' = "'.mres($username).'"
;';
  $row = $db->query($query)->fetch_assoc();
  $db->query('SET names utf8;');
  
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
  global $conf, $db;
  
  $query = '
SELECT 
  '.$conf['user_fields']['username'].' AS username,
  '.$conf['user_fields']['password'].' AS password
FROM '.USERS_TABLE.'
WHERE '.$conf['user_fields']['id'].' = '.$user_id;
  $result = $db->query($query);
  
  if ($result->num_rows)
  {
    $row = $result->fetch_assoc();
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
  global $conf, $db;

  $query = '
SELECT '.$conf['user_fields']['id'].'
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['username'].' = "'.mres($username).'"
;';
  $result = $db->query($query);

  if (!$result->num_rows)
  {
    return false;
  }
  else
  {
    list($user_id) = $result->fetch_row();
    return $user_id;
  }
}

/**
 * returns user name thanks to his identifier, false if not found
 * @param int identifier
 * @param string username
 */
function get_username($id=null)
{
  global $conf, $user, $db;

  if ($id == null)
  {
    return $user['username'];
  }
  else
  {
    $query = '
SELECT '.$conf['user_fields']['username'].'
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['id'].' = "'.mres($id).'"
;';
    $result = $db->query($query);

    if (!$result->num_rows)
    {
      return false;
    }
    else
    {
      list($username) = $result->fetch_row();
      return $username;
    }
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
  global $conf, $db;

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
    
    list($count) = $db->query($query)->fetch_row();
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
  global $conf, $db;
  
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
  
    single_insert(
      USERS_TABLE,
      array_combine($insert_names, $insert_values)
      );
    
    create_user_infos($db->insert_id, 'visitor');
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
  u.'.$conf['user_fields']['email'].' as email,
  u.'.$conf['user_fields']['username'].' as username
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
  global $user, $db;

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
    list($status) = $db->query($query)->fetch_row();
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
 * @param string project
 * @return bool
 */
function is_translator($lang=null, $project=null)
{
  if (is_admin()) return true;
  
  global $user;
  
  $cond = get_user_status() == 'translator' || get_user_status() == 'manager'; // status
  if ($lang != null) // access to language
  {
    $cond = $cond && in_array($lang, $user['languages']);
  }
  if ($project != null) // access to project
  {
    $cond = $cond && in_array($project, $user['projects']);
  }
  
  return $cond;
}

/**
 * search if current user is manager
 * @param string project
 * @return bool
 */
function is_manager($project=null)
{
  global $user;
  
  $cond = get_user_status() == 'manager'; // status
  if ($project != null) // access to project
  {
    $cond = $cond && in_array($project, $user['manage_projects']);
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

/**
 * build profile url
 */
function get_user_url($user_id)
{
  return get_url_string(array('user_id'=>$user_id), true, 'profile');
}

/**
 * return a color for each status
 */
function get_status_color($status)
{
  $map = array(
    'guest' => '000000',
    'visitor' => '000000',
    'translator' => '000000',
    'manager' => '0000ff',
    'admin' => 'ff0000',
    );
    
  return $map[$status];
}

?>