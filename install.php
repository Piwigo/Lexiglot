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
define('VERSION', '1.0');

// +-----------------------------------------------------------------------+
// |                         FUNCTIONS
// +-----------------------------------------------------------------------+
include_once(LEXIGLOT_PATH . 'include/functions.inc.php');

function print_install_page()
{
  global $page, $conf;
  
  $page['content'].= ob_get_contents();
  ob_end_clean();

  echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>'.$page['title'].'</title>
  <link type="text/css" rel="stylesheet" media="screen" href="template/style.css">
  <link type="text/css" rel="stylesheet" media="screen" href="template/admin.css">
  <link type="text/css" rel="stylesheet" media="screen" href="template/js/jquery.ui/jquery.ui.custom.css">
</head>

<body>
<div id="the_header">
  <div id="title">
    '.$page['title'].'
  </div>
</div>
<div id="the_page">';

  if ( count($page['errors']) or count($page['infos']) )
  {
    include(LEXIGLOT_PATH . 'template/messages.php');
  }
  echo $page['content'];
  include(LEXIGLOT_PATH . 'template/footer.php');
  
  exit();
}

function execute_sqlfile($filepath, $replaced, $replacing)
{
  global $db;
  
  $sql_lines = file($filepath);
  $query = '';
  foreach ($sql_lines as $sql_line)
  {
    $sql_line = trim($sql_line);
    if (preg_match('/(^--|^$)/', $sql_line))
    {
      continue;
    }
    $query.= ' '.$sql_line;
    // if we reached the end of query, we execute it and reinitialize the variable "query"
    if (preg_match('/;$/', $sql_line))
    {
      $query = trim($query);
      $query = str_replace($replaced, $replacing, $query);
      $db->query($query);
      $query = '';
    }
  }
}

// +-----------------------------------------------------------------------+
// |                         COMMON
// +-----------------------------------------------------------------------+
header('Content-type: text/html; charset=utf-8');
fix_magic_quotes();

// default arrays
$conf = array();
$page = array(
  'title' => 'Lexiglot '.VERSION.' | Installation',
  'header' => null,
  'content' => null,
  'caption' => null,
  'errors' => array(),
  'infos' => array(),
  );

// outbut buffer
ob_start();

// installation step
$install_step = (isset($_POST['install_step'])) ? $_POST['install_step'] : 'config';


// +-----------------------------------------------------------------------+
// |                         TESTS and CONFIGURATION
// +-----------------------------------------------------------------------+
if ($install_step == 'config') 
{   
  // PHP version
  if (version_compare(PHP_VERSION, '5.0.0', '<')) 
  {
    array_push($page['errors'], 'This tool needs PHP version 5.0.0 at least.');
  }
  
  // configuration files
  if ( !is_writable(LEXIGLOT_PATH . 'config/') and !chmod(LEXIGLOT_PATH . 'config/', 0777) ) 
  {
    array_push($page['errors'], 'The folder <i>config/</i> must be writable, please change the chmod to 0777.');
  }
  if (file_exists(LEXIGLOT_PATH .'config/database.inc.php'))
  {
    array_push($page['errors'], 'The tool is already installed. <a href="index.php">Go back</a>');
  }
  
  if (count($page['errors']) > 0)
  {
    print_install_page();
  }
  
  // configuration form
  echo '
  <form method="post" action="" id="config">
    <fieldset class="common">
      <legend>Database configuration</legend>
      <table class="common">
        <tr>
          <td>Host :</td>
          <td><input name="dbhost" type="text" size="25" value="localhost"></td>
        </tr>
        <tr>
          <td>Database name :</td> 
          <td><input name="dbname" type="text" size="25"></td>
        </tr>
        <tr>
          <td>Username :</td>
          <td><input name="dbuser" type="text" size="25"></td>
        </tr>
        <tr>
          <td>Password :</td> 
          <td><input name="dbpwd" type="password" size="25"></td>
        </tr>
        <tr>
          <td>Tables prefix :</td>
          <td><input name="dbprefix" type="text" size="25" value="lexiglot_"></td>
        </tr>
      </table>
    </fieldset>
    
    <fieldset class="common">
      <legend>Admin account</legend>
      <table class="common">
        <tr>
          <td>Username :</td>
          <td><input name="username" type="text" size="25" maxlength="32"></td>
        </tr>
        <tr>
          <td>Password :</td> 
          <td><input name="password" type="password" size="25" maxlength="32"></td>
        </tr>
        <tr>
          <td>Email adress :</td>
          <td><input name="email" type="text" size="25" maxlength="64"></td>
        </tr>
      </table>
    </fieldset>
    
    <input type="hidden" name="install_step" value="save_config">
    <input type="submit" value="Install" class="blue big"/>
  </form>';

} 

