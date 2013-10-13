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


// +-----------------------------------------------------------------------+
// |                         SAVE CONFIG
// +-----------------------------------------------------------------------+
if (isset($_POST['save_config']))
{
  $new_conf = array(
    'install_name' =>           $_POST['install_name'],
    'intro_message' =>          $_POST['intro_message'],
    'default_language' =>       $_POST['default_language'],
    'var_name' =>               $_POST['var_name'],
    'delete_done_rows' =>       set_boolean(isset($_POST['delete_done_rows'])),
    'use_stats' =>              set_boolean(isset($_POST['use_stats'])),
    'use_talks' =>              set_boolean(isset($_POST['use_talks'])),
    'allow_edit_default' =>     set_boolean(isset($_POST['allow_edit_default'])),
    
    'access_to_guest' =>        set_boolean(isset($_POST['access_to_guest'])),
    'allow_registration' =>     set_boolean(isset($_POST['allow_registration'])),
    'allow_profile' =>          set_boolean(isset($_POST['allow_profile'])),
    'user_can_add_language' =>  set_boolean(isset($_POST['user_can_add_language'])),
    
    'user_default_language' =>  $_POST['user_default_language'],
    'user_default_project' =>   $_POST['user_default_project'],
    'language_default_user' =>  $_POST['language_default_user'],
    'project_default_user' =>   $_POST['project_default_user'],
    
    'new_file_content' =>       $_POST['new_file_content'],
    );
    
  if (function_exists('exec'))
  {
    $new_conf = array_merge($new_conf, array(
      'svn_activated' =>        set_boolean(isset($_POST['svn_activated'])),
      'svn_server' =>           rtrim($_POST['svn_server'], '/').'/',
      'svn_path' =>             $_POST['svn_path'],
      'svn_user' =>             $_POST['svn_user'],
      'svn_password' =>         $_POST['svn_password'],
      ));
      
    if ($new_conf['svn_activated'] == 'true' and $new_conf['svn_server'] != $conf['svn_server'])
    { // we must relocate all working directories
      foreach ($conf['all_projects'] as $key => $row)
      {
        svn_switch($new_conf['svn_server'].$row['directory'], $conf['local_dir'].$row['id'], $conf['svn_server'].$row['directory']);
      }
    }
  }
    
  foreach ($new_conf as $param => $value)
  {
    $query = '
UPDATE '.CONFIG_TABLE.'
  SET value = "'.mres($value).'"
  WHERE param = "'.mres($param).'"
;';
    $db->query($query);
  }
  
  load_conf_db($conf);
  array_push($page['infos'], 'Configuration saved in the database.');
}


// +-----------------------------------------------------------------------+
// |                         PREPARE FORM
// +-----------------------------------------------------------------------+
if (function_exists('exec'))
{
  $template->assign('USE_SVN', true);
}
else
{
  array_push($page['warnings'], 'SVN support not available. You can not use <b>exec()</b> function on this server.');
}

$template->assign('F_ACTION', get_url_string(array('page'=>'config'), true));


// +-----------------------------------------------------------------------+
// |                         OUTPUT
// +-----------------------------------------------------------------------+
$template->close('admin/config');

?>