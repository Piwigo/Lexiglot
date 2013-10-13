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

defined('LEXIGLOT_PATH') or die('Hacking attempt!');

if (!file_exists(LEXIGLOT_PATH . 'config/database.inc.php'))
{
  if (!file_exists(LEXIGLOT_PATH . 'install.php'))
  {
    trigger_error('Unable to load "config/database.inc.php"', E_USER_ERROR);
    exit();
  }
  
  header('Request-URI: install.php');
  header('Content-Location: install.php');
  header('Location: install.php');
  exit();
}

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

// includes
include_once(LEXIGLOT_PATH . 'config/database.inc.php');
include_once(LEXIGLOT_PATH . 'include/functions.inc.php');

// configuration (default + local + db)
include(LEXIGLOT_PATH . 'config/config_default.inc.php');
@include(LEXIGLOT_PATH . 'config/config_local.inc.php');

include_once(LEXIGLOT_PATH . 'include/constants.inc.php');

// protect vars
header('Content-type: text/html; charset=utf-8');
fix_magic_quotes();
umask(0);

// begin with a clean buffer
if (ob_get_length() !== false)
{
  ob_end_clean();
}

// MySQL connection
$db = init_db();
load_conf_db($conf);

// available projects and langs
$query = 'SELECT * FROM '.PROJECTS_TABLE.' ORDER BY rank DESC, id ASC;';
$conf['all_projects'] = hash_from_query($query, 'id');

$query = 'SELECT * FROM '.LANGUAGES_TABLE.' ORDER BY rank DESC, id ASC;';
$conf['all_languages'] = hash_from_query($query, 'id');

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
  include_once(LEXIGLOT_PATH . 'include/template.inc.php');
  $template = new Template();
  
  // is the site private ?
  if ( is_guest() and !$conf['access_to_guest'] and script_basename() != 'user' )
  {
    array_push($page['errors'], 'Access denied for guests ! <a href="user.php?login">Login</a> '.($conf['allow_registration']?'<i>or</i> <a href="user.php?register">Register</a>':null));
    $template->close('messages');
  }

  // check if at least the default language is registered
  if ( !defined('IN_ADMIN') and script_basename() != 'user' )
  {
    if ( empty($conf['default_language']) or !isset($conf['all_languages'][ $conf['default_language'] ]) )
    {
      array_push($page['errors'], 'Default language not registered.');
      $template->close('messages');
    }
  }
  
  // check SVN client
  if ( is_admin() and $conf['svn_activated'] )
  {
    exec($conf['svn_path'].' 2>&1', $out);
    if ($out[0] != 'Type \'svn help\' for usage.')
    {
      array_push($page['errors'], 'Unable to find SVN client &laquo; '.$conf['svn_path'].' &raquo;');
    }
  }
}

?>