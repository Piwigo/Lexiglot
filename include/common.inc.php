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

if (!file_exists(PATH.'config/database.inc.php'))
{
  header('Request-URI: install.php');
  header('Content-Location: install.php');
  header('Location: install.php');
  exit();
}

// includes
include_once(PATH.'config/database.inc.php');
include_once(PATH.'include/functions.inc.php');

// protect vars
header('Content-type: text/html; charset=utf-8');
fix_magic_quotes();
umask(0);

// default arrays
$conf = array();
$page = array(
  'title' => null,
  'window_title' => null,
  'header' => null,
  'script' => null,
  'content' => null,
  'begin' => null,
  'errors' => array(),
  'warnings' => array(),
  'infos' => array(),
  );
$tabsheet = array();
$user = array();

// begin with a clean buffer
if (ob_get_length() !== false)
{
  ob_end_clean();
}

// MySQL connection
mysql_connect(DB_HOST, DB_USER, DB_PWD);
mysql_select_db(DB_NAME);
mysql_query('SET names utf8;');

// configuration (default + local + db)
include(PATH.'config/config_default.inc.php');
@include(PATH.'config/config_local.inc.php');
load_conf_db($conf);

// available sections and langs
$query = 'SELECT * FROM '.SECTIONS_TABLE.' ORDER BY id;';
$conf['all_sections'] = hash_from_query($query, 'id');
ksort($conf['all_sections']);

$query = 'SELECT * FROM '.LANGUAGES_TABLE.' ORDER BY id;';
$conf['all_languages'] = hash_from_query($query, 'id');
ksort($conf['all_languages']);

// user infos
$user['id'] = $conf['guest_id'];
if (isset($_COOKIE[session_name()]))
{
  session_start();
  if (isset($_GET['action']) and $_GET['action'] == 'logout')
  {
    logout_user();
    redirect(get_home_url());
  }
  else if (!empty($_SESSION['uid']))
  {
    $user['id'] = $_SESSION['uid'];
  }
}
if ($user['id'] == $conf['guest_id'])
{
  auto_login();
}
if (session_id() == '')
{
  session_start();
}
$user = build_user($user['id']);

// redirect the user to welcome page he is new (comming from an external base)
if (isset($user['is_new']))
{
  redirect('profile.php?new');
}

if (!defined('IN_AJAX'))
{
  // outbut buffer (don't forget to call print_page at the end)
  ob_start();

  // is the site private ?
  if ( is_guest() and !$conf['access_to_guest'] and script_basename() != 'user')
  {
    array_push($page['errors'], 'Access denied for guests ! <a href="user.php?login">Login</a> '.($conf['allow_registration']?'<i>or</i> <a href="user.php?register">Register</a>':null));
    print_page();
  }

  // check if at least the default language is registered
  if ( !defined('IN_ADMIN') and script_basename() != 'user' )
  {
    if ( empty($conf['default_language']) or !isset($conf['all_languages'][ $conf['default_language'] ]) )
    {
      array_push($page['errors'], 'Default language not registered.');
      print_page();
    }
  }
  
  // check SVN client
  if ($conf['svn_activated'])
  {
    exec($conf['svn_path'].' 2>&1', $out);
    if ($out[0] != 'Type \'svn help\' for usage.')
    {
      array_push($page['errors'], 'Unable to find SVN client &laquo; '.$conf['svn_path'].' &raquo;');
    }
  }
}

?>