// +-----------------------------------------------------------------------+
// |                         SAVE CONFIGURATION
// +-----------------------------------------------------------------------+
else if ($install_step == 'save_config') 
{
  $_POST = array_map('trim', $_POST);
  $_POST['salt_key'] = md5( microtime(true).mt_rand(10000,100000) );
  
  // connection test
  $db = new mysqli($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpwd'], $_POST['dbname']);

  if ($db->connect_error)
  {
    array_push($page['errors'], 'Unable to connect to the database. <a href="javascript:history.back();">Go back</a>');
    print_install_page();
  }
  
  // prefix test
  $result = $db->query('SHOW TABLES LIKE "'.$_POST['dbprefix'].'%";');
  if ($result->num_rows)
  {
    array_push($page['errors'], 'This prefix is already in use. <a href="javascript:history.back();">Go back</a>');
    print_install_page();
  }
  
  // database file content
  $file_content = "
<?php

defined('LEXIGLOT_PATH') or die('Hacking attempt!');

define('DB_HOST',  '".$_POST['dbhost']."');
define('DB_NAME',  '".$_POST['dbname']."');
define('DB_USER',  '".$_POST['dbuser']."');
define('DB_PWD',   '".$_POST['dbpwd']."');
define('DB_PREFIX','".$_POST['dbprefix']."');

define('ROWS_TABLE', 
  DB_PREFIX.'rows');
define('USERS_TABLE', 
  DB_PREFIX.'users');
define('USER_INFOS_TABLE', 
  DB_PREFIX.'user_infos');
define('USER_LANGUAGES_TABLE', 
  DB_PREFIX.'user_languages');
define('USER_PROJECTS_TABLE', 
  DB_PREFIX.'user_projects');
define('PROJECTS_TABLE', 
  DB_PREFIX.'projects');
define('STATS_TABLE', 
  DB_PREFIX.'stats');
define('LANGUAGES_TABLE', 
  DB_PREFIX.'languages');
define('CONFIG_TABLE', 
  DB_PREFIX.'config');
define('CATEGORIES_TABLE', 
  DB_PREFIX.'categories');
define('MAIL_HISTORY_TABLE', 
  DB_PREFIX.'mail_history');

define('SALT_KEY', '".$_POST['salt_key']."');

?>
";
  if (!@file_put_contents(LEXIGLOT_PATH . 'config/database.inc.php', $file_content))
  {
    array_push($page['errors'], 'Unable to create configuration file.');
    print_install_page();
  }
  
  // create blank config file
  if (!file_exists(LEXIGLOT_PATH . 'config/config_local.inc.php'))
  {
    file_put_contents(LEXIGLOT_PATH . 'config/config_local.inc.php', "<?php\n\n?>");
  }
  
  // create tables
  execute_sqlfile(LEXIGLOT_PATH . 'config/structure.sql', 'lexiglot_', $_POST['dbprefix']);
  
  // load config
  include(LEXIGLOT_PATH . '/config/config_default.inc.php');
  @include(LEXIGLOT_PATH . '/config/config_local.inc.php');
  include(LEXIGLOT_PATH . '/config/database.inc.php');
  include(LEXIGLOT_PATH . '/include/constants.inc.php');
  
  // register guest and admin
  $db->query('INSERT INTO '.USERS_TABLE.'(id, username, password, email)           VALUES('.$conf['guest_id'].', "guest", NULL, NULL);');
  $db->query('INSERT INTO '.USER_INFOS_TABLE.'(user_id, registration_date, status) VALUES('.$conf['guest_id'].', NOW(), "guest");'); 
  $db->query('INSERT INTO '.USERS_TABLE.'(id, username, password, email)           VALUES(NULL, "'.$_POST['username'].'", "'.$conf['pass_convert']($_POST['password']).'", "'.$_POST['email'].'");');
  $db->query('INSERT INTO '.USER_INFOS_TABLE.'(user_id, registration_date, status) VALUES('.$db->insert_id.', NOW(), "admin");');
  $db->query('INSERT INTO '.CONFIG_TABLE.'(param, value)                           VALUES("version", "'.VERSION.'");');
  
  // log admin
  try_log_user($_POST['username'], $_POST['password'], true);
  
  mkgetdir(DATA_LOCATION);
  
  // finish
  echo '
  <fieldset class="common">
    <legend>Congratulations!</legend>
    <div class="ui-state-highlight" style="padding: 0.7em;margin-bottom:10px;">
      <span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.7em;"></span>
      Lexiglot has successfully been installed. For security reasons, you should delete the file <i>install.php</i>.
    </div>
    <a href="admin.php?page=config">Go to configuration page</a>
  </fieldset>';
  
  $db->close();
}

print_install_page();
?>